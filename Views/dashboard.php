<?php
/* ------------- auth ------------- */
session_start();
if(!isset($_SESSION['user_id'])){
  session_unset();session_destroy();
  header("Location: ../Views/login.php");exit;
}
require_once '../Database/connection.php';
$user_id=$_SESSION['user_id'];
$conn->set_charset('utf8mb4');

/* ---- helper --------------------------------------------------------- */
function val($conn,$sql,$types,$params){
  $stmt=$conn->prepare($sql);
  $stmt->bind_param($types,...$params);
  $stmt->execute();$stmt->bind_result($v);$stmt->fetch();$stmt->close();
  return $v??0;
}

/* ---- top-bar numbers ------------------------------------------------ */
$totalSalesToday = val($conn,
  "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE user_id=? AND DATE(sale_date)=CURDATE()",
  "i",[$user_id]);

$itemsSoldToday  = val($conn,
  "SELECT COALESCE(SUM(quantity),0) FROM sales WHERE user_id=? AND DATE(sale_date)=CURDATE()",
  "i",[$user_id]);

$stmt=$conn->prepare("
  SELECT c.category_name, COALESCE(SUM(s.total_amount),0) t
  FROM   sales s JOIN categories c ON s.category_id=c.id
  WHERE  s.user_id=? AND DATE(s.sale_date)=CURDATE()
  GROUP  BY s.category_id ORDER BY t DESC LIMIT 1
");
$stmt->bind_param("i",$user_id);$stmt->execute();
$stmt->bind_result($topCategoryName,$tmp);$stmt->fetch();$stmt->close();
if(!$topCategoryName)$topCategoryName='N/A';

/* ---- range labels --------------------------------------------------- */
$weekStart=date('M j',strtotime('monday this week'));
$weekEnd  =date('M j',strtotime('sunday this week'));
$ranges=[
  'weekly'=>"Showing: $weekStart – $weekEnd",
  'monthly'=>'Showing: '.date('F Y'),
  'yearly' =>'Showing: '.date('Y')
];

/* ---- fetchers ------------------------------------------------------- */
function cats($conn,$uid,$where){
  $lab=$dat=[];
  $st=$conn->prepare("
    SELECT c.category_name, COALESCE(SUM(s.quantity),0)
    FROM   sales s JOIN categories c ON s.category_id=c.id
    WHERE  s.user_id=? AND $where
    GROUP  BY s.category_id ORDER BY 2 DESC
  ");
  $st->bind_param("i",$uid);$st->execute();$rs=$st->get_result();
  while($r=$rs->fetch_row()){ $lab[]=$r[0]; $dat[]=(int)$r[1]; }
  $st->close();
  return [$lab,$dat];
}

/* ---- weekly data (last 7 days) ------------------------------------- */
$weeklyLabels=$weeklySales=[];
for($i=6;$i>=0;$i--){
  $d=date('Y-m-d',strtotime("-$i days"));
  $weeklyLabels[]=date('D',strtotime($d));
  $weeklySales[]=val($conn,"
    SELECT COALESCE(SUM(total_amount),0) FROM sales
    WHERE user_id=? AND DATE(sale_date)=?","is",[$user_id,$d]);
}
[$weeklyCatLab,$weeklyCatDat]=cats($conn,$user_id,"sale_date>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)");

/* ---- monthly (weeks 1-4) ------------------------------------------- */
$monthlySales=[0,0,0,0];
$st=$conn->prepare("
  SELECT (WEEK(sale_date,1)-WEEK(DATE_FORMAT(sale_date,'%Y-%m-01'),1)+1) w,
         COALESCE(SUM(total_amount),0)
  FROM   sales WHERE user_id=? AND MONTH(sale_date)=MONTH(CURDATE())
                AND YEAR(sale_date)=YEAR(CURDATE())
  GROUP  BY w
");
$st->bind_param("i",$user_id);$st->execute();$rs=$st->get_result();
while($r=$rs->fetch_row())$monthlySales[$r[0]-1]=(float)$r[1];
$st->close();
$monthlyLabels=['Week 1','Week 2','Week 3','Week 4'];
[$monthlyCatLab,$monthlyCatDat]=cats($conn,$user_id,
  "MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())");

/* ---- yearly (Jan-current) ------------------------------------------ */
$yearlySales=array_fill(0,12,0);
$st=$conn->prepare("
  SELECT MONTH(sale_date), COALESCE(SUM(total_amount),0)
  FROM   sales WHERE user_id=? AND YEAR(sale_date)=YEAR(CURDATE())
  GROUP  BY MONTH(sale_date)
");
$st->bind_param("i",$user_id);$st->execute();$rs=$st->get_result();
while($r=$rs->fetch_row())$yearlySales[$r[0]-1]=(float)$r[1];
$st->close();
$yearlyLabels=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$yearlySales=array_slice($yearlySales,0,date('n'));
$yearlyLabels=array_slice($yearlyLabels,0,date('n'));
[$yearlyCatLab,$yearlyCatDat]=cats($conn,$user_id,"YEAR(sale_date)=YEAR(CURDATE())");

/* ---- pack for JS ---------------------------------------------------- */
$dashboardData=[
  'ranges'=>$ranges,
  'weekly' =>['labels'=>$weeklyLabels,'salesData'=>$weeklySales,
              'categoryLabels'=>$weeklyCatLab,'categoryData'=>$weeklyCatDat],
  'monthly'=>['labels'=>$monthlyLabels,'salesData'=>$monthlySales,
              'categoryLabels'=>$monthlyCatLab,'categoryData'=>$monthlyCatDat],
  'yearly' =>['labels'=>$yearlyLabels,'salesData'=>$yearlySales,
              'categoryLabels'=>$yearlyCatLab,'categoryData'=>$yearlyCatDat]
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Salesflow — Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{
  /* keep the simple v1 names so future devs recognise them */
  --sidebar-width:250px;
  --sidebar-collapsed-width:70px;
}

/* -------------------------------------------------- core reset */
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Poppins,sans-serif;
  background:#f5f5f5;
  color:#333;
}

.wrapper{display:flex;min-height:100vh}

/* -------------------------------------------------- sidebar */
.sidebar{
  width:var(--sidebar-width);
  transition:width .3s ease;
}
.sidebar.collapsed{width:var(--sidebar-collapsed-width)}

/* -------------------------------------------------- main pane */
.main-content{
  flex:1;
  padding:40px;
  margin-left:var(--sidebar-collapsed-width);
  transition:margin-left .3s ease;
  min-width:0;               /* ⬅ lets the flex-child squeeze smaller */
}
.sidebar:not(.collapsed) ~ .main-content{
  margin-left:var(--sidebar-width);
}

/* -------------------------------------------------- headings */
h1{font-size:2rem;margin-bottom:10px}
p {font-size:1rem;color:#666}

/* -------------------------------------------------- breakpoints */
@media(max-width:768px){
  .sidebar{display:none!important;}
  .main-content{margin-left:0!important;padding:20px!important;}
  .toggle-btn{display:none!important;}     /* ← from v1: hide burger */
}

@media(max-width:450px){
  .main-content{padding:12px;}            /* extra squeeze (kept from v2) */
}


/* scope bar -------------------------------------------------------- */
.scope-bar{flex-wrap:wrap}
.scope-bar .btn{box-shadow:none}
@media(max-width:576px){
  .scope-bar .btn{padding:.35rem .55rem;font-size:.82rem}
}

/* stats cards ------------------------------------------------------ */
.stats-grid i{font-size:1.4rem;margin-right:.65rem;line-height:1}
@media(max-width:450px){
   .stats-grid i{font-size:1.2rem;margin-right:.45rem}
   .stats-grid h6{font-size:.76rem;margin-bottom:2px}
   .stats-grid p{font-size:.8rem}
}
.row-cols-2.row-cols-sm-3>.col{flex:0 0 50%}
@media(min-width:576px){.row-cols-2.row-cols-sm-3>.col{flex:0 0 33.333%}}

/* chart wrapper ---------------------------------------------------- */
.chart-wrap{position:relative;width:100%;height:clamp(180px,42vw,320px)}
.card-body>canvas,.chart-wrap>canvas{display:block!important;width:100%!important;height:100%!important}

/* table tweaks ----------------------------------------------------- */
@media(max-width:450px){ table{font-size:.78rem} }

/* remove unwanted focus ring -------------------------------------- */
button:focus:not(:focus-visible){outline:0}
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
      <div class="d-flex justify-content-between align-items-center mb-3 scope-bar">
        <div class="btn-group" role="group">
          <button class="btn btn-outline-primary active" onclick="setScope('weekly')">Weekly</button>
          <button class="btn btn-outline-primary"  onclick="setScope('monthly')">Monthly</button>
          <button class="btn btn-outline-primary"  onclick="setScope('yearly')">Yearly</button>
        </div>
        <small class="text-muted fw-semibold ms-2" id="scopeRange"></small>
      </div>

      <!-- stat cards -->
      <div class="row g-2 stats-grid row-cols-2 row-cols-sm-3 mb-3">
        <div class="col">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-currency-exchange text-primary"></i>
              <div><h6>Total Sales Today</h6><p class="mb-0">₱<?=number_format($totalSalesToday,2)?></p></div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-box text-success"></i>
              <div><h6>Items Sold</h6><p class="mb-0"><?=$itemsSoldToday?> pcs</p></div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-bar-chart text-warning"></i>
              <div><h6>Top Category</h6><p class="mb-0"><?=htmlspecialchars($topCategoryName)?></p></div>
            </div>
          </div>
        </div>
      </div>

      <!-- charts -->
      <div class="row g-2 align-items-stretch mb-3">
        <div class="col-12 col-lg-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-header fw-semibold bg-white">Sales Over Time</div>
            <div class="card-body p-0"><div class="chart-wrap"><canvas id="salesChart"></canvas></div></div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-header fw-semibold bg-white">Category Breakdown</div>
            <div class="card-body p-0"><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></div>
          </div>
        </div>
      </div>

      <!-- recent sales -->
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold bg-white">Recent Sales Logs</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
              <thead class="table-light">
                <tr><th>Date</th><th>Item</th><th>Category</th><th>Qty</th><th>Total</th></tr>
              </thead>
              <tbody>
              <?php
                $stmt=$conn->prepare("
                  SELECT sale_date,product_name,c.category_name,quantity,total_amount
                  FROM sales s JOIN categories c ON s.category_id=c.id
                  WHERE s.user_id=? ORDER BY sale_date DESC LIMIT 5
                ");
                $stmt->bind_param("i",$user_id);$stmt->execute();$rs=$stmt->get_result();
                while($r=$rs->fetch_assoc()){
                  echo '<tr>'.
                       '<td>'.date('Y-m-d',strtotime($r['sale_date'])).'</td>'.
                       '<td>'.htmlspecialchars($r['product_name']).'</td>'.
                       '<td>'.htmlspecialchars($r['category_name']).'</td>'.
                       '<td>'.$r['quantity'].'</td>'.
                       '<td>₱'.number_format($r['total_amount'],2).'</td>'.
                       '</tr>';
                }$stmt->close();
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
<script>
const data=<?=json_encode($dashboardData,JSON_NUMERIC_CHECK)?>;
const ctxS=document.getElementById('salesChart'), ctxC=document.getElementById('categoryChart');

/* ---- charts ------------------------------------------------------ */
const salesChart=new Chart(ctxS,{type:'line',data:{
  labels:data.weekly.labels,
  datasets:[{label:'₱ Sales',data:data.weekly.salesData,borderWidth:2,
    borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,.12)',tension:.3,fill:true,pointRadius:3}]},
  options:{maintainAspectRatio:false,responsive:true,plugins:{legend:{display:false}},
  scales:{y:{ticks:{callback:v=>'₱'+v}}}}});

const catChart=new Chart(ctxC,{type:'doughnut',data:{
  labels:data.weekly.categoryLabels,
  datasets:[{data:data.weekly.categoryData}]},
  options:{maintainAspectRatio:false,responsive:true,
    plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}});

/* ---- scope switcher --------------------------------------------- */
function setScope(s){
  document.querySelectorAll('.btn-group .btn').forEach(b=>b.classList.remove('active'));
  document.querySelector(`.btn-group .btn[onclick="setScope('${s}')"]`).classList.add('active');
  document.getElementById('scopeRange').textContent=data.ranges[s];

  salesChart.data.labels=data[s].labels;
  salesChart.data.datasets[0].data=data[s].salesData;
  salesChart.update();

  catChart.data.labels=data[s].categoryLabels;
  catChart.data.datasets[0].data=data[s].categoryData;
  catChart.update();
}
setScope('weekly');
</script>
</body>
</html>
