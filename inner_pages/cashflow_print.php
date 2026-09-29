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
$con = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

$start_date = $_SESSION['cashflow_period_start'] ?? null;
$end_date = $_SESSION['cashflow_period_end'] ?? null;

//Where conditions because the date range cannot be inserted after group by condition
$conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
if ($start_date && $end_date) {
    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
}

//To fetch sum of client's payment to RVR Squared
$cash_inflow_query = "SELECT SUM(amount) as total_cash_in FROM payment_clients WHERE $conditions";
$cash_inflow_result = mysqli_query($con, $cash_inflow_query);
$cash_inflow_row = mysqli_fetch_assoc($cash_inflow_result);
$total_cash_in = $cash_inflow_row['total_cash_in'] ?? 0;

// To fetch expenses and calculate the total by category (will be considered as outflows)
$cash_outflow_query = "SELECT category, SUM(amount) AS total_amount FROM expenses WHERE $conditions
                                            GROUP BY category";
$cash_outflow_result =  mysqli_query($con, $cash_outflow_query);

//Initialize amount and array for storing fetched data
$total_outflow = 0;
$outflow_data = [];

while ($outflow_row = mysqli_fetch_assoc($cash_outflow_result)) {
    $category = $outflow_row['category'];
    $amount = $outflow_row['total_amount'];
    $outflow_data[] = [
        'category' => $category,
        'amount' => $amount,
    ];
    $total_outflow += $amount;
}

$net_cash_flow = $total_cash_in - $total_outflow;

if ($total_cash_in == 0 && $total_outflow == 0 && $net_cash_flow == 0) {
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
            align-items: flex-end;
            margin-bottom: 25px;
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

        #download-container td.double-underline {
            position: relative;
            font-weight: bold;
            width: 10%;
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
        #download-container.pdf-mode td.double-underline::after {
            content: "";
            position: absolute;
            bottom: 3px;
            right: 0;
            left: 50%;
            height: 1px;
            background: #000;
            transform: scaleY(0.5);
        }

        #download-container.pdf-mode td.double-underline::before {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            left: 50%;
            height: 1px;
            background: #000;
            transform: scaleY(0.5);
        }
        #download-container .right-column {
            text-align: right !important;
            white-space: nowrap;
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
            <div>
                <h4>Client: <strong><?= $client_name ?></strong></h4>
                <h4>Project: <strong><?= $project_name ?></strong></h4>
            </div>
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
                    <td>Cash received from client as payment</td>
                    <td class="right-column">&#8369; <?php echo number_format($total_cash_in, 2); ?></td>
                </tr>

                <?php foreach ($outflow_data as $outflow) { ?>
                    <tr>
                        <td>Payment of <?php echo $outflow['category']; ?></td>
                        <td class="right-column">(&#8369; <?php echo number_format($outflow['amount'], 2); ?>)</td>
                    </tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th style="border-bottom: none;">Cash Generated by Operating Activities:</th>
                    <td class="right-column double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53); padding-top: 5px;">
                    &#8369; <?php echo number_format($net_cash_flow, 2); ?>
                    </td>
                </tr>

            </tbody>
        </table>
        <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?> 
                (<?php echo htmlspecialchars($email); ?>)     |</p>
            <p><strong>|     Date & Time:</strong> <?php echo $generated_at; ?></p>
        </div>
    </div>

    <script src="./resources/js/auto_logout.js"></script>

</body>

</html>