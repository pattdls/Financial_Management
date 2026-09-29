<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM expenses WHERE id = $id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header('location: ../inner_pages/expenses.php');
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/expenses.php');
        exit();
    }
}


if (isset($_GET['company_exp_id'])) {
    $company_exp_id = $_GET['company_exp_id'];

    $sql = "DELETE FROM company_expense WHERE company_exp_id = $company_exp_id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header('location: ../inner_pages/company_exp.php');
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/company_exp.php');
        exit();
    }
}


if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    $sql = "DELETE FROM payment_clients WHERE payment_id = $payment_id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header('location: ../inner_pages/payment.php');
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/payment.php');
        exit();
    }
}
?>
