<?php
session_start();
require '../Database/connection.php'; // Adjust this path if needed

// Get submitted form data
$email = $_POST['email'];
$password = $_POST['password'];

// Query for the user by email
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user exists and password matches
if ($user && password_verify($password, $user['password'])) {
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    // Redirect to dashboard
    header("Location: ../Views/dashboard.php");
    exit;
} else {
    // Redirect back with error
    header("Location: ../Views/login.php?error=invalid");
    exit;
}
?>
