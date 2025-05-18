<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php");
  exit;
}
require_once '../Database/connection.php'; // provides $conn
$user_id = $_SESSION['user_id'];

// AJAX: Top‐cards stats
if (isset($_GET['action']) && $_GET['action'] === 'getStats') {
  $today = date('Y-m-d');
  // Total sales & items sold today
  $stmt = $conn->prepare("
    SELECT 
      IFNULL(SUM(total_amount),0) AS total_sales,
      IFNULL(SUM(quantity),0)     AS items_sold
    FROM sales
    WHERE user_id=? AND DATE(sale_date)=?
  ");
  $stmt->bind_param("is", $user_id, $today);
  $stmt->execute();
  $stats = $stmt->get_result()->fetch_assoc();

  // Top category by quantity today
  $stmt = $conn->prepare("
    SELECT c.category_name, SUM(s.quantity) AS qty
    FROM sales s
    JOIN categories c ON s.category_id=c.id
    WHERE s.user_id=? AND DATE(s.sale_date)=?
    GROUP BY s.category_id
    ORDER BY qty DESC
    LIMIT 1
  ");
  $stmt->bind_param("is", $user_id, $today);
  $stmt->execute();
  $topCat = $stmt->get_result()->fetch_assoc();

  echo json_encode([
    'totalSales'  => (float)$stats['total_sales'],
    'itemsSold'   => (int)$stats['items_sold'],
    'topCategory' => $topCat ? $topCat['category_name'] : '—'
  ]);
  exit;
}

// AJAX: Chart data
if (isset($_GET['action']) && $_GET['action'] === 'getDashboardData') {
  $scope = $_GET['scope'] ?? 'weekly';
  $today = new DateTime();

  // determine range
  if ($scope === 'weekly') {
    $start = $today->modify('-6 days')->format('Y-m-d');
    $end   = (new DateTime())->format('Y-m-d');
  } elseif ($scope === 'monthly') {
    $start = $today->format('Y-m-01');
    $end   = $today->format('Y-m-t');
  } else { // yearly
    $start = $today->format('Y-01-01');
    $end   = $today->format('Y-12-31');
  }

  // Sales over time
  $labels = [];
  $salesData = [];
  if ($scope === 'weekly') {
    $period = new DatePeriod(
      new DateTime($start),
      new DateInterval('P1D'),
      (new DateTime($end))->modify('+1 day')
    );
    foreach ($period as $dt) {
      $labels[] = $dt->format('D');
      $dateStr  = $dt->format('Y-m-d');
      $stmt = $conn->prepare("
        SELECT IFNULL(SUM(total_amount),0) AS sum_amount
        FROM sales
        WHERE user_id=? AND DATE(sale_date)=?
      ");
      $stmt->bind_param("is", $user_id, $dateStr);
      $stmt->execute();
      $salesData[] = (float)$stmt->get_result()->fetch_assoc()['sum_amount'];
    }
  } elseif ($scope === 'monthly') {
    // 4 “weeks” – chunk by 7 days
    for ($w = 0; $w < 4; $w++) {
      $weekStart = date('Y-m-d', strtotime("$start +$w week"));
      $weekEnd   = date('Y-m-d', min(
        strtotime("$weekStart +6 days"),
        strtotime($end)
      ));
      $labels[] = 'Week '.($w+1);
      $stmt = $conn->prepare("
        SELECT IFNULL(SUM(total_amount),0) AS sum_amount
        FROM sales
        WHERE user_id=? AND sale_date BETWEEN ? AND ?
      ");
      $stmt->bind_param("iss", $user_id, $weekStart, $weekEnd);
      $stmt->execute();
      $salesData[] = (float)$stmt->get_result()->fetch_assoc()['sum_amount'];
    }
  } else {
    // first six months
    for ($m = 1; $m <= 6; $m++) {
      $monthStart = date('Y-m-01', strtotime($today->format("Y-$m-01")));
      $monthEnd   = date('Y-m-t', strtotime($monthStart));
      $labels[] = date('M', strtotime($monthStart));
      $stmt = $conn->prepare("
        SELECT IFNULL(SUM(total_amount),0) AS sum_amount
        FROM sales
        WHERE user_id=? AND sale_date BETWEEN ? AND ?
      ");
      $stmt->bind_param("iss", $user_id, $monthStart, $monthEnd);
      $stmt->execute();
      $salesData[] = (float)$stmt->get_result()->fetch_assoc()['sum_amount'];
    }
  }

  // Category breakdown
  $categoryLabels = [];
  $categoryData   = [];
  $stmt = $conn->prepare("
    SELECT c.category_name, SUM(s.quantity) AS sum_qty
    FROM sales s
    JOIN categories c ON s.category_id=c.id
    WHERE s.user_id=? AND s.sale_date BETWEEN ? AND ?
    GROUP BY s.category_id
    ORDER BY sum_qty DESC
    LIMIT 5
  ");
  $stmt->bind_param("iss", $user_id, $start, $end);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $categoryLabels[] = $row['category_name'];
    $categoryData[]   = (int)$row['sum_qty'];
  }

  echo json_encode([
    'labels'         => $labels,
    'salesData'      => $salesData,
    'categoryLabels'=> $categoryLabels,
    'categoryData'   => $categoryData
  ]);
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
    /* ——— your existing CSS ——— */
    :root { --sidebar-width:250px; --sidebar-collapsed-width:70px; }
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Poppins',sans-serif; background:#f5f5f5; color:#333; }
    .wrapper { display:flex; min-height:100vh; }
    .sidebar { width:var(--sidebar-width); transition:width .3s ease; }
    .sidebar.collapsed { width:var(--sidebar-collapsed-width); }
    .main-content {
      flex:1; padding:40px; transition:margin-left .3s ease;
      margin-left:var(--sidebar-collapsed-width);
    }
    .sidebar:not(.collapsed)~.main-content { margin-left:var(--sidebar-width); }
    @media(max-width:768px){
      .sidebar { display:none!important; }
      .main-content{ margin-left:0!important; padding:20px!important; }
      .toggle-btn{ display:none!important; }
    }
    h1 { font-size:2rem; margin-bottom:10px; }
    p  { font-size:1rem; color:#666; }
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
              <button type="button" class="btn btn-outline-primary"         onclick="setScope('monthly')">Monthly</button>
              <button type="button" class="btn btn-outline-primary"         onclick="setScope('yearly')">Yearly</button>
            </div>
          </div>
          <div class="text-muted" id="scopeRange">Loading…</div>
        </div>

        <!-- Top Stats Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-currency-exchange fs-3 text-primary me-3"></i>
                <div>
                  <h6 class="mb-0">Total Sales Today</h6>
                  <p class="mb-0">₱<span id="totalSalesToday">0.00</span></p>
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
                  <p class="mb-0"><span id="itemsSold">0</span> Items</p>
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
                  <p class="mb-0" id="topCategory">—</p>
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
                <canvas id="salesChart" style="max-width:100%;max-height:300px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header fw-semibold">Category Breakdown</div>
              <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="categoryChart" style="max-width:100%;max-height:300px;"></canvas>
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
                    <th>Date</th><th>Item</th><th>Category</th><th>Qty</th><th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $stmt = $conn->prepare("
                      SELECT s.sale_date, s.product_name, c.category_name, s.quantity, s.total_amount
                      FROM sales s
                      LEFT JOIN categories c ON s.category_id=c.id
                      WHERE s.user_id=?
                      ORDER BY s.sale_date DESC
                      LIMIT 5
                    ");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                      echo "<tr>
                        <td>".substr($row['sale_date'],0,10)."</td>
                        <td>".htmlspecialchars($row['product_name'])."</td>
                        <td>".htmlspecialchars($row['category_name'])."</td>
                        <td>{$row['quantity']}</td>
                        <td>₱".number_format($row['total_amount'],2)."</td>
                      </tr>";
                    }
                  ?>
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
    let salesChart, categoryChart;

    function fetchStats() {
      fetch('?action=getStats')
        .then(res => res.json())
        .then(data => {
          document.getElementById('totalSalesToday').textContent = data.totalSales.toFixed(2);
          document.getElementById('itemsSold').textContent      = data.itemsSold;
          document.getElementById('topCategory').textContent    = data.topCategory;
        });
    }

    function updateChartsForScope(scope) {
      // update the range display
      const rangeDisplay = document.getElementById('scopeRange');
      if (scope === 'weekly') {
        const end = new Date(), start = new Date(end.getTime() - 6*86400000);
        rangeDisplay.textContent = 
          `Showing: ${start.toLocaleDateString()} – ${end.toLocaleDateString()}`;
      } else if (scope === 'monthly') {
        const now = new Date();
        rangeDisplay.textContent = `Showing: ${now.toLocaleString('default',{month:'long', year:'numeric'})}`;
      } else {
        rangeDisplay.textContent = `Showing: ${new Date().getFullYear()}`;
      }

      // fetch the chart data
      fetch(`?action=getDashboardData&scope=${scope}`)
        .then(res => res.json())
        .then(data => {
          salesChart.data.labels   = data.labels;
          salesChart.data.datasets[0].data = data.salesData;
          salesChart.update();

          categoryChart.data.labels = data.categoryLabels;
          categoryChart.data.datasets[0].data = data.categoryData;
          categoryChart.update();
        });
    }

    function setScope(scope){
      document.querySelectorAll('.btn-group button')
        .forEach(btn => btn.classList.remove('active'));
      document.querySelector(`.btn-group button[onclick="setScope('${scope}')"]`)
        .classList.add('active');
      updateChartsForScope(scope);
    }

    document.addEventListener('DOMContentLoaded', () => {
      // init charts
      salesChart = new Chart(
        document.getElementById('salesChart'),
        {
          type: 'line',
          data: {
            labels: [], 
            datasets: [{
              label: '₱ Sales',
              data: [],
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13,110,253,0.1)',
              tension: 0.4,
              fill: true
            }]
          },
          options: { responsive:true, plugins:{legend:{display:false}} }
        }
      );
      categoryChart = new Chart(
        document.getElementById('categoryChart'),
        {
          type: 'doughnut',
          data: {
            labels: [],
            datasets: [{
              label: 'Sales by Category',
              data: [],
              backgroundColor: ['#ffc107','#198754','#dc3545','#0d6efd','#6f42c1']
            }]
          },
          options: { responsive:true, plugins:{legend:{position:'bottom'}} }
        }
      );

      // initial load
      fetchStats();
      setScope('weekly');
    });
  </script>
</body>
</html>
