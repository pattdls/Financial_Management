<?php

error_reporting(E_ALL);
ini_set('display_errors', 1); // Don't display errors
ini_set('log_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include('../dbcon.php');
/** @var mysqli $conn */
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Capture any errors/warnings
ob_start();

ini_set('error_log', __DIR__ . '/php_errors.log');

// To get the logged in user 
$user_id = $_SESSION['auth_user']['id'] ?? null;
$conn->query("SET time_zone = '+08:00'");
$now = date('Y-m-d H:i:s'); // This is PH time

//To fetch data from the expense form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handling the first form (Expense recording for a project)
    if (isset($_POST['save_expense'])) {
        $date = date('Y-m-d', strtotime($_POST['date']));
        $recorder_id = $_SESSION['auth_user']['id'] ?? null;
        $client = $_POST['client_id'];
        $main_project_id = $_POST['main_project_id'] ?? null;
        $addon_id = $_POST['addon_id'] ?? null;
        $description_raw = $_POST['item_name'];
        // This is to save both category/account number and category/account title
        $category_num = $_POST['category_num'];

        // Fetch the category (account title) from chart_accounts
        $category_num_val = $category_num ?? null;
        $category = null;
        if ($category_num_val) {
            $cat_query = mysqli_query($conn, "SELECT category FROM chart_accounts WHERE account_num = '$category_num_val' LIMIT 1");
            $cat_row = mysqli_fetch_assoc($cat_query);
            $category = $cat_row['category'] ?? null;
        }

        $invoice_num = trim($_POST['invoice_num']);
        $store_name_raw = $_POST['store_name'];
        $raw_amount = $_POST['amount'];
        $amount = str_replace([',', ' '], '', $raw_amount);
        $payment_method = $_POST['payment_method'];
        $receipt_file = $_FILES['receipt_file']['name'];

        $frontend_errors_json = $_POST['frontend_errors'] ?? '{}';
        $frontend_errors = json_decode($frontend_errors_json, true);
        if (!is_array($frontend_errors)) $frontend_errors = ['description' => '', 'storeName' => '', 'amount' => ''];

        $invoice_error = "";
        $file_error = "";
        $general_error = "";

        // Check for duplicate invoice
        if (trim(strtolower($invoice_num)) !== strtolower("No Invoice Number Required")) {
            $check_invoice_query = "SELECT * FROM expenses WHERE invoice_num = '$invoice_num'";
            $check_invoice_query_run = mysqli_query($conn, $check_invoice_query);
            if (mysqli_num_rows($check_invoice_query_run) > 0) {
                $invoice_error = "Invoice number <strong>" . htmlspecialchars($invoice_num) . "</strong> already exists.";
            }
        }

        // Check for duplicate file
        $finalFilePath = "../receipts/project_expenses_files/" . $receipt_file;
        if (file_exists($finalFilePath)) {
            $file_error = "The file <strong>" . htmlspecialchars($receipt_file) . "</strong> already exists.";
        }

        $description = trim(preg_replace('/[^\w\s]/', '', $description_raw));
        $store_name =  trim(preg_replace('/[^\w\s]/', '', $store_name_raw));

        $hasFrontendError = trim($frontend_errors['description']) !== "" || trim($frontend_errors['storeName']) !== "" || trim($frontend_errors['amount']) !== "";

        if ($hasFrontendError || $invoice_error !== "" || $file_error !== "") {
            echo json_encode([
                'status' => 'error',
                'frontend_errors' => $frontend_errors,
                'invoice_error' => $invoice_error,
                'file_error' => $file_error
            ]);
            exit;
        }

        // If just validation scan, return success
        if (isset($_POST['validate_only_scan']) && $_POST['validate_only_scan'] === '1') {
            echo json_encode(['status' => 'success']);
            exit;
        }

        // Proceed with insert
        if (isset($_POST['validate_only_scan']) && $_POST['validate_only_scan'] === '0') {
            $insert_query = "INSERT INTO expenses (user_id, client_id, project_id,addon_id, date, description, category, category_num, invoice_num, store_name, amount, payment_method, receipt_file, date_verify, created_at) 
                         VALUES ('$recorder_id', '$client', '$main_project_id', '$addon_id', '$date', '$description', '$category', '$category_num', '$invoice_num', '$store_name', '$amount', '$payment_method', '$receipt_file', '$now', '$now')";
            $insert_query_run = mysqli_query($conn, $insert_query);

            if ($insert_query_run) {
                if (move_uploaded_file($_FILES["receipt_file"]["tmp_name"], $finalFilePath)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Expense recorded successfully!'
                    ]);
                } else {
                    // Rollback if file failed to move
                    mysqli_query($conn, "DELETE FROM expenses WHERE invoice_num = '$invoice_num'");
                    echo json_encode([
                        'status' => 'error',
                        'general_error' => 'Failed to save receipt file. Expense not recorded.'
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'general_error' => 'Database error. Please try again.'
                ]);
            }
            exit;
        }
    }

     if (isset($_POST['save_batch_expense'])) {

        error_log("Session data: " . print_r($_SESSION, true));
        error_log("POST data: " . print_r($_POST, true));
        header('Content-Type: application/json');

        $recorder_id = $_SESSION['auth_user']['id'] ?? null;

        if (!$recorder_id) {
            echo json_encode([
                'status' => 'error',
                'errors' => [['type' => 'session', 'message' => 'User session not found.']]
            ]);
            exit;
        }

        $date = date('Y-m-d', strtotime($_POST['date']));
        $client = $_POST['client_id'];
        $main_project_id_input = $_POST['main_project_id'] ?? null; 
        $addon_id_input = $_POST['addon_id'] ?? null;
        $description = $_POST['item_name'];
        $invoice_num = $_POST['invoice_num'];
        $store_name = $_POST['store_name'];
        $raw_amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $receipt_files = $_FILES['receipt_file']['name'] ?? [];
        $receipt_tmp_files = $_FILES['receipt_file']['tmp_name'] ?? [];

        $rowCount = count($description);
        $all_rows_to_insert = [];
        $errors = [];
        $geminiResponses = [];
        $mismatchRows = [];

        if (!$conn) {
            echo json_encode([
                'status' => 'error',
                'errors' => [['type' => 'db', 'message' => 'Database connection failed']]
            ]);
            exit;
        }

        $category_num = $_POST['category_num'];

        for ($i = 0; $i < $rowCount; $i++) {
            if (
                empty($description[$i]) && empty($category_num[$i]) && empty($invoice_num[$i]) && empty($store_name[$i])
                && empty($raw_amount[$i]) && empty($payment_method[$i]) && empty($receipt_files[$i])
            ) {
                continue;
            }

            // To fetch both account title and number
            $category_num_val = $category_num[$i] ?? null;
            $category = null;
            if ($category_num_val) {
                $cat_query = mysqli_query($conn, "SELECT category FROM chart_accounts WHERE account_num = '$category_num_val' LIMIT 1");
                if (!$cat_query) {
                    echo json_encode([
                        'status' => 'error',
                        'errors' => [[
                            'row' => $i,
                            'type' => 'db_error',
                            'message' => 'Database query failed: ' . mysqli_error($conn)
                        ]]
                    ]);
                    exit;
                }
                $cat_row = mysqli_fetch_assoc($cat_query);
                $category = $cat_row['category'] ?? null;
            }
            
            $receipt_file = $receipt_files[$i];
            $receipt_tmp = $receipt_tmp_files[$i];
            $finalFilePath = "../receipts/project_expenses_files/" . basename($receipt_file);

            //SERVER-SIDE VALIDATIONS FOR INPUT FIELDS

            $rowErrors = [];


            // To check for file type and size
            $allowedTypes = ['jpg', 'jpeg'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $fileExt = strtolower(pathinfo($receipt_file, PATHINFO_EXTENSION));
            $fileSize = $_FILES['receipt_file']['size'][$i];

            if (!in_array($fileExt, $allowedTypes)) {
                $rowErrors[] = [
                    'row' => $i,
                    'type' => 'file_invalid',
                    'message' => "Only JPG image files are allowed."
                ];
            }

            if ($fileSize > $maxSize) {
                $rowErrors[] = [
                    'row' => $i,
                    'type' => 'file_too_large',
                    'message' => "File size must not exceed 5MB."
                ];
            }

            // Check for duplicate invoice number
           // Skip duplicate checking for exempt invoice numbers
            if (trim(strtolower($invoice_num[$i])) !== strtolower("No Invoice Number Required")) {

                $check_invoice_query = "SELECT * FROM expenses WHERE invoice_num = '{$invoice_num[$i]}'";
                $check_invoice_query_run = mysqli_query($conn, $check_invoice_query);

                if (mysqli_num_rows($check_invoice_query_run) > 0) {
                    $rowErrors[] = [
                        'row' => $i,
                        'type' => 'invoice_duplicate',
                        'message' => "Invoice number <strong>" . htmlspecialchars($invoice_num[$i]) . "</strong> already exists."
                    ];
                }
            }

            // Check for duplicate file
            if (!empty($receipt_file) && file_exists($finalFilePath)) {
                $rowErrors[]  = [
                    'row' => $i,
                    'type' => 'file_duplicate',
                    'message' => "File <strong>" . htmlspecialchars($receipt_file) . "</strong> already exists."
                ];
            }

            //Check wordcount for description/item_name and store name
            $item_name_trimmed = trim(preg_replace('/[^\w\s]/', '', $description[$i]));
            $item_name_count = count(array_filter(preg_split('/\s+/', $item_name_trimmed)));
            if ($item_name_count < 1 || $item_name_count > 10) {
                $rowErrors[]  = [
                    'row' => $i,
                    'type' => 'item_name_count',
                    'message' => "Item Name must contain 1-10 words only."
                ];
            }

            $store_name_trimmed = trim(preg_replace('/[^\w\s]/', '', $store_name[$i]));
            $store_name_count = count(array_filter((preg_split('/\s+/', $store_name_trimmed))));
            if ($store_name_count < 1 || $store_name_count > 10) {
                $rowErrors[]  = [
                    'row' => $i,
                    'type' => 'store_name_count',
                    'message' => "Store Name must contain 1-10 words only."
                ];
            }

            $amount = str_replace([',', ' '], '', $raw_amount[$i]);
            if (!preg_match('/^(?!-)(?![0-9]*\b0\d)[0-9]+(\.[0-9]+)?$/', $amount) || $amount < 5) {
                $rowErrors[]  = [
                    'row' => $i,
                    'type' => 'amount_format',
                    'message' => "Amount must be a valid number, not less than 5 pesos, no spaces."
                ];
            }
            //Used to collect all errors for each row and display all at once
            if (!empty($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);
            }


            // Save for batch insert later
            $all_rows_to_insert[] = [
                'row_num' => $i,
                'user_id' => $recorder_id,
                'client' => $client,
                'main_project_id' => $main_project_id_input,
                'addon_id' => $addon_id_input,
                'date' => $date,
                'description' => $description[$i],
                'category_num' => $category_num_val,
                'category' => $category,
                'invoice_num' => $invoice_num[$i],
                'store_name' => $store_name[$i],
                'amount' => $amount,
                'payment_method' => $payment_method[$i],
                'receipt_file' => $receipt_file,
                'receipt_tmp' => $receipt_tmp,
                'final_path' => $finalFilePath,
            ];
        }

        // If there are errors, don't insert anything
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error',
                'errors' => $errors,
                'gemini_debug' => $geminiResponses
            ]);
            exit;
        }


        // This condition is used to only insert data once the 'Confirm and Save' button is clicked
        // If just validation scan, return success
        if (isset($_POST['validate_only']) && $_POST['validate_only'] === '1') {
            echo json_encode(['status' => 'success']);
            exit;
        }
        if (empty($main_project_id_input)) {
            echo json_encode([
                'status' => 'error',
                'errors' => [[
                    'type' => 'missing_project',
                    'message' => 'You must select a main project.'
                ]]
            ]);
            exit;
        }
        if (isset($_POST['validate_only']) && $_POST['validate_only'] === '0') {
            // Insert all valid rows now
            foreach ($all_rows_to_insert as $row) {
                error_log("Row to insert: " . print_r($row, true));

                $desc = mysqli_real_escape_string($conn, $row['description']);
                $store = mysqli_real_escape_string($conn, $row['store_name']);
                $invoice = mysqli_real_escape_string($conn, $row['invoice_num']);
                $category = mysqli_real_escape_string($conn, $row['category']);
                $payment = mysqli_real_escape_string($conn, $row['payment_method']);
                $receipt = mysqli_real_escape_string($conn, $row['receipt_file']);

               
                $main_project_id = mysqli_real_escape_string($conn, $row['main_project_id']);
                $addon_id = !empty($row['addon_id']) ? "'" . mysqli_real_escape_string($conn, $row['addon_id']) . "'" : "NULL";

                $insert_batch_query = "INSERT INTO expenses (user_id,client_id, project_id, addon_id, date, description, category, category_num, invoice_num, store_name, amount, payment_method, receipt_file, date_verify, created_at)
        VALUES (
            '{$row['user_id']}', '{$row['client']}', '$main_project_id', $addon_id,
            '{$row['date']}', '$desc', '$category', 
            '{$row['category_num']}',  '$invoice',
            '$store', '{$row['amount']}', 
            '$payment', '$receipt', '$now', '$now'
            )";

                $batch_query_run = mysqli_query($conn, $insert_batch_query);
                if ($batch_query_run) {
                    $folderPath = dirname($row['final_path']);
                    if (!is_dir($folderPath)) {
                        mkdir($folderPath, 0777, true); // create the folder recursively
                    }
                    move_uploaded_file($row['receipt_tmp'], $row['final_path']);
                } else {
                    error_log("Insert failed: " . mysqli_error($conn));
                    echo json_encode([
                        'status' => 'error',
                        'errors' => [[
                            'row' => $row['row_num'],
                            'type' => 'db_error',
                            'message' => "Failed to save data to the database."
                        ]]
                    ]);
                    exit;
                }
            }
        }

        // All success
        echo json_encode([
            'status' => 'success',
            'message' => 'All expenses saved successfully!'
        ]);
        exit;
    }

    // Handling the second form (Company Expense recording)
    if (isset($_POST['save_company_expense'])) {
        $recorder_id = $_SESSION['auth_user']['id'] ?? null;
        $date = date('Y-m-d', strtotime($_POST['date'])); // to convert format
        $description = $_POST['description_field'];
        // This is to save both category/account number and category/account title
        $category_num = $_POST['category_num'];

        // Fetch the category (account title) from chart_accounts
        $category_num_val = $category_num ?? null;
        $category = null;
        if ($category_num_val) {
            $cat_query = mysqli_query($conn, "SELECT category FROM chart_accounts WHERE account_num = '$category_num_val' LIMIT 1");
            $cat_row = mysqli_fetch_assoc($cat_query);
            $category = $cat_row['category'] ?? null;
        }

        $payment_method = $_POST['payment_method'];
        $raw_amount = $_POST['amount'];
        $amount = str_replace([',', ' '], '', $raw_amount);
        $invoice_num = trim($_POST['invoice_num']);
        $receipt_file = $_FILES['receipt_file']['name'];

        $errors = [];

        // To check for file type and size
        $allowedTypes = ['jpg', 'jpeg', 'pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileExt = strtolower(pathinfo($receipt_file, PATHINFO_EXTENSION));
        $fileSize = $_FILES['receipt_file']['size'];

        if (!in_array($fileExt, $allowedTypes)) {
            $errors['file_invalid'] = "Only JPG image files are allowed.";
        }

        if ($fileSize > $maxSize) {
            $errors['file_too_large'] =  "File size must not exceed 5MB.";
        }

        // Check for duplicate invoice number
        $check_invoice_query = "SELECT * FROM company_expense WHERE invoice_num = '$invoice_num'";
        $check_invoice_query_run = mysqli_query($conn, $check_invoice_query);

        if (mysqli_num_rows($check_invoice_query_run) > 0) {
            $errors['invoice'] = "Invoice number <strong>" . htmlspecialchars($invoice_num) . "</strong> already exists. Please enter a unique invoice number.";
        }

        // Check if the file already exists
        if (file_exists("../receipts/company_expense_files/" . $_FILES['receipt_file']['name'])) {
            $filename = $_FILES['receipt_file']['name'];
            $errors['file'] = "File <strong>" . htmlspecialchars($filename) . "</strong> already exists. Please try again!";
        }
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'alert',
                'errors' => $errors
            ]);
            exit;
        }
        // If just validation scan, return success
        if (isset($_POST['validate_only_company']) && $_POST['validate_only_company'] === '1') {
            echo json_encode(['status' => 'success']);
            exit;
        }
        if (isset($_POST['validate_only_company']) && $_POST['validate_only_company'] === '0') {
            // Insert the company expense into the database
            $company_exp_query = "INSERT INTO company_expense (user_id, date, description, category, category_num, payment_method, amount, invoice_num, receipt_file, date_verify, created_at) 
                                  VALUES ('$recorder_id', '$date', '$description', '$category', '$category_num', '$payment_method', '$amount', '$invoice_num', '$receipt_file', '$now', '$now')";
            $companyExp_query_run = mysqli_query($conn, $company_exp_query);

            if ($companyExp_query_run) {
                move_uploaded_file($_FILES["receipt_file"]["tmp_name"], "../receipts/company_expense_files/" . $_FILES['receipt_file']['name']);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Company expense recorded successfully!'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error occurred. Please try again!'
                ]);
            }
            exit();
        }
    }

    if (isset($_POST['save_client_payment'])) {
        $recorder_id = $_SESSION['auth_user']['id'] ?? null;
        $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
        $client = $_POST['client_id'];
        $main_project_id = isset($_POST['main_project_id']) ? intval($_POST['main_project_id']) : null;
        $addon_id = isset($_POST['addon_id']) ? intval($_POST['addon_id']) : null;
        // This is to save both category/account number and category/account title
        $category_num = $_POST['category_num'];

        // Fetch the category (account title) from chart_accounts
        $category_query = mysqli_query($conn, "SELECT category FROM chart_accounts WHERE account_num = '$category_num' LIMIT 1");
        $category_row = mysqli_fetch_assoc($category_query);
        $category = $category_row['category'] ?? null;

        $description = $_POST['description_field'];
        $payment_method = $_POST['payment_method'];
        $raw_amount = $_POST['amount'];
        $amount = str_replace([',', ' '], '', $raw_amount);
        $invoice_num = trim($_POST['invoice_num']);
        $receipt_file = $_FILES['receipt_file']['name'];

        $errors = [];
        
          // To check for file type and size
        $allowedTypes = ['jpg', 'jpeg', 'pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileExt = strtolower(pathinfo($receipt_file, PATHINFO_EXTENSION));
        $fileSize = $_FILES['receipt_file']['size'];

        if (!in_array($fileExt, $allowedTypes)) {
            $errors['file_invalid'] = "Only JPG image files are allowed.";
        }

        if ($fileSize > $maxSize) {
            $errors['file_too_large'] =  "File size must not exceed 5MB.";
        }

        // Check for duplicate invoice number
        $check_invoice_payment = "SELECT * FROM payment_clients WHERE invoice_num = '$invoice_num'";
        $check_invoice_payment_run = mysqli_query($conn, $check_invoice_payment);

        if (mysqli_num_rows($check_invoice_payment_run) > 0) {
            $errors['invoice'] = "Invoice number <strong>" . htmlspecialchars($invoice_num) . "</strong> already exists. Please enter a unique invoice number.";
        }

        // Check if the file already exists
        if (file_exists("../receipts/client_payment_files/" . $_FILES['receipt_file']['name'])) {
            $filename = $_FILES['receipt_file']['name'];
            $errors['file'] = "File <strong>" . htmlspecialchars($filename) . "</strong> already exists. Please try again!";
        }
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'alert',
                'errors' => $errors
            ]);
            exit;
        }

        if (isset($_POST['validate_only_payment']) && $_POST['validate_only_payment'] === '1') {
            echo json_encode(['status' => 'success']);
            exit;
        }
        if (isset($_POST['validate_only_payment']) && $_POST['validate_only_payment'] === '0') {
            $payment_query = "INSERT INTO payment_clients (date,user_id,client_id,project_id,addon_id,category,category_num,description,payment_method,amount,invoice_num,receipt_file,date_verify,created_at)
                            VALUES ('$date', '$recorder_id', '$client', '$main_project_id', '$addon_id', '$category', '$category_num', '$description', '$payment_method', '$amount', '$invoice_num', '$receipt_file', '$now', '$now')";
            $payment_query_run = mysqli_query($conn, $payment_query);

            if ($payment_query_run) {
                move_uploaded_file($_FILES["receipt_file"]["tmp_name"], "../receipts/client_payment_files/" . $_FILES['receipt_file']['name']);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Client payment recorded successfully!'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error occurred. Please try again!'
                ]);
            }

            exit();
        }
    }
    
     if (isset($_POST['save_budget_allocation'])) {
        $recorder_id = $_SESSION['auth_user']['id'] ?? null;
        $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
        $client = $_POST['client_id'];
        $main_project_id = isset($_POST['main_project_id']) ? intval($_POST['main_project_id']) : null;
        $addon_id = isset($_POST['addon_id']) ? intval($_POST['addon_id']) : null;
        // This is to save both category/account number and category/account title
        // $category_num = $_POST['category_num'];

        // // Fetch the category (account title) from chart_accounts
        // $category_query = mysqli_query($conn, "SELECT category FROM chart_accounts WHERE account_num = '$category_num' LIMIT 1");
        // $category_row = mysqli_fetch_assoc($category_query);
        // $category = $category_row['category'] ?? null;
        $category = 'Capital';

        $description = $_POST['description_field'];
        $raw_amount = $_POST['amount'];
        $amount = str_replace([',', ' '], '', $raw_amount);


        if (isset($_POST['validate_only_budget']) && $_POST['validate_only_budget'] === '1') {
            echo json_encode(['status' => 'success']);
            exit;
        }
        if (isset($_POST['validate_only_budget']) && $_POST['validate_only_budget'] === '0') {
            $payment_query = "INSERT INTO budget_allocation (date,user_id,client_id,project_id,addon_id,description,category,amount)
                            VALUES ('$date', '$recorder_id', '$client', '$main_project_id', '$addon_id', '$description', '$category', '$amount')";
            $payment_query_run = mysqli_query($conn, $payment_query);

            if ($payment_query_run) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Allocated budget recorded successfully!'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error occurred. Please try again!'
                ]);
            }

            exit();
        }
    }
}
if (isset($_GET['client_id'])) {
    $clientID = isset($_GET['client_id']) ? $_GET['client_id'] : null;

    if (!$clientID) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Client ID not provided'
        ]);
        exit;
    }
    $formType = $_GET['form'] ?? 'expense';

    $projects = mysqli_query($conn, "SELECT * FROM projects WHERE client_id = '$clientID'");
    $projectList = [];

     if (!$projects) {
        echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
        exit;
    }

    while ($p = mysqli_fetch_array($projects)) {
        $project_id = $p['project_id'];
        $status = $p['project_status'];

        //Only allow Ongoing projects
        if ($status !== 'Ongoing') continue;

        if ($formType === 'expense') {
            // To check is payment exists before recording any expense
            $paymentCheck = mysqli_query($conn, "SELECT COUNT(*) as payment_count FROM payment_clients WHERE project_id = '$project_id' AND client_id = '$clientID'");
            $paymentData = mysqli_fetch_assoc($paymentCheck);
            $hasPayment = $paymentData['payment_count'] > 0;

            $budgetCheck = mysqli_query($conn, "SELECT COUNT(*) as budget_count FROM budget_allocation WHERE project_id = '$project_id' AND client_id = '$clientID'");
            $budgetData = mysqli_fetch_assoc($budgetCheck);
            $hasBudget = $budgetData['budget_count'] > 0;

            if (!$hasPayment && !$hasBudget) continue;
        }

        // This is to check and compare the allocated budget and actual expenditures
        $allocated_main = (float) $p['allocated_budget'];

        // To sum all expenses for that main project (excluding the addons)
        $main_exp_query = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total_main FROM expenses WHERE project_id = '$project_id' AND (addon_id = 0 OR addon_id IS NULL)");
        $main_exp_data = mysqli_fetch_assoc($main_exp_query);
        $main_total_exp = (float) $main_exp_data['total_main'];

        $isMainOver = $main_total_exp >= $allocated_main;

        $projectList[] = [
            'type' => 'main',
            'project_id' => $p['project_id'],
            'project_name' => $p['project_name'],
            'project_status' => $status,
            'allocated_budget' => $allocated_main,
            'actual_expenses' => $main_total_exp,
            'over_spending' => $isMainOver,
        ];

        // To fetch the project add ons if and only if the main project is Ongoing
        $addons = mysqli_query($conn, "SELECT * FROM project_addons WHERE project_id = '$project_id'");
        while ($a = mysqli_fetch_assoc($addons)) {
            $addon_id = $a['addon_id'];
            $allocated_addons = (float) $a['allocated_budget'];
        
            // To calculate total expense for that add on 
            $addon_exp_query = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total_addon_exp FROM expenses WHERE addon_id = '$addon_id'");
            $addon_exp_data = mysqli_fetch_assoc($addon_exp_query);
            $addons_total_exp = $addon_exp_data['total_addon_exp'] ?? 0;

            $isAddonOver = $addons_total_exp >= $allocated_addons;

            $projectList[] = [
                'type' => 'addon',
                'addon_id' => $a['addon_id'],
                'addon_name' => $a['addon_name'],
                'main_project_name' => $p['project_name'],
                'main_project_id' => $project_id,
                'project_status' => $status,
                'allocated_budget' => $allocated_addons,
                'actual_expenses' => $addons_total_exp,
                'over_spending' => $isAddonOver,
            ];
        }
    }
//     if ($formType === 'payment') {
//     // Do not filter by payment/budget for this form
//     $skipBudgetCheck = true;
// }
    echo json_encode($projectList);
    exit;
}
