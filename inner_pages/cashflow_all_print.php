<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Database Connection
$connection = mysqli_connect("localhost", "root", "", "financial_management");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

$con = mysqli_connect("localhost", "root", "", "financial_management");

$start_date = $_SESSION['cashflow_period_start'] ?? null;
$end_date = $_SESSION['cashflow_period_end'] ?? null;
$targetYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); //To set the year

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "This document summarizes cash generated and used by the company during the period ending: $startFormatted to $endFormatted";
}

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

        #download-container .border-top-exp {
            position: relative;
            width: 25%;
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
        #download-container .indent {
            padding-left: 50px !important;
        }
        #download-container .footer{
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
                <h2>CASHFLOW REPORT</h2>
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
                    document.getElementById('asOfDate').textContent = 'This document summarizes cash generated and used by the company during the period ending: ' + now.toLocaleDateString('en-US', options);
                </script>
            <?php endif; ?>

        </div>

        <!-- INCOME TABLE -->
        <table>
            <!-- Revenue Section -->
            <thead>
                <tr>
                    <th colspan="2">Cash Flow from Operating Activities:</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Cash receipts from</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="indent">Clients</td>
                    <td class="right-column">&#8369; <?php echo number_format($total_from_clients, 2) ?></td>
                </tr>
                <tr>
                    <td>Cash paid for</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="indent">Project Expenses</td>
                    <td class="right-column">(&#8369; <?php echo number_format($total_project_expenses, 2) ?>)</td>
                </tr>

                <?php foreach ($comp_expense_categories as $kategorya => $halaga): ?>
                    <tr>
                        <td class="indent"><?php echo htmlspecialchars(($kategorya)) ?></td>
                        <td class="right-column">(&#8369; <?php echo number_format($halaga, 2) ?>)</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td></td>
                    <td></td>
                </tr>
                <!--<tr>-->
                <!--    <th style="border-bottom: none; padding: 0;">Net Cash Flow from Operating Activities</th>-->
                <!--    <td style="border-top: 1px solid rgba(31, 30, 30, 0.53); text-align: right;">-->
                <!--        <?php echo  $total_operating_activities < 0 ? '(&#8369; ' . number_format(abs($total_operating_activities), 2) . ')' : '&#8369; ' . number_format($total_operating_activities, 2); ?>-->
                <!--    </td>-->
                <!--</tr>-->
                <thead>
                    <tr>
                        <th style="border-bottom: none;">Net Cash Flow from Operating Activities</th>
                        <td class="double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53); text-align: right;">
                            <?php echo  $total_operating_activities < 0 ? '(&#8369; ' . number_format(abs($total_operating_activities), 2) . ')' : '&#8369; ' . number_format($total_operating_activities, 2); ?>
                        </td>
                    </tr>
                </thead>
            </tbody>
        </table>
        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?> 
                (<?php echo htmlspecialchars($email); ?>)     |</p>
            <p><strong>|     Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>
    </div>

</body>

</html>
        