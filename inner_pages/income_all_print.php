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

$start_date = $_SESSION['income_period_start'] ?? null;
$end_date = $_SESSION['income_period_end'] ?? null;
$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "This document summarizes the company’s Revenues and Expenses, resulting in the Net Income (or Loss) for the period ending: $startFormatted to $endFormatted";
}

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
    <title>Income Statement</title>
    <style>
        body #download-container {
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
            margin-bottom: 25px;
        }

        #download-container th {
            text-align: left;
            font-size: 15px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            color: #000;
        }

        #download-container td {
            padding: 7px 12px;
            vertical-align: top;
            font-weight: normal;
        }

        #download-container .cf-head{
            padding-left: 0 !important;
        }
        #download-container .line-total-exp{
            display: inline-block;
            border-bottom: 1px solid #6c757d;
            text-align: right;
            min-width: 90px;
            position: relative;
        }

        #download-container .border-top-exp {
            position: relative;
            width: 15%;
        }

        #download-container td.double-underline {
            position: relative;
            font-weight: bold;
            width: 25%;
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
            left: 0%;
            height: 1px;
            background: #000;
            transform: scaleY(0.5);
        }

        #download-container .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }

        #download-container .right-column {
            text-align: right !important;
            white-space: nowrap;
        }

        #download-container .totals {
            text-align: left !important;
            white-space: nowrap;
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
                <h2>INCOME STATEMENT</h2>
            </div>
        </div>

        <div class="info">
            <?php if (!empty($date_range_text)) : ?>
                <p class="as-of" id="asOfDate"> <?= $date_range_text ?></p>
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
                    document.getElementById('asOfDate').textContent = 'This document summarizes the company’s Revenues and Expenses, resulting in the Net Income (or Loss) for the period ending: ' + now.toLocaleDateString('en-US', options);
                </script>
            <?php endif; ?>

        </div>

        <!-- INCOME TABLE -->
        <table>
            <!-- Revenue Section -->
            <thead>
                <tr>
                    <th colspan="3">Revenue</th>
                </tr>
            </thead>

            <tr>
                <td>Service Revenue</td>
                <td class="right-column">&#8369; <?php echo number_format($total_revenue, 2); ?></td>
                <td></td>
            </tr>
            <tr>
                <td class="cf-head">Total Revenue</td>
                <td></td>
                <td class="totals">&#8369; <?php echo number_format($total_revenue, 2); ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <!-- Expenses Section -->
            <thead>
                <tr>
                    <th colspan="3">Expenses</th>
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
                <td class="border-top-exp">
                    (&#8369; <?php echo number_format($total_overall_expenses, 2); ?>)</td>
            </tr>

            <!-- Net Income Section -->
            <tr>
                <th style="border-bottom: none; padding: 0;">Net Income Before Tax</th>
                <td></td>
                <td style="border-top: 1px solid rgba(31, 30, 30, 0.53);">
                    <?php echo  $gross_profit < 0 ? '(&#8369; ' . number_format(abs($gross_profit), 2) . ')' : '&#8369; ' . number_format($gross_profit, 2); ?>
                </td>
            </tr>
            <tr>
                <td class="cf-head">Income Tax Expense</td>
                <td></td>
                <td>
                    (&#8369; <?php echo number_format($tax_expense, 2); ?>)
                </td>
            </tr>
            <thead>
                <tr>
                    <th style="border-bottom: none;">Net Income After Tax</th>
                    <td></td>
                    <td class="double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53);">
                        <?php echo  $income_after_tax < 0 ? '(&#8369; ' . number_format(abs($income_after_tax), 2) . ')' : '&#8369; ' . number_format($income_after_tax, 2); ?>
                    </td>
                </tr>
            </thead>
        </table>
        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?>
                (<?php echo htmlspecialchars($email); ?>) |</p>
            <p><strong>| Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>
    </div>

</body>

</html>