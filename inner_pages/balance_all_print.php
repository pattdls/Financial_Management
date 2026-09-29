<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Database Connection
$connection = mysqli_connect("localhost", "root", "", "financial_management");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
// To fetch the project cost and assign as service revenue
$con = mysqli_connect("localhost", "root", "", "financial_management");


$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

$start_date = $_SESSION['balance_period_start'] ?? null;
$end_date = $_SESSION['balance_period_end'] ?? null;
$date_range_text = '';

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
$project_cost = mysqli_fetch_assoc($cost_result)['project_prices'];
 // Addons
$addons_result = mysqli_query($con, $addons_cost_query);
$addons_cost = mysqli_fetch_assoc($addons_result)['project_prices'];
                        
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
    <title>Balance Sheet</title>
    <style>
        #download-container {
            font-family: "Times New Roman", serif;
            color: #000;
            font-size: 14px;
            margin: 30px;
            margin-top: 2px;
        }

        #download-container .header {
            text-align: center;
            margin-bottom: 5px;
            position: relative;
        }

        #download-container .header img {
            width: 400px;
            margin-bottom: 10px;
            margin-top: 0;
        }

        #download-container .title-row {
            margin-top: 10px;
        }

        #download-container .title-row h2 {
            font-size: 22px;
            font-weight: bold;
            color: #1e1082;
            margin: 0 auto;
        }

        #download-container .info {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        #download-container .info h4 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        #download-container .as-of {
            font-size: 14px;
            text-align: center;
            margin-top: 5px;
            font-style: italic;
        }

        #download-container table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
            table-layout: auto;
        }

        #download-container th {
            text-align: left;
            font-size: 15px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            color: #000;
            word-wrap: break-word;
        }

        #download-container td {
            padding: 5px 10px;
            vertical-align: top;
            font-weight: normal;
            word-wrap: break-word;
        }

        #download-container .text-end {
            text-align: right;
        }

        #download-container .note {
            font-style: italic;
            font-size: 13px;
            padding-left: 20px;
            color: #555;
        }

        #download-container td.double-underline {
            position: relative;
            font-weight: bold;
        }

        #download-container td.double-underline::after {
            content: "";
            position: absolute;
            bottom: 3px;
            right: 0;
            left: 0;
            height: 1px;
            background: #000;
            transform: scaleY(0.5);
        }

        #download-container td.double-underline::before {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            height: 1px;
            background: #000;
            transform: scaleY(0.5);
        }

        #download-container .line-total-current{
            display: inline-block;
            border-bottom: 1px solid #6c757d;
            min-width: 100px;
            position: relative;
        }

        #download-container tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        #download-container .indent {
            padding-left: 50px !important;
        }

        #download-container .footer {
            position: fixed;
            bottom: 15px;
            font-size: 10px;
            justify-content: center;
            text-align: center;
            align-items: center;
            color: #555;
            padding-top: 5px;
            display: flex;
        }
        #download-container .watermark {
            position: fixed; 
            top: 50%;        
            left: 50%;      
            transform: translate(-50%, -50%) rotate(-30deg); 
            opacity: 0.1;    
            z-index: 0;      
            pointer-events: none; 
            width: 100%;   
            height: auto;  
            font-size: 30px;
            font-weight: 400px;
            text-align: center;
        }

        #download-container .watermark .bgpic {
            width: 100%;    
            height: auto;   
        }
    </style>
</head>

<body>
    <script>
        window.onload = () => {
            if (window === window.top) {
                window.print();
            }
        };
    </script>
    <div id="download-container">
        <div class="watermark"><h1>FOR RVR SMES USE ONLY</h1></div>
        <div class="header">
            <img src="../resources/images/files_header.jpg" alt="Company Logo" class="logo">

            <div class="title-row">
                <h2>BALANCE SHEET</h2>
            </div>
        </div>

        <div class="info">
            <?php if (!empty($date_range_text)) : ?>
                <p class="as-of" id="asOfDate"><?= $date_range_text ?></p>
            <?php else: ?>
                <p class="as-of" id="asOfDate"></p>
                <script>
                    // Format current date as "Month Day, Year"
                    const now = new Date();
                    const options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    document.getElementById('asOfDate').textContent = 'This document reports the company’s Assets, Liabilities, and Equity as of: ' + now.toLocaleDateString('en-US', options);
                </script>
            <?php endif; ?>
        </div>

        <!-- ASSETS TABLE -->
        <table>
            <thead>
                <tr>
                    <th colspan="3">ASSETS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><i>Current Assets</i></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="indent">Cash</td>
                    <td class="text-end">
                        <?php echo  $total_cash < 0 ? '(&#8369; ' . number_format(abs($total_cash), 2) . ')' : '&#8369; ' . number_format($total_cash, 2); ?>
                    </td>
                    <td></td>
                </tr>
                <?php if (!empty($accounts_receivable) && $accounts_receivable != 0): ?>
                    <tr>
                        <td class="indent">Accounts Receivable</td>
                        <td class="text-end">&#8369; <?php echo number_format($accounts_receivable, 2) ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td><i>Non-Current Assets</i></td>
                     <td class="text-end">
                        <span class="line-total-current">
                             —
                        </span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>TOTAL ASSETS</strong></td>
                    <td></td>
                    <td class="text-end double-underline">
                        <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- LIABILITIES TABLE -->
        <table>
            <thead>
                <tr>
                    <th colspan="3">LIABILITIES AND OWNER'S EQUITY</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><i>Current Liabilities</i></td>
                    <td class="text-end">—</td>
                    <td></td>
                </tr>
                <tr>
                    <td><i>Non-Current Liabilities</i></td>
                    <td class="text-end">   
                        <span class="line-total-current">
                            —
                        </span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>Total Liabilities</td>
                    <td></td>
                    <td class="text-end">—</td>
                </tr>
        <!-- &#8369; <?php echo number_format(0, 2) ?> -->

        <!-- EQUITY TABLE -->
                <tr>
                    <td class="indent">Owner's Capital</td>
                    <td class="text-end">
                        <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>Total Equity</td>
                    <td></td>
                    <td class="text-end">
                        <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>TOTAL LIABILITIES AND EQUITY</strong></td>
                    <td></td>
                    <td class="text-end double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53);">
                        <?php echo  $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?>
                (<?php echo htmlspecialchars($email); ?>) |</p>
            <p><strong>| Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>
    </div>

</body>

</html>