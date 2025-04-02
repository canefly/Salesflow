<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Salesflow Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
      --sidebar-width: 250px;
      --sidebar-bg: #212529;
      --sidebar-hover: #343a40;
      --sidebar-active: #0d6efd;
      --text-color: #ffffff;
      --text-muted: #adb5bd;
      --transition: all 0.3s ease;
      --radius: 8px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
      color: #333;
    }

    .sidebar {
      width: 70px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: var(--sidebar-bg);
      color: var(--text-color);
      display: flex;
      flex-direction: column;
      transition: var(--transition);
      overflow-y: auto;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
      padding-top: 1rem;
    }

    .sidebar:not(.collapsed) {
      width: var(--sidebar-width);
    }

    .sidebar-header {
      text-align: center;
      padding: 1rem;
      font-weight: 600;
      font-size: 1.2rem;
      color: var(--text-color);
    }

    .nav-link {
      display: flex;
      align-items: center;
      padding: 0.75rem 1rem;
      color: var(--text-muted);
      text-decoration: none;
      transition: var(--transition);
      border-left: 4px solid transparent;
    }

    .nav-link:hover,
    .nav-link.active {
      background: var(--sidebar-hover);
      color: #fff;
      border-left: 4px solid var(--sidebar-active);
    }

    .nav-icon {
      width: 30px;
      display: flex;
      justify-content: center;
      font-size: 1.2rem;
    }

    .nav-label {
      margin-left: 12px;
      white-space: nowrap;
    }

    .sidebar.collapsed .nav-label {
      display: none;
    }

    .toggle-btn {
      position: fixed;
      top: 1rem;
      left: 80px;
      z-index: 1001;
      background: #fff;
      border: none;
      border-radius: 50%;
      padding: 0.5rem 0.7rem;
      cursor: pointer;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.15);
      transition: var(--transition);
    }

    .sidebar:not(.collapsed) ~ .toggle-btn {
      left: calc(var(--sidebar-width) + 10px);
    }
</style>
<div class="sidebar collapsed" id="sidebar">
    <div class="sidebar-header">
      <i class="fas fa-chart-line"></i> <span class="nav-label">SalesFlow</span>
    </div>
    <a href="../Views/dashboard.php" class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-home"></i></div>
      <div class="nav-label">Dashboard</div>
    </a>
    <a href="../Views/stats.php" class="nav-link <?= $currentPage == 'stats.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
      <div class="nav-label">Stats</div>
    </a>
    <a href="../Views/addincome.php" class="nav-link <?= $currentPage == 'addincome.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-plus-circle"></i></div>
      <div class="nav-label">Add Income</div>
    </a>
    <a href="../Views/transaction.php" class="nav-link <?= $currentPage == 'transaction.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-right-left"></i></div>
      <div class="nav-label">Transaction</div>
    </a>
    <a href="../Views/quickshortcut.php" class="nav-link <?= $currentPage == 'quickshortcut.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-bolt"></i></div>
      <div class="nav-label">Quick Shortcut</div>
    </a>
    <a href="../Views/userprofile.php" class="nav-link <?= $currentPage == 'userprofile.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-user"></i></div>
      <div class="nav-label">User Profile</div>
    </a>
    <a href="../Views/settings.php" class="nav-link <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
      <div class="nav-icon"><i class="fas fa-gear"></i></div>
      <div class="nav-label">Settings</div>
    </a>
    <a href="../Backend/logout.php" class="nav-link">
      <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
      <div class="nav-label">Logout</div>
    </a>
</div>

<button class="toggle-btn" id="toggleBtn">
    <i class="fas fa-bars"></i>
</button>

<script>
  (function() {
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      document.body.classList.toggle('sidebar-collapsed');
    });
  })();
</script>
