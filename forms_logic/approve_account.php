<?php
session_start();
include('../dbcon.php');

require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$connection = $connection ?? $conn ?? null;
if (!$connection) {
    if (isset($conn)) $connection = $conn;
    if (isset($connection) === false && isset($con)) $connection = $con;
}

if (!$connection) {
    http_response_code(500);
    echo "Database connection not available.";
    exit;
}

$token = $_REQUEST['token'] ?? '';

if ($token === '') {
    http_response_code(400);
    echo "Invalid approval link.";
    exit;
}

// Fetch pending approval
$stmt = $connection->prepare("SELECT user_id, email, expires_at FROM pending_account_approvals WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($user_id, $email, $expires_at);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    echo "Approval link not found or already used.";
    exit;
}

if (strtotime($expires_at) < time()) {
    echo "Approval link has expired.";
    exit;
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    $p = '';
    for ($i = 0; $i < $length; $i++) $p .= $chars[random_int(0, strlen($chars)-1)];
    return $p;
}

// Handle POST (user submitted acceptance)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accepted = isset($_POST['accept_terms']) && ($_POST['accept_terms'] === '1' || $_POST['accept_terms'] === 'on');

    if (!$accepted) {
        $error = "You must accept the Terms and Conditions to activate your account.";
    } else {
        // Generate temporary password, update user, send email, delete pending, notify
        $temp_password = generateRandomPassword(12);
        $hashed = password_hash($temp_password, PASSWORD_DEFAULT);

        // Update users table
        $u = $connection->prepare("UPDATE users SET password = ?, verify_status = 1 WHERE user_id = ?");
        $u->bind_param("ss", $hashed, $user_id);
        $u->execute();
        $u->close();

        // Send credentials email
        try {
            // Get client name from database
            $name_query = $connection->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
            $name_query->bind_param("s", $user_id);
            $name_query->execute();
            $name_query->bind_result($client_name);
            $name_query->fetch();
            $name_query->close();
            
            if (empty($client_name)) {
                $client_name = 'User';
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'cacorereservation@gmail.com';
            $mail->Password = 'gdbsgyjujydcsqdb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('cacorereservation@gmail.com', 'Financial Management System');
            $mail->addAddress($email, $client_name);

            $mail->isHTML(true);
            $mail->AddEmbeddedImage(__DIR__ . '/../resources/images/email_header2.jpg', 'logo_cid');
            $mail->Subject = 'Your Account Has Been Activated';
            
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
                            <p>Great news! Your account for the RVR SMES Financial Management System has been successfully approved and is now active.</p>
                            <p>You can now log in using the credentials provided below.</p>
                        </div>
                        
                        <div class='highlight-box' style='
                            background-color: #f8f9ff;
                            border-left: 4px solid #2a5298;
                            padding: 20px;
                            margin: 25px 0;
                            border-radius: 4px;
                        '>
                            <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>
                                Account Credentials
                            </div>
                            <p><strong>Email:</strong> {$email}</p>
                            <p><strong>Temporary Password:</strong> {$temp_password}</p>
                        </div>
                        
                        <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                            <p>For security purposes, please log in at your earliest convenience and update your password immediately.</p>            
                        </div>
                        
                        <div style='text-align: center;'>
                            <a href='https://{$_SERVER['HTTP_HOST']}/login_form.php' class='cta-button' style='
                                display: inline-block;
                                background: linear-gradient(135deg, #2a5298, #1e3c72);
                                color: white;
                                padding: 12px 35px;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: bold;
                                margin: 25px 0;
                                transition: transform 0.2s ease;
                            '>Login to Your Account</a>
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
        } catch (Exception $e) {
            error_log("Credential email error: " . ($mail->ErrorInfo ?? $e->getMessage()));
        }

        // Remove pending record
        $d = $connection->prepare("DELETE FROM pending_account_approvals WHERE token = ?");
        $d->bind_param("s", $token);
        $d->execute();
        $d->close();

        // Find internal user id for notification
        $q = $connection->prepare("SELECT id FROM users WHERE user_id = ? LIMIT 1");
        $q->bind_param("s", $user_id);
        $q->execute();
        $q->bind_result($internal_id);
        $got = $q->fetch();
        $q->close();

        if ($got && !empty($internal_id)) {
            if (function_exists('create_notification')) {
                create_notification($connection, $internal_id, 0, 'account_approved', 'Your account has been approved and credentials were emailed to you.');
            }
        }

        // Show styled confirmation page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Account Approved</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
            <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">
            <link rel="stylesheet" href="resources/css/forms.css">
            <style>
                .success-card {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    padding: 3rem;
                    max-width: 600px;
                    margin: 2rem auto;
                    text-align: center;
                }
                .success-icon {
                    font-size: 4rem;
                    color: #28a745;
                    margin-bottom: 1rem;
                }
                .btn-login {
                    background: linear-gradient(135deg, #2a5298, #1e3c72);
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    border-radius: 8px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-login:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(42, 82, 152, 0.4);
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="card-header">
                <div class="logo-section d-flex align-items-center">
                    <img src="../resources/images/login_page_logo.png" alt="RVR Logo" class="logo-img ms-3" style="height:80px;">
                    <div class="company-info ms-2">
                        <div class="company-name">RVR SMES</div>
                        <div class="company-subtitle">Financial Management System</div>
                    </div>
                    <p id="datetime" class="ms-auto me-3 mt-4"></p>
                </div>
            </div>

            <div class="main-container">
                <div class="success-card">
                    <i class="bi bi-check-circle-fill success-icon"></i>
                    <h3 class="mb-3">Account Approved Successfully!</h3>
                    <p class="text-muted mb-4">
                        Your account is now active. Login credentials have been sent to<br>
                        <strong><?php echo htmlspecialchars($email); ?></strong>
                    </p>
                    <p class="text-muted mb-4">
                        Please check your email and use the provided credentials to log in.
                    </p>
                    <a href="/login_form.php" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Proceed to Login
                    </a>
                </div>
            </div>

            <script>
                function updateDateTime() {
                    const now = new Date();
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    };
                    document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
                }
                setInterval(updateDateTime, 1000);
                updateDateTime();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// If GET or acceptance error, show Terms & Conditions form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approval - Terms & Conditions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">
    <link rel="stylesheet" href="resources/css/forms.css">
    <style>
        .terms-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .terms-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            margin: 1.5rem 0;
        }
        
        .terms-box h5 {
            color: #2a5298;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .terms-box h5:first-child {
            margin-top: 0;
        }
        
        .terms-box p {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .terms-box ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .terms-box li {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .checkbox-container {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
        }
        
        .form-check-input:checked {
            background-color: #2a5298;
            border-color: #2a5298;
        }
        
        .form-check-label {
            color: #495057;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-accept {
            background: linear-gradient(135deg, #2a5298, #1e3c72);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 82, 152, 0.4);
            color: white;
        }
        
        .btn-accept:disabled {
            background: #6c757d;
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert-custom {
            border-left: 4px solid #dc3545;
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="card-header">
        <div class="logo-section d-flex align-items-center">
            <img src="../resources/images/login_page_logo.png" alt="RVR Logo" class="logo-img ms-3" style="height: 80px;">
            <div class="company-info ms-2">
                <div class="company-name">RVR SMES</div>
                <div class="company-subtitle">
                    Financial Management System
                </div>
            </div>
            <p id="datetime" class="ms-auto me-3 mt-4"></p>
        </div>
    </div>

    <div class="main-container">
        <div class="terms-card">
            <div class="form-title text-center">
                <i class="bi bi-shield-check"></i>
                Account Approval
            </div>
            <p class="form-subtitle text-center">
                To activate your account, please review and accept the Terms and Conditions below.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert-custom">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Terms and Conditions Box -->
            <div class="terms-box">
                <h5>1. Acceptance of Terms</h5>
                <p>By accessing and using the RVR SMES Financial Management System, you agree to be bound by these Terms and Conditions. If you do not agree to these terms, please do not use this system.</p>

                <h5>2. User Account Security</h5>
                <p>You are responsible for maintaining the confidentiality of your account credentials. You must:</p>
                <ul>
                    <li>Keep your password secure and confidential</li>
                    <li>Change your temporary password immediately upon first login</li>
                    <li>Not share your account with any other person</li>
                    <li>Notify the system administrator immediately of any unauthorized access</li>
                </ul>

                <h5>3. Proper Use of the System</h5>
                <p>You agree to use this system only for its intended purpose of financial management. Prohibited activities include:</p>
                <ul>
                    <li>Attempting to access data or accounts that do not belong to you</li>
                    <li>Interfering with the system's operation or security</li>
                    <li>Using the system for any illegal or unauthorized purpose</li>
                    <li>Transmitting any malicious code or harmful programs</li>
                </ul>

                <h5>4. Data Privacy and Protection</h5>
                <p>We are committed to protecting your personal information. Your data will be handled in accordance with applicable data protection laws. You understand that:</p>
                <ul>
                    <li>Your financial and personal data will be stored securely</li>
                    <li>System administrators may access your data for support purposes</li>
                    <li>Your information will not be shared with third parties without consent</li>
                </ul>

                <h5>5. System Availability</h5>
                <p>While we strive to maintain system availability, we do not guarantee uninterrupted access. The system may be temporarily unavailable due to maintenance, updates, or unforeseen circumstances.</p>

                <h5>6. Limitation of Liability</h5>
                <p>RVR SMES shall not be liable for any indirect, incidental, or consequential damages arising from your use of this system. Users are responsible for maintaining their own data backups.</p>

                <h5>7. Changes to Terms</h5>
                <p>We reserve the right to modify these Terms and Conditions at any time. Continued use of the system after changes constitutes acceptance of the modified terms.</p>

                <h5>8. Account Termination</h5>
                <p>We reserve the right to suspend or terminate accounts that violate these terms or engage in activities that compromise system security or integrity.</p>
            </div>

            <!-- Acceptance Checkbox -->
            <div class="checkbox-container">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_terms" name="accept_terms" onchange="toggleButton()" <?php echo isset($_POST['accept_terms']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="accept_terms">
                        I have read, understood, and agree to abide by the Terms and Conditions outlined above.
                    </label>
                </div>
            </div>

            <!-- Form -->
            <form method="post" id="approvalForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="accept_terms" id="accept_terms_hidden" value="">
                
                <button type="submit" class="btn btn-accept w-100" id="submitBtn" disabled>
                    <i class="bi bi-check-circle me-2"></i>
                    Accept & Activate Account
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted" style="font-size: 0.9rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    If you did not request this account, you may safely ignore this page.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        function toggleButton() {
            const checkbox = document.getElementById('accept_terms');
            const submitBtn = document.getElementById('submitBtn');
            const hiddenInput = document.getElementById('accept_terms_hidden');
            
            if (checkbox.checked) {
                submitBtn.disabled = false;
                hiddenInput.value = '1';
            } else {
                submitBtn.disabled = true;
                hiddenInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleButton();
        });
    </script>
</body>
</html>