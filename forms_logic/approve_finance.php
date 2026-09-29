<?php
session_start();
include('../dbcon.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] === 'finance') {
    $po_num = mysqli_real_escape_string($conn, $_POST['po_num']);
    $project_id = intval($_POST['project_id']);

    // Fetch the actual PO number from the database for this project
    $check_query = "SELECT po_num FROM projects WHERE project_id = $project_id LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);

    if ($row = mysqli_fetch_assoc($check_result)) {
        $actual_po = $row['po_num'];

        if ($po_num === $actual_po) {
            // PO number matches — proceed with approval
            $query = "UPDATE projects SET finance_approval = 1 WHERE project_id = $project_id";
            if (mysqli_query($conn, $query)) {
                header("Location: ../inner_pages/proj_stat.php?project_id=$project_id&finance_approved=1");
                exit();
            } else {
                echo "Error updating approval: " . mysqli_error($conn);
            }
        } else {
            // PO number mismatch — redirect back with error
            $_SESSION['status'] = "<div class='alert alert-danger'>Incorrect PO number. Please try again.</div>";
            header("Location: ../inner_pages/proj_stat.php?project_id=$project_id&error=invalid_po");
            exit();
        }
    } else {
        echo "Project not found.";
    }
} else {
    echo "Unauthorized or Invalid Access.";
}
?>
