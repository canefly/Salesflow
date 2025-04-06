<?php
session_start();
require '../Database/connection.php'; // Confirm path is correct

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
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    header("Location: ../Views/register.php?error=emailtaken");
    exit;
}

// Hash the password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$insert->bind_param("sss", $username, $email, $hashed);

if ($insert->execute()) {
    $_SESSION['user_id'] = $insert->insert_id;
    $_SESSION['username'] = $username;

    header("Location: ../Views/dashboard.php");
    exit;
} else {
    header("Location: ../Views/register.php?error=insertfail");
    exit;
}
?>
