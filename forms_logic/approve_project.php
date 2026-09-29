<?php
session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_project'])) {
    $project_id = mysqli_real_escape_string($connection, $_POST['project_id']);
    $entered_po = mysqli_real_escape_string($connection, $_POST['po_number']);
    $role = $_SESSION['auth_user']['role'];

    // ✅ Use the correct column name: project_id
    $query = "SELECT po_num, user_approval, finance_approval FROM projects WHERE project_id = '$project_id'";
    $result = mysqli_query($connection, $query);
    $project = mysqli_fetch_assoc($result);

    if (!$project) {
        $_SESSION['status'] = "Project not found.";
        header("Location: ../user/dashboard.php");
        exit();
    }

    // Normalize PO number comparison (just in case of case/space difference)
    if (trim(strtoupper($project['po_num'])) !== trim(strtoupper($entered_po))) {
        $_SESSION['status'] = "Invalid P.O. Number.";
        header("Location: ../user/dashboard.php");
        exit();
    }

    // Update approval based on role
    if ($role == 'user' && $project['user_approval'] == 0) {
        $update = "UPDATE projects SET user_approval = 1 WHERE project_id = '$project_id'";
    } elseif ($role == 'finance' && $project['finance_approval'] == 0) {
        $update = "UPDATE projects SET finance_approval = 1 WHERE project_id = '$project_id'";
    } else {
        $_SESSION['status'] = "You have already approved or are not allowed.";
        header("Location: ../user/dashboard.php");
        exit();
    }

    mysqli_query($connection, $update);

    // Check if both approved, then mark project as Ongoing
    $check_approval = "SELECT user_approval, finance_approval FROM projects WHERE project_id = '$project_id'";
    $res = mysqli_fetch_assoc(mysqli_query($connection, $check_approval));
    if ($res['user_approval'] == 1 && $res['finance_approval'] == 1) {
        mysqli_query($connection, "UPDATE projects SET project_status = 'Ongoing' WHERE project_id = '$project_id'");
    }

    $_SESSION['status'] = "Project approved successfully.";
    header("Location: ../user/dashboard.php");
    exit();
}
?>
