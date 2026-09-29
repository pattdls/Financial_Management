<?php
session_start();
include('../dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function generateRandomPassword($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

if (isset($_POST['edit_user_btn'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $reset_password = isset($_POST['reset_password']);

    // Get current user data
    $current_user_query = "SELECT * FROM users WHERE id = '$user_id'";
    $current_user_result = mysqli_query($conn, $current_user_query);
    $current_user = mysqli_fetch_assoc($current_user_result);

    if (!$current_user) {
        $_SESSION['warning'] = "User not found.";
        header("Location: ../inner_pages/create_user.php");
        exit();
    }

    // Check if email already exists for other users
    $check_email_query = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
    $check_email_result = mysqli_query($conn, $check_email_query);

    if (mysqli_num_rows($check_email_result) > 0) {
        $_SESSION['warning'] = "This email is already used by another user.";
        header("Location: ../inner_pages/create_user.php");
        exit();
    }

    $newPassword = null;
    $hashedPassword = null;

    // Handle password reset
    if ($reset_password) {
        $newPassword = generateRandomPassword();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE users SET 
                        name = '$name', 
                        email = '$email', 
                        password = '$hashedPassword',
                        updated_at = NOW() 
                        WHERE id = '$user_id'";
    } else {
        $update_query = "UPDATE users SET 
                        name = '$name', 
                        email = '$email',
                        updated_at = NOW() 
                        WHERE id = '$user_id'";
    }

    $update_result = mysqli_query($conn, $update_query);

    if ($update_result) {

        // Send email if password was reset or if email changed
        if ($reset_password || $current_user['email'] !== $email) {
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
                
                if ($reset_password) {
                    $mail->Subject = 'Your Account Password Has Been Reset';
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
                        <img src='cid:logo_cid' alt='Company Logo' class='logo' style='width: 400px; height: 150px;'>
                        </div>
                        </div>
                        
                        <!-- Main Content -->
                        <div class='content' style='padding: 40px 30px;'>
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$current_user['name']},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>Your account password has been reset. Here are your updated account details:</p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;
                             '>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>New Account Credentials</div>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>New Password:</strong> $newPassword</p>
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
                } else {
                    $mail->Subject = 'Your Account Information Has Been Updated';
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
                        <img src='../resources/images/email_header2.jpg' alt='Company Logo' class='logo'>
                        </div>
                        </div>
                        
                        <!-- Main Content -->
                        <div class='content' style='padding: 40px 30px;'>
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$current_user['name']},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>Your account has been updated. Here are your updated account details:</p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;
                             '>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>New Account Credentials</div>
                                    <p><strong>Name:</strong> $name</p>
                                    <p><strong>Email:</strong> $email</p>
                                    <p><strong>Role:</strong> {$current_user['role']}</p>
                                </div>
                            <div class='main-message' style='color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;'>
                            <p>Please use the updated credentials to log in to your account.</p>            
                            </div>
                            <div style='text-align: center;'>
                                <a href='../login_form.php'  class='cta-button'
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
                }

                $mail->send();
                if ($reset_password) {
                    $success_message .= "User updated successfully! New password sent to user's email.";
                } else {
                    $success_message .= "<div class='alert-success'>User updated successfully! Notification email sent to user.</div>";
                }
            } catch (Exception $e) {
                $success_message .= " However, email notification could not be sent.";
            }
        }

        $_SESSION['success'] = $success_message;
    } else {
        $_SESSION['danger'] = "Failed to update user. Please try again.";
    }

    header("Location: ../inner_pages/create_user.php");
    exit();
}

header("Location: ../inner_pages/create_user.php");
exit();
?>


                        <!-- <h3>Password Reset</h3>
                        <p>Your account password has been reset.</p>
                        <p>Here are your updated account details:</p>
                        <ul>
                            <li><strong>User ID:</strong> {$current_user['user_id']}</li>
                            <li><strong>Email:</strong> $email</li>
                            <li><strong>New Password:</strong> $newPassword</li>
                        </ul>
                        <p><strong>Please change your password upon next login for security.</strong></p> -->


                         <!-- <h3>Account Updated</h3>
                        <p>Your account information has been updated.</p>
                        <p>Updated details:</p>
                        <ul>
                            <li><strong>User ID:</strong> {$current_user['user_id']}</li>
                            <li><strong>Name:</strong> $name</li>
                            <li><strong>Email:</strong> $email</li>
                            <li><strong>Role:</strong> {$current_user['role']}</li>
                        </ul> -->