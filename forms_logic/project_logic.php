<?php
session_start();

// To get the logged in user
$user_id = $_SESSION['auth_user']['id'] ?? null;
$user_role = $_SESSION['auth_user']['role'] ?? null;

if (!$user_id || !$user_role) {
    error_log("Missing user session details for logging activity.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['save_project'])) {
    error_log("Form submitted with save_project. POST data: " . print_r($_POST, true));
    error_log("User ID: $user_id, Role: $user_role");

    $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
    $project_name = mysqli_real_escape_string($connection, $_POST['project_name']); 
    $po_num = mysqli_real_escape_string($connection, $_POST['po_num']); 
    $start_date = mysqli_real_escape_string($connection, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($connection, $_POST['end_date']);
    $projected_budget_cost = (float) str_replace(',', '', $_POST['projected_budget_cost']);
    $project_type = mysqli_real_escape_string($connection, $_POST['project_type']);
    $allocated_budget      = (float) str_replace(',', '', $_POST['allocated_budget']);
    $materials_cost        = (float) str_replace(',', '', $_POST['materials_cost']);
    $labor_cost            = (float) str_replace(',', '', $_POST['labor_cost']);
    $other_expenses_cost   = (float) str_replace(',', '', $_POST['other_expenses_cost']);
    
     // **STEP 1: Validate Allocated Budget does not exceed Project Cost**
    if ($allocated_budget > $projected_budget_cost) {
        $_SESSION['danger'] = "Cannot submit: Total Allocated Budget Cost (₱" . number_format($allocated_budget, 2) . ") exceeds the Project Cost (₱" . number_format($projected_budget_cost, 2) . ").";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    // **STEP 1b: Validate Allocated Budget meets minimum requirement**
    if ($allocated_budget < 1000) {
        $_SESSION['danger'] = "Cannot submit: Total Allocated Budget Cost cannot be less than ₱1,000.00.";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    // **STEP 1c: Validate Project Cost meets minimum requirement**
    if ($projected_budget_cost < 1000) {
        $_SESSION['danger'] = "Cannot submit: Project Cost cannot be less than ₱1,000.00.";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    // Validate and process contract file FIRST (BLOCKING VALIDATION)
    $contract_data = null;
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['project_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'png', 'jpeg', 'jpg'];

        // Check file size (limit to 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['project_file']['size'] > $max_size) {
            $_SESSION['danger'] = "File size exceeds 5MB limit.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }

        // Check if file extension is allowed
        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['danger'] = "Invalid file format. Only PDF, PNG, JPEG, and JPG files are allowed.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }

        // Check if contract with same original filename already exists for this client
        $escaped_filename = mysqli_real_escape_string($connection, $file_name);
        $contract_check_query = "SELECT contract_id FROM contracts 
                                WHERE client_id = '$client_id' 
                                AND original_filename = '$escaped_filename'";
        $contract_check_result = mysqli_query($connection, $contract_check_query);

        if (mysqli_num_rows($contract_check_result) > 0) {
            $_SESSION['danger'] = "A contract with the filename '$file_name' already exists for this client. Please upload a different contract.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }

        // Upload and validate contract BEFORE creating project
        $upload_dir = '../uploads/contracts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Create temporary file for validation
        $temp_file_name = 'temp_contract_' . $client_id . '_' . time() . '.' . $file_ext;
        $temp_file_path = $upload_dir . $temp_file_name;

        if (move_uploaded_file($_FILES['project_file']['tmp_name'], $temp_file_path)) {
            error_log("Contract uploaded to temp path: " . $temp_file_path);

            // Store validated contract data for later use
            $contract_data = [
                'temp_path' => $temp_file_path,
                'file_name' => $file_name,
                'file_ext' => $file_ext
            ];
        } else {
            error_log("Contract upload failed");
            $_SESSION['danger'] = "Failed to upload contract file.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }
    }

    // **STEP 2: Business Logic Validations**

    // Check for project overlap
    $check_query = "SELECT * FROM projects 
                    WHERE client_id = '$client_id' 
                    AND project_name = '$project_name'
                    AND project_status != 'Completed'
                    AND ('$start_date' <= end_date AND '$end_date' >= start_date)";
    $check_result = mysqli_query($connection, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Clean up temp contract if exists
        if ($contract_data && file_exists($contract_data['temp_path'])) {
            unlink($contract_data['temp_path']);
        }
        $_SESSION['danger'] = "A similar project is already scheduled in this time range.";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    // **STEP 3: Generate PO number with global continuous counter**
    // $date_today = date("Ymd");

    // Find the total count of projects across ALL days
    $count_query = "
    SELECT COUNT(*) AS project_count 
    FROM projects
";
    $count_result = mysqli_query($connection, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $project_count = $count_row['project_count'] + 1;

    // Generate PO number
    // $po_num = "PO-$date_today-" . str_pad($project_count, 3, '0', STR_PAD_LEFT);

    // **STEP 4: Insert project into database**
    $insert_query = "INSERT INTO projects (
                        client_id, project_name, po_num, start_date, end_date, 
                        projected_budget_cost, project_type, allocated_budget, 
                        materials_cost, labor_cost, other_expenses_cost, 
                        project_status, user_approval, finance_approval, added_by
                    ) VALUES (
                        '$client_id', '$project_name', '$po_num', '$start_date', '$end_date', 
                        '$projected_budget_cost', '$project_type', '$allocated_budget', 
                        '$materials_cost', '$labor_cost', '$other_expenses_cost',
                        'Upcoming', 0, 0, '$user_id'
                    )";
    $insert_result = mysqli_query($connection, $insert_query);

    if ($insert_result) {
        $project_id = mysqli_insert_id($connection); // <-- get the new project ID

        // Notify Client
        require_once '../user/notification_functions.php';
        $message = "A new project $project_name requires your approval.";
        create_notification($connection, $client_id, $project_id, 'project_approval', $message);

        // Notify finance users with PO number
        $finance_query = "SELECT id FROM users WHERE role = 'finance'";
        $finance_result = mysqli_query($connection, $finance_query);

        while ($finance_user = mysqli_fetch_assoc($finance_result)) {
            $message = "New project <strong>$project_name</strong> has been created with P.O. Number <strong>$po_num</strong> and requires finance review.";
            create_notification($connection, $finance_user['id'], $project_id, 'po_review', $message);
        }
    }

    if (!$insert_result) {
        // Clean up temp contract if exists
        if ($contract_data && file_exists($contract_data['temp_path'])) {
            unlink($contract_data['temp_path']);
        }
        $_SESSION['warning'] = "Failed to add project. Please try again.";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    // **STEP 5: Insert activity log**
    $activity = "Added New Project ($project_name)";
    $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
    $log_stmt = mysqli_prepare($connection, $log_sql);
    if ($log_stmt) {
        mysqli_stmt_bind_param($log_stmt, "iiss", $user_id, $client_id, $user_role, $activity);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }

    // **STEP 6: Insert default project phases**
    $default_phases = [
        'Design & Permits',
        'Material Procurement',
        'Site Preparation',
        'Project Installation',
        'Final Inspection'
    ];
    foreach ($default_phases as $phase) {
        $stmt = $connection->prepare("INSERT INTO project_phases (project_id, phase_name, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("is", $project_id, $phase);
        $stmt->execute();
        $stmt->close();
    }

    // **STEP 7: Move validated contract to permanent location and save to database**
    if ($contract_data) {
        $new_file_name = 'contract_' . $client_id . '_' . $project_id . '_' . time() . '.' . $contract_data['file_ext'];
        $final_file_path = $upload_dir . $new_file_name;

        // Move from temp to permanent location
        if (rename($contract_data['temp_path'], $final_file_path)) {
            error_log("Contract moved to permanent location: " . $final_file_path);

            // Save contract record to database
            $original_filename = mysqli_real_escape_string($connection, $contract_data['file_name']);
            $insert_contract = "INSERT INTO contracts (client_id, project_id, file_path, original_filename) 
                               VALUES ('$client_id', '$project_id', '$final_file_path', '$original_filename')";

            if (mysqli_query($connection, $insert_contract)) {
                error_log("Contract record saved to database");
            } else {
                error_log("Failed to save contract record: " . mysqli_error($connection));
            }
        } else {
            error_log("Failed to move contract to permanent location");
        }
    }

    // **STEP 8: Send email notification**
    $client_query = "SELECT email, name FROM users WHERE client_id = '$client_id' LIMIT 1";
    $client_result = mysqli_query($connection, $client_query);
    $client_data = mysqli_fetch_assoc($client_result);
    $client_email = $client_data['email'];
    $client_name = $client_data['name'];

    // Send email with PHPMailer
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
        $mail->addAddress($client_email, $client_name);

        $mail->isHTML(true);
        $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
        $mail->Subject = 'New Project Created - P.O. Number Verification';
        $mail->Body = "
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
                                <p>A new project <strong>\"{$project_name}\"</strong> has been created under your name. To confirm this, you are required to verify and approve the contract in your dashboard. 
                                Kindly use the PO Number below to complete the approval process on your end. </p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;'>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>PO Number Details</div>
                                <p><strong>Project:</strong>{$project_name}</p>
                                <p><strong>P.O. Number:</strong> {$po_num}</p>
                            </div>
                            <div class='main-message' style='color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;'>
                            <p>Please login to your account and use this detail to process the pending project.
                            If you don't have any official project with the RVR Squared Mechanical Engineering Services, kindly ignore this message. 
                            </p>            
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
        $mail->AltBody = "Dear $client_name,\n\nA new project \"$project_name\" has been created.\nPO Number: $po_num\n\nPlease log in to approve it.";

        $mail->send();
        $email_status = "Email sent successfully!";
    } catch (Exception $e) {
        $email_status = "Email could not be sent. Error: " . $mail->ErrorInfo;
        error_log("Email error: " . $e->getMessage());
    }

    // **STEP 8b: Notify Finance Users**
    $finance_query = "SELECT email, name FROM users WHERE role = 'finance'";
    $finance_result = mysqli_query($connection, $finance_query);

    while ($finance_user = mysqli_fetch_assoc($finance_result)) {
        try {
            $finance_mail = new PHPMailer(true);
            $finance_mail->isSMTP();
            $finance_mail->Host = 'smtp.gmail.com';
            $finance_mail->SMTPAuth = true;
            $finance_mail->Username = 'cacorereservation@gmail.com';
            $finance_mail->Password = 'gdbsgyjujydcsqdb';
            $finance_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $finance_mail->Port = 587;

            $finance_mail->setFrom('cacorereservation@gmail.com', 'Financial Management System');
            $finance_mail->addAddress($finance_user['email'], $finance_user['name']);

            $finance_mail->isHTML(true);
            $mail->AddEmbeddedImage('../resources/images/email_header2.jpg', 'logo_cid');
            $finance_mail->Subject = 'New Project Requires Approval';
            $finance_mail->Body = "
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
                            <div class='greeting' style='font-size: 18px; color: #333; margin-bottom: 20px;'>Dear <strong>{$finance_user['name']},</strong></div>
                            
                            <div class='main-message' style='color: #555; font-size: 16px; margin-bottom: 10px; line-height: 1.8;'>
                                <p>A new project <strong>\"{$project_name}\"</strong> has been created for you. 
                                Kindly use the PO Number below to complete the approval process on your end. </p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;'>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>Project Approval Details</div>
                                <p><strong>Client:</strong> {$client_name}<br>
                                <strong>Project:</strong> {$project_name}<br>
                                <strong>P.O. Number:</strong> {$po_num}</p>
                            </div>
                            <div class='main-message' style='color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;'>
                            <p>Please login to your account and use these details to approve the pending project.</p>            
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
            $finance_mail->AltBody = "Dear {$finance_user['name']},\n\nA new project has been created and requires your approval.\n\nClient: {$client_name}\nProject: {$project_name}\nPO Number: {$po_num}\n\nPlease log in to review and approve it.";

            $finance_mail->send();
            error_log("Finance email sent to: " . $finance_user['email']);
        } catch (Exception $e) {
            error_log("Finance email error: " . $finance_mail->ErrorInfo);
        }
    }

    // **STEP 9: Set final success message with validation results**
    $final_message = "Project added successfully! " . $email_status;

    if (isset($_SESSION['validation_success'])) {
        $final_message .= "<br><br>" . $_SESSION['validation_success'];
        unset($_SESSION['validation_success']);
    }

    if (isset($_SESSION['validation_warnings'])) {
        $warning_list = "<ul style='margin-top:10px;'><li>" . implode("</li><li>", $_SESSION['validation_warnings']) . "</li></ul>";
        $final_message .= "<br><br>⚠️ Validation warnings:" . $warning_list;
        unset($_SESSION['validation_warnings']);
    }

    $_SESSION['status'] = $final_message;
    header("Location: ../inner_pages/projects.php?client_id=$client_id");
    exit();
}

// Extracting text from files function
// function extractTextFromFile($filePath)
// {
//     try {
//         $absolutePath = realpath($filePath);
//         if (!$absolutePath || !file_exists($absolutePath)) {
//             error_log("File not found for extraction: " . $filePath);
//             throw new Exception("File not found for text extraction.");
//         }

//         $file_ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

//         if ($file_ext === 'pdf') {
//             // Extract text from PDF using PDF parser
//             $parser = new \Smalot\PdfParser\Parser();
//             $pdf = $parser->parseFile($absolutePath);
//             $text = $pdf->getText();
//             error_log("PDF text extracted: " . substr($text, 0, 200) . "...");
//             return $text;
//         } elseif (in_array($file_ext, ['png', ' immigrant', 'jpeg'])) {
//             // Extract text from image using Tesseract OCR
//             $tempImgPath = sys_get_temp_dir() . '/' . uniqid('contract_ocr_') . '.' . $file_ext;
//             copy($absolutePath, $tempImgPath);

//             $ocrOutput = shell_exec("tesseract " . escapeshellarg($tempImgPath) . " stdout 2>&1");
//             unlink($tempImgPath);

//             $ocrText = trim(preg_replace('/\s+/', ' ', $ocrOutput));
//             error_log("OCR text extracted: " . substr($ocrText, 0, 200) . "...");
//             return $ocrText;
//         }

//         throw new Exception("Unsupported file format: " . $file_ext);
//     } catch (\Smalot\PdfParser\Exception\EmptyPdfException $e) {
//         error_log("Empty PDF file: " . $filePath);
//         throw new Exception("Empty PDF file; cannot extract text.");
//     } catch (Exception $e) {
//         error_log("Error extracting text from file: " . $e->getMessage());
//         throw $e;
//     }
// }

// Contract Validation using Gemini
// function validateContractWithGemini($contractText, $projectData)
// {
//     try {
//         // Load API key
//         $apiKey = 'AIzaSyAhtisPRDlr2WRn5-GBf8hyl8i9lmb52kI';
//         $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

//           // --- Normalize and prepare expected cost ---
//         $expectedCostRaw = str_replace([',', ' ', '₱', 'PHP', 'php'], '', $projectData['projected_budget_cost']);
//         $expectedCost = (float) $expectedCostRaw;

//         // --- Extract possible cost values from contract ---
//         $contractCostMatches = [];
//         preg_match_all('/(?:₱|PHP|php)?\s*([0-9,]+\.?[0-9]*)/i', $contractText, $contractCostMatches);

//         $contractCosts = [];
//         if (!empty($contractCostMatches[1])) {
//             foreach ($contractCostMatches[1] as $match) {
//                 $cleanCost = str_replace(',', '', $match);
//                 if (is_numeric($cleanCost)) {
//                     $contractCosts[] = (float) $cleanCost;
//                 }
//             }
//         }

//         // Check if expected cost is present manually
//         $costFound = in_array($expectedCost, $contractCosts, true);

//         error_log("DEBUG: Expected cost: {$expectedCost}");
//         error_log("DEBUG: Contract costs found: " . implode(', ', array_slice($contractCosts, -10)));
//         error_log("DEBUG: Cost match found: " . ($costFound ? 'YES' : 'NO'));

//         if (!$costFound) {
//             return [
//                 'is_match' => false,
//                 'reply' => "Cost mismatch. Expected PHP {$expectedCost}, but not found in contract. Found amounts: " . implode(', ', array_slice($contractCosts, -5))
//             ];
//         }

//         // --- Gemini validation (only cost) ---
//         $prompt = "You are validating a construction contract. Check if the following TOTAL UNIT COST is correctly included:\n\n"
//             . "TOTAL UNIT COST: PHP {$expectedCost}\n\n"
//             . "CONTRACT TEXT:\n"
//             . substr($contractText, 0, 2000) . "\n\n"
//             . "Validation notes:\n"
//             . "- The total unit cost should match exactly {$expectedCost}.\n"
//             . "- It may appear as {$expectedCost}, ₱{$expectedCost}, or PHP {$expectedCost}.\n"
//             . "- Accept if the amount is present in any valid format.\n\n"
//             . "Respond with 'YES - Cost matches' if the contract contains the expected cost.\n"
//             . "Respond with 'NO - Cost not found' if the cost is missing or different.";

//         $postData = [
//             'contents' => [[
//                 'parts' => [['text' => $prompt]]
//             ]],
//             'generationConfig' => [
//                 'temperature' => 0.1,
//                 'maxOutputTokens' => 150
//             ]
//         ];

//         // cURL request
//         $ch = curl_init($url);
//         curl_setopt_array($ch, [
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_POST => true,
//             CURLOPT_POSTFIELDS => json_encode($postData, JSON_UNESCAPED_UNICODE),
//             CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
//             CURLOPT_TIMEOUT => 30
//         ]);

//         $response = curl_exec($ch);
//         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//         $curlError = curl_error($ch);
//         curl_close($ch);

//         if ($httpCode !== 200 || !$response || $curlError) {
//             return [
//                 'is_match' => $costFound,
//                 'reply' => $costFound ? 'Manual validation passed - cost found in contract' : 'API validation failed'
//             ];
//         }

//         $json = json_decode($response, true);
//         if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
//             return [
//                 'is_match' => $costFound,
//                 'reply' => $costFound ? 'Manual validation passed - cost found in contract' : 'Invalid API response'
//             ];
//         }

//         $reply = trim($json['candidates'][0]['content']['parts'][0]['text']);
//         error_log("Gemini validation reply: " . $reply);

//         $isMatch = (stripos($reply, 'YES') === 0);

//         // Final fallback: accept if cost was found manually
//         if (!$isMatch && $costFound) {
//             $isMatch = true;
//             $reply .= " [Accepted - cost amount {$expectedCost} verified in contract]";
//         }

//         return [
//             'is_match' => $isMatch,
//             'reply' => $reply
//         ];

//     } catch (Exception $e) {
//         error_log("Contract validation error: " . $e->getMessage());
//         return [
//             'is_match' => false,
//             'reply' => 'Validation failed due to error: ' . $e->getMessage()
//         ];
//     }
// }


            // <p>Dear {$client_name},</p>
            // <p>A new project <strong>\"{$project_name}\"</strong> has been created for you.</p>
            // <p><strong>P.O. Number:</strong> {$po_num}</p>
            // <p>Please use this P.O. number to verify and approve the contract in your dashboard.</p>
            // <br>
            // <p>Regards,<br>Financial Management System Team</p>
            //  <p>Dear {$finance_user['name']},</p>
            //     <p>A new project has been created and requires your approval.</p>
            //     <p><strong>Client:</strong> {$client_name}<br>
            //     <strong>Project:</strong> {$project_name}<br>
            //     <strong>P.O. Number:</strong> {$po_num}</p>
            //     <p>Please log in to the Financial Management System to review and approve this project.</p>
            //     <br>
            //     <p>Regards,<br>Financial Management System Team</p>