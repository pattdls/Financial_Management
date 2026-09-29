<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include('../dbcon.php');

// Authentication check
// if (!isset($_SESSION['authenticated']) || !in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])) {
//     $_SESSION['status'] = "<div class='alert alert-danger'>Access Denied.</div>";
//     header("Location: ../login_form.php");
//     exit(0);
// }

// Debug: Log session data
file_put_contents('debug_session.txt', print_r($_SESSION, true), FILE_APPEND);

// Get user ID
$user_id = $_SESSION['auth_user']['id'] ?? null;
if (!$user_id) {
    $_SESSION['status'] = "<div class='alert alert-danger'>Invalid user session.</div>";
    header("Location: ../inner_pages/my_profile.php");
    exit(0);
}

// File upload handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $upload_dir = '../uploads/images/';
    $upload_path = 'uploads/images/';

    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Check file size
        if ($file['size'] > $max_size) {
            $_SESSION['status'] = "<div class='alert alert-danger'>File size exceeds 5MB.</div>";
            header("Location: ../inner_pages/my_profile.php");
            exit(0);
        }

        // Check file type and validity
        if (!in_array($file['type'], $allowed_types) || !getimagesize($file['tmp_name'])) {
            $_SESSION['status'] = "<div class='alert alert-danger'>Only JPG, PNG, or GIF images are allowed.</div>";
            header("Location: ../inner_pages/my_profile.php");
            exit(0);
        }

        // Generate unique file name
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;

        // Move file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Delete old image if exists
            $old_image_query = "SELECT profile_image FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $old_image_query);
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $old_image = mysqli_fetch_assoc($result)['profile_image'];
            if ($old_image && file_exists('../' . $old_image)) {
                unlink('../' . $old_image);
            }
            mysqli_stmt_close($stmt);

            // Update database
            $image_path = $upload_path . $new_file_name;
            $query = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'si', $image_path, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['status'] = "<div class='alert alert-success'>Profile image updated successfully.</div>";
            } else {
                $_SESSION['status'] = "<div class='alert alert-danger'>Database update failed.</div>";
                unlink($destination); // Remove uploaded file if DB update fails
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['status'] = "<div class='alert alert-danger'>Failed to upload image.</div>";
        }
    } else {
        $_SESSION['status'] = "<div class='alert alert-danger'>Upload error: " . htmlspecialchars($file['error']) . "</div>";
    }
} else {
    $_SESSION['status'] = "<div class='alert alert-danger'>No file uploaded.</div>";
}

header("Location: ../inner_pages/my_profile.php");
exit(0);
?>