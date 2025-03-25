<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../Database/connection.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Prepare MySQLi statement
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    header("Location: ../Views/dashboard.php");
    exit;
} else {
    header("Location: ../Views/login.php?error=invalid");
    exit;
}
?>
