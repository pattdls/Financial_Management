<?php
include('dbcon.php');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id > 0) {
    $query = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE project_id = $project_id";
    $result = mysqli_query($conn, $query);

    $total_expenses = 0;
    if ($row = mysqli_fetch_assoc($result)) {
        $total_expenses = $row['total_expenses'];
    }

    echo "<p>Total Expenses: ₱" . number_format($total_expenses, 2) . "</p>";
} else {
    echo "<p style='color: gray;'>Select a project to view expenses</p>";
}
?>
