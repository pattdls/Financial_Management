<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "root", "", "financial_management");

// Check if the connection is successful
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['save_projectChanges'])) {
    $category_id = $_POST['category_id'];
    $accountNum = $_POST['accountNum'];
    $account_des = $_POST['accountDes'];
    $account_type = $_POST['account_type'];
    $form_usage = $_POST['form_usage'];

    // Prevent SQL Injection (Recommended)
    $accountNum = mysqli_real_escape_string($connection, $accountNum);
    $account_des = mysqli_real_escape_string($connection, $account_des);
    $account_type = mysqli_real_escape_string($connection, $account_type);
    $form_usage = mysqli_real_escape_string($connection, $form_usage);

    // Prepare SQL Query
    $sql = "UPDATE chart_accounts SET 
            account_num = '$accountNum', 
            category = '$account_des', 
            account_type = '$account_type', 
            form_usage = '$form_usage'
            WHERE category_id = $category_id";

    if (mysqli_query($connection, $sql)) {
        // Success: Redirect with success message
        header('Location: ../inner_pages/chartaccounts.php?update=success');
        exit();
    } else {
        // Error: Display the MySQL error
        die("Error updating record: " . mysqli_error($connection));
    }
}

?>
