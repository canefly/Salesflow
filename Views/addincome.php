<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header("Location: ../Views/login.php");
    exit;
}

require_once '../Database/connection.php';          // $conn (mysqli)
$user_id = $_SESSION['user_id'];

/* ------------------ AJAX ------------------ */
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $a = $_GET['action'];

    /* fetch categories + usage count */
    if ($a === 'fetch_categories') {
        $sql = "SELECT id, category_name,
                       (SELECT COUNT(*) FROM sales
                        WHERE category_id = c.id AND user_id = ?) AS usage_count
                FROM categories c
                WHERE user_id = ? AND parent_id IS NULL
                ORDER BY category_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        echo json_encode(['status'=>'success',
                          'data'=>$stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        exit;
    }

    /* add category */
    if ($a === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') die(json_encode(['status'=>'error','message'=>'Name required']));
        $stmt = $conn->prepare("INSERT INTO categories (user_id, category_name) VALUES (?,?)");
        $stmt->bind_param('is', $user_id, $name);
        echo json_encode($stmt->execute()
               ? ['status'=>'success']
               : ['status'=>'error','message'=>'Insert failed']);
        exit;
    }

    /* rename category */
    if ($a === 'rename_category') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if ($name==='') die(json_encode(['status'=>'error','message'=>'Name required']));
        $stmt = $conn->prepare("UPDATE categories SET category_name=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sii', $name, $id, $user_id);
        echo json_encode($stmt->execute()
               ? ['status'=>'success']
               : ['status'=>'error','message'=>'Rename failed']);
        exit;
    }

    /* linked sales count */
    if ($a === 'check_category_usage') {
        $id = (int)$_POST['id'];
        $stmt=$conn->prepare("SELECT COUNT(*) cnt FROM sales WHERE category_id=? AND user_id=?");
        $stmt->bind_param('ii',$id,$user_id); $stmt->execute();
        $cnt=$stmt->get_result()->fetch_assoc()['cnt']??0;
        echo json_encode(['status'=>'success','count'=>(int)$cnt]);
        exit;
    }

    /* delete category (+ cascade sales) */
    if ($a === 'delete_category') {
        $id = (int)$_POST['id'];
        $conn->begin_transaction();
        $ok1=$conn->prepare("DELETE FROM sales WHERE category_id=? AND user_id=?");
        $ok1->bind_param('ii',$id,$user_id); $ok1=$ok1->execute();
        $ok2=$conn->prepare("DELETE FROM categories WHERE id=? AND user_id=?");
        $ok2->bind_param('ii',$id,$user_id); $ok2=$ok2->execute();
        if ($ok1 && $ok2) { $conn->commit(); echo json_encode(['status'=>'success']); }
        else { $conn->rollback(); echo json_encode(['status'=>'error','message'=>'Delete failed']); }
        exit;
    }

    /* add sale — takes sale_date from front-end */
    if ($a === 'add_sale') {
        $product = trim($_POST['product'] ?? '');
        $qty     = (int)$_POST['qty'];
        $amount  = (float)$_POST['amount'];
        $cid     = (int)$_POST['category_id'];
        $sdate   = trim($_POST['sale_date'] ?? '');                 // ISO string or empty
        if ($product===''||$qty<=0||$amount<=0||$cid<=0)
            die(json_encode(['status'=>'error','message'=>'Invalid data']));
        if ($sdate === '') $sdate = date('Y-m-d H:i:s');

        $stmt=$conn->prepare(
            "INSERT INTO sales (user_id, category_id, product_name, quantity, total_amount, sale_date)
             VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iisids', $user_id, $cid, $product, $qty, $amount, $sdate);
        echo json_encode($stmt->execute()
               ? ['status'=>'success']
               : ['status'=>'error','message'=>'Sale insert failed']);
        exit;
    }

    echo json_encode(['status'=>'error','message'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Salesflow — Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {--sidebar-width: 250px; --sidebar-collapsed-width: 70px;}
    * {box-sizing: border-box;margin: 0;padding: 0;}
body {font-family: 'Poppins', sans-serif;background: #f5f5f5;color: #333;}
.wrapper{display: flex;min-height: 100vh;}
@media(max-width: 768px) {.sidebar {display: none !important;}.main-content {margin-left: 0 !important;padding: 20px !important;}.toggle-btn {display: none !important;}}
.sidebar{width: var(--sidebar-width);transition: width 0.3s ease;}
.sidebar.collapsed {width: var(--sidebar-collapsed-width);}
.main-content { flex: 1;padding: 40px;transition: margin-left 0.3s ease;margin-left: var(--sidebar-collapsed-width);}
.sidebar:not(.collapsed) ~ .main-content {margin-left: var(--sidebar-width);}
.nav-pills .nav-link{color:#495057;background:transparent;border-radius:.5rem;padding:.5rem 1rem;transition:.2s;margin-right:.5rem;}
.nav-pills .nav-link:hover{background:rgba(var(--bs-primary-rgb),.1);color:var(--bs-primary);}
.nav-pills .nav-link.active{background:var(--bs-primary);color:#fff;font-weight:600;}.category-card{box-shadow:0 .125rem .25rem rgba(0,0,0,.075);border-radius:.5rem;transition:.2s;}
.category-card:hover{transform:translateY(-4px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15);}
.btn-outline-danger:hover,.btn-outline-secondary:hover{background:var(--bs-primary);color:#fff;border-color:var(--bs-primary);}
#notification{display:none;position:fixed;top:1rem;right:1rem;min-width:250px;z-index:1055;}
#total-banner{display:none;font-weight:600;}
</style>
</head>
<body>
<div class="wrapper">
  <?php include '../include/mobile-side-nav.php'; ?>
  <?php include '../include/chat.html'; ?>
  <?php include '../include/sidenav.php'; ?>

  <main class="main-content">
    <!-- notification -->
    <div id="notification" class="alert bg-primary text-white alert-dismissible fade shadow">
      <span id="notification-message"></span>
      <button class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>

    <!-- tabs -->
    <ul class="nav nav-pills mb-4" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#category">Categories</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#income">Income</a></li>
    </ul>

    <div class="tab-content">
      <!-- categories -->
      <div class="tab-pane fade show active" id="category">
        <h2 class="mb-4">Manage Categories</h2>
        <div class="input-group mb-4">
          <input id="new-category" class="form-control" placeholder="New category name">
          <button id="add-category" class="btn btn-primary">Add</button>
        </div>
        <div id="category-list" class="row g-3"></div>
      </div>

      <!-- income -->
      <div class="tab-pane fade" id="income">
        <h2 class="mb-4">Add Income</h2>
        <form id="income-form">
          <div class="mb-3">
            <label class="form-label" for="product-name">Product Name</label>
            <input id="product-name" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="unit-price">Unit Price</label>
              <input id="unit-price" type="number" step="0.01" min="0" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="quantity">Quantity (pcs)</label>
              <input id="quantity" type="number" min="1" value="1" class="form-control" required>
            </div>
          </div>

          <div class="mb-3 d-flex align-items-end">
            <div class="flex-grow-1 me-2">
              <label class="form-label" for="date-display">Date</label>
              <input id="date-display" class="form-control" readonly>
            </div>
            <button id="open-date" type="button" class="btn btn-outline-secondary mb-1" title="Change date">
              <i class="fas fa-calendar-alt"></i>
            </button>
          </div>

          <div id="total-banner" class="alert alert-info p-2 mb-3">
            Total: ₱<span id="total-display">0.00</span>
          </div>

          <div class="mb-3">
            <label class="form-label" for="category-select">Category</label>
            <select id="category-select" class="form-select" required>
              <option value="">Select category</option>
            </select>
          </div>

          <button class="btn btn-primary">Save Income</button>
        </form>
      </div>
    </div>

    <!-- rename modal -->
    <div class="modal fade" id="rename-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body text-center">
          <p>Rename category</p>
          <input id="rename-input" class="form-control mb-3" placeholder="New name">
          <div class="d-flex justify-content-center">
            <button id="confirm-rename" class="btn btn-secondary me-3" disabled>Save</button>
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div></div>
    </div>

    <!-- delete modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body text-center">
          <p id="delete-message"></p>
          <div class="d-flex justify-content-center">
            <button id="confirm-delete" class="btn btn-danger me-3">Delete</button>
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div></div>
    </div>

    <!-- date modal -->
    <div class="modal fade" id="date-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body text-center">
          <p>Select date &amp; time</p>
          <input id="date-picker" type="datetime-local" class="form-control mb-3">
          <div class="d-flex justify-content-center">
            <button id="date-save" class="btn btn-secondary me-3">Apply</button>
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div></div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ---------- helpers ---------- */
const $ = s => document.querySelector(s);
const notify = msg=>{
  $('#notification-message').textContent = msg;
  const n = $('#notification');
  n.style.display='block'; n.classList.add('show');
  setTimeout(()=>{n.classList.remove('show'); setTimeout(()=>n.style.display='none',300)},2000);
};

/* ---------- global refs ---------- */
let cats=[], delIdx=null, renIdx=null;
const list  = $('#category-list'),
      sel   = $('#category-select'),
      delM  = new bootstrap.Modal('#delete-modal'),
      renM  = new bootstrap.Modal('#rename-modal'),
      dateM = new bootstrap.Modal('#date-modal'),
      delMsg=$('#delete-message'),
      delBtn=$('#confirm-delete'),
      renBtn=$('#confirm-rename'),
      renIn =$('#rename-input');

/* ---------- categories flow ---------- */
function fetchCats(){
  fetch('?action=fetch_categories').then(r=>r.json()).then(j=>{
    if(j.status==='success'){cats=j.data; draw(); initTips();}
  });
}
function draw(){
  list.innerHTML=''; sel.innerHTML='<option value=\"\">Select category</option>';
  cats.forEach((c,i)=>{
    const col=document.createElement('div');col.className='col-sm-6 col-md-4';
    const card=document.createElement('div');card.className='card category-card';
    card.dataset.bsToggle='tooltip';card.title=`${c.usage_count} sale${c.usage_count!=1?'s':''}`;
    const body=document.createElement('div');body.className='card-body d-flex justify-content-between align-items-center';
    const name=document.createElement('span'); name.textContent=c.category_name;
    const act=document.createElement('div'); act.className='d-flex gap-2';
    const pen=document.createElement('button'); pen.className='btn btn-outline-secondary btn-sm rounded-pill';
    pen.innerHTML='<i class=\"fas fa-pen\"></i>'; pen.onclick=()=>openRename(i);
    const del=document.createElement('button'); del.className='btn btn-outline-danger btn-sm rounded-pill';
    del.textContent='Delete'; del.onclick=()=>openDelete(i);
    act.append(pen,del); body.append(name,act); card.append(body); col.append(card); list.append(col);
    const opt=document.createElement('option'); opt.value=c.id; opt.textContent=c.category_name; sel.append(opt);
  });
}
function initTips(){document.querySelectorAll('[data-bs-toggle=\"tooltip\"]').forEach(el=>new bootstrap.Tooltip(el));}

/* add category */
$('#add-category').onclick=()=>{
  const val=$('#new-category').value.trim();
  if(!val) return notify('Name required');
  fetch('?action=add_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'name='+encodeURIComponent(val)})
    .then(r=>r.json()).then(j=>{
      j.status==='success'?( $('#new-category').value='', fetchCats(), notify('Category added') )
                          : notify(j.message);
    });
};

/* delete */
function openDelete(i){
  delIdx=i;
  fetch('?action=check_category_usage',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+cats[i].id})
    .then(r=>r.json()).then(j=>{
      if(j.count>0){
        delMsg.textContent=`Deleting will remove ${j.count} linked sale${j.count!=1?'s':''}. Continue?`;
        let s=3; delBtn.disabled=true; delBtn.textContent=`Delete (${s})`;
        const t=setInterval(()=>{s--;delBtn.textContent=`Delete (${s})`;
          if(s===0){clearInterval(t); delBtn.disabled=false; delBtn.textContent='Delete';}},1000);
      }else{
        delMsg.textContent='Delete this category?'; delBtn.disabled=false; delBtn.textContent='Delete';
      }
      delM.show();
    });
}
delBtn.onclick=()=>{
  fetch('?action=delete_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+cats[delIdx].id})
    .then(r=>r.json()).then(j=>{
      j.status==='success'?( fetchCats(), notify('Category deleted') ):notify(j.message);
      delM.hide();
    });
};

/* rename */
function openRename(i){
  renIdx=i; renIn.value=cats[i].category_name; renBtn.disabled=true; renM.show();
}
renIn.oninput=()=>renBtn.disabled=renIn.value.trim()==='';
renBtn.onclick=()=>{
  fetch('?action=rename_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`id=${cats[renIdx].id}&name=${encodeURIComponent(renIn.value.trim())}`})
    .then(r=>r.json()).then(j=>{
      j.status==='success'?( fetchCats(), notify('Category renamed') ):notify(j.message);
      renM.hide();
    });
};

/* ---------- income / date & total ---------- */
const price=$('#unit-price'),
      qty  =$('#quantity'),
      totWrap=$('#total-banner'),
      totDisp=$('#total-display'),
      dateDisp=$('#date-display'),
      datePicker=$('#date-picker');

/* set today */
const nowISO = new Date().toISOString().slice(0,16);
dateDisp.value = new Date().toLocaleString('sv-SE',{hour12:false}).replace(' ',' Time: ');
datePicker.value = nowISO;

/* open calendar */
$('#open-date').onclick=()=>{ datePicker.value = dateDisp.value; dateM.show(); };
$('#date-save').onclick=()=>{ dateDisp.value = datePicker.value; dateM.hide(); };

/* total calc */
function calcTotal(){
  const p = parseFloat(price.value)||0,
        q = parseInt(qty.value)||0,
        t = p*q;
  totDisp.textContent = t.toFixed(2);
  totWrap.style.display = (q>1 ? 'block':'none');
}
price.oninput=calcTotal; qty.oninput=calcTotal;

/* save sale */
$('#income-form').onsubmit=e=>{
  e.preventDefault(); calcTotal();
  const unit = parseFloat(price.value)||0,
        qv   = parseInt(qty.value)||0,
        total= unit*qv;
  const data = new URLSearchParams();
  data.append('product',$('#product-name').value);
  data.append('qty',qv); data.append('amount',total);
  data.append('category_id', sel.value);
  data.append('sale_date', dateDisp.value);          // ISO string
  fetch('?action=add_sale',{method:'POST',body:data})
    .then(r=>r.json()).then(j=>{
      j.status==='success'?( notify('Sale recorded'), e.target.reset(),
                             totWrap.style.display='none', dateDisp.value=new Date().toLocaleString('sv-SE',{hour12:false}).replace(' ',' Time: '),
                             fetchCats() )
                          : notify(j.message);
    });
};

/* ---------- init ---------- */
fetchCats();
</script>
</body>
</html>
