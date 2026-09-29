<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];

    $sql = "DELETE FROM clients WHERE client_id = $client_id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header('location: ../inner_pages/clients.php');
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/clients.php');
        exit();
    }
}
?>
