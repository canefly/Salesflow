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

// Enforce password strength
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

// Insert user
$insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$insert->bind_param("sss", $username, $email, $hashed);

if ($insert->execute()) {
    $user_id = $insert->insert_id;

    // Create profile and settings
    $conn->query("INSERT INTO user_profiles (user_id) VALUES ($user_id)");
    mysqli_query($conn, "INSERT INTO user_settings (user_id, theme) VALUES ($user_id, 'light')");

     // Session data
     $_SESSION['user_id'] = $user_id;
     $_SESSION['username'] = $username;
     $_SESSION['first_time'] = true; // 👈 THIS is the magic flag
 
     // Redirect to setup wizard
     header("Location: ../Views/setup.php");
     exit;
 } else {
     header("Location: ../Views/register.php?error=insertfail");
     exit;
 }
 ?>