<?php
session_start();
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Salesflow</title>

  <!-- Bootstrap & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Main stylesheet -->
  <link rel="stylesheet" href="../public/css/styles.css" />

  <style>
    body.login-page {
      min-height: 100vh;
      background:
        radial-gradient(circle at 20% 30%, #001f4d 0%, transparent 40%),
        radial-gradient(circle at 60% 20%, #004080 0%, transparent 50%),
        radial-gradient(circle at 80% 60%, #0074d9 0%, transparent 45%),
        radial-gradient(circle at 30% 80%, #00bfff 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, #0061b3 0%, transparent 40%),
        radial-gradient(circle at 70% 70%, #003366 0%, transparent 50%),
        radial-gradient(circle at 10% 90%, #0096c7 0%, transparent 45%);
      background-color: #001f4d;
      background-size: 250% 250%;
      animation: meshFlow 30s ease-in-out infinite;
      color: white;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-wrapper {
      background: #fff;
      color: #000;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
      max-width: 420px;
      width: 100%;
      margin: 100px auto;
    }

    .login-wrapper h2 {
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      color: #003366;
      margin-bottom: 20px;
    }

    .btn-primary, .btn-outline-dark {
      width: 100%;
      font-weight: 600;
    }

    .text-link {
      display: block;
      text-align: center;
      margin-top: 1rem;
      color: #005f91;
      text-decoration: none;
    }

    .text-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body class="login-page">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
    <div class="container">
      <a class="navbar-brand" href="homepage.php">Salesflow</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="homepage.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Login Box -->
  <div class="login-wrapper">
    <h2>Log In to Continue</h2>
    <form method="POST" action="../backend/login_action.php">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required />
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <input type="password" class="form-control" id="password" name="password" required />
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">👁️</button>
        </div>
      </div>
      <a href="#" class="text-link">Forgot Password?</a>
      <button type="submit" class="btn btn-primary mt-3">Login</button>
    </form>

    <div class="text-center mt-3">or</div>

    <a href="register.php" class="text-link">New here? Create an account</a>
  </div>

  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      pass.type = pass.type === "password" ? "text" : "password";
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
