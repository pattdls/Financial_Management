<?php
session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_POST['upload_contract']) && isset($_FILES['contract_file']) && isset($_POST['client_id'])) {
    $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
    $file = $_FILES['contract_file'];
    $target_dir = "../uploads/contracts/";
    $file_name = basename($file["name"]);
    $target_file = $target_dir . time() . "_" . $file_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $insert = "INSERT INTO contracts (client_id, file_path) VALUES ('$client_id', '$target_file')";
        mysqli_query($connection, $insert);
        $_SESSION['status'] = "Contract uploaded successfully!";
    } else {
        $_SESSION['status'] = "Failed to upload contract.";
    }
    header("Location: ../inner_pages/projects.php?client_id=$client_id");
    exit();
}
?>