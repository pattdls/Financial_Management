<?php
include(__DIR__ . '/../dbcon.php');

// Define inactivity threshold (in seconds)
$gracePeriod = 1800; // 30 minutes after last_seen

$query = "
    UPDATE session_logs
    SET logout_time = NOW()
    WHERE logout_time IS NULL
    AND last_seen IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, last_seen, NOW()) > ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $gracePeriod);
$stmt->execute();
$stmt->close();
