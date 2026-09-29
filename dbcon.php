<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'financial_management'; // Replace this with your actual database name

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>