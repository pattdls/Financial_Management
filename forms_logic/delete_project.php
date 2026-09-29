<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_GET['project_id']) && isset($_GET['client_id'])) {
    $project_id = intval($_GET['project_id']);
    $client_id = intval($_GET['client_id']); // Ensure client_id is captured

    $sql = "DELETE FROM projects WHERE project_id = $project_id";

    if (mysqli_query($connection, $sql)) 
    {
        $_SESSION['deleteProject_status'] = "Data Deleted successfully";
        header("Location: ../inner_pages/projects.php?client_id=" . $_GET['client_id']);
        exit();

    }
    else
    {
        $_SESSION['deleteProject_status'] = "Deletion of data failed";
        header("Location: ../inner_pages/projects.php?client_id=" . $client_id);
        exit();
    }
}
?>
