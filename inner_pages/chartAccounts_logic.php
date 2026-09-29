<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 
date_default_timezone_set('Asia/Manila');

// To get the logged in user 
$user_id = $_SESSION['auth_user']['id'] ?? null;
$conn->query("SET time_zone = '+08:00'");
$now = date('Y-m-d H:i:s'); // This is PH time

if(isset($_POST['save_accountTitle']))
{
    $recorder_id = $_SESSION['auth_user']['id'] ?? null;
    $accountNum = $_POST['accountNum'];
    $account_des = $_POST['accountDes'];
    $account_type = $_POST['account_type'];
    $form_usage = $_POST['form_usage'];
    $statement_field = $_POST['statement_field'];

      // Check for duplicate account title
    $check_query = "SELECT * FROM chart_accounts WHERE category = '$account_des' AND account_type = '$account_type' AND form_usage = '$form_usage'";
    $check_result = mysqli_query($conn, $check_query);

    if(mysqli_num_rows($check_result) > 0){
        $_SESSION['title_status'] = "Warning";
        $_SESSION['title_message'] = "Account title <strong>{$account_des}</strong> already exists under {$account_type} and is currently being used in the {$form_usage} form.";
    } else {
        $insert_query= "INSERT INTO chart_accounts (user_id,account_num,category,account_type,form_usage,statement_field,created_at) 
                        VALUES ('$recorder_id', '$accountNum', '$account_des', '$account_type', '$form_usage', '$statement_field', '$now')";
        if(mysqli_query($conn, $insert_query)){
            $_SESSION['title_status'] = "Account Title Successfully Added";
            $_SESSION['title_message'] = "Account title <strong>{$account_des}</strong> has been added successfully and can be now used for categorizing expenses.";
        } else {
            $_SESSION['title_status'] = "Error";
            $_SESSION['title_message'] = "Something went wrong. Please try again.";
        }
    }

    // This is for redirection once account is added
    $anchor = '';
    switch($form_usage){
        case 'Project Expense':
            $anchor = 'project_expense_accounts';
            break;
        case 'Company Expense':
            $anchor = 'company_expense_accounts';
            break;
        case 'Payment Transaction':
            $anchor = 'payment_accounts';
            break;
        default:
            $anchor = 'project_expense_accounts';
    }
   header("Location: chartaccounts.php?success=1#{$anchor}");
   exit();
}

if (isset($_GET['account_type'])) {
    $accountType = $_GET['account_type'];

    // Define starting numbers based on account type
    $startingNumbers = [
        "Asset" => 1001,
        "Liability" => 2001,
        "Equity" => 3001,
        "Revenue" => 4001,
        "Expense" => 5001
    ];

   
    $query = "SELECT MAX(account_num) AS last_number FROM chart_accounts WHERE account_type = '$accountType'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row['last_number']) {
        echo $row['last_number'] + 1; // Increment by 1
    } else {
        echo $startingNumbers[$accountType]; // Use starting number if no record exists
    }
}

?>