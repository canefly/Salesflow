<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  // Not logged in = get out
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php"); // adjust path if needed
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 250px;
      --sidebar-collapsed-width: 70px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f5f5f5;
      color: #333;
    }

    .wrapper {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: var(--sidebar-width);
      transition: width 0.3s ease;
    }
    .sidebar.collapsed {
      width: var(--sidebar-collapsed-width);
    }
    .main-content {
      flex: 1;
      padding: 40px;
      transition: margin-left 0.3s ease;
      margin-left: var(--sidebar-collapsed-width);
    }
    .sidebar:not(.collapsed) ~ .main-content {
      margin-left: var(--sidebar-width);
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none !important;
      }

      .main-content {
        margin-left: 0 !important;
        padding: 20px !important;
      }

      .toggle-btn {
        display: none !important;
      }
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    p {
      font-size: 1rem;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <?php include '../include/mobile-side-nav.php'; ?>
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>
    <main class="main-content">
      <div class="container-fluid">
      

<!-- Scope Filter & Date Range -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <div class="btn-group" role="group">
      <button type="button" class="btn btn-outline-primary active" onclick="setScope('weekly')">Weekly</button>
      <button type="button" class="btn btn-outline-primary" onclick="setScope('monthly')">Monthly</button>
      <button type="button" class="btn btn-outline-primary" onclick="setScope('yearly')">Yearly</button>
    </div>
  </div>
  <div class="text-muted" id="scopeRange">Showing: March 30, 2025 – April 4, 2025</div>
</div>

        <!-- Top Stats Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-currency-exchange fs-3 text-primary me-3"></i>
                <div>
                  <h6 class="mb-0">Total Sales Today</h6>
                  <p class="mb-0">₱2,150.00</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-box fs-3 text-success me-3"></i>
                <div>
                  <h6 class="mb-0">Items Sold</h6>
                  <p class="mb-0">36 Items</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-bar-chart fs-3 text-warning me-3"></i>
                <div>
                  <h6 class="mb-0">Top Category</h6>
                  <p class="mb-0">Snacks</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4 align-items-stretch">
  <div class="col-lg-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header fw-semibold">Sales Over Time</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="salesChart" style="max-width: 100%; max-height: 300px;"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header fw-semibold">Category Breakdown</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="categoryChart" style="max-width: 100%; max-height: 300px;"></canvas>
      </div>
    </div>
  </div>
</div>
<!-- Sales Log Preview -->
        <div class="card shadow-sm border-0">
          <div class="card-header fw-semibold">Recent Sales Logs</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>2025-04-06</td>
                    <td>Coca Cola</td>
                    <td>Drinks</td>
                    <td>5</td>
                    <td>₱75.00</td>
                  </tr>
                  <tr>
                    <td>2025-04-06</td>
                    <td>Chippy</td>
                    <td>Snacks</td>
                    <td>8</td>
                    <td>₱112.00</td>
                  </tr>
                  <tr>
                    <td>2025-04-06</td>
                    <td>Lucky Me Pancit</td>
                    <td>Instant Meals</td>
                    <td>3</td>
                    <td>₱45.00</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function setScope(scope) {
  const rangeDisplay = document.getElementById('scopeRange');
  let newRange = '';

  if (scope === 'weekly') {
    newRange = 'Showing: March 30, 2025 – April 4, 2025';
  } else if (scope === 'monthly') {
    newRange = 'Showing: April 2025';
  } else if (scope === 'yearly') {
    newRange = 'Showing: 2025';
  }

  document.querySelectorAll('.btn-group button').forEach(btn => btn.classList.remove('active'));
  document.querySelector(`.btn-group button[onclick="setScope('${scope}')"]`).classList.add('active');
  rangeDisplay.textContent = newRange;
  updateChartsForScope(scope);
}

function updateChartsForScope(scope) {
  let salesData = [], labels = [], categoryData = [], categoryLabels = [];

  if (scope === 'weekly') {
    labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    salesData = [250, 320, 180, 420, 530, 680, 750];
    categoryLabels = ['Snacks', 'Drinks', 'Instant Meals'];
    categoryData = [48, 28, 24];
  } else if (scope === 'monthly') {
    labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    salesData = [1100, 980, 1230, 1400];
    categoryLabels = ['Snacks', 'Drinks', 'Frozen Goods'];
    categoryData = [120, 90, 40];
  } else if (scope === 'yearly') {
    labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    salesData = [4500, 4700, 5100, 6000, 5800, 6200];
    categoryLabels = ['Snacks', 'Drinks', 'Stationery'];
    categoryData = [360, 220, 140];
  }

  salesChart.data.labels = labels;
  salesChart.data.datasets[0].data = salesData;
  salesChart.update();

  categoryChart.data.labels = categoryLabels;
  categoryChart.data.datasets[0].data = categoryData;
  categoryChart.update();
}

const salesChart = new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: '₱ Sales',
      data: [250, 320, 180, 420, 530, 680, 750],
      borderColor: '#0d6efd',
      backgroundColor: 'rgba(13, 110, 253, 0.1)',
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } }
  }
});

const categoryChart = new Chart(document.getElementById('categoryChart'), {
  type: 'doughnut',
  data: {
    labels: ['Snacks', 'Drinks', 'Instant Meals'],
    datasets: [{
      label: 'Sales by Category',
      data: [48, 28, 24],
      backgroundColor: ['#ffc107', '#198754', '#dc3545']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
</body>
</html>
