<div class="mobile-fab">
  <button id="fabToggle" class="fab-main"><i class="fas fa-ellipsis-v"></i></button>
  <div class="fab-menu">
    <a href="../Views/dashboard.php" class="fab-button"><i class="fas fa-home"></i></a>
    <a href="../Views/stats.php" class="fab-button"><i class="fas fa-chart-line"></i></a>
    <a href="../Views/addincome.php" class="fab-button"><i class="fas fa-plus-circle"></i></a>
    <a href="../Views/transaction.php" class="fab-button"><i class="fas fa-right-left"></i></a>
    <a href="../Views/quickshortcut.php" class="fab-button"><i class="fas fa-bolt"></i></a>
    <a href="../Views/userprofile.php" class="fab-button"><i class="fas fa-user"></i></a>
    <a href="../Views/settings.php" class="fab-button"><i class="fas fa-gear"></i></a>
    <a href="../Backend/logout.php" class="fab-button"><i class="fas fa-sign-out-alt"></i></a>
  </div>
</div>

<style>
  .mobile-fab {
    display: none;
  }

  @media (max-width: 768px) {
    .sidebar {
      display: none !important;
    }

    .mobile-fab {
      display: block;
      position: fixed;
      bottom: 100px;
      right: 1.8rem;
      left: auto;
      z-index: 9999;
    }

    .fab-main {
      background: #0d6efd;
      color: white;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .fab-menu {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 1rem;
      gap: 0.75rem;
      position: absolute;
      bottom: 70px;
      right: 5px;
      opacity: 0;
      pointer-events: none;
      transition: all 0.3s ease;
    }

    .fab-menu.show {
      display: flex;
      opacity: 1;
      pointer-events: auto;
    }

    .fab-button {
      background: #343a40;
      color: white;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      text-align: center;
      line-height: 50px;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      transform: scale(0.5) translateY(20px);
      opacity: 0;
    }

    .fab-menu.show .fab-button {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    .fab-menu.show .fab-button:nth-child(1) {
      transition-delay: 0.35s;
    }
    .fab-menu.show .fab-button:nth-child(2) {
      transition-delay: 0.3s;
    }
    .fab-menu.show .fab-button:nth-child(3) {
      transition-delay: 0.25s;
    }
    .fab-menu.show .fab-button:nth-child(4) {
      transition-delay: 0.2s;
    }
    .fab-menu.show .fab-button:nth-child(5) {
      transition-delay: 0.15s;
    }
    .fab-menu.show .fab-button:nth-child(6) {
      transition-delay: 0.1s;
    }
    .fab-menu.show .fab-button:nth-child(7) {
      transition-delay: 0.05s;
    }
    .fab-menu.show .fab-button:nth-child(8) {
      transition-delay: 0s;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('fabToggle');
    const menu = document.querySelector('.fab-menu');
    toggle.addEventListener('click', function () {
      menu.classList.toggle('show');
    });
  });
</script>
