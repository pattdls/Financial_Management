<?php
// Enhanced logout.php file with user choice
session_start();
include('../dbcon.php');
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

$site_base_url = "https://rvrsmes-fms.com/"; // adjust as needed

// ✅ If user is logged in, log the logout activity
if (isset($_SESSION['auth_user'])) {
    $user_id = $_SESSION['auth_user']['id'];
    $role = $_SESSION['auth_user']['role'];

    // Update the latest session_log with logout time
    $log_query = "UPDATE session_logs 
                  SET logout_time = NOW() 
                  WHERE user_id = ? 
                  ORDER BY login_time DESC 
                  LIMIT 1";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $user_id);
    $log_stmt->execute();
    $log_stmt->close();
}

// ✅ Clear all session variables
$_SESSION = array();

// ✅ Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Detect if request came from Beacon/AJAX
$isBeacon = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || isset($_GET['beacon']);

// If beacon, just return success (no redirect)
if ($isBeacon) {
    http_response_code(200);
    echo json_encode(["status" => "logged_out"]);
    exit;
}

// Log user logout activity
if (isset($_SESSION['auth_user'])) {
    $user_id = $_SESSION['auth_user']['id'];

    // ✅ Update session_logs with logout + last_seen
    $log_query = "UPDATE session_logs 
                  SET logout_time = NOW(), last_seen = NOW()
                  WHERE user_id = ? 
                  ORDER BY login_time DESC 
                  LIMIT 1";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $user_id);
    $log_stmt->execute();
    $log_stmt->close();
}

// ✅ Otherwise, normal logout with redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header("Location: " . $site_base_url . "login_form.php?logged_out=1");
exit();