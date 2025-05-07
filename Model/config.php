<?php
// Ensure the session is only started once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "doctor_appointment_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);  // Log connection error
    die("Connection failed: " . $conn->connect_error);
}
?>
