<?php
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP MySQL password is blank
$database = "dashboard_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: ensure UTF-8 character encoding
$conn->set_charset("utf8mb4");
?>