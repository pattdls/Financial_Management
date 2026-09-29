<?php
// ...existing code...
require_once __DIR__ . '/../dbcon.php';
require_once __DIR__ . '/../user/notification_functions.php';

$days_before = 3; // notify when end_date is within N days
$today = date('Y-m-d');
$threshold = date('Y-m-d', strtotime("+{$days_before} days"));

if (!isset($connection)) {
    // fallback if dbcon uses another var name
    $connection = $conn ?? $connection ?? mysqli_connect('localhost','root','','financial_management');
}

$stmt = $connection->prepare(
    "SELECT project_id, project_name, client_id, end_date
     FROM projects
     WHERE project_status <> 'Completed'
       AND end_date BETWEEN ? AND ?
       AND (end_date_notified = 0 OR end_date_notified IS NULL)"
);
$stmt->bind_param('ss', $today, $threshold);
$stmt->execute();
$res = $stmt->get_result();

while ($proj = $res->fetch_assoc()) {
    $project_id = (int)$proj['project_id'];
    $project_name = $proj['project_name'];
    $client_id = (int)$proj['client_id'];
    $end_date = $proj['end_date'];

    $message = "Project <strong>{$project_name}</strong> is nearing its agreed end date ({$end_date}). Please review progress.";

    // Notify finance + admin
    $role_q = $connection->query("SELECT id FROM users WHERE role IN ('finance','admin')");
    while ($r = $role_q->fetch_assoc()) {
        create_notification($connection, (int)$r['id'], $project_id, 'deadline_warning', $message);
    }

    // Notify client users (all users linked to client_id)
    $cu = $connection->prepare("SELECT id FROM users WHERE client_id = ?");
    $cu->bind_param("i", $client_id);
    $cu->execute();
    $cr = $cu->get_result();
    while ($c = $cr->fetch_assoc()) {
        create_notification($connection, (int)$c['id'], $project_id, 'deadline_warning', $message);
    }
    $cu->close();

    // Mark project as notified
    $u = $connection->prepare("UPDATE projects SET end_date_notified = 1 WHERE project_id = ?");
    $u->bind_param('i', $project_id);
    $u->execute();
    $u->close();

    error_log("Deadline notification created for project_id {$project_id}");
}

$stmt->close();
echo "Deadline check completed.\n";
// ...existing code...