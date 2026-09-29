<?php
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_POST['addon_id'], $_POST['status'], $_POST['project_id'], $_POST['client_id'])) {
    $addon_id = mysqli_real_escape_string($connection, $_POST['addon_id']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    $project_id = mysqli_real_escape_string($connection, $_POST['project_id']);
    $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);

    $query = "UPDATE project_addons SET status = '$status' WHERE addon_id = '$addon_id'";
    mysqli_query($connection, $query);
    
    header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id&client_id=$client_id");
} else {
    // Handle missing parameters - redirect back or show error
    header("Location: ../inner_pages/proj_budget_sum.php");
}

exit();
?>