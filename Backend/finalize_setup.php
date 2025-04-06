<?php
session_start();
require '../Database/connection.php'; // Adjust path if needed

// Only let a first-time user run this
if (!isset($_SESSION['user_id']) || !isset($_SESSION['first_time']) || $_SESSION['first_time'] !== true) {
  echo json_encode(['status' => 'unauthorized']);
  exit;
}

$user_id = $_SESSION['user_id'];

// Pull posted data
$full_name   = trim($_POST['full_name'] ?? '');
$theme       = $_POST['theme']        ?? 'light';
$timezone    = $_POST['timezone']     ?? 'Asia/Manila';
$time_format = $_POST['time_format']  ?? '12';
$currency    = $_POST['currency']     ?? 'PHP';

// If user uploaded a profile pic
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
  $target_dir = '../uploads/profiles/';
  if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
  }
  $target_file = $target_dir . 'user_' . $user_id . '.jpg';
  move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);

  // Save filename in DB
  $stmt = $conn->prepare("UPDATE user_profiles SET profile_image = ? WHERE user_id = ?");
  $filename = 'user_' . $user_id . '.jpg';
  $stmt->bind_param("si", $filename, $user_id);
  $stmt->execute();
}

// Update full_name if provided
if ($full_name !== '') {
  $stmt = $conn->prepare("UPDATE user_profiles SET full_name = ? WHERE user_id = ?");
  $stmt->bind_param("si", $full_name, $user_id);
  $stmt->execute();
}

// Update user_settings
$stmt = $conn->prepare("UPDATE user_settings
                        SET theme = ?, timezone = ?, time_format = ?, currency_symbol = ?
                        WHERE user_id = ?");
$stmt->bind_param("ssssi", $theme, $timezone, $time_format, $currency, $user_id);
$stmt->execute();

// Setup complete
unset($_SESSION['first_time']);

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
exit;
