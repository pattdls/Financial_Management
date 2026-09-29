<?php
// Include session cleanup script
include(__DIR__ . '/../forms_logic/session_cleanup.php');

// Start session securely and apply settings before starting
if (session_status() === PHP_SESSION_NONE) {
    // Session should end when the browser closes
    ini_set("session.cookie_lifetime", 0); 
    ini_set("session.gc_maxlifetime", 300); // 5 mins inactivity cleanup
    ini_set("session.use_strict_mode", 1); // prevent session fixation

    session_set_cookie_params([
        'lifetime' => 0,              // expire on browser close
        'path' => '/',
        'domain' => '',               // current domain
        'secure' => isset($_SERVER['HTTPS']), // only over HTTPS
        'httponly' => true,           // prevent JS access
        'samesite' => 'Strict'        // no cross-site session usage
    ]);

    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['auth_user'])) {
    header("Location: /Financial_Management/login_form.php");
    exit();
}

$user_id = $_SESSION['auth_user']['id'] ?? null;

if ($conn && $user_id) {
    $stmt = $conn->prepare("SELECT verify_status, updated_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // If account removed/disabled, log out
    if (!$user || (int)$user['verify_status'] === 0) {
        header("Location: /Financial_Management/forms_logic/logout.php");
        exit();
    }

    // Auto logout if password was reset AFTER login
    if (isset($_SESSION['auth_user']['login_time']) && isset($user['updated_at'])) {
        if (strtotime($user['updated_at']) > $_SESSION['auth_user']['login_time']) {
            header("Location: /Financial_Management/forms_logic/logout.php");
            exit();
        }
    }
}

// Auto logout after inactivity (30 mins)
$inactive = 1800; // 30 minutes

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

$session_life = time() - $_SESSION['last_activity'];
if ($session_life > $inactive) {
    session_unset();
    session_destroy();
    // Notify user about session expiration
    $message = urlencode("Session expired due to inactivity");
    header("Location: /Financial_Management/login_form.php?message=$message");
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// ✅ Update last_seen in session_logs
if ($conn && isset($_SESSION['auth_user']['id'])) {
    $user_id = $_SESSION['auth_user']['id'];
    $stmt = $conn->prepare("
        UPDATE session_logs 
        SET last_seen = NOW() 
        WHERE user_id = ? 
        ORDER BY login_time DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

