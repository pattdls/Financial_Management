<?php
// check_session.php - Create this file in your root directory
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json");

// Check if user is authenticated
$authenticated = false;

if (isset($_SESSION['authenticated']) && 
    $_SESSION['authenticated'] === true && 
    isset($_SESSION['auth_user']['role']) && 
    in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])) {
    $authenticated = true;
}

// Return JSON response
echo json_encode([
    'authenticated' => $authenticated,
    'timestamp' => time()
]);
exit();
?>