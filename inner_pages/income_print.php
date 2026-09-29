<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Check if project_id is provided
if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = mysqli_real_escape_string($conn, $_GET['project_id']);

    // Fetch project details along with client_id
    $project_query = "SELECT * FROM projects WHERE project_id = '$project_id'";
    $project_result = mysqli_query($conn, $project_query);
    $project = mysqli_fetch_assoc($project_result);

    if (!$project) {
        die("Project not found.");
    }

    $client_id = $project['client_id']; // Get the client_id from the project
} else {
    die("No project selected.");
}

//Will be used to disable print and download
$project_status_query = "SELECT project_name, project_status FROM projects WHERE project_id = '$project_id'";
$project_status_result = mysqli_query($conn, $project_status_query);
$project_data = mysqli_fetch_assoc($project_status_result);
$project_status = $project_data['project_status'] ?? 'Ongoing';
$is_completed = ($project_status === 'Completed');
$button_class = $project_status !== 'Completed' ? 'btn-outline-secondary' : 'btn-outline-success'; //This is for changing the color of print and pdf btns


// To fetch the project cost and assign as service revenue
$con = mysqli_connect("localhost", "root", "", "financial_management");

$start_date = $_SESSION['income_period_start'] ?? null;
$end_date = $_SESSION['income_period_end'] ?? null;

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "As of: $startFormatted to $endFormatted";
}

//Where conditions because the date range cannot be inserted after group by condition
$conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
if ($start_date && $end_date) {
    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
}

$project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
$project_cost_result = mysqli_query($con, $project_cost_query);
$project_cost_row = mysqli_fetch_assoc($project_cost_result);
$service_revenue = $project_cost_row['projected_budget_cost'] ?? 0;
$clean_main_project = (float) str_replace(',', '', $service_revenue);

$addons_cost_query = "SELECT projected_budget_cost FROM project_addons WHERE project_id = '$project_id'";
$addons_cost_result = mysqli_query($con, $addons_cost_query);
$addons_cost_row = mysqli_fetch_assoc($addons_cost_result);
$addons_cost = $addons_cost_row['projected_budget_cost'] ?? 0;
$clean_addons_cost = (float) str_replace(',', '', $addons_cost);

$clean_service_revenue = $clean_main_project + $clean_addons_cost;

// To fetch expenses and calculate the total by category
$expenses_query = "SELECT category, SUM(amount) AS total_amount FROM expenses WHERE $conditions
                                        GROUP BY category";
$expense_result =  mysqli_query($con, $expenses_query);

//Initialize amount and array for storing fetched data
$total_expense = 0;
$expense_data = [];

while ($expense_row = mysqli_fetch_assoc($expense_result)) {
    $category = $expense_row['category'];
    $amount = $expense_row['total_amount'];
    $expense_data[] = [
        'category' => $category,
        'amount' => $amount,
    ];
    $total_expense += $amount;
}

$net_before_tax = $clean_service_revenue - $total_expense;
$first_tax_expense = $clean_service_revenue / 1.12;
$second_tax_expense = $first_tax_expense * 0.12;
$tax_expense = $second_tax_expense;
$net_after_tax = $net_before_tax - $tax_expense;

if ($clean_service_revenue == 0 && $total_expense == 0 &&  $net_after_tax == 0) {
    $_SESSION['status_type'] = "no_data";
    $_SESSION['status'] = "No Record Found";
}

//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($con, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($con, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

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
            margin-bottom: 20px;
            margin-top: 10px;
            margin-right: 0;
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
            padding: 5px 10px;
            vertical-align: top;
            font-weight: normal;
        }
        #download-container .cf-head{
            padding-left: 0 !important;
        }
        #download-container .border-top-exp {
            position: relative;
            width: 15%;
        }
        #download-container .line-total-exp{
            display: inline-block;
            border-bottom: 1px solid #6c757d;
            text-align: right;
            min-width: 90px;
            position: relative;
        }
        /* #download-container .border-top-exp::before {
            content: "";
            position: absolute;
            top: 2px;
            right: 0;
            left: -80%;
            height: 2px;
            background: rgba(31, 30, 30, 0.53);
            transform: scaleY(0.5);
        } */

        #download-container td.double-underline {
            position: relative;
            font-weight: bold;
            width: 20%;
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

            <h4>Client: <strong><?= $client_name ?></strong></h4>
            <h4>Project: <strong><?= $project_name ?></strong></h4>

        </div>

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
                document.getElementById('asOfDate').textContent = 'As of: ' + now.toLocaleDateString('en-US', options);
            </script>
        <?php endif; ?>

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
                <td class="right-column">&#8369; <?php echo number_format($clean_service_revenue, 2); ?></td>
                <td></td>
            </tr>
            <tr>
                <td class="cf-head">Total Revenue</td>
                <td></td>
                <td class="totals">&#8369; <?php echo number_format($clean_service_revenue, 2); ?></td>
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
            <?php
            // This is for giving span class only to the last row for expenses
            $lastIndex = count($expense_data) - 1;
            foreach ($expense_data as $index => $expense) { ?>
                <tr>
                    <td><?php echo $expense['category']; ?></td>
                    <td class="right-column">
                        <?php
                        if ($index === $lastIndex) { ?>
                            <span class="line-total-exp">
                                (&#8369; <?php echo number_format($expense['amount'], 2); ?>)
                            </span>
                        <?php } else { ?>
                            (&#8369; <?php echo number_format($expense['amount'], 2); ?>)
                        <?php } ?>
                    </td>
                    <td></td>
                </tr>
            <?php } ?>
            <tr>
                <td class="cf-head">Total Expenses</td>
                <td></td>
                <td class="border-top-exp">
                    (&#8369; <?php echo number_format($total_expense, 2); ?>)</td>
            </tr>

            <!-- Net Income Section -->

            <tr>
                <th style="border-bottom: none; padding: 0;">Operating Income</th>
                <td></td>
                <td style="border-top: 1px solid rgba(31, 30, 30, 0.53);">&#8369; <?php echo number_format($net_before_tax, 2); ?></td>
            </tr>


            <tr>
                <td class="cf-head">Value Added Tax</td>
                <td></td>
                <td>
                    (&#8369; <?php echo number_format($tax_expense, 2); ?>)
                </td>

            </tr>

            <thead>
                <tr>
                    <th style="border-bottom: none;">Net Income</th>
                    <td></td>
                    <td class="double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53);">&#8369; <?php echo number_format($net_after_tax, 2); ?></td>
                </tr>
            </thead>
        </table>
        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?>
                (<?php echo htmlspecialchars($email); ?>) |</p>
            <p><strong>| Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>
    </div>

    <script src="./resources/js/auto_logout.js"></script>
    
</body>

</html>