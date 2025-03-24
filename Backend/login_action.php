<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../Database/connection.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
