<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

// Check if the connection is successful
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['update_expense'])) {
    $expense_id = $_POST['expense_id'];
    $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
    $client = $_POST['client_id'];
    $project = $_POST['project_id'];
    $description = $_POST['description_field'];
    $category = $_POST['category'];
    $invoice_num = $_POST['invoice_num'];
    $store_name = $_POST['store_name'];
    $raw_amount = $_POST['amount'];
    $amount = str_replace([',', ' '], '', $raw_amount);
    $payment_method = $_POST['payment_method'];
    $receipt_file = $_FILES['receipt_file']['name'];

    // Prevent SQL Injection (Recommended)
    $date = mysqli_real_escape_string($connection, $date);
    $client = mysqli_real_escape_string($connection, $client);
    $project = mysqli_real_escape_string($connection, $project);
    $description = mysqli_real_escape_string($connection, $description);
    $category = mysqli_real_escape_string($connection, $category);
    $invoice_num = mysqli_real_escape_string($connection, $invoice_num);
    $store_name = mysqli_real_escape_string($connection, $store_name);
    $amount = mysqli_real_escape_string($connection, $amount);
    $payment_method = mysqli_real_escape_string($connection, $payment_method);
    $receipt_file = mysqli_real_escape_string($connection, $receipt_file);


    // Prepare SQL Query
    $sql = "UPDATE expenses SET 
            client_id = '$client',
            project_id = '$project',
            date = '$date',
            description = '$description',
            category = '$category',
            invoice_num = '$invoice_num',
            store_name = '$store_name',
            amount = '$amount',
            payment_method = '$payment_method',
            receipt_file = '$receipt_file'
            WHERE id = $expense_id";

    if (mysqli_query($connection, $sql)) {
        // Success: Redirect with success message
        header('Location: ../inner_pages/expenses.php?update=success');
        exit();
    } else {
        // Error: Display the MySQL error
        die("Error updating record: " . mysqli_error($connection));
    }
}

// For updating inner records

if (isset($_POST['update_expense_inner'])) {
    $expense_id = $_POST['expense_id'];
    $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
    $client = $_POST['client_id'];
    $project = $_POST['project_id'];
    $description = $_POST['description_field'];
    $category = $_POST['category'];
    $invoice_num = $_POST['invoice_num'];
    $store_name = $_POST['store_name'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];

    // Prevent SQL Injection (Recommended)
    $date = mysqli_real_escape_string($connection, $date);
    $client = mysqli_real_escape_string($connection, $client);
    $project = mysqli_real_escape_string($connection, $project);
    $description = mysqli_real_escape_string($connection, $description);
    $category = mysqli_real_escape_string($connection, $category);
    $invoice_num = mysqli_real_escape_string($connection, $invoice_num);
    $store_name = mysqli_real_escape_string($connection, $store_name);
    $amount = mysqli_real_escape_string($connection, $amount);
    $payment_method = mysqli_real_escape_string($connection, $payment_method);


    // Prepare SQL Query
    $sql = "UPDATE expenses SET 
            client_id = '$client',
            project_id = '$project',
            date = '$date',
            description = '$description',
            category = '$category',
            invoice_num = '$invoice_num',
            store_name = '$store_name',
            amount = '$amount',
            payment_method = '$payment_method'
            WHERE id = $expense_id";

    if (mysqli_query($connection, $sql)) {
        header("Location: ../inner_pages/project_details.php?project_id=" . $project);
        exit();
    } else {
        die("Error updating record: " . mysqli_error($connection));
}
}

if (isset($_POST['update_company_expense'])) {
    $company_exp_id = $_POST['company_exp_id'];
    $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
    $description = $_POST['description_field'];
    $category = $_POST['category'];
    $payment_method = $_POST['payment_method'];
    $store_name = $_POST['store_name'];
    $amount = $_POST['amount'];
    $invoice_num = $_POST['invoice_num'];

    // Prevent SQL Injection (Recommended)
    $date = mysqli_real_escape_string($connection, $date);
    $description = mysqli_real_escape_string($connection, $description);
    $category = mysqli_real_escape_string($connection, $category);
    $payment_method = mysqli_real_escape_string($connection, $payment_method);
    $store_name = mysqli_real_escape_string($connection, $store_name);
    $amount = mysqli_real_escape_string($connection, $amount);
    $invoice_num = mysqli_real_escape_string($connection, $invoice_num);


    // Prepare SQL Query
    $sql = "UPDATE company_expense SET 
            date = '$date',
            description = '$description',
            category = '$category',
            payment_method = '$payment_method',
            store_name = '$store_name',
            amount = '$amount',
            invoice_num = '$invoice_num'
            WHERE company_exp_id = $company_exp_id";

    if (mysqli_query($connection, $sql)) {
        // Success: Redirect with success message
        header('Location: ../inner_pages/company_exp.php?update=success');
        exit();
    } else {
        // Error: Display the MySQL error
        die("Error updating record: " . mysqli_error($connection));
    }
}

if (isset($_POST['update_client_payment'])) {
    $payment_id = $_POST['payment_id'];
    $date = date('Y-m-d', strtotime($_POST['date'])); //to convert format
    $client = $_POST['client_id'];
    $project = $_POST['project_id'];
    $category = $_POST['category'];
    $description = $_POST['description_field'];
    $payment_method = $_POST['payment_method'];
    $amount = $_POST['amount'];
    $invoice_num = $_POST['invoice_num'];
    
    // Prevent SQL Injection (Recommended)
    $date = mysqli_real_escape_string($connection, $date);
    $client = mysqli_real_escape_string($connection, $client);
    $project = mysqli_real_escape_string($connection, $project);
    $category = mysqli_real_escape_string($connection, $category);
    $description = mysqli_real_escape_string($connection, $description);
    $payment_method = mysqli_real_escape_string($connection, $payment_method);
    $amount = mysqli_real_escape_string($connection, $amount);
    $invoice_num = mysqli_real_escape_string($connection, $invoice_num);
    

    // Prepare SQL Query
    $sql = "UPDATE payment_clients SET 
            date = '$date',
            client_id = '$client',
            project_id = '$project',
            category = '$category',
            description = '$description',
            payment_method = '$payment_method',
            amount = '$amount', 
            invoice_num = '$invoice_num'
            WHERE payment_id = $payment_id";

    if (mysqli_query($connection, $sql)) {
        // Success: Redirect with success message
        header('Location: ../inner_pages/payment.php?update=success');
        exit();
    } else {
        // Error: Display the MySQL error
        die("Error updating record: " . mysqli_error($connection));
    }
}
?>
