<?php
include('dbcon.php');
include('./layout/session_check.php');

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
$projectQuery = mysqli_query($conn, "SELECT p.project_id, p.project_name, p.start_date, p.end_date, p.project_status, p.user_approval, p.finance_approval, c.client_name 
                                    FROM projects p 
                                    LEFT JOIN clients c ON p.client_id = c.client_id");
if ($projectQuery && mysqli_num_rows($projectQuery) > 0) {
    while ($row = mysqli_fetch_assoc($projectQuery)) {
        $projects[] = $row;
    }
}

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

// Get user name from session
$name = $_SESSION['auth_user']['name'] ?? 'Guest';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
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

        <div id="sidebar" class="side-nav-container container-fluid">
            <!-- Side Nav Header -->
            <!-- <div class="d-flex justify-content-end">
                <button id="sidebarToggle" class="btn btn-outline-light mb-3" type="button" style="width:40px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left-to-line-icon lucide-arrow-left-to-line"><path d="M3 19V5"/><path d="m13 6-6 6 6 6"/><path d="M7 12h14"/></svg>
                </button>
            </div> -->
            <!-- Company Name -->
            <div class="company-name">
                <img src="resources/images/rvr logo.png " alt="" width="45" height="45" class="rounded-circle me-2">
                <strong>RVR Squared</strong>
            </div>

            <!-- Side Nav Options -->
            <ul class="nav nav-pills flex-column mb-auto text-start">
                <li class="nav-item mb-2">
                    <a href="index.php" class="nav-link active d-flex align-items-center" data-section="home.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                            <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        </svg>
                        Dashboard
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="inner_pages/clients.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round">
                            <path d="M18 21a8 8 0 0 0-16 0" />
                            <circle cx="10" cy="8" r="5" />
                            <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                        </svg>
                        Clients
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="inner_pages/expenses.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-banknote-arrow-down-icon lucide-banknote-arrow-down">
                            <path d="M12 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" />
                            <path d="m16 19 3 3 3-3" />
                            <path d="M18 12h.01" />
                            <path d="M19 16v6" />
                            <path d="M6 12h.01" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                        Transactions
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="inner_pages/chartaccounts.php" class="nav-link d-flex align-items-center data-section=" clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chart-gantt-icon lucide-square-chart-gantt">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 8h7" />
                            <path d="M8 12h6" />
                            <path d="M11 16h5" />
                        </svg>
                        Chart of Accounts
                    </a>
                </li>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/create_user.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'create_user.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-user-round-icon lucide-square-user-round">
                                <path d="M18 21a6 6 0 0 0-12 0" />
                                <circle cx="12" cy="11" r="4" />
                                <rect width="18" height="18" x="3" y="3" rx="2" />
                            </svg>
                            Manage users
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mb-2">
                    <a href="inner_pages/intro_archive.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'intro_archive.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive">
                            <rect width="20" height="5" x="2" y="3" rx="1" />
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                        Archive Records
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
                            RVR SMES
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/exp_logs_list.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page == 'exp_logs_list.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-logs-icon lucide-logs">
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
                            System Logs
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
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Log Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <a href="/Financial_Management/forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper d-flex flex-column flex-grow-1">

        <!-- Top Nav -->
        <nav class="d-flex px-5 flex-row align-items-start justify-content-between">

            <div class="page-title">
                Dashboard
            </div>

            <div class="d-flex align-items-center">
                <p id="datetime"></p>

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
                        <!-- <li><a class="dropdown-item" href="#">Settings</a></li> -->
                        <li><a class="dropdown-item" href="inner_pages/my_profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="../forms_logic/logout.php" class="nav-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="content flex-grow-1">
            <div class="parent">
                <div class="div1">
                    <div class="flex-grow-1" style="flex: 2;">
                        <!-- Project Dropdown and Profit & Loss Chart -->
                        <div class=" d-flex align-items-start justify-content-between">
                            <div>
                                <p class="project_name">
                                    <span id="project-name">
                                        <?php
                                        // Only show project name if one is selected
                                        if ($selectedProject) {
                                            echo htmlspecialchars($selectedProject['project_name']);
                                        } else {
                                            echo 'No Project Selected';
                                        }
                                        ?>
                                    </span>
                                    <span id="client-name" class="text-muted ms-1" style="font-size: 1rem;">
                                        <?php
                                        if ($selectedProject) {
                                            echo htmlspecialchars($selectedProject['client_name']);
                                        } else {
                                            echo 'Please select a project';
                                        }
                                        ?>
                                    </span>
                                </p>
                            </div>

                            <!-- Dropdown -->
                            <div class="btn-group align-self-center">
                                <button type="button" class="select-project btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Project
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($projects as $project): ?>
                                        <li>
                                            <a class="dropdown-item project-select" href="?project_id=<?= $project['project_id'] ?>"
                                                data-id="<?= $project['project_id'] ?>"
                                                data-project="<?= htmlspecialchars($project['project_name']) ?>"
                                                data-client="<?= htmlspecialchars($project['client_name']) ?>">
                                                <?= htmlspecialchars($project['project_name']) ?> <br>
                                                <small class="text-muted">Client: <?= htmlspecialchars($project['client_name']) ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Profit and Loss -->
                        <p style="font-size: 1rem;">Profit and Loss</p>
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

                <!-- Budget Analysis -->
                <div class="div2">
                    <div class="d-flex flex-column h-100">
                        <div class="mb-2">
                            <h6 class="text-muted mb-1">Budget Analysis</h6>
                            <div class="budget-summary" id="budget-summary-display">
                                <!-- Select a project to view budget breakdown -->
                            </div>
                        </div>
                        <div class="flex-grow-1 d-flex justify-content-center align-items-center">
                            <canvas id="budgetAnalysisChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Clients -->
                <!-- <div class="div3">
                   
                </div>
                <div class="div4">
                   
                </div>
                <div class="div5">
                 
                </div>
                <div class="div6">
               
                </div> -->


                <!-- Project Status -->
                <!-- <div class="div7">
                    <div style="max-height: 500px; width: 100%; overflow-y: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Remaining Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <a href="inner_pages/proj_stat.php?project_id=<?= $project['project_id'] ?>">
                                                <div class="fw-bold text-success"><?= htmlspecialchars($project['project_name']) ?></div>
                                            </a>
                                            <div class="text-muted fst-italic"><?= htmlspecialchars($project['client_name']) ?></div>
                                        </td>

                                        <td><?= htmlspecialchars($project['start_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($project['end_date'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            if (!empty($project['end_date'])) {
                                                $endDate = new DateTime($project['end_date']);
                                                $today = new DateTime();
                                                $interval = $today->diff($endDate);
                                                $remainingDays = (int)$interval->format('%r%a');

                                                echo '<span style="color: red; font-weight: bold;">';
                                                if ($remainingDays > 0) {
                                                    echo $remainingDays . ' days left';
                                                } elseif ($remainingDays === 0) {
                                                    echo 'Due today';
                                                } else {
                                                    echo abs($remainingDays) . ' days overdue';
                                                }
                                                echo '</span>';
                                            } else {
                                                echo '<span style="color: red; font-weight: bold;">No end date</span>';
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            $clientApproved = $project['user_approval'] ?? 0;
                                            $financeApproved = $project['finance_approval'] ?? 0;

                                            if (!$clientApproved || !$financeApproved) {
                                                echo '<span class="badge bg-warning text-dark p-2"> Pending Approval</span>';
                                            } else {
                                                switch ($project['project_status']) {
                                                    case 'Ongoing':
                                                        echo '<span class="badge bg-success p-2"> Ongoing</span>';
                                                        break;
                                                    case 'Upcoming':
                                                        echo '<span class="badge bg-primary text-light p-2"> Upcoming</span>';
                                                        break;
                                                    case 'Overdue':
                                                        echo '<span class="badge bg-danger p-2"> Overdue</span>';
                                                        break;
                                                    case 'Completed':
                                                        echo '<span class="badge bg-secondary p-2">Completed</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-light text-dark">Unknown</span>';
                                                }
                                            }
                                            ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div> -->

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
                                <?php foreach ($projects as $project): ?>
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


                                        <td style="padding: 8px; text-align: center;">
                                            <?php
                                            if (!empty($project['end_date'])) {
                                                $endDate = new DateTime($project['end_date']);
                                                $today = new DateTime();

                                                // Reset time to avoid time comparison issues
                                                $endDate->setTime(0, 0, 0);
                                                $today->setTime(0, 0, 0);

                                                $interval = $today->diff($endDate);
                                                $remainingDays = (int)$interval->format('%r%a');

                                                // Determine color and text based on remaining days
                                                if ($remainingDays > 0) {
                                                    $color = $remainingDays <= 7 ? 'orange' : 'green';
                                                    echo '<span style="color: ' . $color . ';">' . $remainingDays . ' day(s) left</span>';
                                                } elseif ($remainingDays === 0) {
                                                    echo '<span style="color: orange;">Due today</span>';
                                                } else {
                                                    echo '<span style="color: red;">' . abs($remainingDays) . ' days overdue</span>';
                                                }
                                            } else {
                                                echo '<span style="color: red;">No end date</span>';
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
                    <div class="d-flex flex-column align-items-start">
                        <!-- Row with Expenses label and SVG button beside each other -->
                        <div class="d-flex mb-2 gap-2 align-items-start">
                            <!-- Display Total Expenses -->
                            <div class="total-expenses" id="total-expenses-display">
                                Select a project to view expenses
                            </div>
                            <div>
                                <a href="inner_pages/expenses.php?"
                                    class="add-expense-link"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Add Expense">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="expenses-container">
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
$selected_month = isset($_POST['month']) ? $_POST['month'] : $current_month;
$selected_year = isset($_POST['year']) ? $_POST['year'] : $current_year;

// Query to fetch total company expenses for the selected month using prepared statements
$expenses_query = "SELECT SUM(amount) AS total_expenses FROM company_expense WHERE MONTH(date) = ? AND YEAR(date) = ?";
$stmt = mysqli_prepare($conn, $expenses_query);
mysqli_stmt_bind_param($stmt, "ii", $selected_month, $selected_year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_expenses = 0;

if ($row = mysqli_fetch_assoc($result)) {
    $total_expenses = $row['total_expenses'] ? $row['total_expenses'] : 0;
}

// Get available months and years for dropdown based on records in the company_expense table
$dates_query = "SELECT DISTINCT MONTH(date) AS month, YEAR(date) AS year FROM company_expense ORDER BY year DESC, month ASC";
$dates_result = mysqli_query($conn, $dates_query);

$available_months = [];
$available_years = [];

while ($row = mysqli_fetch_assoc($dates_result)) {
    if ($row['year'] == $selected_year) {
        $available_months[] = $row['month'];
    }
    if (!in_array($row['year'], $available_years)) {
        $available_years[] = $row['year'];
    }
}

// If no months available for selected year, get all available months
if (empty($available_months)) {
    $all_months_query = "SELECT DISTINCT MONTH(date) AS month FROM company_expense";
    $all_months_result = mysqli_query($conn, $all_months_query);
    
    while ($row = mysqli_fetch_assoc($all_months_result)) {
        $available_months[] = $row['month'];
    }
}

// Map month numbers to month names
$months = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];
?>

<div class="div11">
    <div class="d-flex">
        <form method="POST" action="">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                    <p style="font-weight: bold; font-size: 1.2rem; margin: 0;">Company Expense</p>
                    <a href="inner_pages/company_exp.php?" style="text-decoration: none; color: inherit;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#4A7938" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                        </svg>
                    </a>

                    <!-- Dropdown for selecting year -->
                    <div style="margin-bottom: 10px;">
                        <select name="year" id="year" onchange="this.form.submit()">
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo ($year == $selected_year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dropdown for selecting month -->
                    <div style="margin-bottom: 10px;">
                        <select name="month" id="month" onchange="this.form.submit()">
                            <?php foreach ($months as $month_num => $month_name): ?>
                                <?php if (in_array((int)$month_num, $available_months)): ?>
                                    <option value="<?php echo $month_num; ?>" <?php echo ($month_num == $selected_month) ? 'selected' : ''; ?>>
                                        <?php echo $month_name; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Displaying Total Expenses -->
                <div>
                    <?php if ($total_expenses > 0) { ?>
                        <div style="margin-bottom: 10px;">
                            <div class="row" style="display: flex; ">
                                <span style="font-weight: bold; color: #1a5319;">
                                    ₱<?php echo number_format($total_expenses, 2); ?>
                                </span>
                                <span>Total Company Expenses for <?php echo $months[$selected_month] . ' ' . $selected_year; ?></span>
                            </div>
                        </div>

                        <!-- Latest Company Expenses List -->
                        <div style="margin-top: 15px;">
                            <p style="font-weight: bold; font-size: 1rem; margin-bottom: 10px;">For the Month of <?php echo $months[$selected_month]; ?></p>
                            <?php
                            // Get latest expenses for the selected month
                            $expenses_list_query = "SELECT description, amount FROM company_expense WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC, company_exp_id DESC";
                            $stmt_list = mysqli_prepare($conn, $expenses_list_query);
                            mysqli_stmt_bind_param($stmt_list, "ii", $selected_month, $selected_year);
                            mysqli_stmt_execute($stmt_list);
                            $expenses_list_result = mysqli_stmt_get_result($stmt_list);

                            if (mysqli_num_rows($expenses_list_result) > 0) {
                                while ($expense_row = mysqli_fetch_assoc($expenses_list_result)) {
                                    echo '<div style="margin-bottom: 5px;">';
                                    echo '<span style="color: #333;">' . htmlspecialchars($expense_row['description']) . ' - </span>';
                                    echo '<span style="font-weight: bold; color: #1a5319;">₱' . number_format($expense_row['amount'], 2) . '</span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted">No expenses recorded for <?php echo $months[$selected_month] . ' ' . $selected_year; ?>.</p>
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