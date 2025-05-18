<?php
/* ─────────────────────────────────────────────────────────
   UNIVERSAL INCOME-LOG PAGE  (UI + API + CRUD)
   ───────────────────────────────────────────────────────── */
session_start();
if (!isset($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php");
  exit;
}
require_once '../Database/connection.php';   // --> mysqli $conn
$user_id = $_SESSION['user_id'];

/* ==== AJAX BLOCK ======================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fetch') {
  /* fetch list for a given date ---------------------------------------- */
  $date = $_GET['date'] ?? date('Y-m-d');
  $start = "$date 00:00:00";
  $end   = "$date 23:59:59";

  $sql = "
    SELECT s.id, s.product_name, s.quantity, s.total_amount, s.sale_date,
           COALESCE(c.category_name,'Uncategorized') AS category
    FROM   sales s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE  s.user_id = ?  AND s.sale_date BETWEEN ? AND ?
    ORDER  BY s.sale_date ASC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('iss', $user_id, $start, $end);
  $stmt->execute();
  $res = $stmt->get_result();

  $data=[]; $sum=0.0;
  while($row=$res->fetch_assoc()){
    $row['amount'] = (float)$row['total_amount'];
    $data[] = $row;
    $sum += $row['amount'];
  }
  echo json_encode(['total_income'=>round($sum,2),'data'=>$data]);
  exit;
}

/* ---------------- UPDATE ---------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
  $id          = (int)$_POST['id'];
  $prod        = trim($_POST['product']);
  $cat_id      = (int)$_POST['category_id'];
  $qty         = (int)$_POST['quantity'];
  $amount      = round((float)$_POST['total_amount'],2);
  $datetime    = $_POST['datetime'];

  $sql="UPDATE sales SET product_name=?, category_id=?, quantity=?, total_amount=?, sale_date=? 
        WHERE id=? AND user_id=?";
  $stmt=$conn->prepare($sql);
  $stmt->bind_param('siidsii', $prod, $cat_id, $qty, $amount, $datetime, $id, $user_id);
  $ok=$stmt->execute();
  echo json_encode(['success'=>$ok]);
  exit;
}

/* ---------------- DELETE ---------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
  $id = (int)$_POST['id'];
  $stmt=$conn->prepare("DELETE FROM sales WHERE id=? AND user_id=?");
  $stmt->bind_param('ii',$id,$user_id);
  $ok=$stmt->execute();
  echo json_encode(['success'=>$ok]);
  exit;
}
/* ====================================================================== */

/* ==== PAGE RENDER (no ajax) ========================================== */
/* pull categories once for the edit dropdown */
$cats=[];
$cr=$conn->prepare("SELECT id,category_name FROM categories WHERE user_id=? OR user_id=0");
$cr->bind_param('i',$user_id);
$cr->execute();
$rs=$cr->get_result();
while($r=$rs->fetch_assoc()){$cats[]=$r;}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Salesflow — Income Log</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
  --sidebar-width:250px;--sidebar-collapsed-width:70px;
  --c-bg:#f8f9fa;--c-surface:#ffffff;--c-border:#e2e6ea;
  --c-text:#212529;--c-text-light:#6c757d;--c-blue:#0d6efd;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:var(--c-bg);color:var(--c-text);min-height:100vh;display:flex}
.wrapper{display:flex;flex:1}
@media(max-width:768px){.sidebar{display:none!important}.main-content{margin-left:0!important;padding:20px!important}}
.sidebar{width:var(--sidebar-width);transition:.3s}.sidebar.collapsed{width:var(--sidebar-collapsed-width)}
.main-content{flex:1;padding:40px;transition:margin-left .3s;margin-left:var(--sidebar-collapsed-width)}
.sidebar:not(.collapsed)~.main-content{margin-left:var(--sidebar-width)}
.header-bar{display:flex;align-items:center;gap:16px;margin-bottom:24px}
.header-bar button{background:none;border:none;color:var(--c-blue);font-size:1.3rem;cursor:pointer}
.summary{background:var(--c-surface);border:1px solid var(--c-border);padding:12px 20px;border-radius:12px;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.search-wrap{position:relative;margin-bottom:16px}
.search-wrap input{width:100%;padding:10px 44px 10px 16px;border:1px solid var(--c-border);border-radius:10px;background:var(--c-surface);outline:none}
.search-wrap input:focus{border-color:var(--c-blue)}
.search-wrap .fa-search{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--c-text-light)}
.txn-list{list-style:none;display:flex;flex-direction:column;gap:12px}
.txn-item{background:var(--c-surface);border:1px solid var(--c-border);border-radius:12px;padding:14px 18px;display:flex;gap:12px;align-items:center;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.icons{display:flex;flex-direction:column;gap:6px}
.icon-btn{background:none;border:none;color:var(--c-text-light);font-size:1rem;cursor:pointer}
.icon-btn:hover{color:var(--c-blue)}
.item-body{flex:1;display:flex;justify-content:space-between;align-items:center}
.prod-name{font-weight:500}
.cat-qty{font-size:.8rem;color:var(--c-text-light)}
.amt{font-weight:600;color:var(--c-blue)}
.time{font-size:.8rem;color:var(--c-text-light)}
.scroll-y{max-height:calc(100vh - 260px);overflow-y:auto;padding-right:4px}
.scroll-y::-webkit-scrollbar{width:6px}.scroll-y::-webkit-scrollbar-thumb{background:#c7ccd1;border-radius:3px}
</style>
</head>
<body>
<div class="wrapper">
  <?php include '../include/mobile-side-nav.php'; ?>
  <?php include '../include/chat.html'; ?>
  <?php include '../include/sidenav.php'; ?>

  <main class="main-content">
    <div class="header-bar">
      <button id="prevDay"><i class="fa fa-chevron-left"></i></button>
      <span id="currentDate" class="fw-semibold"></span>
      <button id="nextDay"><i class="fa fa-chevron-right"></i></button>
    </div>

    <div class="summary">
      <span class="text-muted fw-medium">Total income</span>
      <span id="totalIncome" class="fs-5 fw-semibold text-primary">₱0.00</span>
    </div>

    <div class="search-wrap">
      <input type="text" id="searchInput" placeholder="Search product / category…">
      <i class="fa fa-search"></i>
    </div>

    <div class="scroll-y">
      <ul class="txn-list" id="txnList"></ul>
    </div>
  </main>
</div>

<!-- ================== MODALS ================== -->
<!-- Edit -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <form id="editForm">
    <div class="modal-header">
      <h5 class="modal-title">Edit Sale</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="saleId">
      <div class="mb-3">
        <label class="form-label">Product</label>
        <input type="text" class="form-control" id="prodInput" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Category</label>
        <select class="form-select" id="catSelect" required></select>
      </div>
      <div class="row">
        <div class="col-6 mb-3">
          <label class="form-label">Quantity</label>
          <input type="number" class="form-control" id="qtyInput" min="1" required>
        </div>
        <div class="col-6 mb-3">
          <label class="form-label">Unit Price (₱)</label>
          <input type="number" class="form-control" id="unitPriceInput" step="0.01" min="0" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Total Amount</label>
        <input type="text" class="form-control" id="totalAmountDisplay" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Date & Time</label>
        <input type="datetime-local" class="form-control" id="dtInput" required>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary" type="submit">Save</button>
    </div>
   </form>
  </div>
 </div>
</div>

<!-- Delete confirm -->
<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Delete Sale</h5></div>
    <div class="modal-body">Are you sure you want to delete this record?</div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
 </div>
</div>

<!-- Notification -->
<div class="modal fade" id="notifyModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <div class="modal-body text-center" id="notifyBody"></div>
  </div>
 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* =================== DATA FROM PHP =================== */
const categories = <?php echo json_encode($cats); ?>;

/* =================== HELPERS =================== */
const ENDPOINT = window.location.pathname; // same file
const fmtPeso  = n=>'₱ '+(+n).toLocaleString('en-PH',{minimumFractionDigits:2});
const pad=n=>String(n).padStart(2,'0');
const dateISO=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

let currentDate=new Date();
let fullData=[];

/* =================== DOM REFS =================== */
const dateLbl=document.getElementById('currentDate');
const totalLbl=document.getElementById('totalIncome');
const listEl =document.getElementById('txnList');
const searchEl=document.getElementById('searchInput');

/* Modals */
const editModal=new bootstrap.Modal(document.getElementById('editModal'));
const delModal =new bootstrap.Modal(document.getElementById('delModal'));
const notifyModal=new bootstrap.Modal(document.getElementById('notifyModal'));
const notifyBody=document.getElementById('notifyBody');

/* Edit Form fields */
const saleIdEl=document.getElementById('saleId');
const prodEl  =document.getElementById('prodInput');
const catEl   =document.getElementById('catSelect');
const qtyEl   =document.getElementById('qtyInput');
const unitEl  =document.getElementById('unitPriceInput');
const totalDisp=document.getElementById('totalAmountDisplay');
const dtEl    =document.getElementById('dtInput');

/* populate categories dropdown once */
catEl.innerHTML=categories.map(c=>`<option value="${c.id}">${c.category_name}</option>`).join('');

/* =================== RENDERERS =================== */
function renderDate(){dateLbl.textContent=currentDate.toLocaleDateString('en-PH',{weekday:'short',day:'numeric',month:'long',year:'numeric'});}
function renderSummary(sum){totalLbl.textContent=fmtPeso(sum);}
function renderList(arr){
  listEl.innerHTML='';
  if(!arr.length){listEl.innerHTML='<li class="text-center text-muted">No income recorded.</li>';return;}
  arr.forEach(txn=>{
    const li=document.createElement('li');
    li.className='txn-item';
    li.innerHTML=`
      <div class="icons">
        <button class="icon-btn edit-btn" data-id="${txn.id}"><i class="fa fa-pen"></i></button>
        <button class="icon-btn del-btn" data-id="${txn.id}"><i class="fa fa-trash"></i></button>
      </div>
      <div class="item-body flex-grow-1">
        <div>
          <div class="prod-name">${txn.product_name}</div>
          <div class="cat-qty">${txn.category} • ${txn.quantity} pcs</div>
        </div>
        <div class="text-end">
          <div class="amt">${fmtPeso(txn.amount)}</div>
          <div class="time">${new Date(txn.sale_date).toLocaleTimeString('en-PH',{hour:'numeric',minute:'2-digit'})}</div>
        </div>
      </div>`;
    listEl.appendChild(li);
  });
}

/* =================== FETCH & LOAD =================== */
async function loadData(){
  const res=await fetch(`${ENDPOINT}?ajax=fetch&date=${dateISO(currentDate)}`);
  const j=await res.json().catch(()=>({total_income:0,data:[]}));
  fullData=j.data; renderSummary(j.total_income); applySearch();
}

/* =================== SEARCH =================== */
function applySearch(){
  const q=searchEl.value.trim().toLowerCase();
  const filtered=!q?fullData:fullData.filter(t=>t.product_name.toLowerCase().includes(q)||t.category.toLowerCase().includes(q));
  renderList(filtered);
}
searchEl.addEventListener('input',applySearch);

/* =================== NAVIGATION =================== */
document.getElementById('prevDay').onclick=()=>{currentDate.setDate(currentDate.getDate()-1);renderDate();loadData();};
document.getElementById('nextDay').onclick=()=>{currentDate.setDate(currentDate.getDate()+1);renderDate();loadData();};

/* =================== EDIT FLOW =================== */
listEl.addEventListener('click',e=>{
  if(e.target.closest('.edit-btn')){
    const id=e.target.closest('.edit-btn').dataset.id;
    const rec=fullData.find(x=>x.id==id);
    if(!rec)return;
    saleIdEl.value=rec.id;
    prodEl.value=rec.product_name;
    catEl.value=categories.find(c=>c.category_name===rec.category)?.id || '';
    qtyEl.value=rec.quantity;
    const unit=(rec.amount/rec.quantity).toFixed(2);
    unitEl.value=unit;
    totalDisp.value=fmtPeso(rec.amount);
    dtEl.value=rec.sale_date.replace(' ','T').slice(0,16); // yyyy-mm-ddThh:mm
    editModal.show();
  }
  if(e.target.closest('.del-btn')){
    const id=e.target.closest('.del-btn').dataset.id;
    document.getElementById('confirmDeleteBtn').dataset.id=id;
    delModal.show();
  }
});

/* qty or unit price change triggers total amount preview */
function updateTotal(){const t=qtyEl.value*unitEl.value;totalDisp.value=fmtPeso(t||0);}
qtyEl.addEventListener('input',updateTotal);unitEl.addEventListener('input',updateTotal);

/* save update */
document.getElementById('editForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const payload=new FormData();
  payload.append('action','update');
  payload.append('id',saleIdEl.value);
  payload.append('product',prodEl.value);
  payload.append('category_id',catEl.value);
  payload.append('quantity',qtyEl.value);
  payload.append('total_amount',(qtyEl.value*unitEl.value).toFixed(2));
  payload.append('datetime',dtEl.value.replace('T',' ') + ':00');

  const res=await fetch(ENDPOINT,{method:'POST',body:payload});
  const j=await res.json();
  editModal.hide();
  showNotify(j.success?'Saved!':'Update failed');
  loadData();
});

/* =================== DELETE FLOW =================== */
document.getElementById('confirmDeleteBtn').addEventListener('click',async e=>{
  const id=e.target.dataset.id;
  const fd=new FormData();fd.append('action','delete');fd.append('id',id);
  const res=await fetch(ENDPOINT,{method:'POST',body:fd});
  const j=await res.json();
  delModal.hide();
  showNotify(j.success?'Deleted!':'Delete failed');
  loadData();
});

/* =================== NOTIFY =================== */
function showNotify(msg){
  notifyBody.textContent=msg;
  notifyModal.show();
  setTimeout(()=>notifyModal.hide(),1500);
}

/* =================== INIT =================== */
renderDate();loadData();
</script>
</body>
</html>
