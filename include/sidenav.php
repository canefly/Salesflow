<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow Sidenav</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 250px;
      background-color: #212529;
      transition: all 0.3s ease;
      overflow-x: hidden;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 15px;
      height: 60px;
      color: #adb5bd;
      text-decoration: none;
      padding-left: 20px;
      padding-right: 20px;
      border-left: 4px solid transparent;
      transition: background 0.2s, border 0.2s, color 0.2s;
      width: 100%;
      box-sizing: border-box;
      font-size: 0.95rem;
    }

    .nav-link:hover {
      background-color: #343a40;
      color: #fff;
      border-left: 4px solid #0d6efd;
    }

    .sidebar.collapsed .nav-link {
      justify-content: center;
      flex-direction: column;
      padding: 0;
      gap: 6px;
    }

    .nav-icon {
      width: 30px;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.3rem;
    }

    .nav-label {
      white-space: nowrap;
      transition: opacity 0.3s ease;
      display: inline-block;
    }

    .sidebar.collapsed .nav-label {
      display: none;
    }

    .toggle-btn {
      position: fixed;
      top: 15px;
      left: 260px;
      z-index: 1000;
      transition: left 0.3s ease;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
      background-color: white;
      border-radius: 8px;
      padding: 10px;
      border: none;
    }

    .toggle-btn i {
      color: black;
      font-size: 1.2rem;
    }

    .sidebar.collapsed + .toggle-btn {
      left: 80px;
    }

    .main-content {
      margin-left: 250px;
      transition: margin-left 0.3s ease;
      padding: 20px;
    }

    .sidebar.collapsed ~ .main-content {
      margin-left: 70px;
    }

    .nav-link.coming-soon {
      opacity: 0.5;
      cursor: not-allowed;
    }
    .nav-link.coming-soon:hover {
      background-color: transparent;
      border-left: 4px solid transparent;
    }
  </style>
</head>
<body>

  <div class="sidebar bg-dark" id="sidebar">
    <a class="nav-link" href="#"><div class="nav-icon"><i class="fas fa-home"></i></div><span class="nav-label">Dashboard</span></a>
    <a class="nav-link" href="#"><div class="nav-icon"><i class="fas fa-chart-line"></i></div><span class="nav-label">Stats</span></a>
    <a class="nav-link" href="#"><div class="nav-icon"><i class="fas fa-plus-circle"></i></div><span class="nav-label">Add Income</span></a>
    <a class="nav-link" href="#"><div class="nav-icon"><i class="fas fa-exchange-alt"></i></div><span class="nav-label">Transaction</span></a>
    <a class="nav-link coming-soon" href="#"><div class="nav-icon"><i class="fas fa-bolt"></i></div><span class="nav-label">Quick Shortcut</span></a>
    <a class="nav-link coming-soon" href="#"><div class="nav-icon"><i class="fas fa-user"></i></div><span class="nav-label">User Profile</span></a>
    <a class="nav-link coming-soon" href="#"><div class="nav-icon"><i class="fas fa-cogs"></i></div><span class="nav-label">Settings</span></a>
  </div>

  <button class="btn btn-outline-light toggle-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>

  <div class="main-content" id="main-content">
    
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('main-content');
      const toggleBtn = document.querySelector('.toggle-btn');
      sidebar.classList.toggle('collapsed');
      content.classList.toggle('expanded');

      if (sidebar.classList.contains('collapsed')) {
        toggleBtn.style.left = '80px';
      } else {
        toggleBtn.style.left = '260px';
      }
    }
  </script>

</body>
</html>