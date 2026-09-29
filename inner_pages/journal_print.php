<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Check if project_id is provided
$connection = mysqli_connect("localhost", "root", "", "financial_management");
if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = mysqli_real_escape_string($connection, $_GET['project_id']);

    // Fetch project details along with client_id
    $project_query = "SELECT * FROM projects WHERE project_id = '$project_id'";
    $project_result = mysqli_query($connection, $project_query);
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
$project_status_result = mysqli_query($connection, $project_status_query);
$project_data = mysqli_fetch_assoc($project_status_result);
$project_status = $project_data['project_status'] ?? 'Ongoing';
$is_completed = ($project_status === 'Completed');
$button_class = $project_status !== 'Completed' ? 'btn-outline-secondary' : 'btn-outline-success'; //This is for changing the color of print and pdf btns


// To fetch the project cost and assign as service revenue
$con = mysqli_connect("localhost", "root", "", "financial_management");


$start_date = $_SESSION['journal_period_start'] ?? null;
$end_date = $_SESSION['journal_period_end'] ?? null;

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "Record from $startFormatted to $endFormatted";
}

//Where conditions because the date range cannot be inserted after group by condition
$conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
if ($start_date && $end_date) {
    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
}
//First query is to select accounts for DEBIT
$project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
$cost_result = mysqli_query($con, $project_cost_query);
$project_cost_row = mysqli_fetch_assoc($cost_result);
$project_cost = $project_cost_row ? (float)$project_cost_row['projected_budget_cost'] : 0;
$clean_poject_cost = (float) str_replace(',', '', $project_cost);

// Query to fetch the very first payment
$payment_query = "SELECT amount FROM payment_clients WHERE $conditions ORDER BY date ASC LIMIT 1";
$payment_result = mysqli_query($con, $payment_query);
$first_payment_row = mysqli_fetch_assoc($payment_result);
$first_payment_amount = $first_payment_row ? (float)$first_payment_row['amount'] : 0;
$clean_first_payment = (float) str_replace(',', '', $first_payment_amount);

// Calculate accounts receivable based on the first payment
$accounts_receivable = $clean_poject_cost - $clean_first_payment;

$journal_query = "SELECT date, description, category AS debit_account, amount AS debit_amount, 
                  payment_method AS credit_account, amount AS credit_amount
                FROM expenses WHERE $conditions ORDER BY date ASC";

$journal_result = mysqli_query($con, $journal_query);

$payment_clients_query = "SELECT date, category, description, amount 
                        FROM payment_clients WHERE $conditions ORDER BY date ASC";

$payment_result = mysqli_query($con, $payment_clients_query);
$rowNumber = 1;
$firstPayment = true;

$capital_query = "SELECT date, description, amount FROM budget_allocation
                                                        WHERE $conditions ORDER BY date ASC";
$capital_result = mysqli_query($con, $capital_query);


//This is used to fetch and store results first in an array
$payments = [];
while ($row = mysqli_fetch_assoc($payment_result)) {
    $row['entry_type'] = 'payment'; //used to define that the row is from payment
    $payments[] = $row;
}

$journals = [];
while ($row = mysqli_fetch_assoc($journal_result)) {
    $row['entry_type'] = 'journal'; //used to define that the row is from expenses
    $journals[] = $row;
}
$capitals = [];
while ($row = mysqli_fetch_assoc($capital_result)) {
    $row['entry_type'] = 'capital';
    $row['debit_account'] = 'Cash';
    $row['credit_account'] = 'Capital';
    $row['debit_amount'] = (float) $row['amount'];
    $row['credit_amount'] = (float) $row['amount'];
    $capitals[] = $row;
}
//used to combine results so that it will be arranged in ASC order based on date
$combined = array_merge($payments, $journals, $capitals);
usort(
    $combined,
    function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    }
);
//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($con, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($con, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

$isFirstTable = true;

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
                <h2>JOURNAL ENTRY</h2>
            </div>
        </div>

        <div class="info">
            <div>
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


        </div>

        <!-- INCOME TABLE -->
        <table>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date</th>
                    <th scope="col">Account Title</th>
                    <th scope="col">Debit</th>
                    <th scope="col">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($combined as $row) {
                    if ($row['entry_type'] === 'payment') {
                        // First payment logic
                        if ($firstPayment) {
                ?>
                            <tr>
                                <td><?php echo $rowNumber++; ?></td>
                                <td class="date"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td>Client Initial Payment</td>
                                <td class="amount">₱ <?php echo number_format($row['amount'], 2); ?></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>Accounts Receivable</td>
                                <td class="amount">₱ <?php echo number_format($accounts_receivable, 2); ?></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td class="credit-account">Service Revenue</td>
                                <td></td>
                                <td class="amount">₱ <?php echo number_format($clean_poject_cost, 2); ?></td>
                            </tr>
                        <?php
                            $firstPayment = false;
                            //If subsequent payments, only Client Initial Payment as Debit and AR as Credit
                        } else {
                        ?>
                            <tr>
                                <td><?php echo $rowNumber++; ?></td>
                                <td class="date"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td>Client Initial Payment</td>
                                <td class="amount">₱ <?php echo number_format($row['amount'], 2); ?></td>
                                <td></td>
                            </tr>
                            <tr class="credit-entry">
                                <td></td>
                                <td></td>
                                <td class="credit-account">Accounts Receivable</td>
                                <td></td>
                                <td class="amount">₱ <?php echo number_format($row['amount'], 2); ?></td>
                            </tr>
                        <?php
                        }

                        // Description
                        ?>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="account-description">(To record the received <?php echo isset($row['description']) ? $row['description'] : 'No Description'; ?> of &#8369; <?php echo number_format($row['amount'], 2); ?>.)</td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php

                    } elseif ($row['entry_type'] === 'journal') {
                    ?>
                        <tr>
                            <td><?php echo $rowNumber++; ?></td>
                            <td class="date"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo $row['debit_account']; ?></td>
                            <td class="amount">₱ <?php echo number_format($row['debit_amount'], 2); ?></td>
                            <td></td>
                        </tr>
                        <tr class="credit-entry">
                            <td></td>
                            <td></td>
                            <td class="credit-account"><?php echo $row['credit_account']; ?></td>
                            <td></td>
                            <td class="amount">₱ <?php echo number_format(floatval(str_replace(',', '', $row['credit_amount'])), 2); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="account-description">
                                <?php
                                if ($row['debit_account'] === 'Salary Expense') {
                                    echo "(To record payment of {$row['description']} - {$row['debit_account']} paid via {$row['credit_account']}.)";
                                } else {
                                    echo "(To record purchase of {$row['description']} - {$row['debit_account']} paid via {$row['credit_account']}.)";
                                }
                                ?>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php
                    } elseif ($row['entry_type'] === 'capital') {
                    ?>
                        <tr>
                            <td><?php echo $rowNumber++; ?></td>
                            <td class="date"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo $row['debit_account']; ?></td>
                            <td class="amount">&#8369; <?php echo number_format($row['debit_amount'], 2); ?></td>
                            <td></td>
                        </tr>
                        <tr class="credit-entry">
                            <td></td>
                            <td></td>
                            <td class="credit-account"><?php echo $row['credit_account']; ?></td>
                            <td></td>
                            <td class="amount">&#8369; <?php echo number_format(floatval(str_replace(',', '', $row['credit_amount'])), 2); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="account-description">(To record <?php echo $row['description']; ?> of &#8369;<?php echo number_format($row['debit_amount'], 2); ?>.)</td>
                            <td></td>
                            <td></td>
                        </tr>
                <?php
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?>
                (<?php echo htmlspecialchars($email); ?>) |</p>
            <p><strong>| Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>

        <style>
            body #download-container {
                font-family: "Times New Roman", serif;
                color: #000;
                font-size: 14px;
                margin: 30px;
                margin-top: 0;
            }

            #download-container .header {
                text-align: center;
                margin-bottom: 0;
                position: relative;
            }

            #download-container .header img {
                width: 400px;
                margin-bottom: 5px;
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
                margin-bottom: 0;
                margin-top: 10px;
            }

            #download-container .info h4 {
                margin: 0;
                font-size: 16px;
                font-weight: normal;
            }

            #download-container .as-of {
                font-size: 14px;
                text-align: right;
                margin-top: 5px;
                font-style: italic;
            }

            #download-container table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #dee2e6;
                margin-bottom: 0;
                page-break-inside: auto;

            }

            #download-container tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            #download-container th {
                text-align: left;
                text-align: center;
                font-size: 15px;
                font-weight: bold;
                border: 1px solid #dee2e6;
                padding-top: 4px;
                padding-bottom: 4px;
                color: #000;
                page-break-inside: auto;
            }

            #download-container td {
                border: 1px solid #dee2e6;
                font-weight: normal;
                font-size: 12px !important;
                padding: 3px 7px !important;
                vertical-align: top;
                page-break-inside: avoid;
            }
            #download-container td.credit-account {
                padding-left: 30px !important;
            }

            #download-container table td.account-description {
                font-style: italic;
                font-size: 12px;
                color: rgb(73, 72, 72) !important;
            }
            #download-container table td.amount{
                white-space: nowrap;
            }

            thead {
                display: table-header-group;
            }

            thead::before {
                content: "";
                display: table-row;
                height: 30px;
                border: 1px solid white;
            }

            #download-container .footer {
                position: fixed;
                bottom: 2px !important;
                font-size: 10px;
                justify-content: center;
                text-align: center;
                align-items: center;
                color: #555;
                display: flex;
            }
            @media print {
            table td {
                padding: 4px 8px !important;
            }
            td.credit-account{
                padding-left: 25px !important;
            }
            table td.account-description {
                font-style: italic !important;
                font-size: 12px;
                color: rgb(73, 72, 72) !important;
            }

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
    </div>

    <script src="./resources/js/auto_logout.js"></script>

</body>

</html>