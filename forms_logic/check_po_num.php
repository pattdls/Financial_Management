<?php
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

$po_num = mysqli_real_escape_string($connection, strtoupper($_GET['po_num'])); // normalize to uppercase
$client_id = mysqli_real_escape_string($connection, $_GET['client_id']);
$exclude_project_id = isset($_GET['exclude_project_id']) ? intval($_GET['exclude_project_id']) : 0;

// Use UPPER in SQL to avoid case mismatches
$query = "SELECT * FROM projects WHERE UPPER(po_num) = '$po_num' AND client_id = '$client_id'";

if ($exclude_project_id) {
    $query .= " AND project_id != $exclude_project_id";
}

$result = mysqli_query($connection, $query);

echo json_encode(['exists' => mysqli_num_rows($result) > 0]);
?>
