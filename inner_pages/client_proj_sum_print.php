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
$dateFilter = "";
if ($start_date && $end_date) {
    $dateFilter = " AND end_date BETWEEN '$start_date' AND '$end_date' ";
}
// For fetching client and project name
$clients_projects = [];
$totalProjectsFound = 0;
$clients_query = mysqli_query($con, "SELECT client_id, client_name FROM clients ORDER BY client_name ASC");
while ($client = mysqli_fetch_assoc($clients_query)) {
    $client_id = $client['client_id'];
    $client_name = $client['client_name'];

    $projects = [];

    $projects_query = mysqli_query($con, "SELECT * FROM projects WHERE client_id = $client_id $dateFilter ORDER BY project_name ASC");

    while ($project = mysqli_fetch_assoc($projects_query)) {
        $totalProjectsFound++;
        $project_id = $project['project_id'];
        $project_name = $project['project_name'];
        $project_status = $project['project_status'];
        $end_date = $project['end_date'];

        // Start with main project income
        $raw_project_income = $project['projected_budget_cost'] ?? 0;
        $clean_project_income = (float) str_replace(',', '', $raw_project_income);

        // Fetch addons for this project
        $addons_query = mysqli_query($con, "SELECT * FROM project_addons WHERE project_id = '$project_id' ORDER BY addon_name ASC");
        $addons = [];
        while ($addon = mysqli_fetch_assoc($addons_query)) {
            $addon_income = (float) str_replace(',', '', $addon['projected_budget_cost'] ?? 0);

            // Add addon income to project total income
            $clean_project_income += $addon_income;

            $addons[] = [
                'name' => $addon['addon_name'],
                'income' => $addon_income,
                // optionally calculate cost/profit for addons
            ];
        }

        // Calculate project cost and profit as usual
        $project_cost_query = mysqli_query($con, "SELECT SUM(amount) AS total_project_cost FROM expenses WHERE project_id = '$project_id'");
        $project_cost_row = mysqli_fetch_assoc($project_cost_query);
        $clean_project_cost = (float) str_replace(',', '', $project_cost_row['total_project_cost'] ?? 0);

        $project_profit_taxless = $clean_project_income - $clean_project_cost;
        $first_revenue = $clean_project_income / 1.12;
        $second_revenue = $first_revenue * 0.12;
        $tax_expense = $second_revenue;
        $clean_project_profit = $project_profit_taxless - $tax_expense;

        $projects[] = [
            'name' => $project_name,
            'income' => $clean_project_income,  // now includes addon income
            'cost' => $clean_project_cost,
            'profit' => $clean_project_profit,
            'status' => $project_status,
            'end_date' => $end_date,
            'addons' => $addons
        ];
    }


  if (!empty($projects)) {
        $clients_projects[] = [
            'client_name' => $client_name,
            'projects' => $projects
        ];
    }
}

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
                <h2>Client Project Financial Summary</h2>
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
                    <th>Client</th>
                    <th>Project</th>
                    <th>Project Income</th>
                    <th>Project Cost</th>
                    <th>Project Profit</th>
                    <th>Status</th>
                    <th>Date Finished</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $client_number = 1;

                if ($totalProjectsFound === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No clients or projects found.</td>
                    </tr>
                    <?php else:
                    foreach ($clients_projects as $client):
                        $projectCount = count($client['projects']);
                        if ($projectCount === 0): ?>
                            <tr>
                                <td><?php echo $client_number++ ?></td>
                                <td class="client-cell"><?php echo htmlspecialchars($client['client_name']) ?></td>
                                <td colspan="5" class="text-center text-muted">No projects found</td>
                            </tr>
                            <?php
                        else:
                            $first = true;
                            foreach ($client['projects'] as $index => $project):
                                $isLastRow = ($index === $projectCount - 1);
                            ?>

                                <tr class="<?php echo $isLastRow ? 'client-end' : '' ?>">
                                    <td class="client-cell" data-client="<?= $first ? $client_number : '' ?>">
                                        <?php echo $first ? $client_number++ : '' ?>
                                    </td>
                                    <td class="client-cell client-name" data-client="<?= htmlspecialchars($client['client_name']) ?>">
                                        <?php echo $first ? htmlspecialchars($client['client_name']) : '' ?>
                                    </td>
                                    <td>
                                        <?php
                                        $project_display_name = htmlspecialchars($project['name']);
                                        if (!empty($project['addons'])) {
                                            $addon_names = array_map(fn($a) => $a['name'], $project['addons']);
                                            $project_display_name .= ' (With Addons: ' . implode(', ', $addon_names) . ')';
                                        }
                                        echo $project_display_name;
                                        ?>
                                    </td>
                                    <td>&#8369; <?php echo number_format($project['income'], 2) ?></td>
                                    <td>&#8369; <?php echo number_format($project['cost'], 2) ?></td>
                                    <td>
                                        <?php if (strtolower($project['status']) === 'completed'): ?>
                                            &#8369; <?php echo number_format($project['profit'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['status']) ?></td>
                                    <td>
                                        <?php if (strtolower($project['status']) === 'completed'): ?>
                                            <?php echo date('M d, Y', strtotime($project['end_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                <?php
                                $first = false;
                            endforeach;
                        endif;
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

            #download-container table td.accnt-description {
                font-style: italic;
                font-size: 12px;
                color: rgb(73, 72, 72);
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

</body>

</html>