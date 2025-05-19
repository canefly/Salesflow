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

/* -------------------------------------------------
   TODAY’S STATS
--------------------------------------------------*/
$conn->set_charset('utf8mb4');

/* total sales today */
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(total_amount),0)
  FROM   sales
  WHERE  user_id = ? AND DATE(sale_date)=CURDATE()
");
$stmt->bind_param("i",$user_id);
$stmt->execute(); $stmt->bind_result($totalSalesToday); $stmt->fetch(); $stmt->close();

/* items sold today */
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(quantity),0)
  FROM   sales
  WHERE  user_id = ? AND DATE(sale_date)=CURDATE()
");
$stmt->bind_param("i",$user_id);
$stmt->execute(); $stmt->bind_result($itemsSoldToday); $stmt->fetch(); $stmt->close();

/* top category today */
$stmt = $conn->prepare("
  SELECT c.category_name, COALESCE(SUM(s.total_amount),0) AS t
  FROM   sales s
  JOIN   categories c ON s.category_id=c.id
  WHERE  s.user_id=? AND DATE(s.sale_date)=CURDATE()
  GROUP  BY s.category_id
  ORDER  BY t DESC
  LIMIT  1
");
$stmt->bind_param("i",$user_id);
$stmt->execute(); $stmt->bind_result($topCategoryName,$dummy); $stmt->fetch(); $stmt->close();
if (!$topCategoryName) $topCategoryName='N/A';

/* -------------------------------------------------
   RANGE LABELS
--------------------------------------------------*/
$weekStart  = date('M j',strtotime('monday this week'));
$weekEnd    = date('M j',strtotime('sunday this week'));
$ranges = [
  'weekly'  => "Showing: $weekStart – $weekEnd",
  'monthly' => 'Showing: '.date('F Y'),
  'yearly'  => 'Showing: '.date('Y'),
];

/* ---------- helper --------- */
function fetchCategoryBreakdown($conn,$uid,$where,&$lab,&$dat){
  $sql="
    SELECT c.category_name, COALESCE(SUM(s.quantity),0) AS q
    FROM   sales s
    JOIN   categories c ON s.category_id=c.id
    WHERE  s.user_id=? AND $where
    GROUP  BY s.category_id
    ORDER  BY q DESC
  ";
  $st=$conn->prepare($sql);
  $st->bind_param("i",$uid);
  $st->execute(); $rs=$st->get_result();
  $lab=$dat=[];
  while($row=$rs->fetch_assoc()){ $lab[]=$row['category_name']; $dat[]=(int)$row['q']; }
  $st->close();
}

/* -------------------------------------------------
     WEEKLY (last 7 days)
--------------------------------------------------*/
$weeklyLabels=$weeklySales=[];
for($i=6;$i>=0;$i--){
  $d=date('Y-m-d',strtotime("-$i days"));
  $weeklyLabels[]=date('D',strtotime($d));
  $st=$conn->prepare("
    SELECT COALESCE(SUM(total_amount),0)
    FROM   sales WHERE user_id=? AND DATE(sale_date)=?
  ");
  $st->bind_param("is",$user_id,$d);
  $st->execute(); $st->bind_result($sum); $st->fetch(); $st->close();
  $weeklySales[]=(float)$sum;
}
fetchCategoryBreakdown($conn,$user_id,"sale_date>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)",$weeklyCatLabels,$weeklyCatData);

/* -------------------------------------------------
     MONTHLY (week-of-month buckets)
--------------------------------------------------*/
$monthlySales=[0,0,0,0];
$st=$conn->prepare("
  SELECT (WEEK(sale_date,1)-WEEK(DATE_FORMAT(sale_date,'%Y-%m-01'),1)+1) AS w,
         COALESCE(SUM(total_amount),0)
  FROM   sales
  WHERE  user_id=? AND MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())
  GROUP  BY w
");
$st->bind_param("i",$user_id); $st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_array()) $monthlySales[$r[0]-1]=(float)$r[1];
$st->close();
$monthlyLabels=['Week 1','Week 2','Week 3','Week 4'];
fetchCategoryBreakdown($conn,$user_id,"MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())",$monthlyCatLabels,$monthlyCatData);

/* -------------------------------------------------
     YEARLY (month buckets)
--------------------------------------------------*/
$yearlySales=array_fill(0,12,0);
$st=$conn->prepare("
  SELECT MONTH(sale_date), COALESCE(SUM(total_amount),0)
  FROM   sales
  WHERE  user_id=? AND YEAR(sale_date)=YEAR(CURDATE())
  GROUP  BY MONTH(sale_date)
");
$st->bind_param("i",$user_id); $st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_array()) $yearlySales[$r[0]-1]=(float)$r[1];
$st->close();
$yearlyLabels=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$yearlySales=array_slice($yearlySales,0,date('n'));
$yearlyLabels=array_slice($yearlyLabels,0,date('n'));
fetchCategoryBreakdown($conn,$user_id,"YEAR(sale_date)=YEAR(CURDATE())",$yearlyCatLabels,$yearlyCatData);

/* -------------------------------------------------
     PACKAGE FOR JS
--------------------------------------------------*/
$dashboardData=[
  'ranges'=>$ranges,
  'weekly'=>[
    'labels'=>$weeklyLabels,'salesData'=>$weeklySales,
    'categoryLabels'=>$weeklyCatLabels,'categoryData'=>$weeklyCatData
  ],
  'monthly'=>[
    'labels'=>$monthlyLabels,'salesData'=>$monthlySales,
    'categoryLabels'=>$monthlyCatLabels,'categoryData'=>$monthlyCatData
  ],
  'yearly'=>[
    'labels'=>$yearlyLabels,'salesData'=>$yearlySales,
    'categoryLabels'=>$yearlyCatLabels,'categoryData'=>$yearlyCatData
  ]
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Salesflow — Dashboard</title>

  <!-- libs -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- responsive overrides -->
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


    .stats-grid .card-body i{
  margin-right: 10px;        /* push icon down */
  line-height: 1;         /* kill extra inline space */
  }

    /* cards on mobile */
    @media(max-width:576px){
      .stats-grid .card{--bs-card-spacer-y:.75rem;--bs-card-spacer-x:.75rem;  }
      .stats-grid i{font-size:1.35rem;margin-right:.5rem;}
      .stats-grid h6{font-size:.78rem;margin-bottom:2px;}
      .stats-grid p{font-size:.82rem;}
    }

    /* chart canvases: stretch 100% width / fixed height on phones */
    .chart-wrap{position:relative;width:100%;height:320px;}
    @media(max-width:576px){ .chart-wrap{height:200px;} }

    /* kill stubborn margins that bootstrap sometimes imposes inside
       the canvas parent when charts overflow */
    .card-body>canvas, .chart-wrap>canvas{display:block!important;width:100%!important;height:100%!important;}

    /* hide focus rings on non-kbd click */
    button:focus:not(:focus-visible){outline:0;}
  </style>
</head>
<body>
  <div class="wrapper">

    <?php include '../include/mobile-side-nav.php'; ?>
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>

    <main class="main-content">
      <div class="container-fluid px-0">

        <!-- scope buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="btn-group shadow-sm" role="group">
            <button class="btn btn-outline-primary active" onclick="setScope('weekly')">Weekly</button>
            <button class="btn btn-outline-primary"  onclick="setScope('monthly')">Monthly</button>
            <button class="btn btn-outline-primary"  onclick="setScope('yearly')">Yearly</button>
          </div>
          <small class="text-muted fw-semibold" id="scopeRange"></small>
        </div>

        <!-- top stats -->
        <div class="row g-2 stats-grid row-cols-2 row-cols-md-3 mb-3">
          <div class="col">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-currency-exchange text-primary"></i>
                <div>
                  <h6>Total Sales Today</h6>
                  <p class="mb-0">₱<?=number_format($totalSalesToday,2)?></p>
                </div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-box text-success"></i>
                <div>
                  <h6>Items Sold</h6>
                  <p class="mb-0"><?=$itemsSoldToday?> pcs</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bi bi-bar-chart text-warning"></i>
                <div>
                  <h6>Top Category</h6>
                  <p class="mb-0"><?=htmlspecialchars($topCategoryName)?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- charts -->
        <div class="row g-2 align-items-stretch mb-3">
          <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-header fw-semibold bg-white">Sales Over Time</div>
              <div class="card-body p-0">
                <div class="chart-wrap"><canvas id="salesChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-header fw-semibold bg-white">Category Breakdown</div>
              <div class="card-body p-0">
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
              </div>
            </div>
          </div>
        </div>

        <!-- recent sales -->
        <div class="card border-0 shadow-sm">
          <div class="card-header fw-semibold bg-white">Recent Sales Logs</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-hover mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Date</th><th>Item</th><th>Category</th><th>Qty</th><th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $stmt=$conn->prepare("
                      SELECT sale_date,product_name,c.category_name,quantity,total_amount
                      FROM   sales s JOIN categories c ON s.category_id=c.id
                      WHERE  s.user_id=? ORDER BY sale_date DESC LIMIT 5
                    ");
                    $stmt->bind_param("i",$user_id); $stmt->execute(); $rs=$stmt->get_result();
                    while($r=$rs->fetch_assoc()){
                      echo '<tr>'.
                           '<td>'.date('Y-m-d',strtotime($r['sale_date'])).'</td>'.
                           '<td>'.htmlspecialchars($r['product_name']).'</td>'.
                           '<td>'.htmlspecialchars($r['category_name']).'</td>'.
                           '<td>'.$r['quantity'].'</td>'.
                           '<td>₱'.number_format($r['total_amount'],2).'</td>'.
                           '</tr>';
                    } $stmt->close();
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const dashboardData=<?=json_encode($dashboardData,JSON_NUMERIC_CHECK)?>;

    /* -------- chart configs -------- */
    const salesCtx=document.getElementById('salesChart');
    const catCtx  =document.getElementById('categoryChart');

    /* line chart */
    const salesChart=new Chart(salesCtx,{
      type:'line',
      data:{
        labels:dashboardData.weekly.labels,
        datasets:[{
          label:'₱ Sales',
          data:dashboardData.weekly.salesData,
          borderWidth:2,
          borderColor:'#0d6efd',
          backgroundColor:'rgba(13,110,253,.12)',
          tension:.3,
          fill:true,
          pointRadius:3,
        }]
      },
      options:{
        maintainAspectRatio:false,
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{y:{ticks:{callback:v=>'₱'+v}}}
      }
    });

    /* doughnut */
    const categoryChart=new Chart(catCtx,{
      type:'doughnut',
      data:{
        labels:dashboardData.weekly.categoryLabels,
        datasets:[{data:dashboardData.weekly.categoryData}]
      },
      options:{
        maintainAspectRatio:false,
        responsive:true,
        layout:{padding:0},
        plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}
      }
    });

    /* -------- scope switcher -------- */
    function setScope(s){
      document.querySelectorAll('.btn-group .btn').forEach(b=>b.classList.remove('active'));
      document.querySelector(`.btn-group .btn[onclick="setScope('${s}')"]`).classList.add('active');

      document.getElementById('scopeRange').textContent=dashboardData.ranges[s];

      salesChart.data.labels=dashboardData[s].labels;
      salesChart.data.datasets[0].data=dashboardData[s].salesData;
      salesChart.update();

      categoryChart.data.labels=dashboardData[s].categoryLabels;
      categoryChart.data.datasets[0].data=dashboardData[s].categoryData;
      categoryChart.update();
    }
    setScope('weekly');
  </script>
</body>
</html>
