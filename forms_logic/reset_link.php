<?php
session_start();
include('../dbcon.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
//Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//function to send password reset link
function send_reset_link($get_name,$get_email,$token)
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();   
    $mail->SMTPAuth   = true;

    $mail->Host       = 'smtp.gmail.com'; 
    $mail->Username   = 'cacorereservation@gmail.com';
    $mail->Password   = 'gdbsgyjujydcsqdb';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;          
    $mail->Port       = 465;     
    
    $mail->setFrom('cacorereservation@gmail.com', 'Financial Management System');
    $mail->addAddress($get_email);

    $mail->isHTML(true);
    $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
    $mail->Subject = 'Reset Password Notification';
    
    $email_template = '
    <div class="email-container" style = "
        max-width: 600px;
        margin: 0 auto;
        background-color: #ffffff;
        border-radius: 8px;
        overflow: hidden;
         box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        ">
        <!-- Header Section -->
        <div class="header" style = "
                display: flex;
                padding: 0;
                height: 155px;
                justify-content: center !important;
                align-items: center !important;">
            <div class="logo" style= "
                width: 400px;
                height: 150px;
                justify-content: center !important;
                align-items: center !important;">
                <img src="cid:logo_cid" alt="Company Logo" style="width: 400px; height: 150px;">
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content" style="padding: 40px 30px;">
            <div class="greeting" style="font-size: 18px; color: #333; margin-bottom: 20px;">Dear <strong>{{name}},</strong></div>
            
            <div class="main-message" style="color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;">
                  <p>We received a request to reset your password for your account in the RVR SMES Financial Management System. 
                    If you made this request, please click the button below to create a new password.
                  </p>
            </div>
             <div style="text-align: center;">
                <a href="https://rvrsmes-fms.com/change_pass.php?token='.$token.'&email='.urlencode($get_email).'"  class="cta-button"
                style= "
                display: inline-block;
                background: linear-gradient(135deg, #2a5298, #1e3c72);
                color: white;
                padding: 10px 30px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                margin: 20px 0;
                transition: transform 0.2s ease;
                ">Reset Your Password</a>
            </div>
            <div class="main-message" style="color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;">
              <p>If you did not request for a reset password, you can safely ignore this email.</p>            
            </div>
            
            <div style="margin-top: 30px;">
                <p style="color: #555;">Best regards,<br>
                RVR SMES Financial Management System
                </p>
            </div>
        </div>
         <div class="footer" style="background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;">
            <p>&copy; 2025 RVR SMES Financial Management. All rights reserved.</p>
            <p>This email contains confidential system account information intended only for the named recipient.</p>
        </div>
    </div>
    ';
    // Replace {{name}} with the actual user’s name.
    var_dump($get_name);
    $email_template = str_replace("{{name}}", htmlspecialchars($get_name), $email_template);

    $mail->Body = $email_template;
    $mail->send();
}
 //isset and POST is used to check if a button is clicked

if(isset($_POST['resetlink_btn']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    $token = md5(rand());

    //Used to check if user input email exists
    $check_email = "SELECT name, email FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($conn, $check_email);

    if(mysqli_num_rows($check_email_run) > 0)
    {
        $row = mysqli_fetch_array($check_email_run);
        $get_name = $row['name'];
        $get_email = $row['email'];

        //Updating token for password reset
        $update_token = "UPDATE users SET verify_token='$token' WHERE email='$get_email' LIMIT 1";
        $update_token_run = mysqli_query($conn, $update_token);

        if($update_token_run)
        {
            send_reset_link($get_name,$get_email,$token);
            $_SESSION['status'] = "<div class='alert-success'>Password reset link has been sent to your email.</div>";
            header("Location: ../to_fpass.php");
            exit(0);
        }
        else
        {
            $_SESSION['status'] = "<div class='alert-danger'>Something went wrong. #1</div>";
            header("Location: ../to_fpass.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "<div class='alert'>No email found!</div>";
        header("Location: ../to_fpass.php");
        exit(0);
    }

}

if(isset($_POST['passupdate_btn']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    $new_confirmpass = mysqli_real_escape_string($conn, $_POST['new_conpassword']);

    $token = mysqli_real_escape_string($conn, $_POST['password_token']);

    if(!empty($token))
    {
        if(!empty($token) && !empty($new_password) && !empty($new_confirmpass))
        {
            //To check if token is valid or not
            $check_token= "SELECT verify_token FROM users WHERE verify_token='$token' LIMIT 1";
            $check_token_run = mysqli_query($conn, $check_token);

            if (mysqli_num_rows($check_token_run) > 0) {
                //condition that will check if passwords match and if yes, database will be updated
                if ($new_password == $new_confirmpass) {
                    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~]{8,}$/', $new_password)) {
                        $_SESSION['status'] = "<div class='alert-danger'>Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character.</div>";
                        header("Location: ../change_pass.php?token=$token&email=$email");
                        exit(0);
                    }

                    // HASH THE PASSWORD BEFORE SAVING
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $update_pass = "UPDATE users SET password='$hashed_password' WHERE verify_token='$token' LIMIT 1";
                    $update_pass_run = mysqli_query($conn, $update_pass);

                    if ($update_pass_run) {
                        $_SESSION['status'] = "<div class='alert-success'>Password successfully updated!</div>";
                        header("Location: ../login_form.php");
                        exit(0);
                    } else {
                        $_SESSION['status'] = "<div class='alert'>Something went wrong. Password was not able to update.</div>";
                        header("Location: ../change_pass.php?token=$token&email=$email");
                        exit(0);
                    }
                } else {
                    $_SESSION['status'] = "<div class='alert'>Passwords do not match. Try again!</div>";
                    header("Location: ../change_pass.php?token=$token&email=$email");
                    exit(0);
                }
            }
        } else {
            $_SESSION['status'] = "<div class='alert'>Invalid token!</div>";
            header("Location: ../change_pass.php?token=$token&email=$email");
            exit(0);
        }
    } else {
        $_SESSION['status'] = "Please fill out all fields.";
        header("Location: ../change_pass.php?token=$token&email=$email");
        exit(0);
    }
} else {
    $_SESSION['status'] = "No token available";
    header("Location: ../change_pass.php");
    exit(0);
}




?>

    <!-- <h2>Reset Password</h2>
        <p>Hi <strong>{{name}}</strong>,</p>
        <p>We received a request to reset your password. If you made this request, please click the link below to reset your password:</p>
        <a href='http://localhost/Financial_Management/change_pass.php?token=$token&email=$get_email'>Click Here</a>
        <p>If you did not request for a reset password, you can safely ignore this email.</p> -->