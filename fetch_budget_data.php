<?php
// fetch_budget_data.php - Create this new file in your project root
include('dbcon.php');
include('./layout/session_check.php');

header('Content-Type: application/json');

if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    echo json_encode(['error' => 'Project ID is required']);
    exit;
}

$project_id = mysqli_real_escape_string($conn, $_GET['project_id']);

// Get client_id from session or determine it from project
$client_id = $_SESSION['client_id'] ?? null;

// If no client_id in session, get it from the project
if (!$client_id) {
    $clientQuery = "SELECT client_id FROM projects WHERE project_id = '$project_id'";
    $clientResult = mysqli_query($conn, $clientQuery);
    if ($clientResult && $row = mysqli_fetch_assoc($clientResult)) {
        $client_id = $row['client_id'];
    }
}

if (!$client_id) {
    echo json_encode(['error' => 'Client ID not found']);
    exit;
}

try {
    // Get allocated budget amounts
    $display_query = "SELECT allocated_budget, materials_cost, labor_cost, other_expenses_cost FROM projects 
                     WHERE client_id = '$client_id' AND project_id = '$project_id'";
    $display_result = mysqli_query($conn, $display_query);
    
    if (!$display_result || mysqli_num_rows($display_result) == 0) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }
    
    $row = mysqli_fetch_assoc($display_result);
    $materials_budget = floatval($row['materials_cost']);
    $labor_budget = floatval($row['labor_cost']);
    $other_budget = floatval($row['other_expenses_cost']);

    // Get actual expenditures for materials
    $material_cost_query = "SELECT SUM(amount) AS total_materials FROM expenses
                           WHERE client_id = '$client_id' 
                           AND project_id = '$project_id' 
                           AND (category LIKE '%Construction%' OR category LIKE '%Materials%')
                           AND (addon_id IS NULL OR addon_id = 0)";
    $material_cost_result = mysqli_query($conn, $material_cost_query);
    $material_cost_row = mysqli_fetch_assoc($material_cost_result);
    $materials_spent = floatval($material_cost_row['total_materials'] ?? 0);

    // Get actual expenditures for labor
    $labor_cost_query = "SELECT SUM(amount) AS total_labor FROM expenses
                        WHERE client_id = '$client_id' 
                        AND project_id = '$project_id' 
                        AND (category LIKE '%Salary%' OR category LIKE '%Labor%')
                        AND (addon_id IS NULL OR addon_id = 0)";
    $labor_cost_result = mysqli_query($conn, $labor_cost_query);
    $labor_cost_row = mysqli_fetch_assoc($labor_cost_result);
    $labor_spent = floatval($labor_cost_row['total_labor'] ?? 0);

    // Get actual expenditures for other expenses
    $other_expenses_query = "SELECT SUM(amount) AS total_others FROM expenses
                            WHERE client_id = '$client_id' 
                            AND project_id = '$project_id' 
                            AND NOT (category LIKE '%Construction%' OR category LIKE '%Materials%'
                            OR category LIKE '%Salary%' OR category LIKE '%Labor%')
                            AND (addon_id IS NULL OR addon_id = 0)";
    $other_expenses_result = mysqli_query($conn, $other_expenses_query);
    $other_expenses_row = mysqli_fetch_assoc($other_expenses_result);
    $other_spent = floatval($other_expenses_row['total_others'] ?? 0);

    // Calculate remaining balances
    $materials_remaining = $materials_budget - $materials_spent;
    $labor_remaining = $labor_budget - $labor_spent;
    $other_remaining = $other_budget - $other_spent;

    // Ensure remaining amounts are not negative for display purposes
    $materials_remaining = max(0, $materials_remaining);
    $labor_remaining = max(0, $labor_remaining);
    $other_remaining = max(0, $other_remaining);

    $response = [
        'materials_budget' => $materials_budget,
        'labor_budget' => $labor_budget,
        'other_budget' => $other_budget,
        'materials_spent' => $materials_spent,
        'labor_spent' => $labor_spent,
        'other_spent' => $other_spent,
        'materials_remaining' => $materials_remaining,
        'labor_remaining' => $labor_remaining,
        'other_remaining' => $other_remaining
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>