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

/* ---------- AJAX HANDLERS ---------- */
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // 1. fetch categories + usage count
    if ($action === 'fetch_categories') {
        $stmt = $conn->prepare(
            "SELECT id, category_name,
                    (SELECT COUNT(*) FROM sales
                     WHERE sales.category_id = categories.id
                       AND user_id = ?) AS usage_count
             FROM categories
             WHERE user_id = ? AND parent_id IS NULL
             ORDER BY category_name"
        );
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) $out[] = $row;
        echo json_encode(['status'=>'success','data'=>$out]);
        exit;
    }

    // 2. add category
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') die(json_encode(['status'=>'error','message'=>'Name required']));
        $stmt = $conn->prepare("INSERT INTO categories (user_id, category_name) VALUES (?,?)");
        $stmt->bind_param("is",$user_id,$name);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success']);
        } else echo json_encode(['status'=>'error','message'=>'Insert failed']);
        exit;
    }

    // 3. rename category
    if ($action === 'rename_category') {
        $id   = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        if ($name==='') die(json_encode(['status'=>'error','message'=>'Name required']));
        $stmt = $conn->prepare("UPDATE categories SET category_name=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii",$name,$id,$user_id);
        echo json_encode($stmt->execute()
            ? ['status'=>'success']
            : ['status'=>'error','message'=>'Rename failed']);
        exit;
    }

    // 4. check usage
    if ($action === 'check_category_usage') {
        $id = intval($_POST['id']);
        $stmt=$conn->prepare("SELECT COUNT(*) AS cnt FROM sales WHERE category_id=? AND user_id=?");
        $stmt->bind_param("ii",$id,$user_id);
        $stmt->execute();
        $cnt=$stmt->get_result()->fetch_assoc()['cnt']??0;
        echo json_encode(['status'=>'success','count'=>(int)$cnt]);
        exit;
    }

    // 5. delete + cascade sales
    if ($action === 'delete_category') {
        $id = intval($_POST['id']);
        $conn->begin_transaction();
        $ok1=$conn->prepare("DELETE FROM sales WHERE category_id=? AND user_id=?");
        $ok1->bind_param("ii",$id,$user_id); $ok1=$ok1->execute();
        $ok2=$conn->prepare("DELETE FROM categories WHERE id=? AND user_id=?");
        $ok2->bind_param("ii",$id,$user_id); $ok2=$ok2->execute();
        if($ok1&&$ok2){$conn->commit();echo json_encode(['status'=>'success']);}
        else{$conn->rollback();echo json_encode(['status'=>'error','message'=>'Delete failed']);}
        exit;
    }

    // 6. add sale
    if ($action === 'add_sale') {
        $product=trim($_POST['product']??'');
        $amount =floatval($_POST['amount']??0);
        $qty    =intval($_POST['qty']??1);
        $cid    =intval($_POST['category_id']??0);
        if($product==''||$amount<=0||$qty<=0||$cid<=0)
            die(json_encode(['status'=>'error','message'=>'Invalid data']));
        $st=$conn->prepare("INSERT INTO sales (user_id,category_id,product_name,quantity,total_amount)
                            VALUES (?,?,?,?,?)");
        $st->bind_param("iisid",$user_id,$cid,$product,$qty,$amount);
        echo json_encode($st->execute()
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Salesflow — Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--sidebar-width:250px;--sidebar-collapsed-width:70px;--bs-primary:#0d6efd;--bs-primary-rgb:13,110,253;}
body{font-family:'Poppins',sans-serif;background:#f5f5f5;color:#333;}
.wrapper{display:flex;min-height:100vh;}
.sidebar{width:var(--sidebar-width);transition:width .3s;}
.sidebar.collapsed{width:var(--sidebar-collapsed-width);}
.main-content{flex:1;padding:2rem;margin-left:var(--sidebar-collapsed-width);transition:margin-left .3s;}
.sidebar:not(.collapsed)~.main-content{margin-left:var(--sidebar-width);}
.nav-pills .nav-link{color:#495057;background:transparent;border-radius:.5rem;padding:.5rem 1rem;transition:.2s;margin-right:.5rem;}
.nav-pills .nav-link:hover{background:rgba(var(--bs-primary-rgb),.1);color:var(--bs-primary);}
.nav-pills .nav-link.active{background:var(--bs-primary);color:#fff;font-weight:600;}
.category-card{box-shadow:0 .125rem .25rem rgba(0,0,0,.075);border-radius:.5rem;transition:.2s;}
.category-card:hover{transform:translateY(-4px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15);}
.btn-outline-danger:hover,.btn-outline-secondary:hover{background:var(--bs-primary);color:#fff;border-color:var(--bs-primary);}
#notification{display:none;position:fixed;top:1rem;right:1rem;min-width:250px;z-index:1055;}
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
              <label class="form-label" for="total-amount">Total Amount</label>
              <input id="total-amount" type="number" step="0.01" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="quantity">Quantity (pcs)</label>
              <input id="quantity" type="number" class="form-control" required>
            </div>
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
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <p>Rename category</p>
            <input id="rename-input" class="form-control mb-3" placeholder="New name">
            <div class="d-flex justify-content-center">
              <button id="confirm-rename" class="btn btn-secondary me-3" disabled>Save</button>
              <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- delete modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <p id="delete-message"></p>
            <div class="d-flex justify-content-center">
              <button id="confirm-delete" class="btn btn-danger me-3">Delete</button>
              <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ---------- helpers ---------- */
const qs = s=>document.querySelector(s);
const notify = msg=>{
  qs('#notification-message').textContent=msg;
  const n = qs('#notification');
  n.style.display='block';
  n.classList.add('show');
  setTimeout(()=>{n.classList.remove('show');setTimeout(()=>n.style.display='none',300)},2000);
};

/* ---------- globals ---------- */
let categories=[], delIdx=null, renIdx=null;
const listRow=qs('#category-list'), sel=qs('#category-select');
const delModal=new bootstrap.Modal('#delete-modal'), renModal=new bootstrap.Modal('#rename-modal');
const delMsg=qs('#delete-message'), delBtn=qs('#confirm-delete'), renBtn=qs('#confirm-rename'), renInput=qs('#rename-input');

/* ---------- fetch & render ---------- */
function fetchCategories(){
  fetch('?action=fetch_categories').then(r=>r.json()).then(j=>{
    if(j.status==='success'){categories=j.data;render();bootstrap.Tooltip.dispose(document.body);initTips();}
  });
}
function render(){
  listRow.innerHTML=''; sel.innerHTML='<option value="">Select category</option>';
  categories.forEach((c,i)=>{
    const col=document.createElement('div');col.className='col-sm-6 col-md-4';
    const card=document.createElement('div');card.className='card category-card';
    card.setAttribute('data-bs-toggle','tooltip');
    card.setAttribute('title',`${c.usage_count} sale${c.usage_count!=1?'s':''}`);

    const body=document.createElement('div');
    body.className='card-body d-flex justify-content-between align-items-center';

    const name=document.createElement('span');name.textContent=c.category_name;

    const actions=document.createElement('div');actions.className='d-flex gap-2';

    const pen=document.createElement('button');
    pen.className='btn btn-outline-secondary btn-sm rounded-pill';
    pen.innerHTML='<i class="fas fa-pen"></i>';
    pen.onclick=()=>renameClick(i);

    const del=document.createElement('button');
    del.className='btn btn-outline-danger btn-sm rounded-pill';
    del.textContent='Delete';
    del.onclick=()=>deleteClick(i);

    actions.append(pen,del);
    body.append(name,actions);
    card.append(body); col.append(card); listRow.append(col);

    const opt=document.createElement('option');opt.value=c.id;opt.textContent=c.category_name;
    sel.append(opt);
  });
}
function initTips(){
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));
}

/* ---------- add category ---------- */
qs('#add-category').onclick=()=>{
  const v=qs('#new-category').value.trim();
  if(!v) return notify('Name required');
  fetch('?action=add_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'name='+encodeURIComponent(v)})
    .then(r=>r.json()).then(j=>{
      j.status==='success'? (qs('#new-category').value='',fetchCategories(),notify('Category added')):notify(j.message);
    });
};

/* ---------- delete flow ---------- */
function deleteClick(i){
  delIdx=i;
  fetch('?action=check_category_usage',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+categories[i].id})
    .then(r=>r.json()).then(j=>{
      if(j.count>0){
        delMsg.textContent=`Deleting this category removes ${j.count} linked sale${j.count!=1?'s':''}. Continue?`;
        let c=3;delBtn.disabled=true;delBtn.textContent=`Delete (${c})`;
        const t=setInterval(()=>{c--;delBtn.textContent=`Delete (${c})`;if(c==0){clearInterval(t);delBtn.disabled=false;delBtn.textContent='Delete';}},1000);
      }else{
        delMsg.textContent='Are you sure you want to delete this category?';
        delBtn.disabled=false;delBtn.textContent='Delete';
      }
      delModal.show();
    });
}
delBtn.onclick=()=>{
  fetch('?action=delete_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+categories[delIdx].id})
    .then(r=>r.json()).then(j=>{
      j.status==='success'? (fetchCategories(),notify('Category deleted')):notify(j.message);
      delModal.hide();
    });
};

/* ---------- rename flow ---------- */
function renameClick(i){
  renIdx=i; renInput.value=categories[i].category_name; renBtn.disabled=true; renModal.show();
}
renInput.oninput=()=>{renBtn.disabled=renInput.value.trim()==='';};
renBtn.onclick=()=>{
  fetch('?action=rename_category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`id=${categories[renIdx].id}&name=${encodeURIComponent(renInput.value.trim())}`})
    .then(r=>r.json()).then(j=>{
      j.status==='success'? (fetchCategories(),notify('Category renamed')):notify(j.message);
      renModal.hide();
    });
};

/* ---------- add sale ---------- */
qs('#income-form').onsubmit=e=>{
  e.preventDefault();
  const p=new URLSearchParams();
  p.append('product',qs('#product-name').value);
  p.append('amount',qs('#total-amount').value);
  p.append('qty',qs('#quantity').value);
  p.append('category_id',qs('#category-select').value);
  fetch('?action=add_sale',{method:'POST',body:p})
    .then(r=>r.json()).then(j=>{
      j.status==='success'? (notify('Sale recorded'),e.target.reset(),fetchCategories()):notify(j.message);
    });
};

/* ---------- init ---------- */
fetchCategories();
</script>
</body>
</html>
