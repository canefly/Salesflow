<?php session_start(); ?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Salesflow</title>

  <!-- Bootstrap CSS for layout and components -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Custom Styles -->
  <link rel="stylesheet" href="../public/css/styles.css" />
  <link rel="stylesheet" href="../public/css/floatingMoney.css">

  <style>
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
  </style>

  <!-- AOS (Animate On Scroll) for scroll animations -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
  <?php include '../include/chat.html'; ?>
  <!-- JavaScript for interactivity (navbar, chat, etc.) -->
  <script src="../public/js/main.js"></script>
  <script src="../public/js/floatingMoney.js" defer></script>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
    <div class="container">
      <a class="navbar-brand" href="#">Salesflow</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero d-flex align-items-center justify-content-center text-center">
    <div class="container">
      <h1 class="display-4 fw-bold">Revolutionize Your Business with AI</h1>
      <p class="lead">Track expenses, optimize profits, and stay ahead — all in one powerful platform.</p>
      <a href="login.php" class="btn btn-primary btn-lg mt-3">Get Started</a>
    </div>
  </section>

  <!-- Why SalesFlow Section -->
  <section class="why-salesflow py-5 bg-light text-center" data-aos="fade-up">
    <div class="container">
      <h2 class="mb-4 fw-bold">Why SalesFlow?</h2>
      <div class="row justify-content-center align-items-center">
        <div class="col-md-6">
          <img src="../Assets/Illustrations/grammarcorrection.svg" class="img-fluid mb-3" alt="Why SalesFlow">
        </div>
        <div class="col-md-6 text-start">
          <p class="lead">
            SalesFlow is built for modern Filipino business owners who want to track income and growth without drowning in spreadsheets. It adapts to chaos rather than trying to control it.
          </p>
          <p>
            Whether you're running a sari-sari store, a small e-commerce shop, or a side hustle, SalesFlow empowers you to make better decisions through clear data, helpful AI insights, and intuitive controls.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features py-5 text-center" id="features" data-aos="fade-up">
    <div class="container">
      <h2 class="mb-4">Key Features</h2>

      <!-- Feature 1 -->
      <div class="row align-items-center mb-5" data-aos="fade-up">
        <div class="col-md-6">
          <img src="../Assets/Illustrations/debugdata.svg" class="img-fluid" alt="Sales Tracking">
        </div>
        <div class="col-md-6 text-start">
          <h3 class="fw-bold">Track Sales in Real-Time</h3>
          <p>Easily input product names, income amounts, and dates to monitor your performance with zero hassle.</p>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="row align-items-center mb-5 flex-md-row-reverse" data-aos="fade-up">
        <div class="col-md-6">
          <img src="../Assets/Illustrations/analytics.svg" class="img-fluid" alt="Product Analytics">
        </div>
        <div class="col-md-6 text-start">
          <h3 class="fw-bold">Product Performance</h3>
          <p>Visual analytics highlight trending and underperforming products to guide your strategy and growth focus.</p>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="row align-items-center mb-5" data-aos="fade-up">
        <div class="col-md-6">
          <img src="../Assets/Illustrations/OSupgrade.svg" class="img-fluid" alt="AI Insights">
        </div>
        <div class="col-md-6 text-start">
          <h3 class="fw-bold">AI-Powered Recommendations</h3>
          <p>Seraphina, your AI assistant, provides insight into sales patterns and suggests improvements based on real data.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">&copy; 2025 Salesflow. All rights reserved.</p>
  </footer>

  <!-- Bootstrap Bundle JS (for dropdowns, collapses, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- AOS JS (scroll animations) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>

</body>
</html>
