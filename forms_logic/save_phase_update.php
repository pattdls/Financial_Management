<?php
include('../dbcon.php');
require_once '../user/notification_functions.php';

$project_id = intval($_POST['project_id']);
$phase_name = mysqli_real_escape_string($conn, $_POST['phase_name']);
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

$uploaded_files = [];
if (!empty($_FILES['progress_files']['name'][0])) {
    $upload_dir = '../pploads/progress/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    foreach ($_FILES['progress_files']['name'] as $i => $name) {
        $tmp_name = $_FILES['progress_files']['tmp_name'][$i];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $new_name = uniqid('progress_') . '.' . $ext;
        $target = $upload_dir . $new_name;
        if (move_uploaded_file($tmp_name, $target)) {
            $uploaded_files[] = $target;
        }
    }
}

$files_json = json_encode($uploaded_files);

$query = "INSERT INTO project_phase_logs (project_id, phase_name, status, description, files, updated_at)
          VALUES ($project_id, '$phase_name', 'Progress Update', '$description', '$files_json', NOW())";
mysqli_query($conn, $query);

$project_query = $conn->query("SELECT client_id FROM projects WHERE project_id = $project_id");
$project = $project_query->fetch_assoc();
$client_id = $project['client_id'];

$message = "Project phase <b>$phase_name</b> received a new progress update.";
create_notification($conn, $client_id, $project_id, 'phase_update', $message);

echo "Success";
?>
