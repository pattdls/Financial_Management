<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Check if project_id is provided
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");
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
    
    if (isset($_GET['addon_id']) && is_numeric($_GET['addon_id'])) {
        $addon_id = mysqli_real_escape_string($connection, $_GET['addon_id']);

        // Fetch addon details
        $addon_query = "SELECT * FROM project_addons WHERE addon_id = '$addon_id' AND project_id = '$project_id'";
        $addon_result = mysqli_query($connection, $addon_query);
        $addon = mysqli_fetch_assoc($addon_result);

        if (!$addon) {
            $addon = ['addon_name' => 'No add-ons have been added to this project.'];
        }
    } else {
        $addon = ['addon_name' => 'No add-ons have been added to this project.'];
    }
}else {
    die("No project selected.");
}



// To fetch the project cost and assign as service revenue
$con = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($con, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($con, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

$start_date = $_GET['start_period'] ?? null;
$end_date = $_GET['end_period'] ?? null;

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "Record from $startFormatted to $endFormatted";
}
// Make sure addon_id is set before using
$addon_id_query = "SELECT addon_id FROM project_addons";
$addon_id_run = mysqli_query($conn, $addon_id_query);
$addon_id_row = mysqli_fetch_assoc($addon_id_run); 

if (!empty($addon_id_row)) {
    $addon_id = $addon_id_row['addon_id'];

    $addon_name_query = "SELECT addon_name FROM project_addons WHERE addon_id = $addon_id";
    $addon_name_run = mysqli_query($conn, $addon_name_query);
    $addon_name_row = mysqli_fetch_assoc($addon_name_run);
    $addon_name = $addon_name_row['addon_name'] ?? 'No add-ons have been added to this project.';
} else {
    $addon_name = ['addon_name' => 'No add-ons have been added to this project.'];
}

$conditions = "client_id = '$client_id' AND project_id = '$project_id' AND addon_id = '$addon_id'";
if ($start_date && $end_date) {
    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
}
$expenses_query = "SELECT category, SUM(amount) AS total_amount 
                   FROM expenses 
                   WHERE $conditions 
                   GROUP BY category 
                   ORDER BY category ASC";
$expense_result = mysqli_query($conn, $expenses_query);

// Individual expense rows for table
$addons_expense_query = "SELECT * FROM expenses WHERE client_id = '$client_id' AND project_id = '$project_id' AND addon_id = '$addon_id'";
if ($start_date && $end_date) {
    $addons_expense_query .= " AND date BETWEEN '$start_date' AND '$end_date'";
}
$addons_expenses = mysqli_query($conn, $addons_expense_query);

// Initialize amount and array for storing fetched data
$total_expense = 0;
$expense_data = [];

while ($expense_row = mysqli_fetch_assoc($expense_result)) {
    $category = $expense_row['category'];
    $amount = $expense_row['total_amount'];

    $expense_data[] = [
        'category' => $category,
        'amount' => $amount,
        'project_id' => $project_id,
        'addon_id' => $addon_id
    ];

    $total_expense += $amount;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expenses</title>
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
                <h2>EXPENSE DETAILS</h2>
            </div>
        </div>

        <div class="info">
            <div>
                <h4>Client: <strong><?= $client_name ?></strong></h4>
                <h4>Project: <strong><?= $project_name ?></strong></h4>
                <h4>Project Add-ons: <strong><?= htmlspecialchars($addon_name) ?></strong></h4>
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
                    <th scope="col">Total Expenses</th>
                    <th scope="col">Amount (PHP)</th>
            </thead>
            <tbody>
                <?php foreach ($expense_data as $expense) { ?>
                    <tr>
                        <td style="padding-left: 20px;"><?php echo $expense['category']; ?></td>
                        <td style="text-align: center;">&#8369; <?php echo number_format($expense['amount'], 2); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td style="padding-left: 20px;"><strong>Total</strong></td>
                    <td style="text-align: center;">&#8369; <?php echo number_format($total_expense, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th scope="col" style="width: 8%;">#</th>
                    <th scope="col" style="width: 16%;">Date</th>
                    <th scope="col" style="width: 19%;">Store/Shop</th>
                    <th scope="col" style="width: 19%;">Description</th>
                    <th scope="col" style="width: 18%;">Category</th>
                    <th scope="col" style="width: 18%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($addons_expenses) > 0): ?>
                    <?php $rowNums = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($addons_expenses)): ?>
                            <?php $id = $row['id']; ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $rowNums++; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo $row['store_name']; ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>&#8369; <?php echo number_format($row['amount'], 2); ?></td>

                        </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No add-on expenses found.</td>
                        </tr>
                    <?php endif; ?>
            </tbody>
        </table>

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
                border-radius: none !important;

            }

            #download-container tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            #download-container th {
                text-align: left;
                text-align: center;
                font-size: 13px;
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
                font-size: 11px !important;
                padding: 3px 7px;
                padding-left: 8px;
                vertical-align: top;
                page-break-inside: avoid;
            }

            #download-container table td.accnt-description {
                font-style: italic;
                font-size: 12px;
                color: rgb(73, 72, 72);
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