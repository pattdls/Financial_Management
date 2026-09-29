<?php
session_start();
include('../dbcon.php');
header('Content-Type: application/json');

//For expense list of each project
if (isset($_GET['clearBTN']) && $_GET['clearBTN'] =='true' ) {
    unset($_SESSION['period_start']);
    unset($_SESSION['period_end']);

    $project_id = $_GET['project_id'];
   
     header("Location: ../inner_pages/project_details.php?project_id=$project_id");

    exit();
}

$client_id = $_GET['client_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
$start_period = $_GET['start_period'] ?? null;
$end_period = $_GET['end_period'] ?? null;

if (!empty($start_period) && !empty($end_period)) {
    // Filtered query
    $query = "SELECT * FROM expenses WHERE client_id = '$client_id' AND project_id = '$project_id'
              AND date BETWEEN '$start_period' AND '$end_period' ORDER BY date ASC";
    $_SESSION['period_start'] = $start_period;
    $_SESSION['period_end'] = $end_period;
} else {
    // No filter, get all
    $query = "SELECT * FROM expenses WHERE client_id = '$client_id' AND project_id = '$project_id' ORDER BY date ASC";
    unset($_SESSION['period_start'], $_SESSION['period_end']);
}

$run = mysqli_query($conn, $query);

//For balance sheet
if (isset($_GET['clearbtn']) && $_GET['clearbtn'] == 'true'){
    unset($_SESSION['balance_period_start']);
    unset($_SESSION['balance_period_end']);

    $project_id = $_GET['project_id'];
   
    header("Location: ../inner_pages/balance.php?project_id=$project_id");

   exit();
}
if (isset($_GET['start_balance']) && isset($_GET['end_balance'])) {
    $_SESSION['balance_period_start'] = $_GET['start_balance'];
    $_SESSION['balance_period_end'] = $_GET['end_balance'];
       
    $project_id = $_GET['project_id'];
    
    header("Location: ../inner_pages/balance.php?project_id=$project_id");
    exit;
} 
//For income statement
if (isset($_GET['clrbtn']) && $_GET['clrbtn'] == 'true'){
    unset($_SESSION['income_period_start']);
    unset($_SESSION['income_period_end']);

    $project_id = $_GET['project_id'];
   
    header("Location: ../inner_pages/Income.php?project_id=$project_id");

   exit();
}
if (isset($_GET['start_income']) && isset($_GET['end_income'])) {
    $_SESSION['income_period_start'] = $_GET['start_income'];
    $_SESSION['income_period_end'] = $_GET['end_income'];
       
    $project_id = $_GET['project_id'];
    
    header("Location: ../inner_pages/Income.php?project_id=$project_id");
    exit;
} 
//For cashflow
if (isset($_GET['clearness']) && $_GET['clearness'] == 'true'){
    unset($_SESSION['cashflow_period_start']);
    unset($_SESSION['cashflow_period_end']);

    $project_id = $_GET['project_id'];
   
    header("Location: ../inner_pages/cashflow.php?project_id=$project_id");

   exit();
}
if (isset($_GET['start_cashflow']) && isset($_GET['end_cashflow'])) {
    $_SESSION['cashflow_period_start'] = $_GET['start_cashflow'];
    $_SESSION['cashflow_period_end'] = $_GET['end_cashflow'];
       
    $project_id = $_GET['project_id'];
    
    header("Location: ../inner_pages/cashflow.php?project_id=$project_id");
    exit;
} 
//For journal entry
if (isset($_GET['klir']) && $_GET['klir'] == 'true'){
    unset($_SESSION['journal_period_start']);
    unset($_SESSION['journal_period_end']);

    $project_id = $_GET['project_id'];
   
    header("Location: ../inner_pages/journal.php?project_id=$project_id");

   exit();
}
if (isset($_GET['start_journal']) && isset($_GET['end_journal'])) {
    $_SESSION['journal_period_start'] = $_GET['start_journal'];
    $_SESSION['journal_period_end'] = $_GET['end_journal'];
       
    $project_id = $_GET['project_id'];
    
    header("Location: ../inner_pages/journal.php?project_id=$project_id");
    exit;
} 
?>
