<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_GET['id']) && isset($_GET['project_id'])) {
    $id = $_GET['id'];
    $project = $_GET['project_id']; 
    
    $sql = "DELETE FROM expenses WHERE id = $id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['delete_status'] = "Data Deleted successfully";
        header("location: ../inner_pages/project_details.php?project_id=" . $project);
        exit();

    }
    else
    {
        $_SESSION['delete_status'] = "Deletion of data failed";
        header('location:../inner_pages/project_details.php');
        exit();
    }
}
?>
