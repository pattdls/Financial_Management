<?php
session_start();
include('../dbcon.php');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
// To get the logged in user 
$user_id = $_SESSION['auth_user']['id'] ?? null;
$conn->query("SET time_zone = '+08:00'");


if (isset($_POST['exp_restore_btn'])) {
    $id = $_POST['id'];
    $restorer_id = $_SESSION['auth_user']['id'] ?? null;

     // Get project_id and client_id from the record being restored
    $get_proj = mysqli_query($conn, "SELECT project_id, client_id FROM expenses_archive WHERE archive_id = '$id'");
    $proj = mysqli_fetch_assoc($get_proj);
    $project_id = $proj['project_id'] ?? null;
    $client_id  = $proj['client_id'] ?? null;

    $check_user = mysqli_query($conn, "SELECT id FROM users WHERE id = (SELECT recorded_by FROM expenses_archive WHERE archive_id = '$id')");
    if (mysqli_num_rows($check_user) === 0) {
        $_SESSION['restore_status'] = "Cannot restore: recorded user no longer exists.";
        $_SESSION['restore_status_type'] = "danger";
        header("Location: ../inner_pages/intro_archive.php?view_project=$project_id");
        exit();
    }

    $copy_record = "INSERT INTO expenses (user_id,client_id,project_id,archived_by,restored_by,archived_date,restored_date,
                    date,description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file)
                    SELECT recorded_by,client_id,project_id,user_id,'$restorer_id',archived_at,NOW(),
                    expense_date,description,category,category_num,invoice_num,store_name,amount,payment_method,receipt_file
                    FROM expenses_archive
                    WHERE archive_id = '$id'";
    $copy_run_query = mysqli_query($conn, $copy_record);

     //To delete record from the previous table
    // $delete_record = "DELETE FROM expenses_archive WHERE archive_id = $id";
    // $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query){
        $mark_restored = "UPDATE expenses_archive SET is_restored = 1 WHERE archive_id = $id";
        mysqli_query($conn, $mark_restored);
        $_SESSION['restore_status'] = "Project Expense Restored";
        $_SESSION['restore_status_type'] = "success";
    } else {
        $_SESSION['restore_status'] = "Failed to restore expense.";
        $_SESSION['restore_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/intro_archive.php?view_project=$project_id");
    exit();

}
// To restore company expense record
if (isset($_POST['compExp_restore_btn'])) {
    $id = $_POST['id'];
    $restorer_id = $_SESSION['auth_user']['id'] ?? null;

    $copy_record = "INSERT INTO company_expense (user_id,archived_by,restored_by,archived_date,restored_date,
                    date,description,category,category_num,payment_method,store_name,amount,invoice_num,receipt_file)
                    SELECT recorded_by,user_id,'$restorer_id',archived_at,NOW(),
                    expense_date,description,category,category_num,payment_method,store_name,amount,invoice_num,receipt_file
                    FROM company_archive
                    WHERE archive_id = '$id'";
    $copy_run_query = mysqli_query($conn, $copy_record);

     //To delete record from the previous table
    // $delete_record = "DELETE FROM company_archive WHERE archive_id = $id";
    // $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query){
        $mark_restored = "UPDATE company_archive SET is_restored = 1 WHERE archive_id = $id";
        mysqli_query($conn, $mark_restored);
        $_SESSION['restore_status'] = "Company Expense Restored";
        $_SESSION['restore_status_type'] = "success";
    } else {
        $_SESSION['restore_status'] = "Failed to restore expense.";
        $_SESSION['restore_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/company_exp_archives.php");
    exit();
}
// To restore payment record
if (isset($_POST['payment_restore_btn'])) {
    $id = $_POST['id'];
    $restorer_id = $_SESSION['auth_user']['id'] ?? null;

    // Get project_id and client_id from the record being restored
    $get_proj = mysqli_query($conn, "SELECT project_id, client_id FROM payment_archive WHERE archive_id = '$id'");
    $proj = mysqli_fetch_assoc($get_proj);
    $project_id = $proj['project_id'] ?? null;
    $client_id  = $proj['client_id'] ?? null;

    $copy_record = "INSERT INTO payment_clients (date,user_id,client_id,project_id,archived_by,restored_by,archived_date,restored_date,
                    category,category_num,description,payment_method,amount,invoice_num,receipt_file)
                    SELECT payment_date,recorded_by,client_id,project_id,user_id,'$restorer_id',archived_at,NOW(),
                    category,category_num,description,payment_method,amount,invoice_num,receipt_file
                    FROM payment_archive
                    WHERE archive_id = '$id'";
    $copy_run_query = mysqli_query($conn, $copy_record);

     //To delete record from the previous table
    // $delete_record = "DELETE FROM payment_archive WHERE archive_id = $id";
    // $delete_run_query = mysqli_query($conn, $delete_record);

    if ($copy_run_query && $delete_run_query){
        $mark_restored = "UPDATE payment_archive SET is_restored = 1 WHERE archive_id = $id";
        mysqli_query($conn, $mark_restored);
        $_SESSION['restore_status'] = "Client Payment Restored";
        $_SESSION['restore_status_type'] = "success";
    } else {
        $_SESSION['restore_status'] = "Failed to restore expense.";
        $_SESSION['restore_status_type'] = "danger";
    }
    
    header("Location: ../inner_pages/payment_archive.php?view_project=$project_id");
    exit();
}
?>