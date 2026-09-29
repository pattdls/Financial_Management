<?php
session_start();
include('../dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

if (isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $new_status = (int)$_POST['status'];

    // Get user details
    $user_query = "SELECT * FROM users WHERE id = '$user_id'";
    $user_result = mysqli_query($conn, $user_query);
    $user = mysqli_fetch_assoc($user_result);

    if (!$user) {
        $_SESSION['warning'] = "User not found.";
        header("Location: ../inner_pages/create_user.php");
        exit();
    }

    // Update user status
    $update_query = "UPDATE users SET verify_status = '$new_status', updated_at = NOW() WHERE id = '$user_id'";
    $update_result = mysqli_query($conn, $update_query);

    if ($update_result) {
        $action = ($new_status == 1) ? 'enabled' : 'disabled';

        // Send notification email to user
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
            $mail->addAddress($user['email'], $user['name']);

            $mail->isHTML(true);
            $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
            
            if ($new_status == 1) {
                $mail->Subject = 'Your Account Has Been Activated';
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
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$user['name']},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>Your account for the RVR SMES Financial Management System <strong>has been successfully activated</strong>. 
                                You may now log in with your credentials to access the system.</p>
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
            } else {
                $mail->Subject = 'Your Account Has Been Deactivated';
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
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$user['name']},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>Your account for the RVR SMES Financial Management System <strong>has been deactivated</strong>. 
                                You no longer have access to the system. If you believe this is an error, please contact our team to resolve the issue.</p>
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

                    <h3>Account Deactivated</h3>
                    <p>Dear {$user['name']},</p>
                    <p>Your account has been deactivated. You will no longer be able to access the system.</p>
                ";
            }

            $mail->send();
            $status_message .= "User '" . htmlspecialchars($user['name']) . "' has been $action successfully. Notification email sent to user.";
        } catch (Exception $e) {
            $status_message .= " However, notification email could not be sent.";
        }

        $_SESSION['success'] = $status_message;
    } else {
        $_SESSION['danger'] = "Failed to update user status. Please try again.";
    }
} else {
    $_SESSION['danger'] = "Invalid request.";
}

header("Location: ../inner_pages/create_user.php");
exit();
?>


                    <!-- <h3>Account Activated</h3>
                    <p>Dear {$user['name']},</p>
                    <p>Your account has been activated. You can now access the system.</p> -->