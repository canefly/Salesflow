<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php");
  exit;
}
require_once '../Database/connection.php';
$user_id = $_SESSION['user_id'];

// --- TODAY'S STATS ---
$conn->set_charset('utf8mb4');

// Total Sales Today
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(total_amount),0)
    FROM sales
   WHERE user_id = ?
     AND DATE(sale_date) = CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($totalSalesToday);
$stmt->fetch();
$stmt->close();

// Items Sold Today
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(quantity),0)
    FROM sales
   WHERE user_id = ?
     AND DATE(sale_date) = CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($itemsSoldToday);
$stmt->fetch();
$stmt->close();

// Top Category Today
$stmt = $conn->prepare("
  SELECT c.category_name, COALESCE(SUM(s.total_amount),0) AS total_amt
    FROM sales s
    JOIN categories c ON s.category_id = c.id
   WHERE s.user_id = ?
     AND DATE(s.sale_date) = CURDATE()
   GROUP BY s.category_id
   ORDER BY total_amt DESC
   LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($topCategoryName, $dummy);
$stmt->fetch();
$stmt->close();
if (!$topCategoryName) {
  $topCategoryName = 'N/A';
}

// --- DATE RANGES ---
$weekStart   = date('M j, Y', strtotime('monday this week'));
$weekEnd     = date('M j, Y', strtotime('sunday this week'));
$monthLabel  = date('F Y');
$yearLabel   = date('Y');

$ranges = [
  'weekly'  => "Showing: $weekStart – $weekEnd",
  'monthly' => "Showing: $monthLabel",
  'yearly'  => "Showing: $yearLabel",
];

// --- SCOPED DATA ---
function fetchCategoryBreakdown($conn, $user_id, $whereClause, &$labels, &$data) {
  $sql = "
    SELECT c.category_name, COALESCE(SUM(s.quantity),0) AS qty
      FROM sales s
      JOIN categories c ON s.category_id = c.id
     WHERE s.user_id = ?
       AND $whereClause
     GROUP BY s.category_id
     ORDER BY qty DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $labels = [];
  $data   = [];
  while ($row = $res->fetch_assoc()) {
    $labels[] = $row['category_name'];
    $data[]   = (int)$row['qty'];
  }
  $stmt->close();
}

// WEEKLY — last 7 days
$weeklyLabels = $weeklySales = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-{$i} days"));
  $weeklyLabels[] = date('D', strtotime($date));
  $stmt = $conn->prepare("
    SELECT COALESCE(SUM(total_amount),0)
      FROM sales
     WHERE user_id = ?
       AND DATE(sale_date) = ?
  ");
  $stmt->bind_param("is", $user_id, $date);
  $stmt->execute();
  $stmt->bind_result($sum);
  $stmt->fetch();
  $weeklySales[] = (float)$sum;
  $stmt->close();
}
fetchCategoryBreakdown(
  $conn, $user_id,
  "sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)",
  $weeklyCatLabels, $weeklyCatData
);

// MONTHLY — this month, by week number
// week index in month: 1–5 max, we'll normalize 1–4
$monthlySalesWeeks = [];
// init weeks 1–4
for ($wk = 1; $wk <= 4; $wk++) {
  $monthlySalesWeeks[$wk] = 0;
}
$stmt = $conn->prepare("
  SELECT
    (WEEK(sale_date,1) - WEEK(DATE_FORMAT(sale_date,'%Y-%m-01'),1) + 1) AS wk,
    COALESCE(SUM(total_amount),0) AS tot
    FROM sales
   WHERE user_id = ?
     AND MONTH(sale_date)=MONTH(CURDATE())
     AND YEAR(sale_date)=YEAR(CURDATE())
   GROUP BY wk
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $w = (int)$row['wk'];
  if ($w >=1 && $w <=4) {
    $monthlySalesWeeks[$w] = (float)$row['tot'];
  }
}
$stmt->close();
$monthlyLabels = ['Week 1','Week 2','Week 3','Week 4'];
$monthlySales  = array_values($monthlySalesWeeks);
fetchCategoryBreakdown(
  $conn, $user_id,
  "MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())",
  $monthlyCatLabels, $monthlyCatData
);

// YEARLY — this year, by month
$yearlySalesMonths = [];
for ($m = 1; $m <= 12; $m++) {
  $yearlySalesMonths[$m] = 0;
}
$stmt = $conn->prepare("
  SELECT MONTH(sale_date) AS mo,
         COALESCE(SUM(total_amount),0) AS tot
    FROM sales
   WHERE user_id = ?
     AND YEAR(sale_date)=YEAR(CURDATE())
   GROUP BY mo
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $yearlySalesMonths[(int)$row['mo']] = (float)$row['tot'];
}
$stmt->close();
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$yearlyLabels = array_slice($monthNames, 0, date('n')); // up to current month
$yearlySales  = array_slice(array_values($yearlySalesMonths), 0, date('n'));
fetchCategoryBreakdown(
  $conn, $user_id,
  "YEAR(sale_date)=YEAR(CURDATE())",
  $yearlyCatLabels, $yearlyCatData
);

// PACK FOR JS
$dashboardData = [
  'ranges'   => $ranges,
  'weekly'   => [
    'labels'        => $weeklyLabels,
    'salesData'     => $weeklySales,
    'categoryLabels'=> $weeklyCatLabels,
    'categoryData'  => $weeklyCatData,
  ],
  'monthly'  => [
    'labels'        => $monthlyLabels,
    'salesData'     => $monthlySales,
    'categoryLabels'=> $monthlyCatLabels,
    'categoryData'  => $monthlyCatData,
  ],
  'yearly'   => [
    'labels'        => $yearlyLabels,
    'salesData'     => $yearlySales,
    'categoryLabels'=> $yearlyCatLabels,
    'categoryData'  => $yearlyCatData,
  ],
];

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
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family:'Poppins',sans-serif; background:#f5f5f5; color:#333; }
    .wrapper { display:flex; min-height:100vh; }
    .sidebar { width:var(--sidebar-width); transition:width .3s ease; }
    .sidebar.collapsed { width:var(--sidebar-collapsed-width); }
    .main-content { flex:1; padding:40px; transition:margin-left .3s ease; margin-left:var(--sidebar-collapsed-width); }
    .sidebar:not(.collapsed)~.main-content { margin-left:var(--sidebar-width); }
    @media (max-width:768px) { .sidebar{display:none!important;} .main-content{margin-left:0!important;padding:20px!important;} .toggle-btn{display:none!important;} }
    h1 {font-size:2rem; margin-bottom:10px;}
    p  {font-size:1rem; color:#666;}
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
          <div class="text-muted" id="scopeRange"></div>
        </div>

        <!-- Top Stats Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-currency-exchange fs-3 text-primary me-3"></i>
                <div>
                  <h6 class="mb-0">Total Sales Today</h6>
                  <p class="mb-0">₱<?= number_format($totalSalesToday,2) ?></p>
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
                  <p class="mb-0"><?= $itemsSoldToday ?> Items</p>
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
                  <p class="mb-0"><?= htmlspecialchars($topCategoryName) ?></p>
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
                <canvas id="salesChart" style="max-width:100%; max-height:300px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header fw-semibold">Category Breakdown</div>
              <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="categoryChart" style="max-width:100%; max-height:300px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Sales Log -->
        <div class="card shadow-sm border-0">
          <div class="card-header fw-semibold">Recent Sales Logs</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr><th>Date</th><th>Item</th><th>Category</th><th>Qty</th><th>Total</th></tr>
                </thead>
                <tbody>
                  <?php
                  $stmt = $conn->prepare("
                    SELECT sale_date, product_name, c.category_name, quantity, total_amount
                      FROM sales s
                      JOIN categories c ON s.category_id=c.id
                     WHERE s.user_id=?
                     ORDER BY sale_date DESC
                     LIMIT 5
                  ");
                  $stmt->bind_param("i", $user_id);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  while ($row = $res->fetch_assoc()) {
                    echo "<tr>
                      <td>".date('Y-m-d', strtotime($row['sale_date']))."</td>
                      <td>".htmlspecialchars($row['product_name'])."</td>
                      <td>".htmlspecialchars($row['category_name'])."</td>
                      <td>{$row['quantity']}</td>
                      <td>₱".number_format($row['total_amount'],2)."</td>
                    </tr>";
                  }
                  $stmt->close();
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
    const dashboardData = <?= json_encode($dashboardData, JSON_NUMERIC_CHECK) ?>;

    // initialize charts
    const salesCtx    = document.getElementById('salesChart').getContext('2d');
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');

    const salesChart = new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: dashboardData.weekly.labels,
        datasets: [{
          label: '₱ Sales',
          data: dashboardData.weekly.salesData,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: { responsive:true, plugins:{legend:{display:false}} }
    });

    const categoryChart = new Chart(categoryCtx, {
      type: 'doughnut',
      data: {
        labels: dashboardData.weekly.categoryLabels,
        datasets: [{
          data: dashboardData.weekly.categoryData
        }]
      },
      options: {
        responsive:true,
        plugins:{legend:{position:'bottom'}}
      }
    });

    function setScope(scope) {
      // update active button
      document.querySelectorAll('.btn-group button').forEach(btn=>btn.classList.remove('active'));
      document.querySelector(`.btn-group button[onclick="setScope('${scope}')"]`).classList.add('active');
      // update range text
      document.getElementById('scopeRange').textContent = dashboardData.ranges[scope];
      // update sales chart
      salesChart.data.labels = dashboardData[scope].labels;
      salesChart.data.datasets[0].data = dashboardData[scope].salesData;
      salesChart.update();
      // update category chart
      categoryChart.data.labels = dashboardData[scope].categoryLabels;
      categoryChart.data.datasets[0].data = dashboardData[scope].categoryData;
      categoryChart.update();
    }

    // kick it off
    setScope('weekly');
  </script>
</body>
</html>
