<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  session_unset(); session_destroy();
  header("Location: ../Views/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
require_once '../Database/connection.php';
$conn->set_charset('utf8mb4');

/* -------------- AJAX -------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  $a = $_POST['action'];

  // Save (add/edit)
  if ($a==='save') {
    $sid   = intval($_POST['shortcut_id']??0);
    $label = trim($_POST['label']??'');
    $prod  = trim($_POST['product_name']??'');
    $cat   = intval($_POST['category_id']??0);
    $amt   = floatval($_POST['amount']??0);
    $qty   = intval($_POST['quantity']??1);
    $hex   = trim($_POST['color_hex']??'#007bff');
    if (!$label||!$prod||!$cat||$amt<=0||$qty<=0) {
      echo json_encode(['ok'=>false,'msg'=>'All fields required'],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
    }
    if ($sid===0) {
      $q=$conn->prepare("INSERT INTO quick_shortcuts
          (user_id,label,product_name,category_id,amount,quantity,note)
          VALUES (?,?,?,?,?,?,?)");
      $q->bind_param("issiids",$user_id,$label,$prod,$cat,$amt,$qty,$hex);
    } else {
      $q=$conn->prepare("UPDATE quick_shortcuts SET
          label=?,product_name=?,category_id=?,amount=?,quantity=?,note=?
          WHERE id=? AND user_id=?");
      $q->bind_param("ssiidsii",$label,$prod,$cat,$amt,$qty,$hex,$sid,$user_id);
    }
    $ok=$q->execute(); $q->close();
    echo json_encode(['ok'=>$ok,'msg'=>$sid? 'Shortcut updated':'Shortcut added'],
      JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
  }

  // Delete
  if ($a==='delete') {
    $sid=intval($_POST['shortcut_id']??0);
    $q=$conn->prepare("DELETE FROM quick_shortcuts WHERE id=? AND user_id=?");
    $q->bind_param("ii",$sid,$user_id); $q->execute();
    echo json_encode(['ok'=>$q->affected_rows>0,'msg'=>'Shortcut deleted'],
      JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
  }

  // Fire (log sale)
  if ($a==='fire') {
    $sid=intval($_POST['shortcut_id']??0);
    $sel=$conn->prepare("SELECT product_name,category_id,amount,quantity
                         FROM quick_shortcuts WHERE id=? AND user_id=?");
    $sel->bind_param("ii",$sid,$user_id); $sel->execute(); $sel->store_result();
    if(!$sel->num_rows){
      echo json_encode(['ok'=>false,'msg'=>'Shortcut not found'],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
    }
    $sel->bind_result($p,$c,$price,$qty); $sel->fetch(); $sel->close();
    $total=$price*$qty;
    $ins=$conn->prepare("INSERT INTO sales
        (user_id,category_id,product_name,quantity,total_amount,notes)
        VALUES (?,?,?,?,?,?)");
    $note="Logged via shortcut #$sid";
    $ins->bind_param("iisids",$user_id,$c,$p,$qty,$total,$note);
    $ok=$ins->execute(); $ins->close();
    echo json_encode(['ok'=>$ok,'msg'=>$ok? 'Sale logged':'Sale failed'],
      JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
  }

  echo json_encode(['ok'=>false,'msg'=>'Unknown action'],
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

/* -------------- PAGE DATA -------------- */
$cats=[]; $c=$conn->prepare("SELECT id,category_name FROM categories
                              WHERE user_id=? ORDER BY category_name");
$c->bind_param("i",$user_id); $c->execute(); $c->bind_result($cid,$cn);
while($c->fetch()) $cats[$cid]=$cn; $c->close();

$shortcuts=[]; $s=$conn->prepare("SELECT id,label,product_name,category_id,
                                  amount,quantity,note FROM quick_shortcuts
                                  WHERE user_id=? ORDER BY id");
$s->bind_param("i",$user_id); $s->execute();
$s->bind_result($sid,$sl,$sp,$sc,$sa,$sq,$sn);
while($s->fetch()) $shortcuts[]=compact('sid','sl','sp','sc','sa','sq','sn');
$s->close();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Salesflow — Quick Shortcuts</title>

<!-- ✅ Bootstrap 5.3.3 CDN (NO integrity/crossorigin) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
.shortcut-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));
               gap:1rem;max-width:800px;margin-top:2rem}
.shortcut-button{aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;
                 color:#fff;border-radius:8px;cursor:pointer;font-weight:500;padding:1rem;
                 transition:transform .15s}
.shortcut-button:hover{transform:scale(1.05)}
.shortcut-modal{display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.5);
                justify-content:center;align-items:center}
.modal-box{background:#fff;border-radius:8px;padding:1.5rem;width:300px;position:relative;
           box-shadow:0 4px 10px rgba(0,0,0,.15)}
.modal-box .close{position:absolute;top:10px;right:10px;font-size:1.4rem;cursor:pointer}
.contextMenu{display:none;position:absolute;z-index:1100;background:#fff;border:1px solid #ccc;
             border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.contextMenu a{display:block;padding:8px 12px;color:#333;text-decoration:none}
.contextMenu a:hover{background:#f1f1f1}
@media(max-width:768px){.sidebar{display:none}.main-content{margin-left:0;padding:20px}}
@media(max-width:480px){.shortcut-grid{grid-template-columns:1fr}}
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head><body>
<div class="wrapper">
<?php include '../include/mobile-side-nav.php'; ?>
<?php include '../include/chat.html'; ?>
<?php include '../include/sidenav.php'; ?>

<main class="main-content">
  <div class="d-flex justify-content-between align-items-center">
    <h2 style="font-weight:600">Quick Shortcuts</h2>
    <div>
      <button id="exitSort" class="btn btn-dark me-2" style="display:none">Return</button>
      <button id="addNew"   class="btn btn-primary">➕ Add Shortcut</button>
    </div>
  </div>
  <p class="text-muted">Tap a tile ➜ instant sale. Right-click ➜ edit, delete, or reorganize.</p>

  <div id="grid" class="shortcut-grid">
    <?php foreach($shortcuts as $sc): ?>
      <div class="shortcut-button"
           data-id="<?= $sc['sid'];?>"
           data-label="<?= htmlspecialchars($sc['sl']);?>"
           data-product="<?= htmlspecialchars($sc['sp']);?>"
           data-cat="<?= $sc['sc'];?>"
           data-amount="<?= $sc['sa'];?>"
           data-qty="<?= $sc['sq'];?>"
           data-note="<?= htmlspecialchars($sc['sn']);?>"
           style="background:<?= htmlspecialchars($sc['sn'] ?: '#007bff');?>;">
        <div class="text-center">
          <div style="font-weight:600"><?= htmlspecialchars($sc['sl']);?></div>
          <div style="font-size:.9rem">₱<?= number_format($sc['sa'],2);?>
            — <?= $sc['sq'];?> pc<?= $sc['sq']>1?'s':'';?>
          </div>
        </div>
      </div>
    <?php endforeach;?>
  </div>

  <!-- Add/Edit Modal -->
  <div id="editModal" class="shortcut-modal">
    <div class="modal-box">
      <span class="close" id="editClose">&times;</span>
      <h5 id="editTitle">Add Shortcut</h5>
      <input type="hidden" id="eSid">
      <label class="mt-1">Label</label>
      <input type="text" id="eLabel" class="form-control">
      <label class="mt-2">Product Name</label>
      <input type="text" id="eProd" class="form-control">
      <label class="mt-2">Category</label>
      <select id="eCat" class="form-control">
        <option value="">-- choose --</option>
        <?php foreach($cats as $id=>$name): ?>
          <option value="<?= $id;?>"><?= htmlspecialchars($name);?></option>
        <?php endforeach;?>
      </select>
      <label class="mt-2">Color</label>
      <input type="color" id="eHex" class="form-control" value="#007bff">
      <label class="mt-2">Amount (₱)</label>
      <input type="number" step="0.01" id="eAmt" class="form-control">
      <label class="mt-2">Quantity</label>
      <input type="number" id="eQty" class="form-control" value="1">
      <button id="saveBtn" class="btn btn-success mt-3">Save</button>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="delModal" class="shortcut-modal">
    <div class="modal-box text-center">
      <h5>Delete this shortcut?</h5>
      <input type="hidden" id="dSid">
      <div class="d-flex gap-2 justify-content-center mt-3">
        <button id="dYes" class="btn btn-danger">Delete</button>
        <button id="dNo"  class="btn btn-secondary">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Context Menu -->
  <div id="ctx" class="contextMenu">
    <a href="#" id="ctxEdit">Edit</a>
    <a href="#" id="ctxDel">Delete</a>
    <a href="#" id="ctxSort">Reorganize</a>
  </div>

  <!-- Toast Zone -->
  <div id="toastZone" class="position-fixed top-0 end-0 p-3" style="z-index:1200"></div>

</main></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- ✅ Bootstrap 5.3.3 JS bundle, no integrity/crossorigin -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showToast(msg, ok = true) {
  if (window.bootstrap && bootstrap.Toast) {
    const id = 't'+Date.now();
    $('#toastZone').append(`
      <div id="${id}" class="toast align-items-center text-white ${ok?'bg-success':'bg-danger'} border-0 mb-2"
           role="alert" data-bs-delay="2200" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${msg}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
                  data-bs-dismiss="toast"></button>
        </div>
      </div>`);
    const toastEl = document.getElementById(id);
    new bootstrap.Toast(toastEl).show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  } else {
    alert(msg); // fallback
  }
}
function openM($m){ $m.css('display','flex').hide().fadeIn(120); }
function closeM($m){ $m.fadeOut(120,()=> $m.hide()); }
let sortable = Sortable.create(document.getElementById('grid'),{
  animation:150, ghostClass:'being-dragged', disabled:true});
$(function(){
  let ctxSid=null;
  $('#addNew').on('click',()=>{
    $('#editTitle').text('Add Shortcut');
    $('#eSid').val('');
    $('#eLabel,#eProd,#eAmt').val('');
    $('#eCat').val(''); $('#eQty').val(1); $('#eHex').val('#007bff');
    openM($('#editModal'));
  });
  $('#editClose').on('click',()=> closeM($('#editModal')));
  $('#saveBtn').on('click',()=>{
    $.ajax({
      type:'POST', url:'', dataType:'json',
      data:{
        action:'save',
        shortcut_id:$('#eSid').val(),
        label:$('#eLabel').val(),
        product_name:$('#eProd').val(),
        category_id:$('#eCat').val(),
        amount:$('#eAmt').val(),
        quantity:$('#eQty').val(),
        color_hex:$('#eHex').val()
      }
    }).done(res=>{
      showToast(res.msg,res.ok);
      if(res.ok) setTimeout(()=>location.reload(),900);
    }).fail(()=> showToast('Server error',false));
  });
  $('#grid').on('click','.shortcut-button',function(){
    if($('#ctx').is(':visible')) return;
    $.ajax({
      type:'POST', url:'', dataType:'json',
      data:{action:'fire',shortcut_id:$(this).data('id')}
    }).done(res=> showToast(res.msg,res.ok))
      .fail(()=> showToast('Server error',false));
  });
  $('.shortcut-button').on('contextmenu',function(e){
    e.preventDefault();
    ctxSid=$(this).data('id');
    $('#ctx').css({top:e.pageY,left:e.pageX}).fadeIn(100);
  });
  $(document).on('click',()=> $('#ctx').fadeOut(100));
  $('#ctxEdit').on('click',()=>{
    $('#ctx').hide();
    const btn=$(`.shortcut-button[data-id="${ctxSid}"]`);
    $('#editTitle').text('Edit Shortcut');
    $('#eSid').val(ctxSid);
    $('#eLabel').val(btn.data('label'));
    $('#eProd').val(btn.data('product'));
    $('#eCat').val(btn.data('cat'));
    $('#eAmt').val(btn.data('amount'));
    $('#eQty').val(btn.data('qty'));
    $('#eHex').val(btn.data('note')||'#007bff');
    openM($('#editModal'));
  });
  $('#ctxDel').on('click',()=>{
    $('#ctx').hide(); $('#dSid').val(ctxSid); openM($('#delModal'));
  });
  $('#dNo').on('click',()=> closeM($('#delModal')));
  $('#dYes').on('click',()=>{
    $.ajax({
      type:'POST',url:'',dataType:'json',
      data:{action:'delete',shortcut_id:$('#dSid').val()}
    }).done(res=>{
      showToast(res.msg,res.ok);
      if(res.ok) setTimeout(()=>location.reload(),900);
    }).fail(()=> showToast('Server error',false));
    closeM($('#delModal'));
  });
  $('#ctxSort').on('click',()=>{
    $('#ctx').hide();
    sortable.option('disabled',false);
    $('#exitSort').show();
    showToast('Drag to reorder, then “Return”',true);
  });
  $('#exitSort').on('click',()=>{
    sortable.option('disabled',true);
    $('#exitSort').hide();
  });
});
</script>
</body></html>
