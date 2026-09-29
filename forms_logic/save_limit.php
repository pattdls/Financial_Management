<?php
session_start();
include('../dbcon.php');
header('Content-Type: application/json');

if (isset($_POST['set_limit'])){
    $client_id = $_POST['client_id'];
    $project_id = $_POST['project_id'];
    $material_limit =  str_replace([',',' '], '', $_POST['material_limit']);
    $labor_limit = str_replace([',',' '], '', $_POST['labor_limit']);
    $others_limit = str_replace([',',' '], '', $_POST['others_limit']);

    $save_limit_query = "INSERT INTO project_limits (client_id, project_id, material_limit, labor_limit, others_limit)
                        VALUES ('$client_id', '$project_id', '$material_limit', '$labor_limit', '$others_limit')";
    $save_limit_query_run = mysqli_query($conn, $save_limit_query);

    if ($save_limit_query_run) {
        $_SESSION['status_type'] = "saved";
        $_SESSION['status'] = true;
        header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    } else {
        $_SESSION['status_type'] = "error";
        $_SESSION['status'] = true;
        header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    }
    exit();
}   

if (isset($_POST['update_limit'])){
    $client_id = $_POST['client_id'];
    $project_id = $_POST['project_id'];
    $material_limit =  str_replace([',',' '], '', $_POST['material_limit']);
    $labor_limit = str_replace([',',' '], '', $_POST['labor_limit']);
    $others_limit = str_replace([',',' '], '', $_POST['others_limit']);

    $update_limit_query = "UPDATE project_limits SET
                            material_limit = '$material_limit',
                            labor_limit = '$labor_limit',
                            others_limit = '$others_limit'
                            WHERE client_id = '$client_id' AND project_id = '$project_id'";
    $update_limit_run = mysqli_query($conn, $update_limit_query);

    if($update_limit_run){
        $_SESSION['status_type'] = "updated";
        $_SESSION['status'] = true;
        header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    } else{
        $_SESSION['status_type'] = "error";
        $_SERVER['status'] = true;
        header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    }
    exit();

}
?>