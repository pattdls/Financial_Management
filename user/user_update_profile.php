<?php
session_start();
include('../dbcon.php');

// Check authentication
if (!isset($_SESSION['auth_user']['id'])) {
    $_SESSION['status'] = "<div class='alert alert-danger'>Access denied.</div>";
    header('Location: ../inner_pages/my_profile.php');
    exit;
}

$user_id = (int)$_SESSION['auth_user']['id'];

$name = trim($_POST['first_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');

$stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone_number = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $phone_number, $user_id);

if (mysqli_stmt_execute($stmt)) {
   $_SESSION['status'] = "
<div class='alert alert-success alert-dismissible fade show' role='alert'>
    Profile updated successfully.
    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
</div>";

    // Optionally update session email/name
    $_SESSION['auth_user']['name'] = $name ;
    $_SESSION['auth_user']['email'] = $email;
} else {
    $_SESSION['status'] = "<div class='alert alert-danger'>Failed to update profile.</div>";
}
mysqli_stmt_close($stmt);

header('Location: user_profile.php');
exit;


?>