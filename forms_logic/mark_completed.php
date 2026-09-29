<?php

session_start(); 

$connection =mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];
    $today = date('Y-m-d');

    // Fetch client_id from the selected project
    $client_query = "SELECT client_id FROM projects WHERE project_id = '$project_id'";
    $client_result = mysqli_query($connection, $client_query);

    if ($client_result && mysqli_num_rows($client_result) > 0) {
        $client_row = mysqli_fetch_assoc($client_result);
        $client_id = $client_row['client_id']; // Get the client_id
    } else {
        $client_id = ""; // Default to empty if not found
    }

    // Update project status to 'Completed'
    $query = "UPDATE projects SET project_status = 'Completed', completed_Date ='$today' WHERE project_id = '$project_id'";
    mysqli_query($connection, $query);

    if ($query) {
        // Redirect to project_details.php with client_id and project_id
        $_SESSION['status_project'] = "Project marked as completed!";
        header("Location: ../inner_pages/projects.php?client_id=" . $client_id);
        exit();
    } else {
        die("Error updating project status: " . mysqli_error($connection));
    }
}
?>
