<?php
include('dbcon.php');
include('./layout/session_check.php');

// ============= DEADLINE NOTIFICATION CODE - START =============
if (isset($_SESSION['auth_user']['role']) && in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])) {
    
    $user_id = $_SESSION['auth_user']['id'];
    $today = date('Y-m-d');
    $days_to_check = [7, 3, 1];
    
    foreach ($days_to_check as $days) {
        $target_date = date('Y-m-d', strtotime("+{$days} days"));
        
        $deadline_query = "
            SELECT p.project_id, p.project_name, p.po_num, c.client_name
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.client_id
            WHERE p.end_date = '$target_date'
            AND p.project_status != 'Completed'
            AND p.user_approval = 1
            AND p.finance_approval = 1
        ";
        
        $deadline_result = mysqli_query($conn, $deadline_query);
        
        if ($deadline_result && mysqli_num_rows($deadline_result) > 0) {
            while ($project = mysqli_fetch_assoc($deadline_result)) {
                
                $check_notif = "SELECT notif_id FROM user_notifications 
                               WHERE user_id = '$user_id' 
                               AND project_id = '{$project['project_id']}' 
                               AND type = 'project_deadline'
                               AND DATE(created_at) = CURDATE()";
                
                $check_result = mysqli_query($conn, $check_notif);
                
                if (mysqli_num_rows($check_result) == 0) {
                    $days_text = $days == 1 ? '1 day' : "$days days";
                    $message = "Project <strong>{$project['project_name']}</strong> (P.O. #{$project['po_num']}) for client <strong>{$project['client_name']}</strong> is ending in <strong>{$days_text}</strong>.";
                    
                    $insert_notif = "INSERT INTO user_notifications (user_id, project_id, type, message, is_read, created_at) 
                                    VALUES ('$user_id', '{$project['project_id']}', 'project_deadline', '" . mysqli_real_escape_string($conn, $message) . "', 0, NOW())";
                    
                    mysqli_query($conn, $insert_notif);
                }
            }
        }
    }
}
// ============= DEADLINE NOTIFICATION CODE - END =============

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// System Logs Notification
include('./layout/system_logs_notif.php');

// Handle clearing project selection
if (isset($_GET['clear_project']) && $_GET['clear_project'] == '1') {
    unset($_SESSION['selected_project_id']);
    header("Location: index.php");
    exit();
}

// Check if user is authenticated
if (
    !isset($_SESSION['authenticated']) || !isset($_SESSION['auth_user']['role']) ||
    !in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])
) {
    header("Location: /Financial_Management/login_form.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Allow both admin and finance roles
if (
    !isset($_SESSION['authenticated']) ||
    !in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])
) {

    $_SESSION['status'] = "<div class='alert'>Access Denied.</div>";
    header("Location: login_form.php");
    exit(0);
}

// Database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all clients
$clients = [];
$clientResult = mysqli_query($conn, "SELECT client_id, client_name FROM clients");
if ($clientResult && mysqli_num_rows($clientResult) > 0) {
    while ($row = mysqli_fetch_assoc($clientResult)) {
        $clients[] = $row;
    }
}

// Get all projects
$projects = [];
$projectQuery = mysqli_query($conn, "SELECT p.project_id, p.project_name, p.projected_budget_cost, p.start_date, p.end_date, p.project_status, p.user_approval, p.finance_approval, c.client_name 
                                    FROM projects p 
                                    LEFT JOIN clients c ON p.client_id = c.client_id");
if ($projectQuery && mysqli_num_rows($projectQuery) > 0) {
    while ($row = mysqli_fetch_assoc($projectQuery)) {
        $projects[] = $row;
    }
}

// Get current year and last year
$currentYear = date('Y');
$lastYear = $currentYear - 1;

// Fetch total projects this year
$thisYearQuery = mysqli_query($conn, "SELECT COUNT(*) AS total_this_year FROM projects WHERE start_date IS NOT NULL AND YEAR(start_date) = '$currentYear'");
$thisYearProjects = mysqli_fetch_assoc($thisYearQuery)['total_this_year'];

// Fetch total projects last year
$lastYearQuery = mysqli_query($conn, "SELECT COUNT(*) AS total_last_year FROM projects WHERE start_date IS NOT NULL AND YEAR(start_date) = '$lastYear'");
$lastYearProjects = mysqli_fetch_assoc($lastYearQuery)['total_last_year'];

// Calculate difference and growth rate
$projectDifference = $thisYearProjects - $lastYearProjects;
$projectGrowth = ($lastYearProjects > 0) ? round(($projectDifference / $lastYearProjects) * 100, 1) : 0;

// Check for client_id in session
$client_id = $_SESSION['client_id'] ?? null;

// Handle project selection from URL parameter or session
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    // If project_id is passed via URL (when user selects a project), store it in session
    $_SESSION['selected_project_id'] = $_GET['project_id'];
    $selected_project_id = $_GET['project_id'];
} elseif (isset($_SESSION['selected_project_id']) && !empty($_SESSION['selected_project_id'])) {
    // If no URL parameter but session has a selected project, use that
    $selected_project_id = $_SESSION['selected_project_id'];
} else {
    // No project selected yet
    $selected_project_id = null;
}

$weeklyIncome = [];
$weeklyExpenses = [];
$weeklyLabels = [];
$selectedProject = null;

$project_start_date = null;
$project_end_date = null;

// Find the selected project details
if ($selected_project_id) {
    foreach ($projects as $project) {
        if ($project['project_id'] == $selected_project_id) {
            $selectedProject = $project;
            break;
        }
    }

    // If selected project is not found in current projects list, clear the selection
    if (!$selectedProject) {
        unset($_SESSION['selected_project_id']);
        $selected_project_id = null;
    }
}

// Only process chart data if a project is selecteda
if ($selected_project_id && $selectedProject) {
    $projectQuery = mysqli_query($conn, "SELECT start_date, end_date FROM projects WHERE project_id = '$selected_project_id'");
    $project = mysqli_fetch_assoc($projectQuery);

    $start_date = $project['start_date'] ?? date('Y-m-01');
    $end_date = $project['end_date'] ?? date('Y-m-t');
    $projected_revenue = 0; // Default value since column doesn't exist

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');

    $interval = new DateInterval('P7D');
    $period = new DatePeriod($start, $interval, $end);
    $weeksCount = iterator_count($period);
    $period = new DatePeriod($start, $interval, $end); // reset

    foreach ($period as $weekStart) {
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        $startStr = $weekStart->format('Y-m-d');
        $endStr = $weekEnd->format('Y-m-d');

        // Income: use default value
        $weeklyIncome[] = round($projected_revenue / max($weeksCount, 1), 2);

        // Expense: sum from database
        $expenseQuery = "
            SELECT SUM(amount) AS total 
            FROM expenses 
            WHERE project_id = '$selected_project_id'
            AND date BETWEEN '$startStr' AND '$endStr'";
        $expenseResult = mysqli_query($conn, $expenseQuery);
        $expenseRow = mysqli_fetch_assoc($expenseResult);

        $weeklyExpenses[] = (float)$expenseRow['total'] ?? 0;
        $weeklyLabels[] = "Week of " . $weekStart->format('M j');
    }
}

// To get the sum of all expenses for every project (not categorized per project nor client since the total is the target)

$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

// Overall company expenses - always use year filter
$expenses_allprojects = mysqli_query($conn, "SELECT * FROM expenses WHERE YEAR(date) = $targetYear");

$total_operating_expenses = 0;
while ($expense = mysqli_fetch_assoc($expenses_allprojects)) {
    $cleaned_expenses = (float) str_replace(',', '', $expense['amount']);
    $total_operating_expenses += $cleaned_expenses;
}

// To get the sum of company expenses per category
$company_exp_query = mysqli_query($conn, "SELECT * FROM company_expense WHERE YEAR(date) = $targetYear ORDER BY category");

$company_expense_categories = [];
while ($row = mysqli_fetch_assoc($company_exp_query)) {
    $category = $row['category'];
    $company_expenses = (float) str_replace(',', '', $row['amount']);

    // Initialize the category if it's not set
    if (!isset($company_expense_categories[$category])) {
        $company_expense_categories[$category] = 0;
    }

    $company_expense_categories[$category] += $company_expenses;
}

$total_company_expenses = array_sum($company_expense_categories);

// Compute overall expenses
$total_overall_expenses = $total_operating_expenses + $total_company_expenses;


// To get the sum of all total project cost (the price of each project)
if ($project_start_date && $project_end_date) {
    $project_rev_query = mysqli_query($conn, "
        SELECT projected_budget_cost, end_date 
        FROM projects 
        WHERE YEAR(end_date) = '$targetYear'
    ");
    $addons_rev_query = mysqli_query($conn, "
        SELECT projected_budget_cost, end_date 
        FROM project_addons 
        WHERE YEAR(end_date) = '$targetYear'
    ");
} else {
    // fallback to year filter
    $project_rev_query = mysqli_query($conn, "
        SELECT projected_budget_cost, end_date 
        FROM projects 
        WHERE YEAR(end_date) = '$targetYear'
    ");
    $addons_rev_query = mysqli_query($conn, "
        SELECT projected_budget_cost, end_date 
        FROM project_addons 
        WHERE YEAR(end_date) = '$targetYear'
    ");
}

$total_revenue = 0;

// Sum project revenue
while ($project = mysqli_fetch_assoc($project_rev_query)) {
    $cleaned_revenue = (float) str_replace(',', '', $project['projected_budget_cost']);
    $total_revenue += $cleaned_revenue;
}

// Sum addon revenue
while ($addon = mysqli_fetch_assoc($addons_rev_query)) {
    $addon_revenue = (float) str_replace(',', '', $addon['projected_budget_cost']);
    $total_revenue += $addon_revenue;
}

// Calculate overall expenses
$total_overall_expenses = $total_operating_expenses + $total_company_expenses;

// Calculate previous year expenses for comparison
$previousYear = $targetYear - 1;

// Get previous year operating expenses
$prev_expenses_allprojects = mysqli_query($conn, "SELECT * FROM expenses WHERE YEAR(date) = $previousYear");
$prev_total_operating_expenses = 0;
while ($expense = mysqli_fetch_assoc($prev_expenses_allprojects)) {
    $cleaned_expenses = (float) str_replace(',', '', $expense['amount']);
    $prev_total_operating_expenses += $cleaned_expenses;
}

// Get previous year company expenses
$prev_company_exp_query = mysqli_query($conn, "SELECT * FROM company_expense WHERE YEAR(date) = $previousYear");
$prev_total_company_expenses = 0;
while ($row = mysqli_fetch_assoc($prev_company_exp_query)) {
    $company_expenses = (float) str_replace(',', '', $row['amount']);
    $prev_total_company_expenses += $company_expenses;
}

$prev_total_overall_expenses = $prev_total_operating_expenses + $prev_total_company_expenses;

// Calculate expense change percentage and direction
$expense_change = 0;
$expense_trend = 'neutral'; // 'up', 'down', or 'neutral'

if ($prev_total_overall_expenses > 0) {
    $expense_change = (($total_overall_expenses - $prev_total_overall_expenses) / $prev_total_overall_expenses) * 100;
    if ($expense_change > 0) {
        $expense_trend = 'up';
    } elseif ($expense_change < 0) {
        $expense_trend = 'down';
    }
}
// ----------------------
// Net profit (after tax) with Progressive Tax Brackets
// ----------------------

// Calculate overall expenses
$total_overall_expenses = $total_operating_expenses + $total_company_expenses;

// Calculate gross profit (revenue minus all expenses)
$gross_profit = $total_revenue - $total_overall_expenses;

// TAX COMPUTATION using Philippine Tax Brackets
$taxable_income = $gross_profit;
$tax_expense = 0;

// Only compute tax if there's positive taxable income
if ($taxable_income > 0) {
    if ($taxable_income <= 250000) {
        $tax_expense = 0;
    } else if ($taxable_income > 250000 && $taxable_income <= 400000) {
        $tax_expense = ($taxable_income - 250000) * 0.15;
    } else if ($taxable_income > 400000 && $taxable_income <= 800000) {
        $tax_expense = 22500 + (($taxable_income - 400000) * 0.20);
    } else if ($taxable_income > 800000 && $taxable_income <= 2000000) {
        $tax_expense = 102500 + (($taxable_income - 800000) * 0.25);
    } else if ($taxable_income > 2000000 && $taxable_income <= 8000000) {
        $tax_expense = 402500 + (($taxable_income - 2000000) * 0.30);
    } else if ($taxable_income > 8000000) {
        $tax_expense = 2202500 + (($taxable_income - 8000000) * 0.35);
    }
}

// Net profit after tax
$net_after_tax = $gross_profit - $tax_expense;

// Safety / formatting
$gross_profit = round($gross_profit, 2);
$tax_expense = round($tax_expense, 2);
$net_after_tax = round($net_after_tax, 2);

// -------------------------------------------------------------------------------------------------------------------//

// ----------------------
// Year-to-Year Net Profit Comparison with Progressive Tax Brackets
// ----------------------

$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$previousYear = $targetYear - 1;

// CURRENT YEAR Net Profit (already calculated as $net_after_tax)
$current_year_net_profit = $net_after_tax;

// ----------------------
// PREVIOUS YEAR Revenue
// ----------------------
$prev_year_revenue_query = mysqli_query($conn, "
    SELECT projected_budget_cost 
    FROM projects 
    WHERE YEAR(end_date) = $previousYear
");
$prev_year_revenue = 0;
while ($project = mysqli_fetch_assoc($prev_year_revenue_query)) {
    $cleaned_revenue = (float) str_replace(',', '', $project['projected_budget_cost']);
    $prev_year_revenue += $cleaned_revenue;
}

$prev_year_addons_query = mysqli_query($conn, "
    SELECT projected_budget_cost 
    FROM project_addons 
    WHERE YEAR(end_date) = $previousYear
");
while ($addon = mysqli_fetch_assoc($prev_year_addons_query)) {
    $addon_revenue = (float) str_replace(',', '', $addon['projected_budget_cost']);
    $prev_year_revenue += $addon_revenue;
}

// ----------------------
// PREVIOUS YEAR Expenses (already calculated as $prev_total_overall_expenses)
// ----------------------
$prev_year_expenses = $prev_total_overall_expenses;

// Calculate previous year gross profit
$prev_year_gross_profit = $prev_year_revenue - $prev_year_expenses;

// Calculate previous year tax using same progressive brackets
$prev_year_taxable_income = $prev_year_gross_profit;
$prev_year_tax_expense = 0;

if ($prev_year_taxable_income > 0) {
    if ($prev_year_taxable_income <= 250000) {
        $prev_year_tax_expense = 0;
    } else if ($prev_year_taxable_income > 250000 && $prev_year_taxable_income <= 400000) {
        $prev_year_tax_expense = ($prev_year_taxable_income - 250000) * 0.15;
    } else if ($prev_year_taxable_income > 400000 && $prev_year_taxable_income <= 800000) {
        $prev_year_tax_expense = 22500 + (($prev_year_taxable_income - 400000) * 0.20);
    } else if ($prev_year_taxable_income > 800000 && $prev_year_taxable_income <= 2000000) {
        $prev_year_tax_expense = 102500 + (($prev_year_taxable_income - 800000) * 0.25);
    } else if ($prev_year_taxable_income > 2000000 && $prev_year_taxable_income <= 8000000) {
        $prev_year_tax_expense = 402500 + (($prev_year_taxable_income - 2000000) * 0.30);
    } else if ($prev_year_taxable_income > 8000000) {
        $prev_year_tax_expense = 2202500 + (($prev_year_taxable_income - 8000000) * 0.35);
    }
}

// Previous year net profit after tax
$prev_year_net_profit = $prev_year_gross_profit - $prev_year_tax_expense;

// Round for display
$prev_year_gross_profit = round($prev_year_gross_profit, 2);
$prev_year_tax_expense = round($prev_year_tax_expense, 2);
$prev_year_net_profit = round($prev_year_net_profit, 2);

// Calculate net profit change (Year over Year)
$profit_change_yoy = 0;
$profit_trend_yoy = 'neutral';
$profit_arrow = '';
$profit_color = '#6c757d'; // gray for neutral

if ($prev_year_net_profit != 0) {
    $profit_change_yoy = (($current_year_net_profit - $prev_year_net_profit) / abs($prev_year_net_profit)) * 100;

    if ($profit_change_yoy > 0) {
        $profit_trend_yoy = 'up';
        $profit_arrow = '↑'; // Up arrow (profit increased - good)
        $profit_color = '#28a745'; // green
    } elseif ($profit_change_yoy < 0) {
        $profit_trend_yoy = 'down';
        $profit_arrow = '↓'; // Down arrow (profit decreased - bad)
        $profit_color = '#dc3545'; // red
    } else {
        $profit_arrow = '→'; // No change
    }
} elseif ($current_year_net_profit > 0 && $prev_year_net_profit == 0) {
    $profit_arrow = '↑';
    $profit_color = '#28a745';
    $profit_trend_yoy = 'up';
} elseif ($current_year_net_profit < 0 && $prev_year_net_profit == 0) {
    $profit_arrow = '↓';
    $profit_color = '#dc3545';
    $profit_trend_yoy = 'down';
}

// ----------------------
// Year-to-Year Overall Expenses Comparison
// ----------------------

$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$previousYear = $targetYear - 1;

// CURRENT YEAR Expenses (already calculated as $total_overall_expenses)
$current_year_expenses = $total_overall_expenses;

// PREVIOUS YEAR Expenses (already calculated as $prev_total_overall_expenses)
$previous_year_expenses = $prev_total_overall_expenses;

// Calculate expense change (Year over Year)
$expense_change_yoy = 0;
$expense_trend_yoy = 'neutral';
$expense_arrow = '';
$expense_color = '#6c757d'; // gray for neutral

if ($previous_year_expenses > 0) {
    $expense_change_yoy = (($current_year_expenses - $previous_year_expenses) / $previous_year_expenses) * 100;

    if ($expense_change_yoy > 0) {
        $expense_trend_yoy = 'up';
        $expense_arrow = '↑'; // Up arrow (expenses increased - bad)
        $expense_color = '#dc3545'; // red
    } elseif ($expense_change_yoy < 0) {
        $expense_trend_yoy = 'down';
        $expense_arrow = '↓'; // Down arrow (expenses decreased - good)
        $expense_color = '#28a745'; // green
    } else {
        $expense_arrow = '→'; // No change
    }
} elseif ($current_year_expenses > 0 && $previous_year_expenses == 0) {
    $expense_arrow = '↑';
    $expense_color = '#dc3545';
    $expense_trend_yoy = 'up';
}

// Get user name from session
$name = $_SESSION['auth_user']['name'] ?? 'Guest';

$total_new = 0;

if (!empty($has_new_recording)) $total_new++;
if (!empty($has_new_archive)) $total_new++;
if (!empty($has_new_sessions)) $total_new++;
if (!empty($has_new_fsAccess)) $total_new++;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="icon" type="image/png" href="resources/images/login_page_logo.png">
    <!-- Bootsrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- System Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet"> -->
    <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="resources/css/main.css">
    <link rel="stylesheet" href="resources/css/index_2.css">

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- ChartJs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <!-- Luicide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

</head>

<body class="d-flex h-100">

    <!-- Side Nav Container -->
    <div>
        <button class="toggle-btn" id="toggleNav">☰</button>
        <div id="sidebar" class="side-nav-container container-fluid">
            <!-- Side Nav Header -->
            <!-- <div class="d-flex justify-content-end">
                <button id="sidebarToggle" class="btn btn-outline-light mb-3" type="button" style="width:40px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left-to-line-icon lucide-arrow-left-to-line"><path d="M3 19V5"/><path d="m13 6-6 6 6 6"/><path d="M7 12h14"/></svg>
                </button>
            </div> -->
            
            <!-- Company Name -->
            <div class="company-name me-1">
                <img src="resources/images/sidenav_logo.svg" alt="RVR SMES logo" width="90" height="90"><br>
                <strong><span class="companyName">RVR SMES</span></strong>
            </div>

            <!-- Side Nav Options -->
            <ul class="nav nav-pills flex-column mb-auto mt-4 text-start">
                <li class="nav-item mb-2">
                    <a href="index.php" class="nav-link active d-flex align-items-center" data-section="home.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                            <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        </svg>
                        <span class="nav-label"> Dashboard</span>
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="inner_pages/clients.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round">
                            <path d="M18 21a8 8 0 0 0-16 0" />
                            <circle cx="10" cy="8" r="5" />
                            <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                        </svg>
                        <span class="nav-label">Clients</span>
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="inner_pages/batch_expense.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-banknote-arrow-down-icon lucide-banknote-arrow-down">
                            <path d="M12 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" />
                            <path d="m16 19 3 3 3-3" />
                            <path d="M18 12h.01" />
                            <path d="M19 16v6" />
                            <path d="M6 12h.01" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                        <span class="nav-label">Transactions</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="inner_pages/chartaccounts.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chart-gantt-icon lucide-square-chart-gantt">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 8h7" />
                            <path d="M8 12h6" />
                            <path d="M11 16h5" />
                        </svg>
                        <span class="nav-label"> Chart of Accounts</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/overall_company.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'overall_company.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brick-wall-icon lucide-brick-wall">
                                <rect width="18" height="18" x="3" y="3" rx="2" />
                                <path d="M12 9v6" />
                                <path d="M16 15v6" />
                                <path d="M16 3v6" />
                                <path d="M3 15h18" />
                                <path d="M3 9h18" />
                                <path d="M8 15v6" />
                                <path d="M8 3v6" />
                            </svg>
                            <span class="nav-label">RVR SMES</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/create_user.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'create_user.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-user-round-icon lucide-square-user-round">
                                <path d="M18 21a6 6 0 0 0-12 0" />
                                <circle cx="12" cy="11" r="4" />
                                <rect width="18" height="18" x="3" y="3" rx="2" />
                            </svg>
                            <span class="nav-label">Manage users</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mb-2">
                    <a href="inner_pages/archive_prjctlist.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'intro_archive.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive">
                            <rect width="20" height="5" x="2" y="3" rx="1" />
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                        <span class="nav-label">Archive Records</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
                    <li class="nav-item mb-2">
                        <a href="./inner_pages/exp_logs_list.php"
                            class="nav-link d-flex align-items-center position-relative <?php echo $current_page == 'exp_logs_list.php' ? 'active nav-link' : 'inactive nav-link'; ?>">

                            <div class="icon-wrapper position-relative">
                                <?php if ($total_new > 0): ?>
                                    <span class="notification-count">
                                        <?php echo ($total_new > 99) ? '99+' : $total_new; ?>
                                    </span>
                                <?php endif; ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-logs-icon lucide-logs">
                                    <path d="M13 12h8" />
                                    <path d="M13 18h8" />
                                    <path d="M13 6h8" />
                                    <path d="M3 12h1" />
                                    <path d="M3 18h1" />
                                    <path d="M3 6h1" />
                                    <path d="M8 12h1" />
                                    <path d="M8 18h1" />
                                    <path d="M8 6h1" />
                                </svg>
                            </div>

                            <span class="nav-label">System Logs</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- <ul class="nav nav-pills flex-column text-start mb-3">
                <li class="nav-item mb-2">
                    <a href="../forms_logic/logout.php" class="nav-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" x2="9" y1="12" y2="12" />
                        </svg>
                        Log out
                    </a>
                </li>
            </ul> -->
        </div>
        <div class="sidenav-backdrop"></div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById("toggleNav");
            const sideNav = document.querySelector(".side-nav-container");
            const backdrop = document.querySelector(".sidenav-backdrop");

            toggleBtn.addEventListener("click", function() {
                sideNav.classList.toggle("active");
                toggleBtn.classList.toggle("active");

                if (sideNav.classList.contains("active")) {
                    document.body.style.overflow = "hidden"; // disable scroll
                } else {
                    document.body.style.overflow = ""; // restore scroll
                }
            });

            // Close sidenav when backdrop is clicked
            backdrop.addEventListener("click", function() {
                sideNav.classList.remove("active");
                toggleBtn.classList.remove("active");
                document.body.style.overflow = "";
            });
        });
    </script>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade logout-modal" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered logout-modal-dialog">
            <div class="modal-content logout-modal-content rounded-4">
                <div class="modal-header logout-modal-header">
                    <h1 class="modal-title logout-modal-title" id="logoutModalLabel">Confirm Log Out</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body logout-modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="logout-modal-footer mt-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <a href="/Financial_Management/forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div class="main-content container-fluid ">

        <!-- Top Nav -->
        <nav class="d-flex flex-row align-items-start justify-content-between">

            <div class="page-title">
                Dashboard
            </div>

            <div class="d-flex align-items-center">
                <p id="datetime"></p>

                <?php
                $user_id = $_SESSION['auth_user']['id'];
                // Count from user_notifications (user-specific)
                $user_notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE user_id = $user_id AND is_read = 0");
                $user_row = mysqli_fetch_assoc($user_notif_check);
                $user_unread = (int)$user_row['total'];

                // Count from notifications (system-wide alerts)
                $system_notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
                $system_row = mysqli_fetch_assoc($system_notif_check);
                $system_unread = (int)$system_row['total'];

                // Combine both counts
                $total_unread = $user_unread + $system_unread;
                $has_unread = $total_unread > 0;

                // Display logic
                $notif_display = $total_unread > 9 ? '9+' : $total_unread;
                ?>
                <div class="notif-icon-wrapper">
                    <a href="./inner_pages/notifications.php" class="notif-icon-wrapper" style="text-decoration: none; color: inherit;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-bell-icon lucide-bell mt-2 me-3">
                            <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                            <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                        </svg>
                        <?php if ($has_unread): ?>
                            <span class="notif-badge"><?php echo $notif_display; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- User Account -->
                <div class="dropdown">
                    <a href="#" class="user-account d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php
                        $profile_image = 'resources/images/default_user.png'; // Default image
                        $role_display = '';
                        $user_id = $_SESSION['auth_user']['id'];
                        $result = mysqli_query($conn, "SELECT profile_image, role FROM users WHERE id = '$user_id' LIMIT 1");
                        if ($row = mysqli_fetch_assoc($result)) {
                            if (!empty($row['profile_image'])) {
                                $profile_image = htmlspecialchars($row['profile_image']);
                            }

                            // Role mapping
                            if (!empty($row['role'])) {
                                switch (strtolower($row['role'])) {
                                    case 'admin':
                                        $role_display = 'ADMIN';
                                        break;
                                    case 'finance':
                                        $role_display = 'FINANCE';
                                        break;
                                    case 'user':
                                        $role_display = 'USER';
                                        break;
                                    default:
                                        $role_display = ucfirst($row['role']);
                                }
                            }
                        }
                        ?>
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
                        <div>
                            <div class=""><?php echo htmlspecialchars($role_display); ?></div>
                        </div>
                    </a>
                    <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow">
                        <li><a class="dropdown-item" href="inner_pages/my_profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="forms_logic/logout.php" class="nav-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="">
            <div class="parent">
                <div class="div1">
                    <div>
                        <!-- Project Dropdown and Profit & Loss Chart -->
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="project_name">
                                    <span id="project-name">
                                        <?php if ($selectedProject): ?>
                                            <?= htmlspecialchars($selectedProject['project_name']) ?>
                                        <?php else: ?>
                                            <!-- No Project Selected -->
                                        <?php endif; ?>
                                    </span>
                                    <span id="client-name" class="client-name">
                                        <?php if ($selectedProject): ?>
                                            <?= htmlspecialchars($selectedProject['client_name']) ?>
                                        <?php else: ?>
                                            <!-- Please select a project -->
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </div>

                            <!-- Dropdown -->
                            <div class="btn-group align-self-center">
                                <button type="button" class="select-project btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Project
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($projects as $proj): ?>
                                        <li>
                                            <a class="dropdown-item project-select"
                                                href="?project_id=<?= $proj['project_id'] ?>"
                                                data-id="<?= $proj['project_id'] ?>"
                                                data-project="<?= htmlspecialchars($proj['project_name']) ?>"
                                                data-client="<?= htmlspecialchars($proj['client_name']) ?>"
                                                data-budget="<?= htmlspecialchars($proj['projected_budget_cost']) ?>">

                                                <?= htmlspecialchars($proj['project_name']) ?> <br>
                                                <small class="text-muted"><?= htmlspecialchars($proj['client_name']) ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Project Financial Performance -->
                        <div class="pfp-container">
                            <p class="pfp-main-text">Project Financial Performance</p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                                class="bi bi-info-circle" viewBox="0 0 16 16"
                                data-bs-toggle="popover"
                                data-bs-trigger="hover"
                                title="Chart Description"
                                data-bs-html="true"
                                data-bs-content="This line graph presents the daily total expenses for project <em><?= $selectedProject ? htmlspecialchars($selectedProject['project_name']) : 'N/A' ?></em>. The gross profit is calculated by subtracting all expenses from the total project cost of <strong>₱<?= $selectedProject ? number_format($selectedProject['projected_budget_cost'], 2) : '0.00' ?></strong>.">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                            </svg>
                        </div>

                        <div>
                            <div style="flex: 1;">
                                <canvas id="profitLossChart"></canvas>
                            </div>
                            <!-- Custom Legend -->
                            <div id="profit-loss-legend" class="custom-legend">
                                <!-- Legend will be rendered here by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Initialize Bootstrap Popover -->
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                        popoverTriggerList.map(function(popoverTriggerEl) {
                            return new bootstrap.Popover(popoverTriggerEl);
                        });
                    });

                    document.addEventListener("DOMContentLoaded", function() {
                        const projectLinks = document.querySelectorAll(".project-select");
                        const selectedBtn = document.getElementById("selectedProjectBtn");
                    });
                </script>

                <!-- Budget Analysis -->
                <div class="div2">
                    <div class="d-flex flex-column h-100">
                        <div class="mb-2">
                            <div class="container-title ">Budget Analysis</div>
                            <div class="budget-summary" id="budget-summary-display">
                                <!-- Select a project to view budget breakdown -->
                            </div>
                        </div>
                        <div class="flex-grow-1 d-flex justify-content-center align-items-center">
                            <canvas id="budgetAnalysisChart"></canvas>
                        </div>
                    </div>
                </div>

              <!-- Net Profit -->
<div class="div3 card-box" style="position: relative;">
    <div class="card-content">

        <!-- Title -->
        <div class="cards-title" style="margin: 0;">Net Profit (After Tax)</div>

        <!-- Net Profit Value -->
        <div style="display: flex; align-items: center; margin-top: 10px;">
            <span class="cards-details" style="font-size: 22px; font-weight: 700; line-height: 1;">
                ₱<?= number_format($net_after_tax, 2) ?>
            </span>
            <span style="margin-left: 8px; margin-bottom: 4px;">
                <?php if ($profit_arrow): ?>
                    <span style="
                        background-color: <?= $profit_color === '#28a745' ? '#d4edda' : '#f8d7da' ?>;
                        color: <?= $profit_color ?>;
                        font-weight: 600;
                        font-size: 13px;
                        padding: 3px 7px;
                        border-radius: 6px;
                        line-height: 1;
                    "><?= $profit_arrow ?> <?= abs(round($profit_change_yoy, 1)) ?>%
                    </span>
                <?php endif; ?>
            </span>
        </div>

        <!-- Tax Breakdown (Optional - can be shown on hover or click) -->
        <!--<div style="font-size: 11px; color: #6c757d; margin-top: 8px;">-->
        <!--    Gross Profit: ₱<?= number_format($gross_profit, 2) ?><br>-->
        <!--    Income Tax: ₱<?= number_format($tax_expense, 2) ?>-->
        <!--    <?php if ($tax_expense > 0): ?>-->
        <!--        <span style="font-size: 10px;">-->
        <!--            (<?= round(($tax_expense / $gross_profit) * 100, 1) ?>% effective rate)-->
        <!--        </span>-->
        <!--    <?php endif; ?>-->
        <!--</div>-->

        <!-- Comparison Text -->
        <div class="comparison-text">
            <?php if ($profit_change_yoy > 0): ?>
                Your Net Profit increased by
                <span style="font-weight: 600; color: #198754;">
                    ₱<?= number_format($current_year_net_profit - $prev_year_net_profit, 2) ?>
                </span>
                compared to last year
                (<span style="color: #6c757d;">₱<?= number_format($prev_year_net_profit, 2) ?></span>).
            <?php elseif ($profit_change_yoy < 0): ?>
                Your Net Profit decreased by
                <span style="font-weight: 600; color: #dc3545;">
                    ₱<?= number_format($prev_year_net_profit - $current_year_net_profit, 2) ?>
                </span>
                compared to last year
                (<span style="color: #6c757d;">₱<?= number_format($prev_year_net_profit, 2) ?></span>).
            <?php else: ?>
                Your Net Profit remained the same as last year
                (<span style="color: #6c757d;">₱<?= number_format($prev_year_net_profit, 2) ?></span>).
            <?php endif; ?>
        </div>
    </div>
 </div>


                
                <!-- Overall Expenses -->
                <div class="div4 card-box" style="position: relative;">
                    <div class="card-content">

                        <!-- Title -->
                        <div class="cards-title" style="margin: 0;">Overall Expenses (<?= $targetYear ?>)</div>

                        <!-- Current Year Value -->
                        <div style="display: flex; align-items: center; margin-top: 10px;">
                            <span class="cards-details" style="font-size: 22px; font-weight: 700; line-height: 1;">
                                ₱<?= number_format($current_year_expenses, 2) ?>
                            </span>
                            <span style="margin-left: 8px; margin-bottom: 4px;">
                                <?php if ($expense_arrow): ?>
                                    <span style="
                            background-color: <?= $expense_color === '#28a745' ? '#d4edda' : '#f8d7da' ?>;
                            color: <?= $expense_color ?>;
                            font-weight: 600;
                            font-size: 13px;
                            padding: 3px 7px;
                            border-radius: 6px;
                            line-height: 1;
                        ">
                                        <?= $expense_arrow ?> <?= abs(round($expense_change_yoy, 1)) ?>%
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Comparison Text -->
                        <div class="comparison-text">
                            <?php if ($expense_change_yoy > 0): ?>
                                Your expenses increased by
                                <span style="font-weight: 600; color: #dc3545;">
                                    ₱<?= number_format($current_year_expenses - $previous_year_expenses, 2) ?>
                                </span>
                                compared to last year 
                                (<span style="color: #6c757d;">₱<?= number_format($previous_year_expenses, 2) ?></span>).
                            <?php elseif ($expense_change_yoy < 0): ?>
                                Your expenses decreased by
                                <span style="font-weight: 600; color: #198754;">
                                    ₱<?= number_format($previous_year_expenses - $current_year_expenses, 2) ?>
                                </span>
                                compared to last year 
                                (<span style="color: #6c757d;">₱<?= number_format($previous_year_expenses, 2) ?></span>).
                            <?php else: ?>
                                Your expenses remained the same as last year 
                                (<span style="color: #6c757d;">₱<?= number_format($previous_year_expenses, 2) ?></span>).
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <!-- <img src="/Financial_Management/resources/svg/2B.svg" alt="Overall Expenses" class="card-icon"> -->
                    </div>
                </div>

                <!-- Total Projects -->
                <div class="div5 card-box">
                    <div class="card-content">
                        <div class="cards-title">Total Projects (<?= date('Y') ?>)</div>
                        <span class="cards-details"><?= $thisYearProjects ?></span>
                        <div class="">
                            <!-- <img src="/Financial_Management/resources/svg/3B.svg" alt="Projects Icon" class="card-icon"> -->
                        </div>

                        <div class="comparison-text mt-2">
                            <span>Total Project(s) from Last Year: <?= $lastYearProjects ?></span><br>
                        </div>
                    </div>
                </div>


                <!-- Ongoing Projects -->
                <!--<div class="div6 card-box">-->
                <!--    <div class="card-content">-->
                <!--        <div class="cards-title">Ongoing Projects</div>-->
                <!--        <span class="cards-details"><?= $ongoingProjects ?? 0 ?></span>-->
                <!--        <div class="">-->
                <!--            <img src="/resources/svg/4B.svg"-->
                <!--                alt="Gross Profit" class="card-icon">-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!-- Project Status -->
                <div class="div7">
                    <div class="table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Project</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Start Date</th>
                                    <th scope="col">End Date</th>
                                    <th scope="col">Remaining Days</th>
                                    <th scope="col">Client</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $latestProjects = array_slice($projects, 0, 5);
                                foreach ($latestProjects as $project):  ?>
                                    <tr>
                                        <td><a href="inner_pages/proj_stat.php?project_id=<?= $project['project_id'] ?>">
                                                <div><?= htmlspecialchars($project['project_name']) ?></div>
                                            </a>
                                        </td>
                                        <td style="padding: 8px; ">
                                            <?php
                                            $clientApproved = $project['user_approval'] ?? 0;
                                            $financeApproved = $project['finance_approval'] ?? 0;

                                            if (!$clientApproved || !$financeApproved) {
                                                echo '<span class="status-indicator">
                                <span class="status-dot dot-warning"></span> Pending Approval
                              </span>';
                                            } else {
                                                switch ($project['project_status']) {
                                                    case 'Ongoing':
                                                        echo '<span class="status-indicator">
                                        <span class="status-dot dot-success"></span> Ongoing
                                      </span>';
                                                        break;
                                                    case 'Upcoming':
                                                        echo '<span class="status-indicator">
                                        <span class="status-dot dot-primary"></span> Upcoming
                                      </span>';
                                                        break;
                                                    case 'Overdue':
                                                        echo '<span class="status-indicator">
                                        <span class="status-dot dot-danger"></span> Overdue
                                      </span>';
                                                        break;
                                                    case 'Completed':
                                                        echo '<span class="status-indicator">
                                        <span class="status-dot dot-secondary"></span> Completed
                                      </span>';
                                                        break;
                                                    default:
                                                        echo '<span class="status-indicator">
                                        <span class="status-dot dot-light"></span> Unknown
                                      </span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td style="padding: 8px;">
                                            <div class="text-muted">
                                                <?php
                                                if (!empty($project['start_date'])) {
                                                    echo date("M j, Y", strtotime($project['start_date'])); // Aug 20, 2025
                                                } else {
                                                    echo "—";
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td style="padding: 8px;">
                                            <div class="text-muted">
                                                <?php
                                                if (!empty($project['end_date'])) {
                                                    echo date("M j, Y", strtotime($project['end_date'])); // Aug 20, 2025
                                                } else {
                                                    echo "—";
                                                }
                                                ?>
                                            </div>
                                        </td>


                                        <td style="padding: 8px; text-align: start;">
                                            <?php
                                            $status = isset($project['project_status']) ? trim($project['project_status']) : '';
                                        
                                            $today = new DateTime();
                                            $today->setTime(0, 0, 0);
                                        
                                            if ($status === 'Upcoming') {
                                                // 🟢 For upcoming projects — show days before the start date
                                                if (!empty($project['start_date'])) {
                                                    $startDate = new DateTime($project['start_date']);
                                                    $startDate->setTime(0, 0, 0);
                                        
                                                    $interval = $today->diff($startDate);
                                                    $daysUntilStart = (int)$interval->format('%r%a');
                                        
                                                    if ($daysUntilStart > 0) {
                                                        $color = $daysUntilStart <= 7 ? 'orange' : 'green';
                                                        echo '<span style="color:' . $color . '; font-weight:500;">Starts in ' . $daysUntilStart . ' day(s)</span>';
                                                    } elseif ($daysUntilStart === 0) {
                                                        echo '<span style="color: orange; font-weight:600;">Starts today</span>';
                                                    } else {
                                                        echo '<span style="color: red; font-weight:600;">Started ' . abs($daysUntilStart) . ' day(s) ago — update status</span>';
                                                    }
                                                } else {
                                                    echo '<span style="color: red;">No start date</span>';
                                                }
                                        
                                            } elseif ($status === 'Completed') {
                                                // ✅ For completed projects — show "Completed"
                                                echo '<span style="color: gray; font-weight:600;">Completed</span>';
                                        
                                            } else {
                                                // 🟠 For ongoing or other statuses — show remaining days until end date
                                                if (!empty($project['end_date'])) {
                                                    $endDate = new DateTime($project['end_date']);
                                                    $endDate->setTime(0, 0, 0);
                                        
                                                    $interval = $today->diff($endDate);
                                                    $remainingDays = (int)$interval->format('%r%a');
                                        
                                                    if ($remainingDays > 0) {
                                                        $color = $remainingDays <= 7 ? 'orange' : 'green';
                                                        echo '<span style="color:' . $color . '; font-weight:500;">' . $remainingDays . ' day(s) left</span>';
                                                    } elseif ($remainingDays === 0) {
                                                        echo '<span style="color: orange; font-weight:600;">Due today</span>';
                                                    } else {
                                                        echo '<span style="color: red; font-weight:600;">' . abs($remainingDays) . ' days overdue</span>';
                                                    }
                                                } else {
                                                    echo '<span style="color: red;">No end date</span>';
                                                }
                                            }
                                            ?>
                                        </td>

                                        <td style="padding: 8px; ">
                                            <div class="text-muted"><?= htmlspecialchars($project['client_name']) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="div10">
                    <div class="d-flex flex-column h-100">
                        <!-- Row with Expenses label + Add button (left) and Total Expenses (right) -->
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left side: Expenses + Add button -->
                            <div class="d-flex gap-2">
                                <p class="container-title ">Expenses</p>
                                <a href="inner_pages/expenses.php?"
                                    class="add-expense-link mb-3"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Add Expense">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                                        <path
                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                                    </svg>
                                </a>
                            </div>
                            <!-- Right side: Total Expenses -->
                            <div class="total-expenses">
                                <p class="exp-total-amt">Total Amount</p>
                                <span id="total-expenses-display"></span>
                            </div>

                        </div>

                        <!-- Chart -->
                        <div class="expenses-container flex-grow-1">
                            <canvas id="expensesBreakdownChart"></canvas>
                        </div>
                    </div>

                </div>

                <?php
                include('dbcon.php');

                // Get current month and year
                $current_month = date('m');
                $current_year = date('Y');

                // Default selected month and year (if no selection is made)
                $selected_month = isset($_POST['month']) ? (string)(int)$_POST['month'] : (string)(int)$current_month;
                $selected_year = isset($_POST['year']) ? (string)(int)$_POST['year'] : (string)(int)$current_year;

                // Query to fetch total company expenses for the selected month
                $expenses_query = "SELECT SUM(amount) AS total_expenses FROM company_expense WHERE MONTH(date) = ? AND YEAR(date) = ?";
                $stmt = mysqli_prepare($conn, $expenses_query);
                mysqli_stmt_bind_param($stmt, "ii", $selected_month, $selected_year);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $total_expenses = 0;

                if ($row = mysqli_fetch_assoc($result)) {
                    $total_expenses = $row['total_expenses'] ? $row['total_expenses'] : 0;
                }

                // Get the earliest and latest year from company_expense table
                $year_range_query = "SELECT MIN(YEAR(date)) AS min_year, MAX(YEAR(date)) AS max_year FROM company_expense";
                $year_range_result = mysqli_query($conn, $year_range_query);
                $year_range = mysqli_fetch_assoc($year_range_result);

                $min_year = $year_range['min_year'] ? $year_range['min_year'] : $current_year - 5;
                $max_year = $year_range['max_year'] ? $year_range['max_year'] : $current_year;

                // Map month numbers to month names
                $months = [
                    '1' => 'January',
                    '2' => 'February',
                    '3' => 'March',
                    '4' => 'April',
                    '5' => 'May',
                    '6' => 'June',
                    '7' => 'July',
                    '8' => 'August',
                    '9' => 'September',
                    '10' => 'October',
                    '11' => 'November',
                    '12' => 'December'
                ];
                ?>

                <!-- Company Expenses -->
                <div class="div11">
                    <div class="">
                        <form method="POST" action="" id="expenseForm">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <p class="container-title">Company Expense</p>
                                        <a class="add-expense-link" href="inner_pages/company_exp.php?">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#4A7938" class="bi bi-plus-circle-fill mb-3" viewBox="0 0 16 16">
                                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                                            </svg>
                                        </a>
                                    </div>

                                    <!-- Month and Year Picker -->
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <!-- Month Dropdown -->
                                        <select name="month" id="month" onchange="this.form.submit()"
                                            style="padding: 8px 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; 
                                       background-color: #fff; cursor: pointer; transition: all 0.3s ease; outline: none;"
                                            onmouseover="this.style.borderColor='#4A7938'"
                                            onmouseout="this.style.borderColor='#ddd'"
                                            onfocus="this.style.borderColor='#4A7938'; this.style.boxShadow='0 0 5px rgba(74, 121, 56, 0.3)'"
                                            onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
                                            <?php foreach ($months as $month_num => $month_name): ?>
                                                <option value="<?php echo $month_num; ?>"
                                                    <?php echo ($month_num == $selected_month) ? 'selected' : ''; ?>>
                                                    <?php echo $month_name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <!-- Year Dropdown -->
                                        <select name="year" id="year" onchange="this.form.submit()"
                                            style="padding: 8px 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; 
                                       background-color: #fff; cursor: pointer; transition: all 0.3s ease; outline: none;"
                                            onmouseover="this.style.borderColor='#4A7938'"
                                            onmouseout="this.style.borderColor='#ddd'"
                                            onfocus="this.style.borderColor='#4A7938'; this.style.boxShadow='0 0 5px rgba(74, 121, 56, 0.3)'"
                                            onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
                                            <?php for ($year = $max_year; $year >= $min_year; $year--): ?>
                                                <option value="<?php echo $year; ?>"
                                                    <?php echo ($year == $selected_year) ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Displaying Total Company Expenses -->
                                <div>
                                    <?php if ($total_expenses > 0) { ?>
                                        <div>
                                            <div class="row" style="display: flex;">
                                                <span class="total-comp-exp">
                                                    ₱<?php echo number_format($total_expenses, 2); ?>
                                                </span>
                                                <span class="comp-exp-des">Total Company Expenses for <?php echo $months[$selected_month] . ' ' . $selected_year; ?></span>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Latest Company Expenses List -->
                                        <div>
                                            <?php
                                            // Get latest expenses for the selected month
                                            $expenses_list_query = "SELECT description, amount, date FROM company_expense WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC, company_exp_id DESC LIMIT 5";
                                            $stmt_list = mysqli_prepare($conn, $expenses_list_query);
                                            mysqli_stmt_bind_param($stmt_list, "ii", $selected_month, $selected_year);
                                            mysqli_stmt_execute($stmt_list);
                                            $expenses_list_result = mysqli_stmt_get_result($stmt_list);

                                            if (mysqli_num_rows($expenses_list_result) > 0) {
                                                while ($expense_row = mysqli_fetch_assoc($expenses_list_result)) {
                                                    echo '<li class="comp-exp-list">';
                                                    echo '<span style="color: #333;">' . htmlspecialchars($expense_row['description']) . ' </span>';
                                                    echo '<span style="font-weight: bold; color: #1a5319;">₱' . number_format($expense_row['amount'], 2) . '</span>';
                                                    echo '</li>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php } else { ?>
                                        <div class="empty-expenses text-center my-4">
                                            <img src="resources/svg/empty-company.svg"
                                                alt="No Expenses"
                                                style="max-width: 220px; opacity: 0.8;">
                                            <p class="comp-exp text-muted mt-3">
                                                No expenses recorded for <?php echo $months[$selected_month] . ' ' . $selected_year; ?>.
                                            </p>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- For AUTO log out -->
    <script src="./resources/js/auto_logout.js"></script>

    <script>
        window.addEventListener('beforeunload', function() {
            navigator.sendBeacon('logout.php'); // call logout script asynchronously
        });

        // Pass PHP data to JavaScript
        window.phpData = {
            selectedProjectId: <?php echo json_encode($selected_project_id); ?>,
            weeklyIncome: <?php echo json_encode($weeklyIncome); ?>,
            weeklyExpenses: <?php echo json_encode($weeklyExpenses); ?>,
            weeklyLabels: <?php echo json_encode($weeklyLabels); ?>,
            selectedProject: <?php echo json_encode($selectedProject); ?>
        };

        // Prevent back button navigation
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
            // Optionally redirect to login page if session is invalid
            fetch('check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.authenticated) {
                        window.location.href = '/Financial_Management/login_form.php';
                    }
                });
        };


        // Pass PHP data to JavaScript
        window.phpData = {
            selectedProjectId: <?php echo json_encode($selected_project_id); ?>,
            weeklyIncome: <?php echo json_encode($weeklyIncome); ?>,
            weeklyExpenses: <?php echo json_encode($weeklyExpenses); ?>,
            weeklyLabels: <?php echo json_encode($weeklyLabels); ?>,
            selectedProject: <?php echo json_encode($selectedProject); ?>
        };

        // Enhanced Back Button Prevention Script
        (function() {
            // Replace current history state
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Push additional state to prevent back navigation
            window.history.pushState(null, null, window.location.href);

            // Handle popstate events (back/forward button clicks)
            window.addEventListener('popstate', function(event) {
                // Prevent the default back behavior
                window.history.pushState(null, null, window.location.href);

                // Check session validity
                checkSessionAndRedirect();
            });

            // Handle page visibility change
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Page became visible again, check session
                    checkSessionAndRedirect();
                }
            });
        })();

        // Function to check session and redirect if invalid
        function checkSessionAndRedirect() {
            fetch('check_session.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-cache'
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.authenticated) {
                        // Clear any cached data
                        if (typeof(Storage) !== "undefined") {
                            sessionStorage.clear();
                            localStorage.clear();
                        }

                        // Redirect to login
                        window.location.replace('/Financial_Management/login_form.php');
                    }
                })
                .catch(error => {
                    console.error('Session check failed:', error);
                    // On error, redirect to login for security
                    window.location.replace('/Financial_Management/login_form.php');
                });
        }

        // Periodic session checking
        setInterval(function() {
            checkSessionAndRedirect();
        }, 30000); // Check every 30 seconds
    </script>

    <script src="resources/js/dashboard.js"></script>
</body>

</html>