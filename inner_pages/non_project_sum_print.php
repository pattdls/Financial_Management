<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// To fetch the project cost and assign as service revenue
$con = mysqli_connect("localhost", "root", "", "financial_management");

$start_date = $_GET['start_period'] ?? null;
$end_date = $_GET['end_period'] ?? null;

$date_range_text = '';

if ($start_date && $end_date) {
    $startFormatted = date("F j, Y", strtotime($start_date)); // e.g., June 30, 2025
    $endFormatted = date("F j, Y", strtotime($end_date));     // e.g., July 5, 2025
    $date_range_text = "Record from $startFormatted to $endFormatted";
}

// For fetching client and project name
$company_expenses = [];
$category_totals = [];
$date_condition = '';
        if ($start_date && $end_date) {
            $startEscaped = mysqli_real_escape_string($con, $start_date);
            $endEscaped = mysqli_real_escape_string($con, $end_date);
            $date_condition = "WHERE date BETWEEN '$startEscaped' AND '$endEscaped'";
        }
$company_expenses_query = mysqli_query($con, "SELECT * FROM company_expense $date_condition ORDER BY category, description ASC");
while ($row = mysqli_fetch_assoc($company_expenses_query)) {
    $category = $row['category'];
    $item = $row['description'];
    $amount = (float) str_replace(',', '', $row['amount']);
    $date = $row['date'];

    // Group by category and item
    if (!isset($company_expenses[$category])) {
        $company_expenses[$category] = [];
        $category_totals[$category] = 0;
    }

    if (!isset($company_expenses[$category][$item])) {
        $company_expenses[$category][$item] = [
            'amount' => 0,
            'date' => $date
        ];
    }

    $company_expenses[$category][$item]['amount'] += $amount;
    $category_totals[$category] += $amount;
};
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
                <h2>Non-Project Financial Summary</h2>
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
                    document.getElementById('asOfDate').textContent = 'As of: ' + now.toLocaleDateString('en-US', options);
                </script>
            <?php endif; ?>

        </div>

        <!-- INCOME TABLE -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Expense Category</th>
                    <th>Expense Item/Purpose</th>
                    <th>Amount</th>
                    <th>Totals</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $category_number = 1;

                if (empty($company_expenses)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No company expenses found.</td>
                    </tr>
                    <?php else:
                    foreach ($company_expenses as $category => $items):
                        $itemKeys = array_keys($items); // get all keys/expense item names)
                        $itemCount = count($itemKeys);
                        $first = true;
                        foreach ($items as $item => $total):
                            $isLastRow = ($item === end($itemKeys)); //To check if this is the last key/item
                    ?>
                            <tr class="<?= $isLastRow ? 'category-end' : '' ?>">
                                <td class="category-cell " data-category="<?= $first ? $category_number : '' ?>">
                                    <?= $first ? $category_number++ : '' ?>
                                </td>
                                <td class="category-cell category-name" data-category="<?= htmlspecialchars($category) ?>">
                                    <?= $first ? htmlspecialchars($category) : '' ?>
                                </td>
                                <td><?= htmlspecialchars($item) ?></td>
                                <td>&#8369; <?= number_format($total['amount'], 2) ?></td>
                                <td class="category-cell"><?= $first ? number_format($category_totals[$category], 2) : '' ?></td>
                                <td> <?= date('M d, Y', strtotime($total['date'])) ?> </td>
                            </tr>
                <?php
                            $first = false;
                        endforeach;
                    endforeach;
                endif;
                ?>
            </tbody>
        </table>
         <div class="footer">
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($username); ?> 
                (<?php echo htmlspecialchars($email); ?>)     |</p>
            <p><strong>|     Date & Time:</strong> <?php echo $generated_at; ?></p>
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
            .category-name {
                    display: table-cell !important;
                    visibility: visible !important;
                    color: #000 !important;
                    border-bottom: 1px solid #dee2e6 !important; 
            }
            #download-container td.category-cell,
            #download-container td.category-name {
                border-bottom: none !important;
                border-left: 1px solid #dee2e6 !important;
                border-right: 1px solid #dee2e6 !important;
                border-top: none !important;
                font-weight: bold !important;
            }

            #download-container tr.category-end td.category-cell,
            #download-container tr.category-end td.category-name {
                border-bottom: 1px solid #dee2e6 !important;
            }

            thead {
                display: table-header-group;
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
    </div>

    <script src="./resources/js/auto_logout.js"></script>

</body>

</html>