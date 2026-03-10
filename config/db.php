<?php
mysqli_report(MYSQLI_REPORT_OFF);
// Database configuration
$host = "localhost";
$user = "root"; // Change if using a different user
$password = ""; // Set your MySQL password
$database = "bilpham_outpatients_system";

// Create a database connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");
?>
