<?php
session_start();

// To get the logged-in user
$user_id = $_SESSION['auth_user']['id'] ?? null;
$user_role = $_SESSION['auth_user']['role'] ?? null;

if (!$user_id || !$user_role) {
    error_log("Missing user session details for logging activity.");
}
error_log("DEBUG project_logic.php invoked. POST params: " . print_r($_POST, true));

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Database connection
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require __DIR__ . '/../vendor/autoload.php'; // Composer autoload for PDF parser

// --- ADD PROJECT ADDON ---
if (isset($_POST['save_project_addon'])) {
    $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
    $parent_project_id = mysqli_real_escape_string($connection, $_POST['parent_project_id']);
    $addon_name = mysqli_real_escape_string($connection, $_POST['addon_name']);
    $projected_budget_cost = (float) str_replace(',', '', $_POST['projected_budget_cost']);
    $project_type = mysqli_real_escape_string($connection, $_POST['project_type']);
    $allocated_budget = (float) str_replace(',', '', $_POST['allocated_budget']);
    $materials_cost = (float) str_replace(',', '', $_POST['materials_cost']);
    $labor_cost = (float) str_replace(',', '', $_POST['labor_cost']);
    $other_expenses_cost = (float) str_replace(',', '', $_POST['other_expenses_cost']);

    // Get parent project PO number
    $parent_query = "SELECT po_num FROM projects WHERE project_id = '$parent_project_id' LIMIT 1";
    $parent_result = mysqli_query($connection, $parent_query);

    if ($parent_row = mysqli_fetch_assoc($parent_result)) {
        $parent_po = $parent_row['po_num']; // Example: PO-20250814-005

        // Extract date and sequence from parent PO
        preg_match('/PO-(\d{8})-(\d{3})/', $parent_po, $matches);
        $po_date = $matches[1] ?? date('Ymd');
        $po_seq  = $matches[2] ?? '001';

        // Find latest add-on number for this parent project
        $addon_query = "
        SELECT po_number 
        FROM project_addons 
        WHERE project_id = '$parent_project_id' 
          AND po_number LIKE 'PO-$po_date-$po_seq-A%' 
        ORDER BY po_number DESC 
        LIMIT 1
    ";
        $addon_result = mysqli_query($connection, $addon_query);

        if ($addon_row = mysqli_fetch_assoc($addon_result)) {
            // Extract last add-on number and increment
            preg_match('/A(\d+)/', $addon_row['po_number'], $a_matches);
            $last_addon_num = (int)($a_matches[1] ?? 0);
            $new_addon_num  = str_pad($last_addon_num + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $new_addon_num = '01';
        }

        // Final new PO number for add-on
        $new_po_number = "PO-$po_date-$po_seq-A$new_addon_num";
    } else {
        die("Parent project not found or missing PO number.");
    }

    // **STEP 1: COMPREHENSIVE CONTRACT VALIDATION (BLOCKING)**
    $contract_data = null;
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['project_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'png'];

        // Check file size (limit to 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['project_file']['size'] > $max_size) {
            $_SESSION['max_contract_error'] = "File size exceeds 5MB limit.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }

        // Check if file extension is allowed
        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['contract_error'] = "Invalid file format. Only PDF and PNG files are allowed.";
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
            $_SESSION['contract_error'] = "A contract with the filename '$file_name' already exists for this client. Please upload a different contract.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }

        // Upload and validate contract BEFORE creating add-on
        $upload_dir = '../uploads/project_addons/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Create temporary file for validation
        $temp_file_name = 'temp_addon_contract_' . $client_id . '_' . time() . '.' . $file_ext;
        $temp_file_path = $upload_dir . $temp_file_name;

        if (move_uploaded_file($_FILES['project_file']['tmp_name'], $temp_file_path)) {
            error_log("Add-on contract uploaded to temp path: " . $temp_file_path);

            // Extract text from contract (PDF or Image)
            $contractText = extractTextFromFile($temp_file_path);
            error_log("Add-on contract text extracted: " . substr($contractText, 0, 200) . "...");

            if (!empty($contractText)) {
                $contractValidation = validateContractWithGemini($contractText, [
                    'addon_name' => $addon_name,
                    'projected_budget_cost' => $projected_budget_cost
                ]);

                if (!$contractValidation['is_match']) {
                    unlink($temp_file_path); // Clean temp file
                    
                    $rawReply = strtolower($contractValidation['reply']);
                    
                    // More specific error messages based on AI response
                    if (strpos($rawReply, 'addon name') !== false && strpos($rawReply, 'cost') !== false) {
                        $specificError = "Both the add-on name and cost in the contract do not match our records.";
                    } elseif (strpos($rawReply, 'addon name') !== false || strpos($rawReply, 'project name') !== false) {
                        $specificError = "The add-on name in the contract does not match the recorded add-on name.";
                    } elseif (strpos($rawReply, 'cost') !== false || strpos($rawReply, 'budget') !== false) {
                        $specificError = "The cost/budget amount in the contract does not match the expected amount.";
                    } elseif (strpos($rawReply, 'signatory') !== false || strpos($rawReply, 'signature') !== false) {
                        $specificError = "The signatories in the contract do not match the expected signatories.";
                    } elseif (strpos($rawReply, 'company') !== false) {
                        $specificError = "The company name in the contract does not match our records.";
                    } else {
                        $specificError = "The contract details do not align with the add-on information.";
                    }
                    
                    $_SESSION['contract_error'] = 
                        "$specificError Please ensure the contract contains the correct add-on details and try uploading again.";
                    
                    header("Location: ../inner_pages/projects.php?client_id=$client_id");
                    exit();
                }

                $_SESSION['validation_success'] = "✅ Contract validation successful: " . $contractValidation['reply'];
            } else {
                unlink($temp_file_path);
                $_SESSION['contract_error'] = "⚠️ Could not extract text from contract.";
                header("Location: ../inner_pages/projects.php?client_id=$client_id");
                exit();
            }

            // Store validated contract data for later use
            $contract_data = [
                'temp_path' => $temp_file_path,
                'file_name' => $file_name,
                'file_ext' => $file_ext
            ];
        } else {
            error_log("Add-on contract upload failed");
            $_SESSION['contract_error'] = "Failed to upload contract file.";
            header("Location: ../inner_pages/projects.php?client_id=$client_id");
            exit();
        }
    }

    // **STEP 2: Insert into project_addons**
    $query = "INSERT INTO project_addons (
        project_id, addon_name, 
        projected_budget_cost, project_type, allocated_budget, 
        materials_cost, labor_cost, other_expenses_cost, 
        po_number, created_at
      ) VALUES (
        '$parent_project_id', '$addon_name', 
        '$projected_budget_cost', '$project_type', '$allocated_budget',
        '$materials_cost', '$labor_cost', '$other_expenses_cost',
        '$new_po_number', NOW()
      )";

    $insert_result = mysqli_query($connection, $query);

    if (!$insert_result) {
        // Clean up temp contract if exists
        if ($contract_data && file_exists($contract_data['temp_path'])) {
            unlink($contract_data['temp_path']);
        }
        $_SESSION['status'] = "Failed to add project add-on. Please try again.";
        header("Location: ../inner_pages/projects.php?client_id=$client_id");
        exit();
    }

    $addon_id = mysqli_insert_id($connection);

    // **STEP 3: Insert activity log**
    if ($user_id && $user_role) {
        $activity = "Added New Project Add-on ($addon_name)";
        $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = mysqli_prepare($connection, $log_sql);
        if ($log_stmt) {
            mysqli_stmt_bind_param($log_stmt, "iiss", $user_id, $client_id, $user_role, $activity);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }

    // **STEP 4: Move validated contract to permanent location and save to database**
    if ($contract_data) {
        $new_file_name = 'addon_contract_' . $parent_project_id . '_' . $addon_id . '_' . time() . '.' . $contract_data['file_ext'];
        $final_file_path = $upload_dir . $new_file_name;

        // Move from temp to permanent location
        if (rename($contract_data['temp_path'], $final_file_path)) {
            error_log("Add-on contract moved to permanent location: " . $final_file_path);

            // Save contract record to database
            $original_filename = mysqli_real_escape_string($connection, $contract_data['file_name']);
            $insert_contract = "INSERT INTO contracts (client_id, project_id, file_path, original_filename) 
                               VALUES ('$client_id', '$parent_project_id', '$final_file_path', '$original_filename')";

            if (mysqli_query($connection, $insert_contract)) {
                error_log("Add-on contract record saved to database");
            } else {
                error_log("Failed to save add-on contract record: " . mysqli_error($connection));
            }
        } else {
            error_log("Failed to move add-on contract to permanent location");
        }
    }

    // **STEP 5: Send email notification to client**
    $client_sql = "SELECT email, client_name FROM clients WHERE client_id = '$client_id' LIMIT 1";
    $client_result = mysqli_query($connection, $client_sql);

    if ($client_row = mysqli_fetch_assoc($client_result)) {
        $client_email = $client_row['email'];
        $client_name = $client_row['client_name'];

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
            $mail->Subject = "New Project Add-on Added to Your Project";
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
                                <p>We are pleased to inform you that a new <strong>Project Add-on</strong> has been added to your existing project.
                                Below are the details of your additional project with RVR SMES.</p>
                            </div>
                             <div class='highlight-box' style='
                                background-color: #f8f9ff;
                                border-left: 4px solid #2a5298;
                                padding: 20px;
                                margin: 25px 0;
                                border-radius: 4px;'>
                                <div class='highlight-title' style='font-weight: bold; color: #1e3c72; margin-bottom: 10px;'>Project Add-on Details</div>
                                <p><strong>Add-on Name:</strong> {$addon_name}<br>
                                    <strong>Projected Budget:</strong> {$projected_budget_cost}<br>
                                    <strong>PO Number:</strong> {$new_po_number}
                                </p>
                            </div>
                            <div class='main-message' style='color: #555;font-size: 16px; margin-bottom: 10px;line-height: 1.8;'>
                            <p>For any questions or further assistance, please contact our team.</p>            
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
            $mail->AltBody = "Dear $client_name,\n\nA new project add-on \"$addon_name\" has been added.\nPO Number: $new_po_number\n\nPlease log in to view details.";

            $mail->send();
            $email_status = "Email sent successfully!";
        } catch (Exception $e) {
            $email_status = "Email could not be sent. Error: " . $mail->ErrorInfo;
            error_log("Add-on email error: " . $e->getMessage());
        }
    }

    // **STEP 6: Set final success message with validation results**
    $final_message = "Project Add-on added successfully! " . ($email_status ?? '');

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

// **STANDARDIZED HELPER FUNCTIONS** (matching main project_logic.php)

// Extracting text from files function
function extractTextFromFile($filePath)
{
    try {
        $absolutePath = realpath($filePath);
        if (!$absolutePath || !file_exists($absolutePath)) {
            error_log("File not found for extraction: " . $filePath);
            throw new Exception("File not found for text extraction.");
        }

        $file_ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($file_ext === 'pdf') {
            // Extract text from PDF using PDF parser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();
            error_log("PDF text extracted: " . substr($text, 0, 200) . "...");
            return $text;
        } elseif (in_array($file_ext, ['png', 'jpg', 'jpeg'])) {
            // Extract text from image using Tesseract OCR
            $tempImgPath = sys_get_temp_dir() . '/' . uniqid('contract_ocr_') . '.' . $file_ext;
            copy($absolutePath, $tempImgPath);

            $ocrOutput = shell_exec("tesseract " . escapeshellarg($tempImgPath) . " stdout 2>&1");
            unlink($tempImgPath);

            $ocrText = trim(preg_replace('/\s+/', ' ', $ocrOutput));
            error_log("OCR text extracted: " . substr($ocrText, 0, 200) . "...");
            return $ocrText;
        }

        throw new Exception("Unsupported file format: " . $file_ext);
    } catch (\Smalot\PdfParser\Exception\EmptyPdfException $e) {
        error_log("Empty PDF file: " . $filePath);
        throw new Exception("Empty PDF file; cannot extract text.");
    } catch (Exception $e) {
        error_log("Error extracting text from file: " . $e->getMessage());
        throw $e;
    }
}

// Contract Validation using Gemini (standardized to match main project validation)
function validateContractWithGemini($contractText, $projectData)
{
    try {
        // Load API key
        $apiKey = 'AIzaSyAhtisPRDlr2WRn5-GBf8hyl8i9lmb52kI';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        // --- Normalize and prepare expected cost ---
        $expectedCostRaw = str_replace([',', ' ', '₱', 'PHP', 'php'], '', $projectData['projected_budget_cost']);
        $expectedCost = (float) $expectedCostRaw;

        // --- Extract possible cost values from contract ---
        $contractCostMatches = [];
        preg_match_all('/(?:₱|PHP|php)?\s*([0-9,]+\.?[0-9]*)/i', $contractText, $contractCostMatches);

        $contractCosts = [];
        if (!empty($contractCostMatches[1])) {
            foreach ($contractCostMatches[1] as $match) {
                $cleanCost = str_replace(',', '', $match);
                if (is_numeric($cleanCost)) {
                    $contractCosts[] = (float) $cleanCost;
                }
            }
        }

        // Check if expected cost is present manually
        $costFound = in_array($expectedCost, $contractCosts, true);

        error_log("DEBUG: Expected cost: {$expectedCost}");
        error_log("DEBUG: Contract costs found: " . implode(', ', array_slice($contractCosts, -10)));
        error_log("DEBUG: Cost match found: " . ($costFound ? 'YES' : 'NO'));

        if (!$costFound) {
            return [
                'is_match' => false,
                'reply' => "Cost mismatch. Expected PHP {$expectedCost}, but not found in contract. Found amounts: " . implode(', ', array_slice($contractCosts, -5))
            ];
        }

        // --- Gemini validation (focusing on cost validation like main project) ---
        $addonName = $projectData['addon_name'] ?? 'Add-on';
        $prompt = "You are validating a construction contract for a project add-on. Check if the following TOTAL UNIT COST is correctly included:\n\n"
            . "ADD-ON: $addonName\n"
            . "TOTAL UNIT COST: PHP {$expectedCost}\n\n"
            . "CONTRACT TEXT:\n"
            . substr($contractText, 0, 2000) . "\n\n"
            . "Validation notes:\n"
            . "- The total unit cost should match exactly {$expectedCost}.\n"
            . "- It may appear as {$expectedCost}, ₱{$expectedCost}, or PHP {$expectedCost}.\n"
            . "- Accept if the amount is present in any valid format.\n\n"
            . "Respond with 'YES - Cost matches' if the contract contains the expected cost.\n"
            . "Respond with 'NO - Cost not found' if the cost is missing or different.";

        $postData = [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 150
            ]
        ];

        // cURL request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response || $curlError) {
            return [
                'is_match' => $costFound,
                'reply' => $costFound ? 'Manual validation passed - cost found in contract' : 'API validation failed'
            ];
        }

        $json = json_decode($response, true);
        if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'is_match' => $costFound,
                'reply' => $costFound ? 'Manual validation passed - cost found in contract' : 'Invalid API response'
            ];
        }

        $reply = trim($json['candidates'][0]['content']['parts'][0]['text']);
        error_log("Gemini validation reply: " . $reply);

        $isMatch = (stripos($reply, 'YES') === 0);

        // Final fallback: accept if cost was found manually
        if (!$isMatch && $costFound) {
            $isMatch = true;
            $reply .= " [Accepted - cost amount {$expectedCost} verified in contract]";
        }

        return [
            'is_match' => $isMatch,
            'reply' => $reply
        ];

    } catch (Exception $e) {
        error_log("Contract validation error: " . $e->getMessage());
        return [
            'is_match' => false,
            'reply' => 'Validation failed due to error: ' . $e->getMessage()
        ];
    }
}

?>


            <!--// <html>-->
            <!--// <body>-->
            <!--//     <p>Dear {$client_name},</p>-->
            <!--//     <p>We are pleased to inform you that a new <strong>Project Add-on</strong> has been added to your existing project.</p>-->
            <!--//     <p><strong>Add-on Name:</strong> {$addon_name}<br>-->
            <!--//     <strong>Start Date:</strong> {$start_date}<br>-->
            <!--//     <strong>End Date:</strong> {$end_date}<br>-->
            <!--//     <strong>Projected Budget:</strong> {$projected_budget_cost}</p>-->
            <!--//     <p>If you have any questions, please contact our team.</p>-->
            <!--//     <p>Thank you,<br>Your Company Name</p>-->
            <!--// </body>-->
            <!--// </html>-->