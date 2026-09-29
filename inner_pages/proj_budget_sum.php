<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Database Connection
$connection = mysqli_connect("localhost", "root", "", "financial_management");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

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
//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($connection, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($connection, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

// Fetch add-ons for this project
$addons_sql = "SELECT * FROM project_addons WHERE project_id = '$project_id'";
$addons_result = mysqli_query($connection, $addons_sql);

// Fetch saved limit amounts first
$limit_query = "SELECT * FROM project_limits WHERE client_id = '$client_id' AND project_id = '$project_id' ";
$limit_query_result = mysqli_query($connection, $limit_query);
$limit_amount_row = mysqli_fetch_assoc($limit_query_result) ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Project Details"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/project_details.css">
    <link rel="stylesheet" href="../resources/css/expense.css">
    <link rel="stylesheet" href="../resources/css/project_budget_sum.css">
</head>

<body>
    <div class="d-flex">

        <!-- Side Nav Container (Layout) -->
        <?php include('../layout/sidenav.php'); ?>


        <!-- Main Content -->
        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'Project Details';
            include '../layout/topnav.php';
            ?>


            <div class="nav-container-fluid p-0">

                   <!-- Breadcrumbs -->
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                        <li class="breadcrumb-item"><a href="projects.php?client_id=<?php echo $client_id; ?>">Projects</a></li> <!-- Pass client_id -->
                        <li class="breadcrumb-item active"><?php echo $project_name; ?></li>
                    </ol>
                </div>
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="proj_stat.php?project_id=<?php echo $project_id; ?>">Project Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Project Budget Summary</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cashflow.php?project_id=<?php echo $project_id; ?>">Cash Flow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_details.php?project_id=<?php echo $project_id; ?>">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link " href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="financial.php?project_id=<?php echo $project_id; ?>">Financial Report</a>
                    </li> -->
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Content Section -->
                <div class="content mt-4">
                    <div id="balanceSheetPDF">
                        <div class="card-body p-0" id="balanceSheetPrint">
                            <form action="../forms_logic/save_limit.php" method="POST">
                                    <input type="hidden" name="client_id" value="<?= $client_id ?>">
                                    <input type="hidden" name="project_id" value ="<?= $project_id ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="income-header mb-3">
                                    <h4 style="font-weight: bold;">Project Budget Summary</h4>
                                    
                            
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                                class="bi bi-info-circle" viewBox="0 0 16 16"
                                data-bs-toggle="popover"
                                data-bs-trigger="hover"
                                title="Budget Summary Description"
                                data-bs-html="true"
                                data-bs-content="This page provides a quick overview of your project's budget, showing how much was allocated, how much has been spent, and how much remains for each category.
                            You can also set a spending limit for each category, and when the remaining balance falls below or reaches the set limit, a warning indicator will appear to alert you.
                            <strong>Note that actual spendings for project addons are not included in this computation.</strong>">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                            </svg>
                        
                                <!--<button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#BudgetSum">-->
                                <!--        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">-->
                                <!--            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />-->
                                <!--            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />-->
                                <!--        </svg>  -->
                                <!--    </button>-->
                                </div>
                                <div class="ms-auto">
                                    <?php if ($limit_amount_row): ?>
                                            <!-- Show Edit / Update -->
                                            <button type="button" id="editBtn" class="btn btn-dark btn-sm me-2 limit-btn" onclick="enableEdit()">Edit Limits</button>
                                            <button type="submit" name="update_limit" id="updateBtn"class="btn btn-dark btn-sm d-none limit-btn">Update Limits</button>
                                        <?php else: ?>
                                            <!-- First time set -->
                                            <button type="submit" name="set_limit" class="btn btn-dark btn-sm limit-btn">Set Limits</button>
                                        <?php endif; ?>
                                </div>
                            </div>
                            <table class="table table-bordered" id="summary_table" style="overflow: hidden;">
                                <thead>
                                    <tr>
                                        <th scope="col">Category</th>
                                        <th scope="col">Allocated Budget Cost</th>
                                        <th scope="col">Actual Expenditures</th>
                                        <th scope="col">Remaining Balance</th>
                                        <th scope="col" style="width: 170px;">Set Limit</th>
                                    </tr>
                                </thead>
                                <!-- Query for Project Dashboard/Summary -->
                                <?php
                                $connection = mysqli_connect("localhost", "root", "", "financial_management");
                                $display_query = "SELECT allocated_budget, materials_cost, labor_cost, other_expenses_cost FROM projects 
                                            WHERE client_id = '$client_id' AND project_id = '$project_id'";
                                $display_result = mysqli_query($conn, $display_query);
                                if (mysqli_num_rows($display_result) > 0) {
                                    $row = mysqli_fetch_assoc($display_result);
                                    $materials_cost = $row['materials_cost'];
                                    $labor_cost = $row['labor_cost'];
                                    $other_expenses_cost = $row['other_expenses_cost'];

                                    $allocated_budget_total = $materials_cost + $labor_cost + $other_expenses_cost;
                                }

                                //To fetch sum for material cost
                                $material_cost_query = "SELECT SUM(amount) AS total_materials FROM expenses
                                                    WHERE client_id = '$client_id' 
                                                    AND project_id = '$project_id' 
                                                    AND category LIKE '%Construction%' OR category LIKE '%Materials%'
                                                    AND (addon_id IS NULL OR addon_id = 0)";
                                $material_cost_result = mysqli_query($conn, $material_cost_query);
                                $material_cost_row = mysqli_fetch_assoc($material_cost_result);

                                //To fetch sum for labor cost
                                $labor_cost_query = "SELECT SUM(amount) AS total_labor FROM expenses
                                                WHERE client_id = '$client_id' 
                                                AND project_id = '$project_id' 
                                                AND (category LIKE '%Salary%' OR category LIKE '%Labor%')
                                                AND (addon_id IS NULL OR addon_id = 0)";
                                $labor_cost_result = mysqli_query($conn, $labor_cost_query);
                                $labor_cost_row = mysqli_fetch_assoc($labor_cost_result);

                                //To fetch sum for other expenses, this will exclude construction and labor category
                                $other_expenses_query = "SELECT SUM(amount) AS total_others FROM expenses
                                                    WHERE client_id = '$client_id' 
                                                    AND project_id = '$project_id' 
                                                    AND NOT (category LIKE '%Construction%' 
                                                    OR category LIKE '%Salary%' OR category LIKE '%Labor%')
                                                    AND (addon_id IS NULL OR addon_id = 0)";
                                $other_expenses_result = mysqli_query($conn, $other_expenses_query);
                                $other_expenses_row = mysqli_fetch_assoc($other_expenses_result);

                                //To compute remaining balances
                                $remaining_materials = floatval($materials_cost) - floatval($material_cost_row['total_materials']);
                                $remaining_labor = floatval($labor_cost) - floatval($labor_cost_row['total_labor']);
                                $remaining_others = floatval($other_expenses_cost) - floatval($other_expenses_row['total_others']);

                                //TOTALS
                                $total_actual_expenditures = floatval($material_cost_row['total_materials']) + floatval($labor_cost_row['total_labor']) + floatval($other_expenses_row['total_others']);
                                $total_remains = $remaining_materials + $remaining_labor + $remaining_others;

                                //For displaying saved limit amounts
                                $limit_query = "SELECT * FROM project_limits WHERE client_id = '$client_id' AND project_id = '$project_id' ";
                                $limit_query_result = mysqli_query($conn, $limit_query);
                                $limit_amount_row = mysqli_fetch_assoc($limit_query_result) ?? [];

                                $total_set_limit = 
                                    floatval($limit_amount_row['material_limit'] ?? 0) +
                                    floatval($limit_amount_row['labor_limit'] ?? 0) +
                                    floatval($limit_amount_row['others_limit'] ?? 0);

                                ?>
                                    <!-- For Material Cost Row -->
                                    <tr>
                                        <td>Material Cost</td>
                                        <td>&#8369; <?php echo number_format($row['materials_cost'], 2); ?></td>
                                        <!-- Actual Material Expenditures -->
                                        <td style="color: <?php echo $material_cost_row['total_materials'] > $row['materials_cost'] ? '#DC143C' : 'inherit' ?>;">
                                            &#8369; <?php echo number_format($material_cost_row['total_materials'] ?? 0, 2); ?>
                                        </td>
                                        <td style="color: 
                                        <?php
                                        if ($remaining_materials < 0) {
                                            echo '#FF0000'; // Red if negative
                                        } elseif (isset($limit_amount_row['material_limit']) && $remaining_materials <= $limit_amount_row['material_limit']) {
                                            echo '#FFA500'; // Orange if it hits limit
                                        } else {
                                            echo 'inherit'; // Default
                                        }
                                        ?>">
                                            <?php
                                            $formatted_amount = number_format(abs($remaining_materials), 2);
                                            if ($remaining_materials < 0) {
                                                echo "(&#8369;{$formatted_amount})";
                                            } else {
                                                echo "&#8369;{$formatted_amount}";
                                            }
                                            ?>

                                            <?php if (isset($limit_amount_row['material_limit']) && $remaining_materials <= $limit_amount_row['material_limit']): ?>
                                                <div style="font-size: 12px; color: #FFA500;">
                                                    ⚠ Remaining balance has reached the limit.
                                                </div>
                                            <?php endif; ?>

                                        </td>
                                        <!-- This is to save the warning/message for notification purposes -->
                                        <?php
                                        if (isset($limit_amount_row['material_limit']) && $remaining_materials <= $limit_amount_row['material_limit']) {
                                            $category = 'Material Cost';
                                            $current_limit_value = $limit_amount_row['material_limit'];
                                            $message = "Remaining balance for <strong>$category</strong> of project <strong>$project_name</strong> for client <strong>$client_name</strong> has reached its limit of ₱" . number_format($current_limit_value, 2) . ".";

                                            $con =  mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

                                            // Check if notification already exists
                                            $check = mysqli_query($con, "SELECT * FROM notifications WHERE client_name='$client_name' AND project_name='$project_name' AND category='$category' AND limit_value='$current_limit_value'");

                                            if (mysqli_num_rows($check) == 0) {
                                                // Insert notification
                                                mysqli_query($con, "INSERT INTO notifications (project_id, client_name, project_name, category, message, limit_value) 
                                                                    VALUES ('$project_id', '$client_name', '$project_name', '$category', '$message', '$current_limit_value')");
                                            }
                                        }
                                        ?>
                                        <td>
                                            <!-- This condition will check if an amount exists then it will be displayed. -->
                                            <!-- If edit button is clicked, an editable field with the existing value will be displayed. -->
                                            <!-- if no data exists, an input field and save button is displayed -->
                                            <?php if (isset($limit_amount_row['material_limit'])): ?>
                                                <span id="displayMaterialLimit" style="color: 	#808080;"><i>&#8369; <?php echo number_format($limit_amount_row['material_limit'], 2); ?></i></span>
                                                <input type="text" name="material_limit" id="materialLimitInput" value="<?php echo number_format($limit_amount_row['material_limit'], 2); ?>"
                                                    class="form-control form-control-sm d-none" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php else: ?>
                                                <input type="text" name="material_limit" class="form-control form-control-sm" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- For Labor Cost Row -->
                                    <tr>
                                        <td>Labor Cost</td>
                                        <td>&#8369; <?php echo number_format($row['labor_cost'], 2); ?></td>
                                        <!-- Actual Labor Cost -->
                                        <td style="color: <?php echo $labor_cost_row['total_labor'] > $row['labor_cost'] ? '#DC143C' : 'inherit' ?>;">
                                            &#8369; <?php echo number_format($labor_cost_row['total_labor'] ?? 0, 2); ?>
                                        </td>
                                        <td style="color: 
                                        <?php
                                        if ($remaining_labor < 0) {
                                            echo '#FF0000'; // Red if negative
                                        } elseif (isset($limit_amount_row['labor_limit']) && $remaining_labor <= $limit_amount_row['labor_limit']) {
                                            echo '#FFA500'; // Orange if it hits limit
                                        } else {
                                            echo 'inherit'; // Default
                                        }
                                        ?>">
                                            <?php
                                            $formatted_amount = number_format(abs($remaining_labor), 2);
                                            if ($remaining_labor < 0) {
                                                echo "(&#8369;{$formatted_amount})";
                                            } else {
                                                echo "&#8369;{$formatted_amount}";
                                            }
                                            ?>

                                            <?php if (isset($limit_amount_row['labor_limit']) && $remaining_labor <= $limit_amount_row['labor_limit']): ?>
                                                <div style="font-size: 12px; color: #FFA500;">
                                                    ⚠ Remaining balance has reached the limit.
                                                </div>
                                            <?php endif; ?>

                                            <!-- This is to save the warning/message for notification purposes -->
                                            <?php
                                            if (isset($limit_amount_row['labor_limit']) && $remaining_labor <= $limit_amount_row['labor_limit']) {
                                                $category = 'Labor Cost';
                                                $current_limit_value = $limit_amount_row['labor_limit'];
                                                $message = "Remaining balance for <strong>$category</strong> of project <strong>$project_name</strong> for client <strong>$client_name</strong> has reached its limit of ₱" . number_format($current_limit_value, 2) . ".";

                                                $con =  mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

                                                // Check if notification already exists
                                                $check = mysqli_query($con, "SELECT * FROM notifications WHERE client_name='$client_name' AND project_name='$project_name' AND category='$category' AND limit_value='$current_limit_value'");

                                                if (mysqli_num_rows($check) == 0) {
                                                    // Insert notification
                                                    mysqli_query($con, "INSERT INTO notifications (project_id, client_name, project_name, category, message, limit_value) 
                                                                    VALUES ('$project_id', '$client_name', '$project_name', '$category', '$message', '$current_limit_value')");
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($limit_amount_row['labor_limit'])): ?>
                                                <span id="displayLaborLimit" style="color: #808080;"><i>&#8369; <?php echo number_format($limit_amount_row['labor_limit'], 2); ?></i></span>
                                                <input type="text" name="labor_limit" id="laborLimitInput" value="<?php echo number_format($limit_amount_row['labor_limit'], 2); ?>"
                                                    class="form-control form-control-sm d-none" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php else: ?>
                                                <input type="text" name="labor_limit" class="form-control form-control-sm" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                    <!-- For Other Expenses Cost -->
                                    <tr>
                                        <td>Other Expenses Cost</td>
                                        <td>&#8369; <?php echo number_format($row['other_expenses_cost'], 2); ?></td>
                                        <!-- Actual Other Expenditures -->
                                        <td style="color: <?php echo $other_expenses_row['total_others'] > $row['other_expenses_cost'] ? '#DC143C' : 'inherit' ?>;">
                                            &#8369; <?php echo number_format($other_expenses_row['total_others'] ?? 0, 2); ?>
                                        </td>
                                        <td style="color: 
                                        <?php
                                        if ($remaining_others < 0) {
                                            echo '#FF0000'; // Red if negative
                                        } elseif (isset($limit_amount_row['others_limit']) && $remaining_others <= $limit_amount_row['others_limit']) {
                                            echo '#FFA500'; // Orange if it hits limit
                                        } else {
                                            echo 'inherit'; // Default
                                        }
                                        ?>">
                                            <?php
                                            $formatted_amount = number_format(abs($remaining_others), 2);
                                            if ($remaining_others < 0) {
                                                echo "(&#8369;{$formatted_amount})";
                                            } else {
                                                echo "&#8369;{$formatted_amount}";
                                            }
                                            ?>
                                            <?php if (isset($limit_amount_row['others_limit']) && $remaining_others <= $limit_amount_row['others_limit']): ?>
                                                <div style="font-size: 12px; color: #FFA500;">
                                                    ⚠ Remaining balance has reached the limit.
                                                </div>
                                            <?php endif; ?>
                                            <!-- This is to save the warning/message for notification purposes -->
                                            <?php
                                            if (isset($limit_amount_row['others_limit']) && $remaining_others <= $limit_amount_row['others_limit']) {
                                                $category = 'Other Expenses Cost';
                                                $current_limit_value = $limit_amount_row['others_limit'];
                                                $message = "Remaining balance for <strong>$category</strong> of project <strong>$project_name</strong> for client <strong>$client_name</strong> has reached its limit of ₱" . number_format($current_limit_value, 2) . ".";

                                                $con =  mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

                                                // Check if notification already exists
                                                $check = mysqli_query($con, "SELECT * FROM notifications WHERE client_name='$client_name' AND project_name='$project_name' AND category='$category' AND limit_value='$current_limit_value'");

                                                if (mysqli_num_rows($check) == 0) {
                                                    // Insert notification
                                                    mysqli_query($con, "INSERT INTO notifications (project_id, client_name, project_name, category, message, limit_value) 
                                                                    VALUES ('$project_id', '$client_name', '$project_name', '$category', '$message', '$current_limit_value')");
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($limit_amount_row['others_limit'])): ?>
                                                <span id="displayOthersLimit" style="color: #808080;"><i>&#8369; <?php echo number_format($limit_amount_row['others_limit'], 2); ?></i></span>
                                                <input type="text" name="others_limit" id="othersLimitInput" value="<?php echo number_format($limit_amount_row['others_limit'], 2); ?>"
                                                    class="form-control form-control-sm d-none" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php else: ?>
                                                <input type="text" name="others_limit" class="form-control form-control-sm" style="padding: 2px 6px; width: 100%; height: 30px; font-size: 13px;">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- For total amounts -->
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td><strong>&#8369; <?php echo number_format($allocated_budget_total, 2); ?></strong></td>
                                        <!-- Total Actual Expenditures -->
                                        <td style="color: <?php echo isset($total_actual_expenditures) > $row['allocated_budget'] ? '#DC143C' : 'inherit' ?>;">
                                            <strong>&#8369; <?php echo number_format($total_actual_expenditures, 2); ?></strong>
                                        </td>
                                        <td><strong>&#8369; <?php echo number_format($total_remains, 2); ?></strong></td>
                                        <td><strong>&#8369; <?php echo number_format($total_set_limit, 2); ?></strong></td>
                                    </tr>
                            </table>
                            </form>
                        </div>
                    </div>
                    <br>
                    <!-- Project Add ons -->
                    <h4 class="mb-3 mt-2 fw-bold">Project Add-ons</h4>

                    <?php if (mysqli_num_rows($addons_result) > 0) { ?>
                        <table class="table table-bordered" style="overflow: hidden; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="text-transform: none;">Add-on Title</th>
                                    <th style="text-transform: none;">PO Number</th>
                                    <!-- <th style="text-transform: none;">Start Date</th> -->
                                    <th style="text-transform: none;">End Date</th>
                                    <th style="text-transform: none;">Budget Cost</th>
                                    <th style="text-transform: none;">Type</th>
                                    <th style="text-transform: none;">Contract File</th>
                                    <!-- <th style="text-transform: none;">Action</th> New column -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($addon = mysqli_fetch_assoc($addons_result)) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($addon['addon_name']); ?></td>
                                        <td><?php echo htmlspecialchars($addon['po_number']); ?></td>
                                        <!-- <td><?php echo date('M d, Y', strtotime($addon['start_date'])); ?></td> -->
                                        <td><?php echo !empty($addon['end_date']) 
                                                  ? date('M d, Y', strtotime($addon['end_date'])) 
                                                  : 'N/A'; 
                                                  ?>
                                         </td>
                                        <td>₱<?php echo number_format($addon['projected_budget_cost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($addon['project_type']); ?></td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <?php
                                                $addon_id = $addon['addon_id'];
                                                $contract_query = "SELECT contract_file FROM project_addons WHERE addon_id = '$addon_id' LIMIT 1";
                                                $contract_result = mysqli_query($connection, $contract_query);

                                                if ($contract = mysqli_fetch_assoc($contract_result)) {
                                                    echo '<a href="' . htmlspecialchars($contract['contract_file']) . '" target="_blank" 
                                                    class="btn btn-outline-primary custom-outline-btn text-decoration-none d-flex p-0 align-items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" 
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                                        style="margin-right: 4px;" class="lucide lucide-file-text">
                                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                                        <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                                        <path d="M10 9H8"/>
                                                        <path d="M16 13H8"/>
                                                        <path d="M16 17H8"/>
                                                    </svg>
                                                   Contract
                                                </a>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">No Contract</span>';
                                                }
                                                ?>

                                                <a href="#" class="btn btn-outline-primary custom-outline-btn text-decoration-none d-flex align-items-center"
                                                    data-bs-toggle="modal" data-bs-target="#viewAddonsExpense<?= $addon['addon_id']; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        style="margin-right: 4px;" class="lucide lucide-file-text">
                                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                                        <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                                        <path d="M10 9H8" />
                                                        <path d="M16 13H8" />
                                                        <path d="M16 17H8" />
                                                    </svg>
                                                    Expenses
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Status Dropdown -->
                                        <!-- <td>
                                            <form method="POST" action="../forms_logic/update_addon_status.php" style="margin:0;">
                                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                                                <input type="hidden" name="addon_id" value="<?php echo $addon['addon_id']; ?>">
                                                <select name="status" class="form-select " onchange="this.form.submit()">
                                                    <option value="Upcoming" <?php if ($addon['status'] == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
                                                    <option value="Ongoing" <?php if ($addon['status'] == 'Ongoing') echo 'selected'; ?>>Ongoing</option>
                                                    <option value="Completed" <?php if ($addon['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                                    <option value="Overdue" <?php if ($addon['status'] == 'Overdue') echo 'selected'; ?>>Overdue</option>
                                                </select>
                                            </form>
                                        </td> -->
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php
                        // reset pointer and include modals OUTSIDE the table
                        mysqli_data_seek($addons_result, 0);
                        while ($addon = mysqli_fetch_assoc($addons_result)) {
                            //  


                            $conn =  mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");
                            $addon_id = $addon['addon_id'];

                            // Fetch addon name
                            $addon_name_query = "SELECT addon_name FROM project_addons WHERE addon_id = $addon_id";
                            $addon_name_run = mysqli_query($conn, $addon_name_query);
                            $addon_name = mysqli_fetch_assoc($addon_name_run);

                            // Fetch detailed expenses
                            $addons_expenses = mysqli_query($conn, "SELECT * FROM expenses WHERE addon_id = $addon_id");

                            // Fetch grouped expenses
                            $expense_result = mysqli_query(
                                $conn,
                                "SELECT category, SUM(amount) AS total_amount FROM expenses 
                                            WHERE client_id = '$client_id' AND project_id = '$project_id' AND addon_id = '$addon_id' GROUP BY category"
                            );

                            $total_expense = 0;
                            $expense_data = [];
                            while ($expense_row = mysqli_fetch_assoc($expense_result)) {
                                $category = $expense_row['category'];
                                $amount = $expense_row['total_amount'];
                                $expense_data[] = ['category' => $category, 'amount' => $amount, 'project_id' => $project_id, 'addon_id' => $addon_id];
                                $total_expense += $amount;
                            }
                        ?>
                            <div class="modal fade" id="viewAddonsExpense<?= $addon_id; ?>" tabindex="-1" aria-labelledby="AddonsExpense" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="AddonsExpenseList">Expense List for Project Addons</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 30px;">
                                            <div id="reviewContent">
                                                <div class="d-flex align-items-center mb-4 addonsHeader">
                                                    <p><strong>Project Addon:</strong> <?php echo htmlspecialchars($addon_name['addon_name']); ?></p>
                                                    <button type="button" id="pdf_addons_exp" class="btn btn-outline-orange ms-auto">
                                                        <i class="bi bi-filetype-pdf"></i> Download PDF
                                                    </button>
                                                    <button type="button" id="print_addons_exp" class="btn btn-outline-orange ms-2">
                                                        <i class="bi bi-printer"></i> Print
                                                    </button>
                                                </div>
                                                <div id="dateRangeWrapper mb-4" style="display: contents">
                                                    <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter Date by:</label>
                                                        <select id="dateFilterExpense" class="form-select form-select-sm w-auto" style="height: 30px;">
                                                            <option value="all">All</option>
                                                            <option value="weekly">This Week</option>
                                                            <option value="monthly">This Month</option>
                                                            <option value="custom">Custom Range</option>
                                                        </select>
                                                        <input type="text" id="customDateRangeExpense" class="forDate form-control form-control-sm w-auto"
                                                            placeholder="Select date range" style="display: none; height: 30px;" />
                                                    </div>
                                                </div>

                                                <div class="exbox">
                                                    <table class="table total_expenses table-bordered mb-5 mt-4" style="width: 95%;" id="summaryTable">
                                                        <thead>
                                                            <th>Total Expenses</th>
                                                            <th style="width: 55%;">Amount (PHP)</th>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($expense_data as $expense) { ?>
                                                                <tr>
                                                                    <td><?php echo $expense['category']; ?></td>
                                                                    <td style="text-align: center;">&#8369; <?php echo number_format($expense['amount'], 2); ?></td>
                                                                </tr>
                                                            <?php } ?>
                                                            <tr>
                                                                <td class="fw-bold" style="background-color: #afdcff4b">Total</td>
                                                                <td class="fw-bold text-center" style="background-color: #afdcff4b">&#8369; <?php echo number_format($total_expense, 2); ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <table class="table table-bordered border-secondary px-0 table-sm" id="addons_expenses">
                                                    <thead class="table-dark text-center">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Date</th>
                                                            <th>Description</th>
                                                            <th>Category</th>
                                                            <th>Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (mysqli_num_rows($addons_expenses) > 0): ?>
                                                            <?php $rowNums = 1; ?>
                                                            <?php while ($row = mysqli_fetch_assoc($addons_expenses)): ?>
                                                                <?php $id = $row['id']; ?>
                                                                <tr>
                                                                    <td><?= $rowNums++ ?></td>
                                                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center justify-content-between position-relative">
                                                                            &#8369; <?= number_format($row['amount'], 2); ?>
                                                                            <button class="three_dots btn btn-light btn-sm ms-1" type="button" data-bs-toggle="dropdown">
                                                                                <i class="bi bi-three-dots"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu">
                                                                                <li>
                                                                                    <a class="dropdown-item open-view-details" href="#" data-bs-target="#viewExpenseModal<?= $id; ?>"  data-bs-current="#viewAddonsExpense<?= $addon_id; ?>">View Details</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger" href="#"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#archiveExpenseModal<?= $id; ?>" data-bs-parent="#viewAddonsExpense<?= $addon['addon_id']; ?>">Archive</a>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <!-- This is to make sure that data tables will still work despite having empty table data -->
                                                            <tr>
                                                                <td style="text-align:center;"><span class="text-muted">#</span></td>
                                                                <td style="text-align:center;"><span class="text-muted">YYY-MM-DD</span></td>
                                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                                <?php mysqli_close($conn); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php mysqli_data_seek($addons_expenses, 0); // rewind the result pointer 
                            ?>
                            <?php while ($row = mysqli_fetch_assoc($addons_expenses)): ?>
                                <?php
                                $id = $row['id'];
                                $receipt_file = $row['receipt_file'];
                                $conn =  mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");
                                $addon_name_query = "SELECT addon_name FROM project_addons WHERE addon_id = $addon_id";
                                $addon_name_run = mysqli_query($conn, $addon_name_query);
                                $addon_name = mysqli_fetch_assoc($addon_name_run);
                                ?>


                                <!-- View Details Modal -->
                                     <div class="modal fade" id="viewExpenseModal<?= $id; ?>" tabindex="-1" aria-hidden="true" data-bs-parent="viewAddonsExpense<?= $addon_id; ?>" data-bs-backdrop="false" data-bs-focus="false" style=" background-color: rgba(0, 0, 0, 0.35) !important">
                                        <div class="modal-dialog modal-md">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Expense Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                <!-- Right Column -->
                                                <p><strong style="font-size: 18px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></p>
                                                <p><strong style="font-size: 18px;">
                                                        <?php
                                                        if (!empty($addon_name['addon_name'])) {
                                                            // If record is from an Add-on project
                                                            echo $client_name . " - " . htmlspecialchars($addon_name['addon_name']) . " <br><small>(Addons for: " . $project_name . ")</small> ";
                                                        } else {
                                                            // Normal project
                                                            echo $project_name . " - " . $client_name;
                                                        }
                                                        ?></strong><br>
                                                    <span style="font-size: 13px; color: #555;"><i>Client - Project</i></span>
                                                </p>
                                                <p><?php echo $row['description'] . ' - ' . ucwords($row['category']) . ' - ' . $row['store_name']; ?><br>
                                                    <span style="font-size: 13px; color: #555;"><i>Item, Category, & Store Name</i></span>
                                                </p>
                                                <p>&#8369; <?php echo number_format($row['amount'], 2) . ' - ' . $row['payment_method']; ?><br>
                                                    <span style="font-size: 13px; color: #555;"><i>Amount & Payment Method</i></span>
                                                </p>
                                                <!-- Inline Invoice Number and Button -->
                                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                    <p><?php echo $row['invoice_num']; ?><br>
                                                        <span style="font-size: 13px; color: #555;"><i>Invoice Number</i></span>
                                                    </p>
                                                    <!-- This condition will check if an attachment exists and displays the button. -->
                                                    <?php if (!empty($receipt_file) && file_exists('../receipts/project_expenses_files/' . $receipt_file)): ?>
                                                        <a href="../receipts/project_expenses_files/<?php echo htmlspecialchars($receipt_file); ?>" target="_blank" style="text-decoration: none;">
                                                            <button type="button" class="btn btn-outline-success btn-sm">
                                                                View Receipt Attachment
                                                            </button>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="font-size: 14px; color: #888;">No Receipt Available</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                $user_id = $row['user_id'] ?? null;
                                                $user_name = '';
                                                $user_role = '';

                                                if ($user_id) {
                                                    $user_query = mysqli_query($con, "SELECT name, role FROM users WHERE id = $user_id");
                                                    if ($user_query && $user_data = mysqli_fetch_assoc($user_query)) {
                                                        $user_name = $user_data['name'];
                                                        $user_role = $user_data['role'];
                                                    }
                                                }
                                                ?>
                                                 <button class="btn btn-sm btn-outline-secondary mt-2" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#logs<?php echo $id; ?>"
                                                        aria-expanded="false" aria-controls="logs<?php echo $id; ?>">
                                                        <i class="bi bi-chevron-bar-down"></i>
                                                    </button>
                                                    <!-- Collapsible Logs Section -->
                                                    <div class="collapse" id="logs<?php echo $id; ?>">
                                                        <div class="highlight-box" style="font-size: 12px; color: #555;">
                                                            <p class="mt-2">Recorded by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>

                                                            <?php if (!empty($archiver_name)): ?>
                                                                <p>
                                                                    <?php echo ucwords(htmlspecialchars($archiver_name)) . ' - ' . ucwords(htmlspecialchars($archiver_role)) . ' - ' . date('M d, Y', strtotime($archived_date)); ?><br>
                                                                    <span style="font-size: 11px; color: #555;"><i>Archived by, User Role, & Archived Date</i></span>
                                                                </p>
                                                            <?php endif; ?>

                                                            <?php if (!empty($restorer_name)): ?>
                                                                <p>
                                                                    <?php echo ucwords(htmlspecialchars($restorer_name)) . ' - ' . ucwords(htmlspecialchars($restorer_role)) . ' - ' . date('M d, Y', strtotime($restored_date)); ?><br>
                                                                    <span style="font-size: 11px; color: #555;"><i>Restored by, User Role, & Restored Date</i></span>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                </div>
                <!-- Archive Modal -->
                <div class="modal fade" id="archiveExpenseModal<?= $id; ?>" data-parent-modal="#viewAddonsExpense<?= $addon_id; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Archive</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="../forms_logic/archive.php">
                                    <input type="hidden" name="id" value="<?= $id; ?>">
                                    <input type="hidden" name="project_id" value="<?= $project_id; ?>">
                                    <p>Are you sure you want to archive this expense?</p>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="prjct_archive_btn" class="btn btn-danger">Archive</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endwhile; ?>

            <script type="text/javascript">
                let selectedStartDate = null;
                let selectedEndDate = null;

                $(document).ready(function() {
                    var table = $('#addons_expenses').DataTable({
                        responsive: true,
                        dom: 'Blfrtip',
                        ordering: true,
                        order: [],
                        buttons: [],
                        language: {
                            search: '',
                            searchPlaceholder: "Search expense record...",
                            paginate: {
                                previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                            }
                        }
                    });

                    // Date range picker for custom filtering
                    flatpickr("#customDateRangeExpense", {
                        mode: "range",
                        dateFormat: "Y-m-d",
                        onClose: function(selectedDates) {
                            filterByDate('custom', selectedDates);
                        }
                    });

                    // Date filter select
                    $('#dateFilterExpense').on('change', function() {
                        const value = $(this).val();
                        if (value === 'custom') {
                            $('#customDateRangeExpense').show();
                        } else {
                            $('#customDateRangeExpense').hide();
                            selectedStartDate = null;
                            selectedEndDate = null;
                            filterByDate(value);
                        }
                    });

                    // Core filtering function
                    function filterByDate(range, customDates = []) {
                        const today = new Date();
                        let startDate, endDate;

                        if (range === 'weekly') {
                            const day = today.getDay();
                            const monday = new Date(today);
                            monday.setDate(today.getDate() - (day === 0 ? 6 : day - 1));
                            const sunday = new Date(monday);
                            sunday.setDate(monday.getDate() + 6);
                            startDate = monday;
                            endDate = sunday;

                        } else if (range === 'monthly') {
                            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                        } else if (range === 'custom' && customDates.length === 2) {
                            startDate = customDates[0];
                            endDate = customDates[1];

                        } else {
                            $.fn.dataTable.ext.search.pop();
                            table.draw();
                            updateSummaryTable();
                            return;
                        }

                        selectedStartDate = startDate.getFullYear() + '-' +
                            String(startDate.getMonth() + 1).padStart(2, '0') + '-' +
                            String(startDate.getDate()).padStart(2, '0');

                        selectedEndDate = endDate.getFullYear() + '-' +
                            String(endDate.getMonth() + 1).padStart(2, '0') + '-' +
                            String(endDate.getDate()).padStart(2, '0');

                        // Apply filtering to DataTable
                        $.fn.dataTable.ext.search.push(function(settings, data) {
                            const dateStr = data[1]; // Date column
                            const rowDate = new Date(dateStr);
                            rowDate.setHours(0, 0, 0, 0);

                            const start = new Date(startDate);
                            const end = new Date(endDate);
                            start.setHours(0, 0, 0, 0);
                            end.setHours(0, 0, 0, 0);

                            return rowDate >= start && rowDate <= end;
                        });

                        table.draw();
                        $.fn.dataTable.ext.search.pop();
                        updateSummaryTable();
                    
                    }

                    // Update summary table dynamically based on filtered rows
                    function updateSummaryTable() {
                        const summary = {};
                        const $summaryBody = $('#summaryTable tbody');

                        // Loop over filtered rows
                        table.rows({
                            filter: 'applied'
                        }).every(function() {
                            const row = $(this.node());
                            const category = row.find('td').eq(3).text().trim();
                            const amountText = row.find('td').eq(4).text().replace(/[₱,]/g, '').trim();
                            const amount = parseFloat(amountText) || 0;

                            if (category) {
                                summary[category] = (summary[category] || 0) + amount;
                            }
                        });

                        $summaryBody.empty();

                        if (Object.keys(summary).length === 0) {
                            $summaryBody.append(`
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No matching records found</td>
                                </tr>`);
                            return;
                        }

                        let grandTotal = 0;
                        for (const [category, total] of Object.entries(summary)) {
                            grandTotal += total;
                            $summaryBody.append(`
                                    <tr>
                                        <td>${category}</td>
                                        <td style="text-align: center;">&#8369; ${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                    </tr>
                                `);
                        }

                        $summaryBody.append(`
                                <tr>
                                    <td class="fw-bold" style="background-color: #afdcff4b">Total</td>
                                    <td class="fw-bold text-center" style="background-color: #afdcff4b">
                                        &#8369; ${grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                    </td>
                                </tr>
                            `);
                    }

                    // Move toolbar above summary table
                    setTimeout(function() {
                        var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between mb-2"></div>');
                        toolbar.append($('#addons_expenses_wrapper .dataTables_length'));
                        toolbar.append($('#dateRangeContainer'));
                        toolbar.append($('#addons_expenses_wrapper .dataTables_filter'));
                        $('#summaryTable').before(toolbar);
                    }, 0);

                    function handlePrint() {
                        const iframe = document.getElementById('printFrame');
                        const clientId = '<?= $_GET['client_id'] ?? '' ?>';
                        const projectId = '<?= $_GET['project_id'] ?? '' ?>';
                        const addonId = '<?= $_GET['addon_id'] ?? '' ?>';

                        let url = `project_addons_print.php?client_id=${clientId}&project_id=${projectId}&addon_id=${addonId}`;
                        if (selectedStartDate && selectedEndDate) {
                            url += `&start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                        }

                        iframe.onload = function() {
                            setTimeout(() => {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                            }, 300);
                        };

                        iframe.src = url;
                    }

                    // Print button event
                    document.getElementById('print_addons_exp').addEventListener('click', function(e) {
                        e.preventDefault();
                            handlePrint();
                    });

                    function handlePdfDownload() {
                        const iframe = document.getElementById('printFrame');

                        iframe.onload = function() {
                            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                            const container = iframeDoc.querySelector('#download-container');

                            if (!container) {
                                alert("Download container not found!");
                                return;
                            }

                            setTimeout(() => {
                                html2pdf()
                                    .from(container)
                                    .set({
                                        filename: 'ProjectAddonsExpenses.pdf',
                                        margin: [15, 10, 10, 10],
                                        html2canvas: {
                                            scale: 2,
                                            scrollY: 0
                                        },
                                        jsPDF: {
                                            unit: 'mm',
                                            format: 'a4',
                                            orientation: 'portrait'
                                        },
                                        pagebreak: {
                                            mode: ['css'],
                                            avoid: ['tr']
                                        }
                                    })
                                    .toPdf()
                                    .get('pdf')
                                    .then(function(pdf) {
                                        const totalPages = pdf.internal.getNumberOfPages();
                                        for (let i = 2; i <= totalPages; i++) {
                                            pdf.setPage(i);
                                            pdf.setFontSize(10);
                                            pdf.setFont('times', 'italic');
                                            pdf.text('Continued...', 10, 10);
                                        }
                                    })
                                    .save()
                                    .catch(err => {
                                        console.error('PDF generation failed:', err);
                                        alert('Failed to generate PDF.');
                                    });
                            }, 500);
                        };

                        const clientId = '<?= $_GET['client_id'] ?? '' ?>';
                        const projectId = '<?= $_GET['project_id'] ?? '' ?>';
                        const addonId = '<?= $_GET['addon_id'] ?? '' ?>';

                        let url = `project_addons_print.php?client_id=${clientId}&project_id=${projectId}&addon_id=${addonId}`;
                        if (selectedStartDate && selectedEndDate) {
                            url += `&start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                        }

                        iframe.src = url;
                    }

                    document.getElementById('pdf_addons_exp').addEventListener('click', function(e) {
                        e.preventDefault();
                            handlePdfDownload();
                    });
                });
            </script>
        <?php
                        }
        ?>
    <?php } else { ?>
        <p class="text-muted">No add-ons have been added to this project.</p>
    <?php } ?>

         <!-- MODAL FOR PROJECT BUDGET SUM INFO -->
            <div class="modal fade" id="BudgetSum" tabindex="-1" aria-labelledby="BudgetSummaryLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="padding: 12px">
                            <h5 class="modal-title fw-bold" id="BudgetSummaryLabel">About Project Budget Summary</h5>
                            <button type="button" class="btn-close" style="font-size: 14px" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                            This page provides a quick overview of your project's budget, showing how much was allocated, how much has been spent, and how much remains for each category.
                            You can also set a spending limit for each category, and when the remaining balance falls below or reaches the set limit, a warning indicator will appear to alert you.
                            <strong>Note that actual spendings for project addons are not included in this computation.</strong>
                        </div>
                        <div class="modal-footer mt-0" style="padding-bottom: 20px; border-top: none;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

    <?php
    if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
    ?>

        <!-- Modal Notification -->
        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-white border-0 shadow-sm">
                    <div class="modal-header border-0 ">
                        <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                    </div>
                    <div class="modal-body text-center" style="padding-top: 10px;">
                        <!-- DIFFERENT MODALS BASED ON STATUS TYPE FOR CORRESPONDING BUTTONS ON THE TABLE -->
                        <?php if ($_SESSION['status_type'] == "updated"): ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 70px; margin-bottom: 50px;"></i>
                            <h4 class="mb-0" style="margin-top: -5px;">Limits Successfully Updated</h4>
                            <p class="pt-3" style="text-align: center; font-size: 16px;">
                                Your budget limits have been updated.<br> These updated values will now reflect in your budget monitoring.
                            </p>
                        <?php elseif ($_SESSION['status_type'] == "error"): ?>
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 70px; margin-bottom: 50px;"></i>
                            <h4 class="mb-0" style="margin-top: -5px;">Update Failed</h4>
                            <p class="pt-3" style="text-align: center; font-size: 16px;">
                                Failed to update limit amounts. Please try again.
                            </p>
                        <?php else: ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 70px; margin-bottom: 50px;"></i>
                            <h4 class="mb-0" style="margin-top: -5px;">Limits Successfully Saved</h4>
                            <p class="pt-3" style="text-align: center; font-size: 16px;">
                                Your budget limits have been successfully saved.<br> These constraints will now guide your project budget tracking.
                            </p>
                        <?php endif; ?>
                        <div class="modal-footer border-0 d-flex justify-content-center">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">Proceed</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto-trigger the modal when a session status exists -->
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var successModal = new bootstrap.Modal(document.getElementById('staticBackdrop'));
                successModal.show();
            });
        </script>
    <?php
        unset($_SESSION['status']);
    }

    ?>

    <!-- This is for print and download purpose  -->
    <iframe id="printFrame" style="display:none;"></iframe>

    <script src="/resources/js/auto_logout.js"></script>

    <script>
          document.addEventListener("DOMContentLoaded", function() {
                        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                        popoverTriggerList.map(function(popoverTriggerEl) {
                            return new bootstrap.Popover(popoverTriggerEl);
                        });
                    });

                    document.addEventListener("DOMContentLoaded", function() {
                        const projectLinks = document.querySelectorAll(".project-select");
                        const selectedBtn = document.getElementById("selectedProjectBtn");
                    });
           document.addEventListener('DOMContentLoaded', function () {

    // Helper to close all dropdowns
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    }

    // 3-dots dropdown handling
    document.querySelectorAll('.three_dots').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeAllDropdowns();
            btn.nextElementSibling.classList.toggle('show');
        });
    });
    document.addEventListener('click', closeAllDropdowns);

    // Handle "View Details" nested modal
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('open-view-details')) {
            e.preventDefault();

            const targetSelector = e.target.getAttribute('data-bs-target');
            const parentSelector = e.target.getAttribute('data-bs-current');
            const targetModal = document.querySelector(targetSelector);
            const parentModal = document.querySelector(parentSelector);

            if (!targetModal || !parentModal) return;

            // Disable Bootstrap’s ESC for the parent temporarily
            const parentInstance = bootstrap.Modal.getInstance(parentModal);
            if (parentInstance) {
                parentModal.setAttribute('data-keyboard-disabled', 'true');
                parentInstance._config.keyboard = false;
            }

            // Open child modal with backdrop
            const childModal = new bootstrap.Modal(targetModal, {
                backdrop: true,
                keyboard: true
            });

            // Stack z-index
            targetModal.addEventListener('show.bs.modal', () => {
                parentModal.style.zIndex = 1050;
                targetModal.style.zIndex = 1060;
            });

            // When child closes
            targetModal.addEventListener('hidden.bs.modal', () => {
                // Restore parent's ESC functionality
                if (parentInstance) {
                    parentInstance._config.keyboard = true;
                    parentModal.removeAttribute('data-keyboard-disabled');
                }

                // Keep backdrop and focus
                if (parentModal.classList.contains('modal')) {
                    const existingBackdrop = document.querySelector('.modal-backdrop');
                    if (!existingBackdrop) {
                        const backdrop = document.createElement('div');
                        backdrop.classList.add('modal-backdrop', 'fade', 'show');
                        document.body.appendChild(backdrop);
                    }

                    parentModal.classList.add('show');
                    parentModal.style.display = 'block';
                    document.body.classList.add('modal-open');
                    parentModal.focus();
                }
            });

            childModal.show();
        }
    });

    // Handle Archive modals reopening their parent
    document.addEventListener('hidden.bs.modal', function (event) {
        const modal = event.target;

        if (modal.id.startsWith('archiveExpenseModal')) {
            const parentSelector = modal.getAttribute('data-parent-modal');
            const targetModal = parentSelector ? document.querySelector(parentSelector) : null;

            if (targetModal) {
                setTimeout(() => {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    const modalInstance = new bootstrap.Modal(targetModal, {
                        backdrop: true,
                        keyboard: true
                    });
                    modalInstance.show();
                }, 400);
            } else {
                setTimeout(() => {
                    if (!document.querySelector('.modal.show')) {
                        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                        document.body.style.overflow = '';
                    }
                }, 400);
            }
        }
    });

    // Custom ESC key behavior — close only topmost modal
   document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const openModals = Array.from(document.querySelectorAll('.modal.show'));

        // If multiple modals are open (nested case)
        if (openModals.length > 1) {
            e.preventDefault();
            e.stopImmediatePropagation(); // stops Bootstrap’s internal handler entirely

            const topModal = openModals[openModals.length - 1];
            const modalInstance = bootstrap.Modal.getInstance(topModal);

            if (modalInstance) {
                modalInstance.hide();
            }
        }
    }
}, true)
});


        // Date and Time Function
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
        }

        // Update every second
        setInterval(updateDateTime, 1000);

        // Initial call to display time immediately
        updateDateTime();

        // const printBtn = document.getElementById('print_summary');

        // printBtn.addEventListener('click', function() {
        //     print();

        // });

        // //This is for downloading PDF

        // jQuery(document).ready(function() {

        //     $('#pdf_summary').click(function() {
        //         //To prevent from flashing the table on the screen
        //         document.body.classList.add("pdf-export-mode");
        //         document.body.style.visibility = "hidden";
        //         document.body.style.opacity = "0";

        //         html2canvas(document.querySelector('#balanceSheetPrint'), {
        //             scale: 3
        //         }).then((canvas) => {
        //             let balanceImage = canvas.toDataURL('image/png');
        //             // console.log(balanceImage);
        //             const {
        //                 jsPDF
        //             } = window.jspdf;
        //             let pdf = new jsPDF('p', 'mm', 'letter');

        //             //To get the size of letter 
        //             const pageWidth = pdf.internal.pageSize.getWidth();
        //             const pageHeight = pdf.internal.pageSize.getHeight();
        //             const margin = 10;

        //             //Stores the height and width of the table (#balanceSheetPrint)
        //             const imgProps = {
        //                 width: canvas.width,
        //                 height: canvas.height
        //             };
        //             //Since the canvas is pixel-based, this is used to convert px to mm
        //             const pxToMm = px => px * 0.264583;

        //             const imgWidthMM = pageWidth - margin * 2; //Sets the actual coverage of table 
        //             const imgHeightMM = pxToMm(canvas.height) * (imgWidthMM / pxToMm(canvas.width));

        //             pdf.addImage(balanceImage, 'PNG', margin, margin, imgWidthMM, imgHeightMM);
        //             pdf.save('project-summary.pdf');

        //             //Restores the whole content after button is clicked
        //             document.body.style.visibility = "visible";
        //             document.body.style.opacity = "1";
        //             document.body.classList.remove("pdf-export-mode");
        //         });

        //     });
        // });

        // //This is for the CSV File
        // function download_csv(csv, filename) {

        //     const csvfile = new Blob([csv], {
        //         type: "text/csv"
        //     });
        //     const downloadLink = document.createElement('a');

        //     downloadLink.download = filename;
        //     downloadLink.href = window.URL.createObjectURL(csvfile);
        //     downloadLink.style.display = "none";
        //     document.body.appendChild(downloadLink);
        //     downloadLink.click();
        // }


        // function export_table_csv(filename) {
        //     let csv = [];
        //     const rows = document.querySelectorAll("#summary_table tr");

        //     for (let i = 0; i < rows.length; i++) {
        //         let row = [];
        //         const cols = rows[i].querySelectorAll("td, th");

        //         for (let j = 0; j < cols.length; j++) {
        //             let cell = cols[j].innerText.replace(/"/g, '""');
        //             row.push(`"${cell}"`);
        //         }

        //         csv.push(row.join(","));
        //     }
        //     download_csv(csv.join("\n"), filename)
        // }

        // const csvBtn = document.getElementById('csv_summary');
        // csvBtn.addEventListener('click', function() {
        //     export_table_csv("project-summary.csv");

        // })

        // //This is for Excel file
        // document.getElementById('xls_summary').addEventListener('click', function() {

        //     const table2excel = new Table2Excel();
        //     table2excel.export(document.querySelectorAll("#summary_table"), "Project-Summary");
        // })

        function enableEdit() {
            //This is to hide the current value of each field
            document.getElementById('displayMaterialLimit').classList.add('d-none');
            document.getElementById('displayLaborLimit').classList.add('d-none');
            document.getElementById('displayOthersLimit').classList.add('d-none');

            //To display the hidden input field
            document.getElementById('materialLimitInput').classList.remove('d-none');
            document.getElementById('laborLimitInput').classList.remove('d-none');
            document.getElementById('othersLimitInput').classList.remove('d-none');

            document.getElementById('editBtn').classList.add('d-none');
            document.getElementById('updateBtn').classList.remove('d-none');

        }
    </script>

</body>

</html>