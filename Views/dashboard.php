<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Salesflow Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #001f4d, #004080);
      color: white;
    }
    .sidebar {
      width: 250px;
      background-color: #001f4d;
      position: fixed;
      top: 0; left: 0;
      height: 100vh;
      padding-top: 1rem;
      transition: all 0.3s ease;
      z-index: 1000;
    }
    .sidebar.collapsed {
      width: 70px;
    }
    .sidebar a {
      color: white;
      padding: 12px 20px;
      display: block;
      text-decoration: none;
      white-space: nowrap;
    }
    .sidebar a:hover {
      background-color: #003366;
    }
    .toggle-btn {
      position: fixed;
      top: 15px;
      left: 260px;
      background-color: #004080;
      border: none;
      color: white;
      z-index: 1100;
    }
    .main {
      margin-left: 250px;
      padding: 2rem;
      transition: margin-left 0.3s ease;
    }
    .collapsed + .main {
      margin-left: 70px;
    }
    .card-custom {
      background: rgba(255, 255, 255, 0.1);
      border: none;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      color: white;
    }
    canvas {
      background: white;
      border-radius: 10px;
      padding: 10px;
    }
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <h4 class="text-center text-white">Salesflow</h4>
    <a href="#">🏠 Dashboard</a>
    <a href="#">📦 Product Trends</a>
    <a href="#">📊 Reports</a>
    <a href="#">⚙️ Settings</a>
    <form action="../backend/logout.php" method="POST" class="text-center mt-5">
      <button type="submit" class="btn btn-danger btn-sm">Logout</button>
    </form>
  </div>

  <button class="btn toggle-btn" id="toggleSidebar"><i class="fas fa-bars"></i></button>

  <div class="main">
    <div class="container-fluid">
      <div class="text-white mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>This is your dashboard. Here you can view insights, trends, and manage your salesflow.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6">
          <div class="card-custom">
            <h4>Total Income</h4>
            <p>₱7,500.00</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-custom">
            <h4>Trending Product</h4>
            <p>Wireless Headphones</p>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-md-6">
          <canvas id="incomeChart"></canvas>
        </div>
        <div class="col-md-6">
          <canvas id="productChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Chat Assistant -->
  <aside id="ai-chat-panel" class="chat-panel position-fixed shadow bg-light" style="bottom: 0; right: 20px; width: 320px; max-height: 500px; border-radius: 10px 10px 0 0; display: none;" role="dialog" aria-modal="true" aria-labelledby="chat-panel-title">
    <header class="chat-header d-flex align-items-center justify-content-between bg-primary text-white px-3 py-2">
      <h5 class="mb-0" id="chat-panel-title">Salesflow AI Assistant</h5>
      <button class="btn-close btn-close-white" id="chat-close-btn" aria-label="Close chat"></button>
    </header>
    <div class="chat-body bg-white p-2" id="chat-messages"></div>
    <footer class="chat-footer p-2 bg-light">
      <div class="input-group">
        <textarea class="form-control" rows="1" id="chat-input" placeholder="Ask something..."></textarea>
        <button class="btn btn-primary" id="chat-send-btn">Send</button>
      </div>
    </footer>
  </aside>
  <button id="ai-chat-button" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow"><i class="fas fa-comment-dots"></i></button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.getElementById("toggleSidebar").addEventListener("click", function () {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
      document.querySelector(".main").classList.toggle("collapsed");
    });

    const incomeChart = new Chart(document.getElementById("incomeChart"), {
      type: 'pie',
      data: {
        labels: ['Wireless Headphones', 'Smartwatches', 'Gaming Mouse', 'Mechanical Keyboards'],
        datasets: [{
          data: [4000, 2000, 1000, 500],
          backgroundColor: ['#66ff66', '#4d94ff', '#ff9900', '#cccccc']
        }]
      },
      options: {
        plugins: {
          legend: {
            labels: {
              color: 'white'
            }
          }
        }
      }
    });

    const productChart = new Chart(document.getElementById("productChart"), {
      type: 'bar',
      data: {
        labels: ['Wireless Headphones', 'Smartwatches', 'Gaming Mouse', 'Mechanical Keyboards'],
        datasets: [{
          label: 'Sales',
          data: [120, 90, 75, 60],
          backgroundColor: ['#66ff66', '#4d94ff', '#ff9900', '#ff4d4d']
        }]
      },
      options: {
        scales: {
          x: { ticks: { color: 'white' } },
          y: { ticks: { color: 'white' } }
        }
      }
    });

    // Chat toggle
    const chatPanel = document.getElementById("ai-chat-panel");
    document.getElementById("ai-chat-button").addEventListener("click", () => {
      chatPanel.style.display = 'flex';
    });
    document.getElementById("chat-close-btn").addEventListener("click", () => {
      chatPanel.style.display = 'none';
    });
  </script>
</body>
</html>
