<?php
session_start();
include('../dbcon.php');

if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $selectedIds = $_POST['selected_ids'];
    $idsToDelete = implode("','", $selectedIds);

    $query = "DELETE FROM chart_accounts WHERE account_num IN ('$idsToDelete')";
    $query_run = mysqli_query($conn, $query);

    if ($query_run) {
        $_SESSION['title_status'] = '<div class="alert alert-success">Selected account titles deleted successfully!</div>';
    } else {
        $_SESSION['title_status'] = '<div class="alert alert-danger">Error deleting selected account titles!</div>';
    }
}

header("Location: chartAccounts.php");
exit();
