<?php
include('../dbcon.php');


$project_id = intval($_POST['project_id']);
$phase_name = mysqli_real_escape_string($conn, $_POST['phase_name']);
$status = mysqli_real_escape_string($conn, $_POST['status']);

// Build SQL with logic for start_date / end_date
$updateFields = "status = '$status'";

if ($status === 'In Progress') {
    $updateFields .= ", start_date = IFNULL(start_date, NOW())";
} elseif ($status === 'Completed') {
    $updateFields .= ", end_date = IFNULL(end_date, NOW())";
}

$query = "UPDATE project_phases SET $updateFields WHERE project_id = $project_id AND phase_name = '$phase_name'";
$result = mysqli_query($conn, $query);

require_once '../user/notification_functions.php';

// Get the client user_id for this project
$project_query = $conn->query("SELECT client_id FROM projects WHERE project_id = $project_id");
$project = $project_query->fetch_assoc();
$client_id = $project['client_id'];

$message = "Project phase $phase_name was updated to $status.";
create_notification($conn, $client_id, $project_id, 'phase_update', $message);

// Error handling
if (!$result) {
    http_response_code(500);
    echo "Query failed: " . mysqli_error($conn);
    exit;
}

if (mysqli_affected_rows($conn) === 0) {
    http_response_code(400);
    echo "No matching phase found for update.";
    exit;
}

// Insert into project_phase_logs after successful update
$log_query = "INSERT INTO project_phase_logs (project_id, phase_name, status, updated_at)
              VALUES ($project_id, '$phase_name', '$status', NOW())";
mysqli_query($conn, $log_query);

echo "Success";

?>