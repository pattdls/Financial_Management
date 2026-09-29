<?php
session_start();
include('../dbcon.php');
date_default_timezone_set('Asia/Manila');

if (isset($_POST['login_btn'])) {
    if (!empty(trim($_POST['email'])) && !empty(trim($_POST['password']))) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        // Login attempts configuration
        $max_attempts = 5;
        $lockout_minutes = 30; 
        $lockout_seconds = $lockout_minutes * 60;

        // First, check if this is an admin user attempting to login
        $admin_check_stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
        $admin_check_stmt->bind_param("s", $email);
        $admin_check_stmt->execute();
        $admin_result = $admin_check_stmt->get_result();
        $admin_user = $admin_result->fetch_assoc();
        $is_admin = ($admin_user && $admin_user['role'] === 'admin');

        // Check current login attempts for this email (only if not admin)
        if (!$is_admin) {
            $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE identifier = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $attempt = $result->fetch_assoc();

            // If account is locked
            if ($attempt && $attempt['locked_until']) {
                $locked_until_ts = strtotime($attempt['locked_until']);

                if ($locked_until_ts > time() && !$is_admin) {
                    // Still locked
                    $remaining_time = $locked_until_ts - time();
                    $_SESSION['status'] = "<div class='alert alert-danger'>Your account is locked. Try again in " . ceil($remaining_time / 60) . " minutes.</div>";
                    header("Location: ../login_form.php");
                    exit;
                } elseif ($locked_until_ts <= time()) {
                    // Lock expired → reset attempts
                    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->close();
                    $attempt = null; // behave like fresh login
                }
            }
        } else {
            // Admin can bypass lockout, but we still check attempts for logging purposes
            $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE identifier = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $attempt = $result->fetch_assoc();
        }

        // Check user credentials - include verify_status in the query
        $stmt = $conn->prepare("SELECT id, name, email, role, client_id, company_id, password, verify_status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();

        // DEBUG: Log what we found (remove this after debugging)
        error_log("User found: " . ($user ? "YES" : "NO"));
        if ($user) {
            error_log("Verify status: '" . $user['verify_status'] . "' (type: " . gettype($user['verify_status']) . ")");
            error_log("Password match: " . (password_verify($password, $user['password']) ? "YES" : "NO"));
        }

        // If user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {

            // Check email verification - handle different possible values
            $verify_status = $user['verify_status'];
            $is_verified = false;

            // Handle different verification status formats
            $verify_status = (int)$user['verify_status']; // cast to int
            $is_verified = ($verify_status === 1); // true if verified

            if (!$is_verified) {
                $_SESSION['status'] = "<div class='alert alert-danger'>Your account is currently disabled. <br> Please contact RVR SMES admin to verify your system account status.</div>";
                header("Location: ../login_form.php");
                exit;
            }

            // Get the current attempt count (including this successful attempt)
            $attempt_count = ($attempt) ? $attempt['attempts'] + 1 : 1;

            // SUCCESS: Clear login attempts and log the session
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            // Set session data
            $_SESSION['authenticated'] = true;
            $_SESSION['auth_user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'client_id' => $user['client_id'],
                'company_id' => $user['company_id'] ?? null,
            ];

            // Log successful login with attempt count
            $user_id = $user['id'];
            $role = $user['role'];
            $conn->query("SET time_zone = '+08:00'");
            $now = date('Y-m-d H:i:s'); // PH time from PHP

            $log_sql = "INSERT INTO session_logs (user_id, role, login_time, login_attempts) 
            VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("issi", $user_id, $role, $now, $attempt_count);
            $log_stmt->execute();
            
            // Handle "Remember Me" functionality
            if (isset($_POST['remember']) && $_POST['remember']) {
                // Set cookies for 30 days
                setcookie("cookie_email", $email, time() + (30 * 24 * 60 * 60), '/');
                setcookie("cookie_rem", $_POST['remember'], time() + (30 * 24 * 60 * 60), '/');
            } else {
                // Clear "Remember Me" cookies if checkbox is unchecked
                if (isset($_COOKIE['cookie_email'])) {
                    setcookie("cookie_email", "", time() - 3600, '/');
                }
                if (isset($_COOKIE['cookie_rem'])) {
                    setcookie("cookie_rem", "", time() - 3600, '/');
                }
            }

            // Redirect based on role
            if ($user['role'] === 'user') {
                header("Location: ../user/dashboard.php");
            } elseif ($user['role'] === 'finance' || $user['role'] === 'admin') {
                header("Location: ../index.php");
            } else {
                $_SESSION['status'] = "Unknown role. Please contact support.";
                header("Location: ../login_form.php");
            }
            exit;

        } else {
            // Store form data to preserve user input on error
            $_SESSION['form_email'] = $email;
            $_SESSION['form_remember'] = isset($_POST['remember']) ? $_POST['remember'] : false;

            // FAILED LOGIN: Increment attempts (admins are still subject to attempt tracking)
            if ($attempt) {
                $new_attempts = $attempt['attempts'] + 1;
                
                if ($new_attempts >= $max_attempts && !$is_admin) {
                    // Lock the account (only for non-admins)
                    $locked_until = date('Y-m-d H:i:s', time() + $lockout_seconds);
                    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE identifier = ?");
                    $stmt->bind_param("iss", $new_attempts, $locked_until, $email);
                    $stmt->execute();
                    $_SESSION['status'] = "<div class='alert alert-danger'>Too many failed attempts. <br> Account locked for " . ($lockout_minutes) . " minutes.</div>";
                } elseif ($new_attempts >= $max_attempts && $is_admin) {
                    // Admin exceeded attempts but won't be locked
                    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, locked_until = NULL WHERE identifier = ?");
                    $stmt->bind_param("is", $new_attempts, $email);
                    $stmt->execute();
                    $_SESSION['status'] = "<div class='alert alert-danger'>Invalid email or password! Please check your credentials.</div>";
                } else {
                    // Just increment attempts
                    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, locked_until = NULL WHERE identifier = ?");
                    $stmt->bind_param("is", $new_attempts, $email);
                    $stmt->execute();
                    $remaining_attempts = $max_attempts - $new_attempts;
                    
                    if ($is_admin) {
                        $_SESSION['status'] = "<div class='alert alert-danger'>Invalid email or password!</div>";
                    } else {
                        $_SESSION['status'] = "<div class='alert alert-danger'>Invalid email or password! Attempts remaining: " . $remaining_attempts . "</div>";
                    }
                }
            } else {
                // First failed attempt - create new record
                $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, attempts, locked_until) VALUES (?, 1, NULL)");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $remaining_attempts = $max_attempts - 1;
                
                if ($is_admin) {
                    $_SESSION['status'] = "<div class='alert alert-danger'>Invalid email or password!</div>";
                } else {
                    $_SESSION['status'] = "<div class='alert alert-danger'>Invalid email or password! Attempts remaining: " . $remaining_attempts . "</div>";
                }
            }

            header("Location: ../login_form.php");
            exit;
        }
    } else {
        // Store form data for empty field errors
        $_SESSION['form_email'] = isset($_POST['email']) ? $_POST['email'] : '';
        $_SESSION['form_remember'] = isset($_POST['remember']) ? $_POST['remember'] : false;
        
        $_SESSION['status'] = "<div class='alert alert-danger'>All fields are required.</div>";
        header("Location: ../login_form.php");
        exit;
    }
}
?>