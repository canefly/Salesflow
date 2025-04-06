<?php
session_start();
require_once '../Database/connection.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Get and sanitize form data
$use_subcategories = isset($_POST['use_subcategories']) ? 1 : 0;
$theme = mysqli_real_escape_string($conn, $_POST['theme']);
$currency_symbol = mysqli_real_escape_string($conn, $_POST['currency_symbol']);
$timezone = mysqli_real_escape_string($conn, $_POST['timezone']);
$time_format = (int)$_POST['time_format'];

// Check if user already has settings
$check = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = '$user_id'");

if (mysqli_num_rows($check) > 0) {
  // Update existing settings
  $update = mysqli_query($conn, "
    UPDATE user_settings SET
      use_subcategories = '$use_subcategories',
      theme = '$theme',
      currency_symbol = '$currency_symbol',
      timezone = '$timezone',
      time_format = '$time_format'
    WHERE user_id = '$user_id'
  ");
} else {
  // Insert new settings
  $insert = mysqli_query($conn, "
    INSERT INTO user_settings (user_id, use_subcategories, theme, currency_symbol, timezone, time_format)
    VALUES ('$user_id', '$use_subcategories', '$theme', '$currency_symbol', '$timezone', '$time_format')
  ");
}

// Optional: Redirect back to settings page
header("Location: ../Views/settings.php");
exit;
?>
