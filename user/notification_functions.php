<?php
function create_notification($connection, $user_id, $project_id, $type, $message) {
    $stmt = $connection->prepare("INSERT INTO user_notifications (user_id, project_id, type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $project_id, $type, $message);
    $stmt->execute();
    $stmt->close();
}