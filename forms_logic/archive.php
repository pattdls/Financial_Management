<?php
session_start();
include('../dbcon.php');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
// To get the logged in user 
$user_id = $_SESSION['auth_user']['id'] ?? null;
$conn->query("SET time_zone = '+08:00'");

// Archive logic for Project Expense
if (isset($_POST['exp_archive_btn'])){
    $id = $_POST['id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;
    
    // This is to move the file to another file
    $get_file = mysqli_query($conn, "SELECT receipt_file FROM expenses WHERE id = $id");
    $file_row = mysqli_fetch_assoc($get_file);

    $fileName = $file_row['receipt_file'] ?? null;

    $source_path = "../receipts/project_expenses_files/" . $fileName;
    $destination_path = "../receipts/archive_prjct_exp/" . $fileName;

    $copy_record = "INSERT INTO expenses_archive (recorded_by,user_id,client_id,project_id,expense_date,archived_at,description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file)  
    SELECT user_id,'$archiver_id',client_id,project_id,date,NOW(),description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file FROM expenses WHERE id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);
    
    if ($copy_run_query) {
        if (copy($source_path, $destination_path)) {
                unlink($source_path);
    }

    //To delete record from the previous table
    $delete_record = "DELETE FROM expenses WHERE id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($delete_run_query){
        $_SESSION['archive_status'] = "Project Expense Archived";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive expense.";
        $_SESSION['archive_status_type'] = "danger";
    }
    } else {
            $_SESSION['archive_status'] = "Failed to copy record to archive.";
            $_SESSION['archive_status_type'] = "danger";
        }
    
    header("Location: ../inner_pages/expenses.php");
    exit();
}
if (isset($_POST['prjct_archive_btn'])){
    $id = $_POST['id'];
    $project_id = $_POST['project_id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;

    $copy_record = "INSERT INTO expenses_archive (recorded_by,user_id,client_id,project_id,expense_date,archived_at,description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file)  
    SELECT user_id,'$archiver_id',client_id,project_id,date,NOW(),description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file FROM expenses WHERE id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);

    //To delete record from the previous table
    $delete_record = "DELETE FROM expenses WHERE id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query){
        $_SESSION['archive_status'] = "Project Expense Archived";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive expense.";
        $_SESSION['archive_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/project_details.php?project_id=$project_id");
    exit();
}
if (isset($_POST['batchexp_archive_btn'])){
    $id = $_POST['id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;
    
    // This is to move the file to another file
    $get_file = mysqli_query($conn, "SELECT receipt_file FROM expenses WHERE id = $id");
    $file_row = mysqli_fetch_assoc($get_file);

    $fileName = $file_row['receipt_file'] ?? null;

    $source_path = "../receipts/project_expenses_files/" . $fileName;
    $destination_path = "../receipts/archive_prjct_exp/" . $fileName;

    $copy_record = "INSERT INTO expenses_archive (recorded_by,user_id,client_id,project_id,expense_date,archived_at,description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file)  
    SELECT user_id,'$archiver_id',client_id,project_id,date,NOW(),description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file FROM expenses WHERE id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);
    
    if ($copy_run_query) {
        if (copy($source_path, $destination_path)) {
                unlink($source_path);
    }

    //To delete record from the previous table
    $delete_record = "DELETE FROM expenses WHERE id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query){
        $_SESSION['archive_status'] = "Project Expense Archived";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive expense.";
        $_SESSION['archive_status_type'] = "danger";
    }
    }
    else {
        $_SESSION['archive_status'] = "Failed to copy record to archive.";
        $_SESSION['archive_status_type'] = "danger";
    }
    header("Location: ../inner_pages/batch_expense.php");
    exit();
}
// if (isset($_POST['archive_chart_account'])){
//     $id = $_POST['id'];

//     $copy_record = "INSERT INTO chart_accounts_archive (archive_id,user_id,account_num,category,account_type,form_usage,statement_field)  
//     SELECT category_id,user_id,account_num,category,account_type,form_usage,statement_field FROM chart_accounts WHERE category_id = $id";
//     $copy_run_query = mysqli_query($conn, $copy_record);

//     //To delete record from the previous table
//     $delete_record = "DELETE FROM chart_accounts WHERE category_id = $id";
//     $delete_run_query = mysqli_query($conn, $delete_record);

//     if ($copy_run_query && $delete_run_query){
//         $_SESSION['archive_status'] = "Account Title successfully archived!";
//         $_SESSION['archive_status_type'] = "success";
//     } else {
//         $_SESSION['archive_status'] = "Failed to archive account title.";
//         $_SESSION['archive_status_type'] = "danger";
//     }
    
//     header("Location: ../inner_pages/chartaccounts.php");
//     exit();
// }

if (isset($_GET['start_archive']) && isset($_GET['end_archive'])) {
        $start_archive = $_GET['start_archive'];
        $end_archive = $_GET['end_archive'];
        $date_query = "SELECT * FROM expenses_archive WHERE expense_date BETWEEN '$start_archive' AND '$end_archive' ORDER BY expense_date ASC";
        $date_query_run = mysqli_query($conn, $date_query);
        
        if (mysqli_num_rows($date_query_run) > 0) {
            // Store the date range in session
            $_SESSION['archive_start'] = $start_archive;
            $_SESSION['archive_end'] = $end_archive;
        } else {
            $_SESSION['status_type'] = "no_data";
            $_SESSION['status'] = "No Record Found";
        }
    
        header("Location: ../inner_pages/intro_archive.php");
        exit();
}

if (isset($_GET['clear'])) {
        unset($_SESSION['archive_start']);
        unset($_SESSION['archive_end']);
        
        // Redirect to avoid the fields from keeping old values
        header("Location: ../inner_pages/intro_archive.php"); 
        exit();
}
// Logic for Company Expenses
if (isset($_POST['comp_archive_btn'])){
    $id = $_POST['company_exp_id'];
     $archiver_id = $_SESSION['auth_user']['id'] ?? null;

     $copy_record = "INSERT INTO company_archive (recorded_by,expense_date,archived_at,description,category,category_num,payment_method,store_name,amount,invoice_num,receipt_file,date_verify,user_id)  
    SELECT user_id,date,NOW(),description,category,category_num,payment_method,store_name,amount,invoice_num,receipt_file,date_verify,'$archiver_id' FROM company_expense WHERE company_exp_id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);

    //To delete record from the previous table
    $delete_record = "DELETE FROM company_expense WHERE company_exp_id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query){
        $_SESSION['archive_status'] = "Company Expense Archived";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive expense.";
        $_SESSION['archive_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/company_exp.php");
    exit();
}
if (isset($_GET['start_archive_company']) && isset($_GET['end_archive_company'])) {
        $start_archive = $_GET['start_archive_company'];
        $end_archive = $_GET['end_archive_company'];
        $date_query = "SELECT * FROM company_archive WHERE expense_date BETWEEN '$start_archive' AND '$end_archive' ORDER BY expense_date ASC";
        $date_query_run = mysqli_query($conn, $date_query);
        
        if (mysqli_num_rows($date_query_run) > 0) {
            // Store the date range in session
            $_SESSION['company_archive_start'] = $start_archive;
            $_SESSION['company_archive_end'] = $end_archive;
        } else {
            $_SESSION['status_type'] = "no_data";
            $_SESSION['status'] = "No Record Found";
        }
    
        header("Location: ../inner_pages/company_exp_archives.php");
        exit();
}

if (isset($_GET['btnClear'])) {
        unset($_SESSION['company_archive_start']);
        unset($_SESSION['company_archive_end']);
        
        // Redirect to avoid the fields from keeping old values
        header("Location: ../inner_pages/company_exp_archives.php"); 
        exit();
}
// Logic for Payment Archives
if (isset($_POST['pay_archive_btn'])){
    $id = $_POST['payment_id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;

    $copy_record = "INSERT INTO payment_archive (recorded_by,payment_date,archived_at,client_id,project_id,category,category_num,description,payment_method,amount,invoice_num,receipt_file,date_verify,user_id)  
    SELECT user_id,date,NOW(),client_id,project_id,category,category_num,description,payment_method,amount,invoice_num,receipt_file,date_verify,'$archiver_id' FROM payment_clients WHERE payment_id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);

    //To delete record from the previous table
    $delete_record = "DELETE FROM payment_clients WHERE payment_id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query){
        $_SESSION['archive_status'] = "Client Payment Archived";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive expense.";
        $_SESSION['archive_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/payment.php");
    exit();
}
if (isset($_GET['start_archive_payment']) && isset($_GET['end_archive_payment'])) {
        $start_archive = $_GET['start_archive_payment'];
        $end_archive = $_GET['end_archive_payment'];
        $date_query = "SELECT * FROM payment_archive WHERE payment_date BETWEEN '$start_archive' AND '$end_archive' ORDER BY payment_date ASC";
        $date_query_run = mysqli_query($conn, $date_query);
        
        if (mysqli_num_rows($date_query_run) > 0) {
            // Store the date range in session
            $_SESSION['payment_archive_start'] = $start_archive;
            $_SESSION['payment_archive_end'] = $end_archive;
        } else {
            $_SESSION['status_type'] = "no_data";
            $_SESSION['status'] = "No Record Found";
        }
    
        header("Location: ../inner_pages/payment_archive.php");
        exit();
}

if (isset($_GET['btnlinis'])) {
        unset($_SESSION['payment_archive_start']);
        unset($_SESSION['payment_archive_end']);
        
        // Redirect to avoid the fields from keeping old values
        header("Location: ../inner_pages/payment_archive.php"); 
        exit();
}

// Logic for Project Archives
if (isset($_POST['archive_project_btn'])) {
    $id = $_POST['project_id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;

    // ✅ Get the client_id of the project first
    $get_client = mysqli_query($conn, "SELECT client_id FROM projects WHERE project_id = $id");
    $client_row = mysqli_fetch_assoc($get_client);
    $client_id = $client_row['client_id'];

    $copy_record = "INSERT INTO project_archive (
        project_id, client_id, project_name, projected_budget_cost, po_num, start_date, end_date, project_type, completed_date, user_id
    )
    SELECT 
        project_id, client_id, project_name, projected_budget_cost, po_num, start_date, end_date, project_type, completed_date, '$archiver_id'
    FROM projects WHERE project_id = $id";
    $copy_run_query = mysqli_query($conn, $copy_record);

    // To delete record from the previous table
    $delete_record = "DELETE FROM projects WHERE project_id = $id";
    $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query) {
        $_SESSION['archive_status'] = "Project record successfully archived!";
        $_SESSION['archive_status_type'] = "success";
    } else {
        $_SESSION['archive_status'] = "Failed to archive project.";
        $_SESSION['archive_status_type'] = "danger";
    }

    // Now $client_id has a value
    header("Location: ../inner_pages/projects.php?client_id=$client_id");
    exit();
}

if (isset($_GET['start_archive_project']) && isset($_GET['end_archive_project'])) {
    $start_archive = $_GET['start_archive_project'];
    $end_archive = $_GET['end_archive_project'];
    $date_query = "SELECT * FROM project_archive WHERE start_date BETWEEN '$start_archive' AND '$end_archive' ORDER BY start_date ASC";
    $date_query_run = mysqli_query($conn, $date_query);

    if (mysqli_num_rows($date_query_run) > 0) {
        $_SESSION['project_archive_start'] = $start_archive;
        $_SESSION['project_archive_end'] = $end_archive;
    } else {
        $_SESSION['status_type'] = "no_data";
        $_SESSION['status'] = "No Record Found";
    }

    header("Location: ../inner_pages/project_archive.php");
    exit();
}

if (isset($_GET['btnlinis_project'])) {
    unset($_SESSION['project_archive_start']);
    unset($_SESSION['project_archive_end']);

    // Redirect to avoid the fields from keeping old values
    header("Location: ../inner_pages/project_archive.php");
    exit();
}
// Logic for Cancel Project Archives
if (isset($_POST['cancel_project_btn'])) {
    $id = (int)$_POST['project_id'];
    $client_id = (int)$_POST['client_id'];
    $archiver_id = $_SESSION['auth_user']['id'] ?? null;

    // Get and sanitize inputs
    $cancellation_reason = trim($_POST['cancellation_reason']);
    $additional_details = trim($_POST['additional_details'] ?? '');

    // Final reason
    if ($cancellation_reason === 'Other' && !empty($additional_details)) {
        $final_reason = "Other: " . $additional_details;
    } else {
        $final_reason = $cancellation_reason;
    }

    // Set timezone to Manila and get proper cancelled_at time
    date_default_timezone_set('Asia/Manila');
    $cancelled_at = date('Y-m-d H:i:s');

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        $copy_record = "
            INSERT INTO cancelled_project_archive (
                project_id, client_id, project_name, projected_budget_cost, po_num, 
                start_date, end_date, project_type, completed_date, user_id, 
                cancellation_reason, cancelled_at
            )
            SELECT 
                project_id, client_id, project_name, projected_budget_cost, po_num, 
                start_date, end_date, project_type, completed_date, ?, ?, ?
            FROM projects 
            WHERE project_id = ?
        ";

        $stmt = mysqli_prepare($conn, $copy_record);
        mysqli_stmt_bind_param($stmt, "issi", $archiver_id, $final_reason, $cancelled_at, $id);
        $copy_run_query = mysqli_stmt_execute($stmt);

        if (!$copy_run_query) {
            throw new Exception("Insert failed: " . mysqli_error($conn));
        }

        // Delete project from original table
        $delete_record = "DELETE FROM projects WHERE project_id = ?";
        $stmt2 = mysqli_prepare($conn, $delete_record);
        mysqli_stmt_bind_param($stmt2, "i", $id);
        $delete_run_query = mysqli_stmt_execute($stmt2);

        if (!$delete_run_query) {
            throw new Exception("Delete failed: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        $_SESSION['archive_status'] = "Project successfully cancelled and archived!";
        $_SESSION['archive_status_type'] = "success";

    } catch (Exception $e) {
        mysqli_rollback($conn);

        $_SESSION['archive_status'] = "Failed to cancel project. Error: " . $e->getMessage();
        $_SESSION['archive_status_type'] = "danger";
    }

    header("Location: ../inner_pages/projects.php?client_id=$client_id");
    exit();
}




?>