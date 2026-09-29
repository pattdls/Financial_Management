<?php
session_start();
require '../dbcon.php';
date_default_timezone_set('Asia/Manila');

//to fetch currently logged in's email
if (!isset($_SESSION['auth_user']['email'])) {
    die("You must be logged in.");
}
$email = $_SESSION['auth_user']['email'];
$error = "";
if (!isset($_SESSION['login_id'])) {
    $_SESSION['login_id'] = uniqid("login_", true);
}
$conn->query("SET time_zone = '+08:00'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm-pass-income'])) {
    $password = trim($_POST['unlock_password']);

    $client_name = $_POST['client_name'] ?? ''; 
    $project_name = $_POST['project_name'] ?? ''; 
    $project_id = $_POST['project_id'] ?? '';

    if (!empty($client_name) && !empty($project_name)) {
        $desc = "Accessed Project: {$project_name} Income Statement from Client: {$client_name}";
    }

    $max_attempts = 5;       
    $lockout_time = 1800; //equvalent to 30 mins

    //fetch attempt for the current user
    $stmt = $conn->prepare("SELECT * FROM fs_unlock_attempts WHERE identifier = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();

    if (!$attempt) {
        // create row if user has no entry yet
        $stmt = $conn->prepare("INSERT INTO fs_unlock_attempts (identifier, attempts, last_status, last_attempt_time) VALUES (?, 0, 'failed', NOW())");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // re-fetch so $attempt is not null
        $stmt = $conn->prepare("SELECT * FROM fs_unlock_attempts WHERE identifier = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $attempt = $stmt->get_result()->fetch_assoc();
    }

    //to check if current user is locked
    if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()) {
        $remaining = ceil((strtotime($attempt['locked_until']) - time()) / 60);
        $_SESSION['fs_error_income'] = "Account locked. Try again in $remaining minutes.";

         // log this attempt (locked)
        $stmt = $conn->prepare("INSERT INTO fs_attempt_logs (identifier, login_id, total_attempts, last_status, last_attempt_time, description) 
                                VALUES (?, ?, ?, ?, NOW()), ?");
        $status = 'locked';
        $dummy_attempts = $attempt['attempts'] + 1;
        $stmt->bind_param("ssiss", $email, $_SESSION['login_id'], $dummy_attempts, $status, $desc);
        $stmt->execute();

        header("Location: ../inner_pages/Income.php?project_id=$project_id");
        exit;
    }

    //to check if password is correct
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // if correct password, the blur will disappear
        $_SESSION['fs_unlocked_income'] = true;

        $stmt = $conn->prepare("UPDATE fs_unlock_attempts SET attempts = 0, last_status = 'success', last_attempt_time = NOW() WHERE identifier = ? ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $status = 'success';
        $success_attempts =  $attempt['attempts'] + 1;
        
         //  update fs_attempt_logs to save number of attempts to be used for system logs
        $stmt = $conn->prepare("INSERT INTO fs_attempt_logs (identifier, login_id, total_attempts, last_status, last_attempt_time, description)
                                VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("ssiss", $email, $_SESSION['login_id'], $success_attempts, $status, $desc);
        $stmt->execute();

        header("Location: ../inner_pages/Income.php?project_id=$project_id");
        exit;
    } else {
        //counts attempts
        $new_attempts = $attempt['attempts'] + 1;

        if ($new_attempts >= $max_attempts) {
            // lock account
            $locked_until = date('Y-m-d H:i:s', time() + $lockout_time);
            $stmt = $conn->prepare("UPDATE fs_unlock_attempts SET attempts = ?, locked_until = ?, last_status = 'failed', last_attempt_time = NOW() WHERE identifier = ? ");
            $stmt->bind_param("iss", $new_attempts, $locked_until, $email);
            $stmt->execute();

            $_SESSION['fs_error_income'] = "Reached maximum attempts. Locked for " . ($lockout_time / 60) . " minutes.";
        } else {
            
            $stmt = $conn->prepare("UPDATE fs_unlock_attempts SET attempts = ?, last_status = 'failed', last_attempt_time = NOW() WHERE identifier = ?");
            $stmt->bind_param("is", $new_attempts, $email);
            $stmt->execute();

            $_SESSION['fs_error_income'] = "Invalid password. Attempts left: " . ($max_attempts - $new_attempts);
        }

        // update the table for storing attempts as well
        $stmt = $conn->prepare("INSERT INTO fs_attempt_logs (identifier, login_id, total_attempts, last_status, last_attempt_time, description)
                                VALUES (?, ?, ?, ?, NOW(), ?)");
        $status = 'failed';
        $stmt->bind_param("ssiss", $email, $_SESSION['login_id'], $new_attempts, $status, $desc);
        $stmt->execute();

        header("Location: ../inner_pages/Income.php?project_id=$project_id");
        exit;
    }
}
?>
