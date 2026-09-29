<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];

    $sql = "DELETE FROM chart_accounts WHERE category_id = $category_id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header('location: ../inner_pages/chartaccounts.php');
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/chartaccounts.php');
        exit();
    }
}
?>