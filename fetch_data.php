<?php
include('dbcon.php');
header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode([]);
    exit;
}

// Fetch expenses
$query = "SELECT date, amount, category FROM expenses WHERE project_id = ? AND (addon_id = 0 OR addon_id IS NULL) ORDER BY date";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$expenses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $expenses[] = $row;
}

// Fetch projected_budget_cost from projects table
$budgetQuery = "SELECT projected_budget_cost FROM projects WHERE project_id = ?";
$stmt2 = mysqli_prepare($conn, $budgetQuery);
mysqli_stmt_bind_param($stmt2, "i", $project_id);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$project = mysqli_fetch_assoc($result2);

$response = [
    "expenses" => $expenses,
    "projected_budget_cost" => $project['projected_budget_cost'] ?? 0
];

echo json_encode($response);
?>
