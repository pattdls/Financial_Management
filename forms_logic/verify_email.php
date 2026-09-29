<?php
session_start();
include('../dbcon.php');

if(isset($_GET['token']))
{
    //Used to fetch and check token
    $token = $_GET['token'];
    $verify_query = "SELECT verify_token, verify_status FROM users WHERE verify_token='$token' LIMIT 1";
    $verify_query_run = mysqli_query($conn, $verify_query);

    //Condition to check if token exists
    if(mysqli_num_rows($verify_query_run) > 0)
    {
        $row = mysqli_fetch_array($verify_query_run);
        // echo $row['verify_token'];

        //Updates the status of token, verified or not
        if($row['verify_status'] == "0")
        {
            $given_token = $row['verify_token'];
            $update_query = "UPDATE users SET verify_status='1' WHERE verify_token='$given_token' LIMIT 1";
            $update_query_run = mysqli_query($conn, $update_query);

            if($update_query_run)
            {
                $_SESSION['status'] = "<div class='alert-success'>Your account has been verified successfully!</div>";
                header("Location: ../login_form.php");
                exit(0);
            }
            else
            {
                $_SESSION['status'] = "<div class='alert'>Verification failed.</div>";
                header("Location: ../login_form.php");
                exit(0);
            }
        }
        else
        {
            $_SESSION['status'] = "<div class='alert'>Email already verified. Please login.</div>";
            header("Location: ../login_form.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "<div class='alert'>This token does not exist.</div>";
        header("Location: ../login_form.php");
    }
}
else
{
    $_SESSION['status'] = "<div class='alert'>Not Allowed</div>";
    header("Location: ../login_form.php");
}


?>