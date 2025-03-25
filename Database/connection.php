<?php
$host = 'localhost';
$dbname = 'salesflow';
$username = 'root';
$password = ''; // Leave blank for XAMPP

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
