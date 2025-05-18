<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow — Income Log</title>

  <!-- fonts / icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --sidebar-width:250px;
      --sidebar-collapsed-width:70px;
      --c-bg:#f8f9fa;
      --c-surface:#ffffff;
      --c-border:#e2e6ea;
      --c-text:#212529;
      --c-text-light:#6c757d;
      --c-blue:#0d6efd;
      --c-blue-light:#58a0ff;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:'Poppins',sans-serif;
      background:var(--c-bg);
      color:var(--c-text);
      min-height:100vh;
      display:flex;
    }
    .wrapper{display:flex;flex:1}
    @media(max-width:768px){
      .sidebar{display:none!important}
      .main-content{margin-left:0!important;padding:20px!important}
      .toggle-btn{display:none!important}
    }
    .sidebar{width:var(--sidebar-width);transition:.3s}
    .sidebar.collapsed{width:var(--sidebar-collapsed-width)}
    .main-content{
      flex:1;
      padding:40px;
      transition:margin-left .3s;
      margin-left:var(--sidebar-collapsed-width);
    }
    .sidebar:not(.collapsed)~.main-content{margin-left:var(--sidebar-width)}

    .header-bar{
      display:flex;align-items:center;gap:16px;margin-bottom:24px;
    }
    .header-bar button{
      background:none;border:none;color:var(--c-blue);font-size:1.3rem;cursor:pointer;
    }
    #currentDate{font-size:1.25rem;font-weight:600}

    .summary{
      background:var(--c-surface);
      border:1px solid var(--c-border);
      padding:12px 20px;
      border-radius:12px;
      display:flex;justify-content:space-between;align-items:center;
      margin-bottom:24px;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    .summary .label{font-weight:500;font-size:.95rem;color:var(--c-text-light)}
    .summary .value{font-size:1.35rem;font-weight:600;color:var(--c-blue)}

    .search-wrap{position:relative;margin-bottom:16px}
    .search-wrap input{
      width:100%;padding:10px 44px 10px 16px;
      border-radius:10px;border:1px solid var(--c-border);
      background:var(--c-surface);color:var(--c-text);
      font-size:.95rem;outline:none;
    }
    .search-wrap input:focus{border-color:var(--c-blue)}
    .search-wrap .fa-search{
      position:absolute;right:16px;top:50%;transform:translateY(-50%);
      color:var(--c-text-light)
    }

    .txn-list{list-style:none;display:flex;flex-direction:column;gap:12px}
    .txn-item{
      background:var(--c-surface);
      border:1px solid var(--c-border);
      border-radius:12px;
      padding:14px 18px;
      display:flex;justify-content:space-between;align-items:center;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    .txn-item .left{display:flex;flex-direction:column;gap:2px}
    .prod-name{font-weight:500;font-size:1rem}
    .cat-qty{font-size:.8rem;color:var(--c-text-light)}
    .txn-item .right{text-align:right}
    .amt{font-weight:600;color:var(--c-blue)}
    .time{font-size:.8rem;color:var(--c-text-light)}

    .scroll-y{max-height:calc(100vh - 260px);overflow-y:auto;padding-right:4px}
    .scroll-y::-webkit-scrollbar{width:6px}
    .scroll-y::-webkit-scrollbar-track{background:transparent}
    .scroll-y::-webkit-scrollbar-thumb{background:#c7ccd1;border-radius:3px}
  </style>
</head>
<body>
  <div class="wrapper">
    <?php include '../include/mobile-side-nav.php'; ?>
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>

    <main class="main-content">
      <!-- date navigation -->
      <div class="header-bar">
        <button id="prevDay"><i class="fa fa-chevron-left"></i></button>
        <span id="currentDate"></span>
        <button id="nextDay"><i class="fa fa-chevron-right"></i></button>
      </div>

      <!-- total -->
      <div class="summary">
        <span class="label">Total income</span>
        <span class="value" id="totalIncome">₱0.00</span>
      </div>

      <!-- search -->
      <div class="search-wrap">
        <input type="text" id="searchInput" placeholder="Search product / category…">
        <i class="fa fa-search"></i>
      </div>

      <!-- list -->
      <div class="scroll-y">
        <ul class="txn-list" id="txnList"></ul>
      </div>
    </main>
  </div>

  <script>
    const ENDPOINT = '../Backend/fetch_income.php'; // make sure this path is correct

    let currentDate = new Date();
    let fullData = [];

    const fmtPeso = n => '₱ ' + (+n).toLocaleString('en-PH',{minimumFractionDigits:2});
    const pad     = n => (''+n).padStart(2,'0');
    const dateISO = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

    const dateLabel = document.getElementById('currentDate');
    const totalLbl  = document.getElementById('totalIncome');
    const listEl    = document.getElementById('txnList');
    const searchEl  = document.getElementById('searchInput');

    function renderDateLabel(){
      const opts = { weekday:'short', day:'numeric', month:'long', year:'numeric' };
      dateLabel.textContent = currentDate.toLocaleDateString('en-PH',opts);
    }
    function renderSummary(sum){ totalLbl.textContent = fmtPeso(sum || 0); }
    function renderList(arr){
      listEl.innerHTML = '';
      if (!arr.length) {
        listEl.innerHTML = `<li style="text-align:center;color:var(--c-text-light)">No income recorded.</li>`;
        return;
      }
      arr.forEach(txn => {
        const li = document.createElement('li');
        li.className = 'txn-item';
        li.innerHTML = `
          <div class="left">
            <span class="prod-name">${txn.product}</span>
            <span class="cat-qty">${txn.category} • ${txn.quantity} pcs</span>
          </div>
          <div class="right">
            <span class="amt">${fmtPeso(txn.amount)}</span><br>
            <span class="time">${new Date(txn.datetime).toLocaleTimeString('en-PH',{hour:'numeric',minute:'2-digit'})}</span>
          </div>
        `;
        listEl.appendChild(li);
      });
    }

    async function loadData() {
      const iso = dateISO(currentDate);
      const res = await fetch(`${ENDPOINT}?date=${iso}`);
      const json = await res.json().catch(()=>({ total_income:0, data:[] }));
      fullData = json.data;
      renderSummary(json.total_income);
      applySearch();
    }

    function applySearch(){
      const q = searchEl.value.trim().toLowerCase();
      const filtered = q
        ? fullData.filter(t => t.product.toLowerCase().includes(q) || t.category.toLowerCase().includes(q))
        : fullData;
      renderList(filtered);
    }

    document.getElementById('prevDay').onclick = ()=>{
      currentDate.setDate(currentDate.getDate()-1);
      renderDateLabel(); loadData();
    };
    document.getElementById('nextDay').onclick = ()=>{
      currentDate.setDate(currentDate.getDate()+1);
      renderDateLabel(); loadData();
    };
    searchEl.addEventListener('input', applySearch);

    // init
    (()=>{
      renderDateLabel();
      loadData();
    })();
  </script>
</body>
</html>
