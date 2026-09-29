<?php
session_start();
include('../dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// For Auto Generated Password
function generateRandomPassword($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// Modified for unique user ID creation
if (isset($_POST['create_user_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    $client_id = ($role === 'User' && isset($_POST['client_id'])) ? mysqli_real_escape_string($conn, $_POST['client_id']) : null;

    // Check for existing email
    $check_user_query = "SELECT id FROM users WHERE email = '$email'";
    $check_user_result = mysqli_query($conn, $check_user_query);

    if (mysqli_num_rows($check_user_result) > 0) {
        $_SESSION['warning'] = "A user with this email already exists.";
        header("Location: ../inner_pages/create_user.php");
        exit();
    }

    // Generate the RVR User ID
    $date = date('Ymd');
    $role_prefix = '';

    switch($role) {
        case 'Admin':
            $role_prefix = 'ADM';
            break;
        case 'Finance':
            $role_prefix = 'FIN';
            break;
        case 'User':
            $role_prefix = 'USR';
            break;
        default:
            $_SESSION['email_warning'] = "Invalid role selected.";
            header("Location: ../inner_pages/create_user.php");
            exit();
    }

    // Count how many users exist for this role (across ALL time)
    $count_query = "SELECT COUNT(*) as count FROM users WHERE role = '$role'";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $count = $count_row['count'] + 1;

    // Format sequence with leading zeros
    $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);

    // Build continuous User ID
    $generated_user_id = "RVR-$role_prefix-$date-$sequence";


    // Auto generated password
    $plainPassword = generateRandomPassword();
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (user_id, name, email, password, role, client_id, verify_status, created_at)
              VALUES ('$generated_user_id', '$name', '$email', '$hashedPassword', '$role', " . ($client_id ? "'$client_id'" : "NULL") . ", 1, NOW())";
    $result = mysqli_query($conn, $query);

    if ($result) {

        // Determine greeting name
        $client_name = $name; // fallback
        if ($role === 'Client' && $client_id) {
            $client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1";
            $client_result = mysqli_query($conn, $client_query);
            if ($client_result && mysqli_num_rows($client_result) > 0) {
                $client_row = mysqli_fetch_assoc($client_result);
                $client_name = $client_row['client_name'];
            }
        }
        
        // Send email with PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cacorereservation@gmail.com';
            $mail->Password   = 'gdbsgyjujydcsqdb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('cacorereservation@gmail.com', 'Financial Management System');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
            $mail->Subject = 'Your Account Details';
            $mail->Body    = "
                <div class='email-container' style = '
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    '>
                        <!-- Header Section -->
                        <div class='header' style = '
                            display: flex;
                            padding: 0;
                            height: 155px;
                            justify-content: center;
                            align-items: center;
                        '>
                        <div class='logo' style= '
                            width: 400px;
                            height: 150px;
                        '>
                        <img src='cid:logo_cid' alt='Company Logo' style='width: 400px; height: 150px;'>
                        </div>
                        </div>
                        
                        <!-- Main Content -->
                        <div class='content' style='padding: 40px 30px;'>
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$client_name},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>Your account for the RVR SMES Financial Management System has been successfully set up. 
                                You can now log in using the credentials provided below.</p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;'>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>Account Credentials</div>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>Password:</strong> $plainPassword</p>
                            </div>
                            <div class='main-message' style='color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;'>
                            <p>For security purposes, please log in at your earliest convenience and update your password immediately.</p>            
                            </div>
                            <div style='text-align: center;'>
                                <a href='https://rvrsmes-fms.com/'  class='cta-button'
                                style= '
                                display: inline-block;
                                background: linear-gradient(135deg, #2a5298, #1e3c72);
                                color: white;
                                padding: 10px 30px;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: bold;
                                margin: 20px 0;
                                transition: transform 0.2s ease;
                                '>Login to Your Account</a>
                            </div>
                            <div style='margin-top: 30px;'>
                                <p style='color: #555;'>Best regards,<br>
                                RVR SMES Financial Management System
                                </p>
                            </div>
                        </div>
                        <div class='footer' style='background-color: #f8f9fa;
                            padding: 20px;
                            text-align: center;
                            font-size: 12px;
                            color: #666;
                            border-top: 1px solid #e9ecef;'>
                            <p>&copy; 2025 RVR SMES Financial Management. All rights reserved.</p>
                            <p>This email contains confidential system account information intended only for the named recipient.</p>
                        </div>
                </div>
            ";

            $mail->send();
            $_SESSION['success'] = "User created and email sent successfully!";
        } catch (Exception $e) {
            $_SESSION['warning'] = "User created, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        $_SESSION['generated_password'] = $plainPassword;
    } else {
        $_SESSION['danger'] = "Failed to create user.";
    }

    header("Location: ../inner_pages/create_user.php");
    exit(0);
}

?>

<!-- 

                <h3>Welcome to the system!</h3>
                <p>Here are your account details:</p>
                <ul>
                    <li><strong>User ID:</strong> $generated_user_id</li>
                    <li><strong>Email:</strong> $email</li>
                    <li><strong>Password:</strong> $plainPassword</li>
                </ul>
                <p>Please change your password upon first login.</p> -->
