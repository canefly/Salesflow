<?php
session_start();
require '../Database/connection.php'; // Ensure this path is correct

// Get form inputs
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// Check if passwords match
if ($password !== $confirm) {
  header("Location: ../Views/register.php?error=nomatch");
  exit;
}

// Enforce password strength: 8+ chars, uppercase, number
if (strlen($password) < 8 || 
    !preg_match('/[A-Z]/', $password) || 
    !preg_match('/[0-9]/', $password)) {
  header("Location: ../Views/register.php?error=weakpass");
  exit;
}

// Check if email already exists
$check = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
  header("Location: ../Views/register.php?error=emailtaken");
  exit;
}

// Hash the password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$insert->execute([$username, $email, $hashed]);

// Auto-login the user
$_SESSION['user_id'] = $conn->lastInsertId();
$_SESSION['username'] = $username;

// Redirect to dashboard
header("Location: ../Views/dashboard.php");
exit;
?>
