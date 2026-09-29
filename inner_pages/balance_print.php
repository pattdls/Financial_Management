<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Check if project_id is provided
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


//Queries for calculating TOTAL ASSETS
$con = mysqli_connect("localhost", "root", "", "financial_management");

$start_date = $_SESSION['balance_period_start'] ?? null;
$end_date = $_SESSION['balance_period_end'] ?? null;

//Where conditions because the date range cannot be inserted after group by condition
$conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
if ($start_date && $end_date) {
    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
}

// Fetch total cash inflow (debits) - payment of client
$query_cash = "SELECT SUM(amount) as total_cash FROM payment_clients WHERE $conditions";
$cash_result = mysqli_query($con, $query_cash);
$cash_row = mysqli_fetch_assoc($cash_result);
$total_cash_in = $cash_row['total_cash'] ?? 0;

//For fetching the project cost as Service Revenue in Equity
$project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
$cost_result = mysqli_query($con, $project_cost_query);
$project_cost = mysqli_fetch_assoc($cost_result)['projected_budget_cost'];
$clean_poject_cost = (float) str_replace(',', '', $project_cost);

//For computing the Accounts Receivable
$payment_query = "SELECT SUM(amount) AS total_client_payments FROM payment_clients WHERE $conditions AND category LIKE '%Payment%'";
$payment_result = mysqli_query($con, $payment_query);
$total_client_payments = mysqli_fetch_assoc($payment_result)['total_client_payments'] ?? 0;

$accounts_receivable = floatval($clean_poject_cost) - floatval($total_client_payments);

// Fetch total cash outflow (expenses) as liabilities

$query_cashOut = "SELECT category, SUM(amount) as total_amount FROM expenses 
                    WHERE $conditions GROUP BY category";
$result_cash_out = mysqli_query($con, $query_cashOut);

$total_cash_out = 0; // Initialize total outflow
while ($row_cash_out = mysqli_fetch_assoc($result_cash_out)) {
    $category = $row_cash_out['category'];
    $amount = $row_cash_out['total_amount'];
    $total_cash_out += $amount; // Add to total outflow
}

if ($total_cash_in == 0 && $clean_poject_cost == 0 && $total_client_payments == 0 && $total_cash_out == 0) {
    $_SESSION['status_type'] = "no_data";
    $_SESSION['status'] = "No Record Found";
}
//COmputation/Assignment for displaying Balance Sheet
$total_cash = floatval($total_cash_in) - floatval($total_cash_out);
$accounts_receivable = floatval($clean_poject_cost) - floatval($total_client_payments);
$total_assets = floatval($total_cash) + floatval($accounts_receivable);
$total_liabilities = floatval($total_cash_out);
$equity = floatval($clean_poject_cost);
$total_liabilities_equity = floatval($clean_poject_cost) - floatval($total_cash_out);

//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($con, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($con, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

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
    color:#1e1082;
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
    text-align: right;
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
#download-container tr {
    page-break-inside: avoid; 
    page-break-after: auto;
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
    <div class="header">
        <img src="../resources/images/files_header.png" alt="Company Logo" class="logo">

        <div class="title-row">
            <h2>BALANCE SHEET</h2>
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
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('asOfDate').textContent = 'As of: ' + now.toLocaleDateString('en-US', options);
        </script>
    </div>

    <!-- ASSETS TABLE -->
    <table>
        <thead>
            <tr>
                <th colspan="2">ASSETS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Cash</td>
                <td class="text-end">
                    <?php echo $total_cash < 0 ? '(&#8369; ' . number_format(abs($total_cash), 2) . ')' : '&#8369; '. number_format($total_cash, 2); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="note">Expenses paid in cash are being deducted</td>
            </tr>
            <tr>
                <td>Accounts Receivable</td>
                <td class="text-end">                                           
                    <?php echo $accounts_receivable < 0 ? '(&#8369; ' . number_format(abs($accounts_receivable), 2) . ')' : '&#8369; '. number_format($accounts_receivable, 2); ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><strong>TOTAL ASSETS</strong></td>
                <td class="text-end double-underline"  style="border-top: 1px solid rgba(31, 30, 30, 0.53); width: 20%;">
                    <?php echo $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; '. number_format($total_assets, 2); ?></td>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- LIABILITIES TABLE -->
    <table>
        <thead>
            <tr>
                <th colspan="2">LIABILITIES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query_cashOut = "SELECT category, SUM(amount) as total_amount FROM expenses 
                            WHERE $conditions GROUP BY category";
            $result_cash_out = mysqli_query($con, $query_cashOut);
            $total_cash_out = 0;

            while ($row_cash_out = mysqli_fetch_assoc($result_cash_out)) {
                $category = $row_cash_out['category'];
                $amount = $row_cash_out['total_amount'];
                $total_cash_out += $amount;
            ?>
                <tr>
                    <td><?= $category; ?></td>
                    <td class="text-end">&#8369 <?= number_format($amount, 2); ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td>Total Liabilities</td>
                <td class="text-end" style="border-top: 1px solid rgba(31, 30, 30, 0.53); width: 20%;">&#8369 <?= number_format($total_cash_out, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- EQUITY TABLE -->
    <table>
        <thead>
            <tr>
                <th colspan="2">EQUITY</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Service Revenue</td>
                <td class="text-end">&#8369 <?= number_format($clean_poject_cost, 2) ?></td>
            </tr>
            <tr>
                <td>Total Equity</td>
                <td class="text-end"  style="border-top: 1px solid rgba(31, 30, 30, 0.53); width: 20%;">&#8369 <?= number_format($clean_poject_cost, 2) ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><strong>TOTAL LIABILITIES AND EQUITY</strong></td>
                <td class="text-end double-underline" style="border-top: 1px solid rgba(31, 30, 30, 0.53); width: 20%;">&#8369 <?= number_format($total_liabilities_equity, 2) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<script src="./resources/js/auto_logout.js"></script>

</body>



</html>