<?php
session_start();
include('../dbcon.php');

if (isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);

    // Get user details before deletion
    $user_query = "SELECT * FROM users WHERE id = '$user_id'";
    $user_result = mysqli_query($conn, $user_query);
    $user = mysqli_fetch_assoc($user_result);

    if (!$user) {
        $_SESSION['status'] = "User not found.";
        header("Location: ../inner_pages/create_user.php");
        exit();
    }

    // Delete the user
    $delete_query = "DELETE FROM users WHERE id = '$user_id'";
    $delete_result = mysqli_query($conn, $delete_query);

    if ($delete_result) {
        $_SESSION['status'] = "<div class='alert-success'>User '" . htmlspecialchars($user['name']) . "' has been deleted successfully.";
    } else {
        $_SESSION['status'] = "Failed to delete user. Please try again.";
    }
} else {
    $_SESSION['status'] = "Invalid request.";
}

header("Location: ../inner_pages/create_user.php");
exit();
?>