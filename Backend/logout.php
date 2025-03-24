<?php
session_start();
session_unset();      // Unset all session variables
session_destroy();    // Destroy the session

// Optional: redirect with a logout flag
header("Location: ../Views/login.php?logout=success");
exit;
?>
