<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// DEBUG: Check what POST data is being received
error_log("=== EDIT PROJECT DEBUG ===");
error_log("POST data received: " . print_r($_POST, true));
error_log("Requested action: " . ($_POST['save_changes'] ?? 'NOT SET'));
error_log("Requested save_project: " . ($_POST['save_project'] ?? 'NOT SET'));

// If somehow save_project is being triggered instead of save_changes
if (isset($_POST['save_project'])) {
    error_log("ERROR: save_project triggered in edit_project.php - this should not happen!");
    die("Error: Wrong form submission detected. Please try again.");
}

// Continue with your existing code...
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (!isset($_SESSION['auth_user']['id']) || !isset($_SESSION['auth_user']['role'])) {
    die("User not authenticated.");
}

$user_id = $_SESSION['auth_user']['id'] ?? null;
$user_role = $_SESSION['auth_user']['role'] ?? null;

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ONLY process if save_changes is set (not save_project)
if (isset($_POST['save_changes']) && !isset($_POST['save_project'])) {
    error_log("Processing EDIT request for project_id: " . ($_POST['project_id'] ?? 'NOT SET'));
    
    $project_id = $_POST['project_id'];
    $client_id = $_POST['client_id'];
    $project_name = $_POST['project_name'];
    $po_num = $_POST['po_num'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $projected_budget_cost = $_POST['projected_budget_cost'];
    $project_type = $_POST['project_type'];
    $allocated_budget = $_POST['allocated_budget'];
    $materials_cost = $_POST['materials_cost'];
    $labor_cost = $_POST['labor_cost'];
    $other_expenses_cost = $_POST['other_expenses_cost'];
    $project_status = $_POST['project_status'] ?? null;

    // Prevent SQL Injection
    $project_id = mysqli_real_escape_string($connection, $project_id);
    $client_id = mysqli_real_escape_string($connection, $client_id);
    $project_name = mysqli_real_escape_string($connection, $project_name);
    $po_num = mysqli_real_escape_string($connection, $po_num);
    $start_date = mysqli_real_escape_string($connection, $start_date);
    $end_date = mysqli_real_escape_string($connection, $end_date);
    $projected_budget_cost = mysqli_real_escape_string($connection, $projected_budget_cost);
    $project_type = mysqli_real_escape_string($connection, $project_type);
    $allocated_budget = mysqli_real_escape_string($connection, $allocated_budget);
    $materials_cost = mysqli_real_escape_string($connection, $materials_cost);
    $labor_cost = mysqli_real_escape_string($connection, $labor_cost);
    $other_expenses_cost = mysqli_real_escape_string($connection, $other_expenses_cost);
    $project_status = $project_status ? mysqli_real_escape_string($connection, $project_status) : null;

    // Fetch existing project data for comparison
    $old_query = "SELECT * FROM projects WHERE project_id = '$project_id'";
    $old_result = mysqli_query($connection, $old_query);
    
    if (!$old_result) {
        die("Error fetching existing project data: " . mysqli_error($connection));
    }
    
    $old_data = mysqli_fetch_assoc($old_result);
    
    if (!$old_data) {
        die("Project not found.");
    }

    error_log("Comparing old vs new data for project: " . $project_name);

    // Build list of changes - with better comparison logic
    $changes = [];
    
    if (trim($project_name) !== trim($old_data['project_name'])) {
        $changes[] = "Edited project name from '{$old_data['project_name']}' to '{$project_name}'";
    }
    
    if (trim($po_num) !== trim($old_data['po_num'])) {
        $changes[] = "Edited PO number from '{$old_data['po_num']}' to '{$po_num}'";
    }
    
    if ($start_date !== $old_data['start_date']) {
        $changes[] = "Edited start date from '{$old_data['start_date']}' to '{$start_date}'";
    }
    
    if ($end_date !== $old_data['end_date']) {
        $changes[] = "Edited end date from '{$old_data['end_date']}' to '{$end_date}'";
    }
    
    if (floatval($projected_budget_cost) !== floatval($old_data['projected_budget_cost'])) {
        $changes[] = "Edited projected budget cost from '{$old_data['projected_budget_cost']}' to '{$projected_budget_cost}'";
    }
    
    if (trim($project_type) !== trim($old_data['project_type'])) {
        $changes[] = "Edited project type from '{$old_data['project_type']}' to '{$project_type}'";
    }
    
    if (floatval($allocated_budget) !== floatval($old_data['allocated_budget'])) {
        $changes[] = "Edited allocated budget from '{$old_data['allocated_budget']}' to '{$allocated_budget}'";
    }
    
    if (floatval($materials_cost) !== floatval($old_data['materials_cost'])) {
        $changes[] = "Edited materials cost from '{$old_data['materials_cost']}' to '{$materials_cost}'";
    }
    
    if (floatval($labor_cost) !== floatval($old_data['labor_cost'])) {
        $changes[] = "Edited labor cost from '{$old_data['labor_cost']}' to '{$labor_cost}'";
    }
    
    if (floatval($other_expenses_cost) !== floatval($old_data['other_expenses_cost'])) {
        $changes[] = "Edited other expenses cost from '{$old_data['other_expenses_cost']}' to '{$other_expenses_cost}'";
    }
    
    if (!empty($project_status) && trim($project_status) !== trim($old_data['project_status'])) {
        $changes[] = "Edited project status from '{$old_data['project_status']}' to '{$project_status}'";
    }

    error_log("Changes detected: " . count($changes) . " changes");
    if (!empty($changes)) {
        error_log("Change details: " . print_r($changes, true));
    }

    // Build the UPDATE query
    $update_fields = [
        "project_name = '$project_name'",
        "po_num = '$po_num'",
        "start_date = '$start_date'",
        "end_date = '$end_date'",
        "projected_budget_cost = '$projected_budget_cost'",
        "project_type = '$project_type'",
        "allocated_budget = '$allocated_budget'",
        "materials_cost = '$materials_cost'",
        "labor_cost = '$labor_cost'",
        "other_expenses_cost = '$other_expenses_cost'"
    ];
    
    if (!empty($project_status)) {
        $update_fields[] = "project_status = '$project_status'";
    }
    
    $sql = "UPDATE projects SET " . implode(', ', $update_fields) . " WHERE project_id = '$project_id'";
    error_log("UPDATE SQL: " . $sql);

    if (mysqli_query($connection, $sql)) {
        // Insert activity logs only if there were changes
        if (!empty($changes)) {
            error_log("Logging " . count($changes) . " activities for user_id: $user_id");
            
            foreach ($changes as $activity) {
                $formatted_activity = $activity . " (Project: " . $project_name . ")";
                
                // Log the activity being inserted
                error_log("Inserting activity: " . $formatted_activity);
                
                $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($connection, $log_sql);
                
                if (!$stmt) {
                    error_log("Prepare failed: " . mysqli_error($connection));
                    continue;
                }
                
                $log_user_id = $user_id ?? 0;
                mysqli_stmt_bind_param($stmt, "iiss", $log_user_id, $client_id, $user_role, $formatted_activity);
                
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Execute failed for activity log: " . mysqli_stmt_error($stmt));
                } else {
                    error_log("Successfully logged activity: " . $formatted_activity);
                }
                
                mysqli_stmt_close($stmt);
            }
        } else {
            error_log("No changes detected, no activity logs created");
        }
        
        error_log("Redirecting to projects.php with client_id: " . $client_id);
        header("Location: ../inner_pages/projects.php?client_id=" . $client_id . "&update=success");
        exit();
    } else {
        error_log("UPDATE failed: " . mysqli_error($connection));
        die("Error updating record: " . mysqli_error($connection));
    }
} else {
    error_log("WARNING: edit_project.php called but save_changes not set");
    if (isset($_POST['save_project'])) {
        error_log("ERROR: save_project was set - this suggests form routing issue");
    }
    
    // If no valid POST data, redirect back
    if (isset($_GET['client_id'])) {
        header("Location: ../inner_pages/projects.php?client_id=" . $_GET['client_id']);
    } else {
        header("Location: ../inner_pages/projects.php");
    }
    exit();
}

error_log("=== END EDIT PROJECT DEBUG ===");
?>