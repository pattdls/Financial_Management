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

// Generate a secure random password
function generateRandomPassword($length = 10)
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// $connection = mysqli_connect("localhost", "root", "", "financial_management");
// if (!$connection) {
//     die("Database connection failed: " . mysqli_connect_error());
// }

if (isset($_POST['save_data'])) {

    // Client Info
    $client_type = mysqli_real_escape_string($conn, $_POST['client_type']);
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $street_address = mysqli_real_escape_string($conn, $_POST['street_address']);
    $zip_code = mysqli_real_escape_string($conn, $_POST['zip_code']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);

    // Contact Info - ensure these are arrays and avoid undefined index notices
    $first_names   = isset($_POST['first_name'])   ? (array) $_POST['first_name']   : [];
    $middle_names  = isset($_POST['middle_name'])  ? (array) $_POST['middle_name']  : [];
    $last_names    = isset($_POST['last_name'])    ? (array) $_POST['last_name']    : [];
    $suffix_names  = isset($_POST['suffix_name'])  ? (array) $_POST['suffix_name']  : [];
    $emails        = isset($_POST['email'])        ? (array) $_POST['email']        : [];
    $phone_numbers = isset($_POST['phone_number']) ? (array) $_POST['phone_number'] : [];
    $designations  = isset($_POST['designation'])  ? (array) $_POST['designation']  : [];

    // Check for duplicate client
    $check_client_query = "SELECT * FROM clients WHERE street_address = '$street_address' AND province = '$province' AND city = '$city' AND barangay = '$barangay'";
    $check_client_result = mysqli_query($conn, $check_client_query);

    if (mysqli_num_rows($check_client_result) > 0) {
        $_SESSION['danger'] = "A client with the same address already exists!";
        header("Location: ../inner_pages/clients.php");
        exit();
    }

    // Email duplication check function
    function emailExists($conn, $email)
    {
        $email = mysqli_real_escape_string($conn, $email);
        $user_check = mysqli_query($conn, "SELECT 1 FROM users WHERE email = '$email' LIMIT 1");
        if (mysqli_num_rows($user_check) > 0) return true;

        $contact_check = mysqli_query($conn, "SELECT 1 FROM contacts WHERE email = '$email' LIMIT 1");
        if (mysqli_num_rows($contact_check) > 0) return true;

        return false;
    }

    // Pre-validate emails
    if ($client_type == 'Company') {
        for ($i = 0; $i < count($emails); $i++) {
            $email = mysqli_real_escape_string($conn, $emails[$i]);
            if (!empty($email) && emailExists($conn, $email)) {
                $_SESSION['danger'] = "The email '$email' is already in use.";
                header("Location: ../inner_pages/clients.php");
                exit();
            }
        }
    } else {
        $email = mysqli_real_escape_string($conn, $emails[0]);
        if (!empty($email) && emailExists($conn, $email)) {
            $_SESSION['danger'] = "The email '$email' is already in use.";
            header("Location: ../inner_pages/clients.php");
            exit();
        }
    }

    // To get the logged in user 
    $added_by = $_SESSION['auth_user']['id'] ?? null;

    // Insert Client
    $insert_client = "INSERT INTO clients (client_type, client_name, street_address, zip_code, province, city, barangay, added_by) VALUES ('$client_type', '$client_name', '$street_address', '$zip_code', '$province', '$city', '$barangay' ,'$added_by')";

    if (mysqli_query($conn, $insert_client)) {

        $client_id = mysqli_insert_id($conn);
        $email_to = '';
        $default_password = generateRandomPassword();
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $role = 'User';
        $verify_status = 1;

        // Generate Custom User ID
        $date = date('Ymd');
        $role_prefix = 'USR';
        $count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'User' AND DATE(created_at) = CURDATE()";
        $count_result = mysqli_query($conn, $count_query);
        $count_row = mysqli_fetch_assoc($count_result);
        $count = $count_row['count'] + 1;

        $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);
        $generated_user_id = "RVR-$role_prefix-$date-$sequence";

        if ($client_type == 'Company') {
            // Insert Contacts (use safe indexing and cast to string to avoid null warnings)
            $iterations = max(count($first_names), count($emails));
            for ($i = 0; $i < $iterations; $i++) {
                $first = mysqli_real_escape_string($conn, (string)($first_names[$i] ?? ''));
                $middle = mysqli_real_escape_string($conn, (string)($middle_names[$i] ?? ''));
                $last = mysqli_real_escape_string($conn, (string)($last_names[$i] ?? ''));
                $suffix = mysqli_real_escape_string($conn, (string)($suffix_names[$i] ?? ''));
                $email = mysqli_real_escape_string($conn, (string)($emails[$i] ?? ''));
                $phone = mysqli_real_escape_string($conn, (string)($phone_numbers[$i] ?? ''));
                $designation = mysqli_real_escape_string($conn, (string)($designations[$i] ?? ''));

                if (empty($first) && empty($last) && empty($email)) continue;

                $insert_contact = "INSERT INTO contacts (client_id, first_name, middle_name, last_name, suffix_name, email, phone_number, designation) 
                           VALUES ('$client_id', '$first', '$middle', '$last', '$suffix', '$email', '$phone', '$designation')";
                mysqli_query($conn, $insert_contact);

                // Use first contact person for user account + save in clients table
                if ($i == 0 && !empty($email)) {
                    $email_to = $email;

                    // Add this so clients table gets the first contact email + phone
                    $update_company = "UPDATE clients SET email = '$email_to', phone_number = '$phone' WHERE client_id = '$client_id'";
                    mysqli_query($conn, $update_company);
                }
            }
        } else { // Individual
            $email_to = mysqli_real_escape_string($conn, (string)($emails[0] ?? ''));
            $phone = mysqli_real_escape_string($conn, (string)($phone_numbers[0] ?? ''));

            $update_individual = "UPDATE clients SET email = '$email_to', phone_number = '$phone' WHERE client_id = '$client_id'";
            mysqli_query($conn, $update_individual);
        }

        // Create User Account (set pending)
        if (!empty($email_to)) {
            $verify_status = 0; // pending - client must approve
            $insert_user = "INSERT INTO users (user_id, name, email, password, role, verify_status, client_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_user = mysqli_prepare($conn, $insert_user);
            mysqli_stmt_bind_param($stmt_user, "sssssis", $generated_user_id, $client_name, $email_to, $hashed_password, $role, $verify_status, $client_id);
            mysqli_stmt_execute($stmt_user);
            mysqli_stmt_close($stmt_user);

            // create a pending approval token (do NOT store plain password)
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
            $pstmt = $conn->prepare("INSERT INTO pending_account_approvals (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
            $pstmt->bind_param("ssss", $generated_user_id, $email_to, $token, $expires_at);
            $pstmt->execute();
            $pstmt->close();

            // Send Approval Request Email (no credentials)
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $approveUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/approve_account.php?token=' . urlencode($token);

            // Replace the existing PHPMailer email section in code.php with this:

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'cacorereservation@gmail.com';
    $mail->Password = 'gdbsgyjujydcsqdb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('cacorereservation@gmail.com', 'Financial Management System');
    $mail->addAddress($email_to, $client_name);

    $mail->isHTML(true);
    $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
    $mail->Subject = 'Please Approve Your Account';

    $mail->Body = "
        <div class='email-container' style='
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        '>
            <!-- Header Section -->
            <div class='header' style='
                display: flex;
                padding: 0;
                height: 155px;
                justify-content: center;
                align-items: center;
            '>
                <div class='logo' style='
                    width: 400px;
                    height: 150px;
                '>
                    <img src='cid:logo_cid' alt='Company Logo' style='width: 400px; height: 150px;'>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class='content' style='padding: 40px 30px;'>
                <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>
                    Dear <strong>{$client_name},</strong>
                </div>
                
                <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                    <p>Your account for the RVR SMES Financial Management System has been created and requires your approval before you can access it.</p>
                    <p>Please click the button below to approve your account and receive your login credentials via email.</p>
                </div>

                <div style='text-align: center;'>
                    <a href='{$approveUrl}' class='cta-button' style='
                        display: inline-block;
                        background: linear-gradient(135deg, #2a5298, #1e3c72);
                        color: white;
                        padding: 12px 35px;
                        text-decoration: none;
                        border-radius: 5px;
                        font-weight: bold;
                        margin: 25px 0;
                        transition: transform 0.2s ease;
                    '>Approve Account</a>
                </div>

                <div class='highlight-box' style='
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 4px;
                '>
                    <div class='highlight-title' style='font-weight: bold; color: #856404; margin-bottom: 10px;'>
                        Important Notice
                    </div>
                    <p style='color: #856404; margin: 0;'>
                        This approval link will expire in 7 days. If you did not request this account, please ignore this email.
                    </p>
                </div>

                <div style='margin-top: 30px;'>
                    <p style='color: #555;'>
                        Best regards,<br>
                        RVR SMES Financial Management System
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class='footer' style='
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #e9ecef;
            '>
                <p>&copy; 2025 RVR SMES Financial Management. All rights reserved.</p>
                <p>This email contains confidential system account information intended only for the named recipient.</p>
            </div>
        </div>
    ";

    $mail->send();
    $_SESSION['status'] = "Client and user account created successfully! Approval email sent.";
} catch (Exception $e) {
    // log error but continue
    error_log("Approval email error: " . $mail->ErrorInfo);
    $_SESSION['status'] = "Client created, but approval email could not be sent.";
}
        }

        header("Location: ../inner_pages/clients.php");
        exit();
        
    } else {
        die("Error inserting client: " . mysqli_error($conn));
    }
}

// <html>
//                 <body>
//                 <h2>Welcome, $client_name!</h2>
//                 <p>Your account has been created. Use the following credentials:</p>
//                 <ul>
//                     <li><strong>Email:</strong> $email_to</li>
//                     <li><strong>Temporary Password:</strong> $default_password</li>
//                 </ul>
//                 <p><a href='https://yourwebsite.com/login'>Click here to login</a></p>
//                 </body>
//                 </html>
