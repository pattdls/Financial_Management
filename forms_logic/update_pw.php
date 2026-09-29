<?php
session_start();
include('../dbcon.php');

if (isset($_POST['change_password'])) {
    $user_id = $_SESSION['auth_user']['id'] ?? null;

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$user_id) {
        $_SESSION['message'] = "Unauthorized access. Please log in again.";
        $_SESSION['message_type'] = "error";
        header("Location: ../login_form.php");
        exit();
    }

    // Validate input fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['message'] = "All password fields are required.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    if (!$stmt->fetch()) {
        $stmt->close();
        $_SESSION['message'] = "User not found.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        $_SESSION['danger'] = "Current password is incorrect.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Check new password match
    if ($new_password !== $confirm_password) {
        $_SESSION['danger'] = "New passwords do not match.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Validate password strength
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~]{8,}$/', $new_password)) {
        $_SESSION['danger'] = "Password must be at least 8 chars long, contain 1 uppercase, 1 number, and 1 special character.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Prevent using the same password again
    if (password_verify($new_password, $hashed_password)) {
        $_SESSION['danger'] = "New password cannot be the same as the current password.";
        $_SESSION['message_type'] = "warning";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Hash and update new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_hashed_password, $user_id);

    if ($update_stmt->execute()) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // (Optional) log this action in your logs table
        $role = $_SESSION['auth_user']['role'] ?? 'unknown';
        $log_stmt = $conn->prepare("INSERT INTO session_logs (user_id, role, login_time, login_attempts) VALUES (?, ?, NOW(), 0)");
        $log_stmt->bind_param("is", $user_id, $role);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['message'] = "Password successfully updated!";
        $_SESSION['message_type'] = "success";

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        $_SESSION['danger'] = "Failed to update password. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $update_stmt->close();
} else {
    $_SESSION['danger'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: ../my_profile.php");
    exit();
}
?>
