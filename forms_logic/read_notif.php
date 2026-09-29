<?php 
session_start();
include('../dbcon.php');

if (isset($_GET['notif_id'], $_GET['project_id'])) {
    $notif_id = $_GET['notif_id'];
    $project_id = $_GET['project_id'];

   
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE notif_id = $notif_id");
   

    header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    exit;
}

// Handle notifications from notifications.php (finance/admin users)
if (isset($_GET['notif_id'], $_GET['project_id'])) {
    $notif_id = intval($_GET['notif_id']);
    $project_id = intval($_GET['project_id']);

    // Mark as read in user_notifications table (not notifications table)
    $update_query = "UPDATE user_notifications SET is_read = 1 WHERE notif_id = $notif_id";
    mysqli_query($conn, $update_query);

    // Fetch notification details to determine redirect
    $notif_query = "SELECT message, type FROM user_notifications WHERE notif_id = $notif_id LIMIT 1";
    $notif_result = mysqli_query($conn, $notif_query);
    
    if ($notif_result && mysqli_num_rows($notif_result) > 0) {
        $notif_row = mysqli_fetch_assoc($notif_result);
        $notif_message = $notif_row['message'];
        $notif_type = $notif_row['type'] ?? '';

        // Check if it's a project approval notification for finance users
        if ($notif_type === 'project_approval' || stripos($notif_message, 'approval') !== false) {
            // Redirect to project status page for approval
            header("Location: ../inner_pages/proj_stat.php?project_id=$project_id");
            exit;
        }
    }

    // Default redirect to project budget summary for other notifications
    header("Location: ../inner_pages/proj_budget_sum.php?project_id=$project_id");
    exit;
}

// Handle notifications from user_notifications.php (client users)
if (isset($_GET['user_notif_id'], $_GET['user_project_id'])) {
    $notif_id = intval($_GET['user_notif_id']);
    $project_id = intval($_GET['user_project_id']);

    // Mark as read in user_notifications table
    $update_query = "UPDATE user_notifications SET is_read = 1 WHERE notif_id = $notif_id";
    mysqli_query($conn, $update_query);

    // Fetch notification type or message
    $notif_query = "SELECT message, type FROM user_notifications WHERE notif_id = $notif_id LIMIT 1";
    $notif_result = mysqli_query($conn, $notif_query);
    
    if ($notif_result && mysqli_num_rows($notif_result) > 0) {
        $notif_row = mysqli_fetch_assoc($notif_result);
        $notif_message = $notif_row['message'];
        $notif_type = $notif_row['type'] ?? '';

        // Decide modal type based on notification type or message content
        if ($notif_type === 'project_approval' || stripos($notif_message, 'approval') !== false) {
            $modal_type = 'approval';
        } else {
            $modal_type = 'view';
        }

        // Redirect to user dashboard with project info
        header("Location: ../user/dashboard.php?project_id=$project_id&modal_type=$modal_type");
        exit;
    }

    // Fallback if notification not found
    header("Location: ../user/dashboard.php?project_id=$project_id");
    exit;
}

// Fallback: redirect to appropriate dashboard based on user role
if (isset($_SESSION['auth_user']['role'])) {
    $role = $_SESSION['auth_user']['role'];
    if ($role === 'finance' || $role === 'admin') {
        header("Location: ../inner_pages/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
} else {
    header("Location: ../login_form.php");
}
exit;
?>