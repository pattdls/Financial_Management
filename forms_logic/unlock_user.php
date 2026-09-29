<?php
include('../dbcon.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Reset attempts + clear locked_until
    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = 0, locked_until = NULL WHERE identifier = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Redirect back with success message
    session_start();
    $_SESSION['status'] = "<div class='alert alert-success'>User has been unlocked successfully.</div>";
    header("Location: ../inner_pages/create_user.php");
    exit;
}
?>
