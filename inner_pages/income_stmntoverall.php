<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 


//For income statement
if (isset($_GET['filter']) && $_GET['filter'] === 'income') {
    if (isset($_GET['clrbtn_income']) && $_GET['clrbtn_income'] === 'true') {
        unset($_SESSION['income_period_start']);
        unset($_SESSION['income_period_end']);
    } elseif (!empty($_GET['start_income']) && !empty($_GET['end_income'])) {
        $_SESSION['income_period_start'] = $_GET['start_income'];
        $_SESSION['income_period_end'] = $_GET['end_income'];
    }
}

//For cashflow statement
if (isset($_GET['filter']) && $_GET['filter'] === 'cashflow') {
    if (isset($_GET['clrbtn_cashflow']) && $_GET['clrbtn_cashflow'] === 'true') {
        unset($_SESSION['cashflow_period_start']);
        unset($_SESSION['cashflow_period_end']);
    } elseif (!empty($_GET['start_cashflow']) && !empty($_GET['end_cashflow'])) {
        $_SESSION['cashflow_period_start'] = $_GET['start_cashflow'];
        $_SESSION['cashflow_period_end'] = $_GET['end_cashflow'];
    }
} //For balance sheet
if (isset($_GET['filter']) && $_GET['filter'] === 'balance') {
    if (isset($_GET['clrbtn_balance']) && $_GET['clrbtn_balance'] === 'true') {
        unset($_SESSION['balance_period_start']);
        unset($_SESSION['balance_period_end']);
    } elseif (!empty($_GET['start_balance']) && !empty($_GET['end_balance'])) {
        $_SESSION['balance_period_start'] = $_GET['start_balance'];
        $_SESSION['balance_period_end'] = $_GET['end_balance'];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('../forms_logic/fs_password.php');
}

$error = $_SESSION['fs_error'] ?? "";

// For quote (username, email, and date generated)
$username = $_SESSION['auth_user']['name'] ?? 'Unknown User';
$email = $_SESSION['auth_user']['email'] ?? 'No Email';
date_default_timezone_set('Asia/Manila');
$generated_at = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/financial_statements.css">
    <link rel="stylesheet" href="../resources/css/main.css">
    <!--<link rel="stylesheet" href="../resources/css/index.css">-->
    <link rel="stylesheet" href="../resources/css/project_details.css">
</head>

<body>


    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>


        <!-- Main Content -->
        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'RVR SMES';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0 pe-3">
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="fs-link" href="overall_company.php">Financial Summary</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="income_stmntoverall.php">Financial Statement</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Content Section -->
                <div class="content p-3">
                     <!-- Password before accessing the financial statements -->
                    <div class="fs-container">
                        <?php $fsClass = empty($_SESSION['fs_unlocked']) ? 'fs-blur' : 'fs-clear';?>
                        <div class="<?= $fsClass ?>">
                    
                        <!-- DIV FOR BALANCE SHEET -->
                        <div class="balance-sheet mt-3 mb-5">
                            <div class="income-header">
                                <h4 style="font-weight: bold;">Balance Sheet</h4>
                                <button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#BalanceInfo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                    </svg>
                                </button>
                            </div>
                            <div class="d-flex gap-5 mt-3 mb-3">
                                <div id="dateRangeContainerBalance">
                                    <form action="" id="dateFilterBalance" method="GET">
                                        <!-- Hidden fields to identify client and project -->
                                        <input type="hidden" name="filter" value="balance">
                                        <!-- For Starting Period Field -->
                                        <div class="period d-flex align-items-center justify-content-end" style="gap: 10px; margin-bottom: 20px;">
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Starting Period:</label>
                                                <input name="start_balance" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;" placeholder="Select start date"
                                                    value="<?php echo isset($_SESSION['balance_period_start']) ? $_SESSION['balance_period_start'] : ''; ?>">
                                            </div>
                                            <!-- For Ending Period Field -->
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                                <input name="end_balance" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; margin-left: 0; background-color: transparent;" placeholder="Select end date"
                                                    value="<?php echo isset($_SESSION['balance_period_end']) ? $_SESSION['balance_period_end'] : ''; ?>">
                                            </div>
                                            <!-- To change buttons -->
                                            <?php
                                            $filterSet = isset($_SESSION['balance_period_start']) && isset($_SESSION['balance_period_end']);
                                            ?>
                                            <button type="submit" id="setBtnBalance" class="btn btn-dark btn-sm <?php echo $filterSet ? 'd-none' : '' ?>">Set Range</button>
                                            <button type="submit" name="clrbtn_balance" id="clearbtnBalance" value="true" class="btn btn-dark btn-sm <?php echo $filterSet ? '' : 'd-none' ?> ">Clear</button>
                                        </div>
                                    </form>
                                </div>
                                <script>
                                    flatpickr(".datepicker", {
                                        dateFormat: "Y-m-d"
                                    });
                                </script>

                                <div class="d-flex align-items-center mb-3 ms-auto">

                                    <button type="button" id="pdf_balance" class="btn btn-outline-orange ms-auto">
                                        <i class="bi bi-filetype-pdf"></i> Download PDF
                                    </button>
                                    <button type="button" id="print_balance" class="btn btn-outline-orange ms-2">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                            </div>


                            <?php
                            // To fetch the project cost and assign as service revenue
                           $con = mysqli_connect("localhost", "root", "", "financial_management");


                            $targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

                            $start_date = $_SESSION['balance_period_start'] ?? null;
                            $end_date = $_SESSION['balance_period_end'] ?? null;


                             // Fetch total cash inflow (debits) - payment of client
                            if ($start_date && $end_date) {
                                $query_cash = "SELECT SUM(amount) as total_cash FROM payment_clients WHERE date BETWEEN '$start_date' AND '$end_date'";
                            } else {
                                $query_cash = "SELECT SUM(amount) as total_cash FROM payment_clients WHERE YEAR(date) = $targetYear";
                            }
                            $cash_result = mysqli_query($con, $query_cash);
                            $cash_row = mysqli_fetch_assoc($cash_result);
                            // From Capitals
                              if ($start_date && $end_date) {
                                $query_cash_capital = "SELECT SUM(amount) as total_capital FROM budget_allocation WHERE date BETWEEN '$start_date' AND '$end_date'";
                            } else {
                                $query_cash_capital = "SELECT SUM(amount) as total_capital FROM budget_allocation WHERE YEAR(date) = $targetYear";
                            }
                            $cash_capital_result = mysqli_query($con, $query_cash_capital);
                            $cash_capital_row = mysqli_fetch_assoc($cash_capital_result);

                            $total_cash = $cash_row['total_cash'] + $cash_capital_row['total_capital'] ?? 0;
                            $total_cash_in = $cash_row['total_cash'] ?? 0;
                            
                            //For fetching the project cost as Service Revenue in Equity
                            if ($start_date && $end_date) {
                                $project_cost_query = "SELECT SUM(projected_budget_cost) AS project_prices FROM projects WHERE end_date BETWEEN '$start_date' AND '$end_date'";
                                $addons_cost_query = ("SELECT SUM(projected_budget_cost) AS project_prices FROM project_addons WHERE end_date BETWEEN '$start_date' AND '$end_date'");

                            } else {
                                $project_cost_query = "SELECT SUM(projected_budget_cost) AS project_prices FROM projects WHERE YEAR(end_date) = $targetYear";
                                $addons_cost_query = "SELECT SUM(projected_budget_cost) AS project_prices FROM project_addons WHERE YEAR(end_date) = $targetYear";
                            }
                            $cost_result = mysqli_query($con, $project_cost_query);
                            $project_cost = mysqli_fetch_assoc($cost_result)['project_prices'] ?? 0;
                            // Addons
                            $addons_result = mysqli_query($con, $addons_cost_query);
                            $addons_cost = mysqli_fetch_assoc($addons_result)['project_prices'] ?? 0;
                        
                            $clean_cost = (float) str_replace(',', '', $project_cost) + (float) str_replace(',', '', $addons_cost);
                            // $first_revenue = $clean_cost / 1.12;
                            // $second_revenue = $first_revenue * 0.12;
                            // $tax_expense = $second_revenue;
                            $clean_project_cost = $clean_cost;

                            // Fetch total cash outflow 
                            if ($start_date && $end_date) {
                                $query_cashOut_project = "SELECT SUM(amount) as total_amount FROM expenses WHERE date  BETWEEN '$start_date' AND '$end_date'";
                            } else {
                                $query_cashOut_project = "SELECT SUM(amount) as total_amount FROM expenses WHERE YEAR(date) = $targetYear";
                            }
                            $query_cashOut_project = "SELECT SUM(amount) as total_amount FROM expenses WHERE YEAR(date) = $targetYear";
                            $result_cash_out = mysqli_query($con, $query_cashOut_project);
                            $project_expOut = mysqli_fetch_assoc($result_cash_out);
                            $total_project_out = $project_expOut['total_amount'] ?? 0;

                            if ($start_date && $end_date) {
                                $query_cashOut_company = "SELECT SUM(amount) as total_companyExp FROM company_expense WHERE date BETWEEN '$start_date' AND '$end_date'";
                            } else {
                                $query_cashOut_company = "SELECT SUM(amount) as total_companyExp FROM company_expense WHERE YEAR(date) = $targetYear";
                            }
                            $result_companyOut = mysqli_query($con, $query_cashOut_company);
                            $company_expOut = mysqli_fetch_assoc($result_companyOut);
                            $total_company_out = $company_expOut['total_companyExp'] ?? 0;

                            if ($start_date && $end_date) {
                                $query_capital = "SELECT SUM(amount) as total_capital FROM budget_allocation WHERE date BETWEEN '$start_date' AND '$end_date'";
                            } else {
                                $query_capital = "SELECT SUM(amount) as total_capital FROM budget_allocation WHERE YEAR(date) = $targetYear";
                            }
                            $capital_result = mysqli_query($con, $query_capital);
                            $capital_row = mysqli_fetch_assoc($capital_result);
                            $total_capital = $capital_row['total_capital'] ?? 0;

                            //COmputation/Assignment for displaying Balance Sheet
                            $overall_expenses = floatval($total_project_out) + floatval($total_company_out);
                            $total_cash = floatval($total_cash) - floatval($overall_expenses);
                            $accounts_receivable = floatval($clean_project_cost) - floatval($total_cash_in);
                            $total_assets = floatval($total_cash) + floatval($accounts_receivable);
                            $equity = $total_assets;

                            ?>

                            <div id="balanceSheetPDF">
                                <!-- Used as id name to prevent having new css -->
                                <div class="card-body" id="balanceSheetPrint">
                                    <!-- This is for cashflow. it's just that the div classes and id are not yet modified. -->
                                    <table class="table income-table" id="income_table">
                                        <!-- Revenue Section -->
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="text-light">Assets</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td class="cf-head fst-italic">Current Assets</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="indent_title">Cash</td>
                                            <td class="right-column">
                                                <?php echo  $total_cash < 0 ? '(&#8369; ' . number_format(abs($total_cash), 2) . ')' : '&#8369; ' . number_format($total_cash, 2); ?>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <?php if (!empty($accounts_receivable) && $accounts_receivable != 0): ?>
                                            <tr>
                                                <td class="indent_title">Accounts Receivable</td>
                                                <td class="right-column">&#8369; <?php echo number_format($accounts_receivable, 2) ?></td>
                                                <td></td>
                                            </tr>
                                        <?php endif; ?>
                                       <tr>
                                            <td class="cf-head fst-italic">Non-Current Assets</td>
                                            <td class="right-column">
                                                <span class="line-total-current">
                                                    —
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                         <tr>
                                            <td class="cf-head fw-bold p-2">Total Assets </td>
                                            <td></td>
                                            <td class="totals fw-bold">
                                                <span class="double-underline">
                                                <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="text-light">Liabilities and Owner's Equity</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td class="cf-head fst-italic">Current Liabilities</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head fst-italic">Non-Current Liabilties</td>
                                            <td class="right-column">   
                                                <span class="line-total-current">
                                                    —
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head fw-bold">Total Liabilities</td>
                                            <td></td>
                                            <td class="totals">
                                                    —
                                            </td>
                                        </tr>
                                       <tr>
                                            <td class="indent_title">Owner's Capital</td>
                                            <td class="right-column"> 
                                                <?php echo  $equity < 0 ? '(&#8369; ' . number_format(abs($equity), 2) . ')' : '&#8369; ' . number_format($equity, 2); ?>
                                            </td>
                                            <td></td>
                                        </tr>
                                       <tr>
                                            <td class="cf-head fw-bold">Total Equity</td>
                                            <td></td>
                                            <td class="totals">
                                                <span class="line-total-assets">
                                                    <?php echo  $equity < 0 ? '(&#8369; ' . number_format(abs($equity), 2) . ')' : '&#8369; ' . number_format($equity, 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head fw-bold p-2">Total Liabilities & Owner's Equity </td>
                                            <td></td>
                                            <td class="totals fw-bold">
                                                <span class="double-underline">
                                                    <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class=" p-0">
                            <hr>
                        </div>

                        <!-- DIV FOR INCOME STATEMENT -->
                        <div class="income_statement mt-5 mb-5">
                            <div class="income-header">
                                <h4 style="font-weight: bold;">Income Statement</h4>
                                <button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#IncomeInfo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                    </svg>  
                                </button>
                            </div>
                            <div class="d-flex gap-5 mt-3 mb-3">
                                <div id="dateRangeContainerIncome">
                                    <form action="" id="dateFilterIncome" method="GET">
                                        <input type="hidden" name="filter" value="income">
                                        <div class="period d-flex align-items-center justify-content-end" style="gap: 10px; margin-bottom: 20px;">
                                            <!-- Starting Period -->
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Starting Period:</label>
                                                <input name="start_income" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;"
                                                    placeholder="Select start date"
                                                    value="<?php echo $_SESSION['income_period_start'] ?? ''; ?>">
                                            </div>
                                            <!-- Ending Period -->
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                                <input name="end_income" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;"
                                                    placeholder="Select end date"
                                                    value="<?php echo $_SESSION['income_period_end'] ?? ''; ?>">
                                            </div>
                                            <!-- Buttons -->
                                            <?php $incomeSet = isset($_SESSION['income_period_start']) && isset($_SESSION['income_period_end']); ?>
                                            <button type="submit" id="setBtnIncome" class="btn btn-dark btn-sm <?php echo $incomeSet ? 'd-none' : ''; ?>">Set Range</button>
                                            <button type="submit" name="clrbtn_income" id="clearBtnIncome" value="true" class="btn btn-dark btn-sm <?php echo $incomeSet ? '' : 'd-none'; ?>">Clear</button>
                                        </div>
                                    </form>
                                </div>
                                <script>
                                    flatpickr(".datepicker", {
                                        dateFormat: "Y-m-d"
                                    });
                                </script>


                                <div class="d-flex align-items-center mb-3 ms-auto">
                                    <button type="button" id="pdf_income" class="btn btn-outline-orange ms-auto">
                                        <i class="bi bi-filetype-pdf"></i> Download PDF
                                    </button>
                                    <button type="button" id="print_income" class="btn btn-outline-orange ms-2">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                            </div>

                            <?php
                            // To fetch the project cost and assign as service revenue
                            $con = mysqli_connect("localhost", "root", "", "financial_management");

                           $targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

                            $start_date = $_SESSION['income_period_start'] ?? null;
                            $end_date = $_SESSION['income_period_end'] ?? null;

                            $total_revenue = 0;
                            $total_operating_expenses = 0;
                            $company_expense_categories = [];

                            //To get the sum of all total project cost (the price of each project)
                            if ($start_date && $end_date) {
                                $project_rev_query = mysqli_query($con, "SELECT projected_budget_cost, end_date FROM projects WHERE end_date BETWEEN '$start_date' AND '$end_date'");
                                $addons_rev_query = mysqli_query($con, "SELECT projected_budget_cost, end_date FROM project_addons WHERE end_date BETWEEN '$start_date' AND '$end_date'");
                            } else {
                                // fallback to year filter
                                $project_rev_query = mysqli_query($con, "SELECT projected_budget_cost, end_date FROM projects WHERE YEAR(end_date) = $targetYear");
                                $addons_rev_query = mysqli_query($con, "SELECT projected_budget_cost, end_date FROM project_addons WHERE YEAR(end_date) = $targetYear");
                            }
                            while ($project = mysqli_fetch_assoc($project_rev_query)) {
                                $cleaned_revenue = (float) str_replace(',', '', $project['projected_budget_cost']);
                                $total_revenue += $cleaned_revenue;
                            }
                            // Sum addon revenue
                            while ($addon = mysqli_fetch_assoc($addons_rev_query)) {
                                $addon_revenue = (float) str_replace(',', '', $addon['projected_budget_cost']);
                                $total_revenue += $addon_revenue;
                            }

                            //To get the sum of all expenses for every project (not categorized per project nor client since the total is the target amount)
                            if ($start_date && $end_date) {
                                $expenses_allprojects = mysqli_query($con, "SELECT * FROM expenses WHERE date BETWEEN '$start_date' AND '$end_date'");
                            } else {
                                $expenses_allprojects = mysqli_query($con, "SELECT * FROM expenses WHERE YEAR(date) = $targetYear");
                            }
                            while ($expense = mysqli_fetch_assoc($expenses_allprojects)) {
                                $cleaned_expenses = (float) str_replace(',', '', $expense['amount']);
                                $total_operating_expenses += $cleaned_expenses;
                            }

                            //To get the sum of company expenses per category 
                            if ($start_date && $end_date) {
                                $company_exp_query = mysqli_query($con, "SELECT * FROM company_expense WHERE date BETWEEN '$start_date' AND '$end_date' ORDER BY category");
                            } else {
                                $company_exp_query = mysqli_query($con, "SELECT * FROM company_expense WHERE YEAR(date) = $targetYear ORDER BY category");
                            }
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

                            $total_overall_expenses = $total_operating_expenses + $total_company_expenses;
                            $gross_profit = $total_revenue - $total_overall_expenses;

                            // TAX COMPUTATION BRACKET
                            $taxable_income = $gross_profit;
                            $tax_expense = 0;

                            if ($taxable_income <= 250000){
                                $tax_expense = 0;
                            } else if ($taxable_income > 250000 && $taxable_income <= 400000){
                                $tax_expense ($taxable_income - 250000) * 0.15;
                            } else if ($taxable_income > 400000 && $taxable_income <= 800000){
                                $tax_expense = 22500 + (($taxable_income - 400000) * 0.20);
                            } else if ($taxable_income > 800000 && $taxable_income <= 2000000){
                                $tax_expense = 102500 + (($taxable_income - 800000) * 0.25);
                            } else if ($taxable_income > 2000000 && $taxable_income <= 8000000){
                                $tax_expense = 402500 + (($taxable_income - 2000000) * 0.30);
                            } else if ($taxable_income > 8000000){
                                $tax_expense = 2202500 + (($taxable_income - 8000000) * 0.35);
                            }

                            $income_after_tax = $gross_profit - $tax_expense;


                            ?>

                            <div id="balanceSheetPDF">
                                <!-- Used as id name to prevent having new css -->
                                <div class="card-body" id="balanceSheetPrint">
                                    <table class="table income-table" id="income_table">
                                        <!-- Revenue Section -->
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="text-light">Revenue</th>
                                            </tr>
                                        </thead>

                                        <tr>
                                            <td>Service Revenue</td>
                                            <td class="right-column">&#8369; <?php echo number_format($total_revenue, 2) ?></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head">Total Revenue</td>
                                            <td></td>
                                            <td>&#8369; <?php echo number_format($total_revenue, 2) ?></td>
                                        </tr>

                                        <!-- Expenses Section -->
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="text-light">Expenses</th>
                                            </tr>
                                        </thead>
                                         <tr>
                                            <td>Operating Expenses (Project Expenses)</td>
                                            <td class="right-column">
                                                (&#8369; <?php echo number_format($total_operating_expenses, 2); ?>)</td>
                                            <td></td>
                                        </tr>
                                       <?php 
                                        $keys = array_keys($company_expense_categories);
                                        $lastKey = end($keys); // To get the last row

                                        foreach ($company_expense_categories as $kategorya => $halaga) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($kategorya); ?></td>
                                                <td class="right-column">
                                                    <?php if ($kategorya === $lastKey) { ?>
                                                        <span class="line-total-exp">
                                                            (&#8369; <?php echo number_format($halaga, 2); ?>)
                                                        </span>
                                                    <?php } else { ?>
                                                        (&#8369; <?php echo number_format($halaga, 2); ?>)
                                                    <?php } ?>
                                                </td>
                                                <td></td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td class="cf-head">Total Expenses</td>
                                            <td></td>
                                            <td>
                                                <span class="line-total-income">
                                                    (&#8369; <?php echo number_format($total_overall_expenses, 2); ?>)
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- Net Income Section -->
                                        <tr>
                                            <td class="cf-head fw-bold" style="background-color: white !important; color: black !important; border-bottom: 1px solid #d6dadfff;">Net Income Before Tax</td>
                                            <td></td>
                                            <td class="totals fw-bold"><?php echo  $gross_profit < 0 ? '(&#8369; ' . number_format(abs($gross_profit), 2) . ')' : '&#8369; ' . number_format($gross_profit, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head">Income Tax Expense</td>
                                            <td></td>
                                            <td class="totals">
                                                <span class="line-total-tax">
                                                    (&#8369; <?php echo number_format($tax_expense, 2); ?>)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head fw-bold" >Net Income After Tax</td>
                                            <td></td>
                                            <td class="totals fw-bold"><?php echo  $income_after_tax < 0 ? '(&#8369; ' . number_format(abs($income_after_tax), 2) . ')' : '&#8369; ' . number_format($income_after_tax, 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class=" p-0">
                            <hr>
                        </div>

                        <!-- DIV FOR CASH FLOW -->
                        <div class="cashflow mt-5 mb-5">
                            <div class="income-header">
                                <h4 style="font-weight: bold;">Statement of Cash Flows</h4>
                                <button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#CashflowInfo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                    </svg>  
                                </button>
                            </div>
                            <div class="d-flex gap-5 mt-3 mb-3">
                                <div id="dateRangeContainerIncome">
                                    <form action="" id="dateFilterCashflow" method="GET">
                                        <input type="hidden" name="filter" value="cashflow">
                                        <div class="period d-flex align-items-center justify-content-end" style="gap: 10px; margin-bottom: 20px;">
                                            <!-- Starting Period -->
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Starting Period:</label>
                                                <input name="start_cashflow" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;"
                                                    placeholder="Select start date"
                                                    value="<?php echo $_SESSION['cashflow_period_start'] ?? ''; ?>">
                                            </div>
                                            <!-- Ending Period -->
                                            <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                                <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                                <input name="end_cashflow" class="form-control form-control-sm datepicker"
                                                    style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;"
                                                    placeholder="Select end date"
                                                    value="<?php echo $_SESSION['cashflow_period_end'] ?? ''; ?>">
                                            </div>
                                            <!-- Buttons -->
                                            <?php $cashflowSet = isset($_SESSION['cashflow_period_start']) && isset($_SESSION['cashflow_period_end']); ?>
                                            <button type="submit" id="setBtnCashflow" class="btn btn-dark btn-sm <?php echo $cashflowSet ? 'd-none' : ''; ?>">Set Range</button>
                                            <button type="submit" name="clrbtn_cashflow" id="clearBtnCashflow" value="true" class="btn btn-dark btn-sm <?php echo $cashflowSet ? '' : 'd-none'; ?>">Clear</button>
                                        </div>
                                    </form>
                                </div>
                                <script>
                                    flatpickr(".datepicker", {
                                        dateFormat: "Y-m-d"
                                    });
                                </script>
                                <!-- For Project Status -->
                                <input type="hidden" id="project_status" value="<?php echo $project_status; ?>">

                                <div class="d-flex align-items-center mb-3 ms-auto">
                                    <button type="button" id="pdf_cashflow" class="btn btn-outline-orange ms-auto">
                                        <i class="bi bi-filetype-pdf"></i> Download PDF
                                    </button>
                                    <button type="button" id="print_cashflow" class="btn btn-outline-orange ms-2">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                            </div>
                            
                            <?php
                            // To fetch the project cost and assign as service revenue
                           $con = mysqli_connect("localhost", "root", "", "financial_management");

                            $targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

                            $start_date = $_SESSION['cashflow_period_start'] ?? null;
                            $end_date = $_SESSION['cashflow_period_end'] ?? null;

                            $total_from_clients = 0;
                            $total_project_expenses = 0;
                            $total_operating_activities = 0;
                            $comp_expense_categories = [];

                            //To get the sum of all total project cost (the price of each project)
                            if ($start_date && $end_date) {
                                $from_clients_query = mysqli_query($con, "SELECT * FROM payment_clients WHERE date BETWEEN '$start_date' AND '$end_date'");
                            } else {
                                // fallback to year filter
                                $from_clients_query = mysqli_query($con, "SELECT * FROM payment_clients WHERE YEAR(date) = $targetYear");
                            }
                            while ($project = mysqli_fetch_assoc($from_clients_query)) {
                                $cleaned_revenue = (float) str_replace(',', '', $project['amount']);
                                $total_from_clients += $cleaned_revenue;
                            }

                            //To get the sum of all expenses for every project (not categorized per project nor client since the total is the target amount)
                            if ($start_date && $end_date) {
                                $total_allprojects = mysqli_query($con, "SELECT * FROM expenses WHERE date BETWEEN '$start_date' AND '$end_date'");
                            } else {
                                // fallback to year filter
                                $total_allprojects = mysqli_query($con, "SELECT * FROM expenses WHERE YEAR(date) = $targetYear");
                            }
                            while ($expense = mysqli_fetch_assoc($total_allprojects)) {
                                $cleaned_expenses = (float) str_replace(',', '', $expense['amount']);
                                $total_project_expenses += $cleaned_expenses;
                            }

                            //To get the sum of company expenses per category 
                            if ($start_date && $end_date) {
                                $comp_exp_query = mysqli_query($con, "SELECT * FROM company_expense WHERE date BETWEEN '$start_date' AND '$end_date' ORDER BY category");
                            } else {
                                $comp_exp_query = mysqli_query($con, "SELECT * FROM company_expense WHERE YEAR(date) = $targetYear ORDER BY category");
                            }
                            while ($row = mysqli_fetch_assoc($comp_exp_query)) {
                                $categ = $row['category'];
                                $comp_expenses = (float) str_replace(',', '', $row['amount']);

                                // Initialize the category if it's not set
                                if (!isset($comp_expense_categories[$categ])) {
                                    $comp_expense_categories[$categ] = 0;
                                }

                                $comp_expense_categories[$categ] += $comp_expenses;
                            }
                            $total_comp_expenses = array_sum($comp_expense_categories);

                            $total_over_expenses = $total_project_expenses + $total_comp_expenses;

                            // the variable $total_overall_expenses came from the logics of income statement
                            $total_operating_activities = $total_from_clients - $total_over_expenses;

                            ?>

                            <div id="balanceSheetPDF">
                                <!-- Used as id name to prevent having new css -->
                                <div class="card-body" id="balanceSheetPrint">
                                    <!-- This is for cashflow. it's just that the div classes and id are not yet modified. -->
                                    <table class="table income-table" id="income_table">
                                        <!-- Revenue Section -->
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="text-light">Cash Flow from Operating Activities</th>
                                            </tr>
                                        </thead>

                                        <tr>
                                            <td class="cf-head">Cash receipts from</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="indent_title">Clients</td>
                                            <td class="right-column-cash">&#8369; <?php echo number_format($total_from_clients, 2) ?></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head">Cash paid for</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="indent_title">Project Expenses</td>
                                            <td class="right-column-cash">(&#8369; <?php echo number_format($total_project_expenses, 2) ?>)</td>
                                            <td></td>
                                        </tr>
                                        <?php foreach ($comp_expense_categories as $kategorya => $halaga): ?>
                                            <tr>
                                                <td class="indent_title"><?php echo htmlspecialchars(($kategorya)) ?></td>
                                                <td class="right-column-cash">(&#8369; <?php echo number_format($halaga, 2) ?>)</td>
                                                <td></td>
                                            </tr>
                                        <?php endforeach; ?>
                                       <tr>
                                            <td class="cf-head fw-bold">Net Cash Flow from Operating Activities </td>
                                            <td class="right-column-cash">
                                                <span class="line-total-cashflow">
                                                    <?php echo  $total_operating_activities < 0 ? '(&#8369; ' . number_format(abs($total_operating_activities), 2) . ')' : '&#8369; ' . number_format($total_operating_activities, 2); ?>
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <!--<thead>-->
                                        <!--    <tr>-->
                                        <!--        <th class="fw-bold" style="background-color: white !important; color: black !important;">Net Increase (Decrease) in Cash</th>-->
                                        <!--        <td></td>-->
                                        <!--        <td class="totals fw-bold">-->
                                        <!--            <?php echo  $total_operating_activities < 0 ? '(&#8369; ' . number_format(abs($total_operating_activities), 2) . ')' : '&#8369; ' . number_format($total_operating_activities, 2); ?></td>-->
                                        <!--        </td>-->
                                        <!--    </tr>-->
                                        <!--</thead>-->
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($_SESSION['fs_unlocked'])): ?>
                        <div class="fs-blur-overlay">
                            <form method="POST">
                                    <div class="description-unlock">
                                       <p>To continue, please provide your account password to verify your identity and access the RVR SMES financial statements.</p>
                                    </div>
                                    <?php if (!empty($_SESSION['fs_error'])): ?>
                                        <div class="text-danger mt-2 mb-2"><?= $_SESSION['fs_error'] ?></div>
                                        <?php unset($_SESSION['fs_error']);?>
                                    <?php endif; ?>
                                    <div class="form-floating mb-3 position-relative">
                                        <input type="password" name="unlock_password" class="form-control" id="floatingPassword"
                                            placeholder="Password"
                                            value="">
                                        <label for="floatingPassword">Password</label>

                                        <!-- Show/Hide Password -->
                                        <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2"
                                            style="z-index: 2;" onclick="togglePassword('floatingPassword', 'toggleIcon1')">
                                            <i id="toggleIcon1" class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <button type="submit" name="confirm-pass" class="login_btn w-100 mt-3">Submit</button>
                                    </div>
                                    
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <script>
                        function togglePassword(inputId, iconId) {
                            let inputField = document.getElementById(inputId);
                            let icon = document.getElementById(iconId);

                            if (inputField.type === "password") {
                                inputField.type = "text";
                                icon.classList.replace("bi-eye", "bi-eye-slash");
                            } else {
                                inputField.type = "password";
                                icon.classList.replace("bi-eye-slash", "bi-eye");
                            }
                        }
                    </script>

                </div>
            </div>
            <!-- MODALS SECTION -->
            <?php if (!empty($_SESSION['status'])): ?>
                <div class="modal fade" id="noDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-center p-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                            <h4 class="mt-3">No Record Found</h4>
                            <p style="text-align: center; font-size: 16px;">
                                There are no expenses found for the selected date range.</p>
                            <div class="modal-footer border-0 d-flex justify-content-center">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Okay</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        new bootstrap.Modal(document.getElementById('noDataModal')).show();
                    });
                </script>
            <?php
                unset($_SESSION['status'], $_SESSION['status_type']);
            endif;
            ?>
            <!-- MODAL FOR INCOME STATEMENT INFO -->
            <div class="modal fade" id="IncomeInfo" tabindex="-1" aria-labelledby="infoIncome" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Centered and wider -->
                    <div class="modal-content">
                        <div class="modal-header" style="padding: 12px">
                            <h5 class="modal-title fw-bold" id="IncomeInfoLabel">RVR SMES Income Statement</h5>
                            <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                            The <strong>Income Statement</strong>, also known as the <strong>Profit and Loss Statement</strong>, 
                            summarizes the company’s revenues, expenses, and net profit or loss for a specific period.
                            All figures shown reflect the company’s performance for the <strong>current year</strong>, covering <strong>January 1 to December 31</strong>.<br><br>

                            In this report, <i>Service Revenue</i> represents the total of all project prices. The <i>Income Tax Expense</i> is computed using the 
                            <strong>annual tax brackets under BIR Form 1701 </strong>, based on the company’s net taxable income for the year.
                        </div>
                        <div class="modal-footer mt-0" style="padding-bottom: 20px; margin-top: none; border-top: none;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

             <!-- MODAL FOR CASHFLOW INFO -->
            <div class="modal fade" id="CashflowInfo" tabindex="-1" aria-labelledby="infoCashflow" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Centered and wider -->
                    <div class="modal-content">
                        <div class="modal-header" style="padding: 12px">
                            <h5 class="modal-title fw-bold" id="CashflowInfoLabel">RVR SMES Cashflow Report</h5>
                            <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                            The <strong>Statement of Cash Flows</strong> presents the cash inflows and outflows of the business for a specific period.
                            Since this report focuses only on <i>Operating Activities,</i> it highlights how the company's core operations generate and use cash. <br><br>
                            
                            It shows cash received from service revenues and cash paid for project expenses, company-wide expenses, and other operational needs.
                            This information helps assess whether the company's operations generate enough cash to cover day-to-day expenses and support its ongoing business activities. 
                        </div>
                         <div class="modal-footer mt-0" style="padding-bottom: 20px; margin-top: none; border-top: none;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

             <!-- MODAL FOR BALANCE SHEET INFO -->
            <div class="modal fade" id="BalanceInfo" tabindex="-1" aria-labelledby="infoBalanceSheet" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Centered and wider -->
                    <div class="modal-content">
                        <div class="modal-header" style="padding: 12px">
                            <h5 class="modal-title fw-bold" id="BalanceSheetInfoLabel">RVR SMES Balance Sheet</h5>
                            <button type="button" class="btn-close" style="font-size: 14px" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                            The <strong>Balance Sheet</strong> outlines the company's financial condition at a specific point of time. It shows that
                            the RVR SMES resources consist mainly of cash and receivables. With no liabilities recorded, the asset is fully financed by the owner's equity,
                            which comes from accumulated net income. 
                            <i>It is important to note that the accumulated net income reported here is presented before tax expenses have been applied.</i><br><br>
                            
                            This indicates that the business currently operates debt-free, relying solely on its own resources and retained earnings to sustain operations. 
                        </div>
                         <div class="modal-footer mt-0" style="padding-bottom: 20px; margin-top: none; border-top: none;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- This is for print and download purpose  -->
            <iframe id="printFrame" style="display:none;"></iframe>
            <script>
                // Date and Time Function
                function updateDateTime() {
                    const now = new Date();
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    };

                    document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
                }

                // Update every second
                setInterval(updateDateTime, 1000);

                // Initial call to display time immediately
                updateDateTime();

                function handlePrintIncome() {
                    const iframe = document.getElementById('printFrame');

                    // Pass PHP values into JS
                    const start_date = "<?php echo $start_date ?? ''; ?>";
                    const end_date = "<?php echo $end_date ?? ''; ?>";

                    let url = `income_all_print.php`;
                    if (start_date && end_date) {
                        url += `?start_period=${start_date}&end_period=${end_date}`;
                    }

                    iframe.onload = function() {
                        setTimeout(() => {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        }, 300);
                    };

                    iframe.src = url;
                }

                document.getElementById('print_income').addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePrintIncome();
                });

                function handlePdfDownloadIncome() {
                    const iframe = document.getElementById('printFrame');
                    

                    iframe.onload = function() {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        const container = iframeDoc.querySelector('#download-container');

                        if (!container) {
                            alert('Download container not found!');
                            return;
                        }

                        // Clone content & styles
                        const clonedContent = container.cloneNode(true);
                        const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                            styleTags.forEach(style => {
                                clonedContent.appendChild(style.cloneNode(true));
                            });

                        // Generate PDF
                        html2pdf()
                            .set({
                                margin: [0.3, 0.5, 0.5, 0.5],
                                filename: 'IncomeStatement_RVR.pdf',
                                image: {
                                    type: 'jpeg',
                                    quality: 0.98
                                },
                                html2canvas: {
                                    scale: 2,
                                    useCORS: true,
                                    logging: false,
                                    scrollY: 0, // Important: prevents scroll offset issues
                                    scrollX: 0,
                                    ignoreElements: (element) => element.classList.contains('footer') 
                                },
                                jsPDF: {
                                    unit: 'in',
                                    format: 'a4',
                                    orientation: 'portrait'
                                },
                                pagebreak: {
                                    avoid: ['table', 'tr']
                                }
                            })
                            .from(clonedContent)
                            .toPdf()
                            .get('pdf')
                            .then(function(pdf) {
                                const totalPages = pdf.internal.getNumberOfPages();
                                if (totalPages > 1) {
                                    pdf.deletePage(totalPages); // Fixes blank last page
                                }
                                const pageWidth = pdf.internal.pageSize.getWidth();
                                const pageHeight = pdf.internal.pageSize.getHeight();

                                for (let i = 1; i <= totalPages; i++) {
                                    pdf.setPage(i);
                                    pdf.setFontSize(10);
                                    pdf.setTextColor(85, 85, 85);

                                    pdf.text(
                                        "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                        pageWidth / 2,
                                        pageHeight - 0.3, 
                                        { align: "center" }
                                    );
                                }
                                pdf.save('IncomeStatement_RVR.pdf');
                            })
                            .catch(err => {
                                console.error('PDF generation failed:', err);
                                alert('Failed to generate PDF.');
                            });
                    };
                    iframe.src = `income_all_print.php<?php
                                                        echo ($start_date && $end_date)
                                                            ? '?start_period=' . $start_date . '&end_period=' . $end_date
                                                            : '';
                                                        ?>`;
                }

                document.getElementById('pdf_income').addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePdfDownloadIncome();
                });

                // FOR CASHFLOW REPORT PRINT AND DOWNLOAD
                function handlePrintCashFlow() {
                    const iframe = document.getElementById('printFrame');

                    // Pass PHP values into JS
                    const start_date = "<?php echo $start_date ?? ''; ?>";
                    const end_date = "<?php echo $end_date ?? ''; ?>";

                    let url = `cashflow_all_print.php`;
                    if (start_date && end_date) {
                        url += `?start_period=${start_date}&end_period=${end_date}`;
                    }

                    iframe.onload = function() {
                        setTimeout(() => {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        }, 300);
                    };

                    iframe.src = url;
                }

                document.getElementById('print_cashflow').addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePrintCashFlow();
                });

                // Function to handle PDF download
                function handlePdfDownloadCashflow() {
                    const iframe = document.getElementById('printFrame');
                    iframe.onload = function() {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        const container = iframeDoc.querySelector('#download-container');

                        if (!container) {
                            alert('Download container not found!');
                            return;
                        }

                        // Clone content & styles
                        const clonedContent = container.cloneNode(true);
                         const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                            styleTags.forEach(style => {
                                clonedContent.appendChild(style.cloneNode(true));
                            });

                        // Generate PDF
                        html2pdf()
                            .set({
                                margin: [0.3, 0.5, 0.5, 0.5],
                                filename: 'CashFlowReport_RVR.pdf',
                                image: {
                                    type: 'jpeg',
                                    quality: 0.98
                                },
                                html2canvas: {
                                    scale: 2,
                                    useCORS: true,
                                    logging: false,
                                    scrollY: 0, // Important: prevents scroll offset issues
                                    scrollX: 0,
                                    ignoreElements: (element) => element.classList.contains('footer')  
                                },
                                jsPDF: {
                                    unit: 'in',
                                    format: 'a4',
                                    orientation: 'portrait'
                                },
                                pagebreak: {
                                    avoid: ['table']
                                }
                            })
                            .from(clonedContent)
                            .toPdf()
                            .get('pdf')
                            .then(function(pdf) {
                                const totalPages = pdf.internal.getNumberOfPages();
                                if (totalPages > 1) {
                                    pdf.deletePage(totalPages); // Fixes blank last page
                                }
                                const pageWidth = pdf.internal.pageSize.getWidth();
                                const pageHeight = pdf.internal.pageSize.getHeight();

                                for (let i = 1; i <= totalPages; i++) {
                                    pdf.setPage(i);
                                    pdf.setFontSize(10);
                                    pdf.setTextColor(85, 85, 85);

                                    pdf.text(
                                        "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                        pageWidth / 2,
                                        pageHeight - 0.3, 
                                        { align: "center" }
                                    );
                                }
                                pdf.save('CashFlowReport_RVR.pdf');
                            })
                            .catch(err => {
                                console.error('PDF generation failed:', err);
                                alert('Failed to generate PDF.');
                            });
                    };
                    iframe.src = `cashflow_all_print.php<?php echo ($start_date && $end_date) ? '?start_period=' . $start_date . '&end_period=' . $end_date : ''; ?>`;
                }


                document.getElementById('pdf_cashflow').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default action

                    handlePdfDownloadCashflow(); // Call PDF download only for Completed projects

                });

                // FOR BALANCE SHEET REPORT PRINT AND DOWNLOAD
                function handlePrintBalance() {
                    const iframe = document.getElementById('printFrame');

                    // Pass PHP values into JS
                    const start_date = "<?php echo $start_date ?? ''; ?>";
                    const end_date = "<?php echo $end_date ?? ''; ?>";

                    let url = `balance_all_print.php`;
                    if (start_date && end_date) {
                        url += `?start_period=${start_date}&end_period=${end_date}`;
                    }

                    iframe.onload = function() {
                        setTimeout(() => {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        }, 300);
                    };

                    iframe.src = url;
                }

                document.getElementById('print_balance').addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePrintBalance();
                });

                // Function to handle PDF download
                function handlePdfDownloadBalance() {
                    const iframe = document.getElementById('printFrame');
                    iframe.onload = function() {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        const container = iframeDoc.querySelector('#download-container');

                        if (!container) {
                            alert('Download container not found!');
                            return;
                        }

                        // Clone content & styles
                        const clonedContent = container.cloneNode(true);
                         const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                            styleTags.forEach(style => {
                                clonedContent.appendChild(style.cloneNode(true));
                            });

                        // Generate PDF
                        html2pdf()
                            .set({
                                margin: [0.3, 0.2, 0.5, 0.5],
                                filename: 'BalanceSheet_RVR.pdf',
                                image: {
                                    type: 'jpeg',
                                    quality: 0.98
                                },
                                html2canvas: {
                                    scale: 2,
                                    useCORS: true,
                                    logging: false,
                                    scrollY: 0, // Important: prevents scroll offset issues
                                   scrollX: 0,
                                   ignoreElements: (element) => element.classList.contains('footer') 
                                },
                                jsPDF: {
                                    unit: 'in',
                                    format: 'a4',
                                    orientation: 'portrait'
                                },
                                pagebreak: {
                                    avoid: ['table']
                                }
                            })
                            .from(clonedContent)
                            .toPdf()
                            .get('pdf')
                            .then(function(pdf) {
                                const totalPages = pdf.internal.getNumberOfPages();
                                if (totalPages > 1) {
                                    pdf.deletePage(totalPages); // Fixes blank last page
                                }
                                const pageWidth = pdf.internal.pageSize.getWidth();
                                const pageHeight = pdf.internal.pageSize.getHeight();

                                for (let i = 1; i <= totalPages; i++) {
                                    pdf.setPage(i);
                                    pdf.setFontSize(10);
                                    pdf.setTextColor(85, 85, 85);

                                    pdf.text(
                                        "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                        pageWidth / 2,
                                        pageHeight - 0.3, 
                                        { align: "center" }
                                    );
                                }
                                pdf.save('BalanceSheet_RVR.pdf');
                            })
                            .catch(err => {
                                console.error('PDF generation failed:', err);
                                alert('Failed to generate PDF.');
                            });
                    };
                    iframe.src = `balance_all_print.php<?php echo ($start_date && $end_date) ? '?start_period=' . $start_date . '&end_period=' . $end_date : ''; ?>`;
                }


                document.getElementById('pdf_balance').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default action

                    handlePdfDownloadBalance(); // Call PDF download only for Completed projects

                });
            </script>


</body>

</html>