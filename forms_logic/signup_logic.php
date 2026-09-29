<?php
session_start();
include('../dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

function sendemail_verfy($name,$email,$verify_token)
{

    $mail = new PHPMailer(true);

    $mail->isSMTP();   
    $mail->SMTPAuth   = true;

    $mail->Host       = 'smtp.gmail.com'; 
    $mail->Username   = 'cacorereservation@gmail.com';
    $mail->Password   = 'gdbsgyjujydcsqdb';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;          
    $mail->Port       = 465;     
    
    $mail->setFrom('cacorereservation@gmail.com', 'Qiell');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Email Verification for Financial Management';
    
    $email_template = "
    <h2>Email Verification</h2>
        <p>Hi <strong>{{name}}</strong>,</p>
        <p>Thank you for signing up! Please verify your email address by clicking the button below:</p>
        <a href='http://localhost/Financial_Management/forms_logic/verify_email.php?token=$verify_token'>Verify Your Email</a>
        <p>If you did not sign up, you can safely ignore this email.</p>
    ";

    $mail->Body = $email_template;
    $mail->send();
    // echo 'Message has been sent.';
}

if(isset($_POST['signup_btn']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $conpassword = $_POST['conpassword'];
    $verify_token = md5(rand());

    //used to check if all fields have input
    if (empty($name) || empty($email) || empty($password) || empty($conpassword)) 
    {
        $_SESSION['status'] = "<div class='alert-danger'>All fields are required. Please fill out the form completely.</div>"; 
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;

        header("Location: ../signup_form.php");
        exit();
    }

    //validates if passwords match
    if ($password !== $conpassword)
    {

        
        $_SESSION['status'] = "<div class='alert'>Passwords do not match. Try again!</div>";

        //Used to keep the name and email
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['email'] = $_POST['email'];

        header("Location: ../signup_form.php");
        exit(0);
    }

    //validate if the password is secured
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>\/?`~]{8,}$/', $password)) {
        $_SESSION['status'] = "<div class='alert-danger'>Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character.</div>";
       
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['email'] = $_POST['email'];
        
        header("Location: ../signup_form.php");
        exit();
    }


    //Email exists or not
    $check_email_q = "SELECT email FROM users WHERE email = '$email' LIMIT 1";
    $check_email_q_run = mysqli_query($conn,  $check_email_q);

    //Condition to check for existing accounts
     if(mysqli_num_rows($check_email_q_run) > 0)
     {
        $_SESSION['status'] = "<div class='alert'>Email already exists.</div>";
        header("Location: ../signup_form.php");
     }
     else{
        $query = "INSERT INTO users (name,email,password,verify_token) VALUES ('$name', '$email', '$password', '$verify_token')";
        $query_run = mysqli_query($conn, $query);

        if($query_run){

            sendemail_verfy("$name", "$email", "$verify_token");

            $_SESSION['status'] = "<div class='alert-success'>Signed up successfully! Please check your email for verification.</div>";

            unset($_SESSION['name']);
            unset($_SESSION['email']);

            header("Location: ../signup_form.php");
        }
        else{
            $_SESSION['status'] = "<div class='alert'>Signing up failed. Please check your inputs.</div>";
            header("Location: ../signup_form.php");
        }
     }
}

?>