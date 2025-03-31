<?php

session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to your Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../public/css/styles.css" />
  <style>
    body {
      background-color: #f8f9fa;
    }
    .section {
      margin-bottom: 2rem;
    }
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
      padding: 0px;
      transition: margin-left 0.3s ease;
      margin-left: var(--sidebar-collapsed-width);
    }
    .sidebar:not(.collapsed) ~ .main-content {
      margin-left: var(--sidebar-width);
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    p {
      font-size: 1rem;
      color: #666;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: absolute;
        left: -100%;
        top: 0;
        height: 100%;
        z-index: 1000;
        transform: translateX(0);
        transition: transform 0.3s ease;
      }
      .sidebar.active {
        left: 0;
        transform: translateX(0);
      }
      .main-content {
        margin-left: 0 !important;
      }
      .backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
      }
      .backdrop.active {
        display: block;
      }
    }
  </style>
</head>
<body>
<div class="wrapper">
<?php include '../include/chat.html'; ?>
<?php include '../include/sidenav.php'; ?>
<div class="backdrop" id="backdrop"></div>
<main class="main-content">
  <script src="../public/js/main.js"></script>

  <div class="container-fluid px-3 py-4">
    <h2 class="mb-4">📂 Category Management</h2>
    <div class="card p-3 section">
      <input type="text" id="cat_name" placeholder="Category Name" class="form-control mb-2">
      <input type="number" id="cat_parent_id" placeholder="Parent ID (optional)" class="form-control mb-2">
      <button onclick="createCategory()" class="btn btn-primary">➕ Create Category</button>
      <div id="categoryList" class="mt-3"></div>
    </div>

    <h2 class="mb-4">💸 Create Sale</h2>
    <div class="card p-3 section">
      <input type="text" id="sale_product" placeholder="Product Name" class="form-control mb-2">
      <input type="number" id="sale_amount" placeholder="Total Amount" class="form-control mb-2">
      <input type="number" id="sale_quantity" value="1" placeholder="Quantity" class="form-control mb-2">
      <select id="sale_category" class="form-select mb-2">
        <option value="">-- Select Category --</option>
      </select>
      <button onclick="createSale()" class="btn btn-success">➕ Create Sale</button>
    </div>

    <h2 class="mb-4">📊 Sales Table</h2>
    <div id="salesTable" class="table-responsive section"></div>

    <div class="card border-warning section">
      <div class="card-body">
        <h5 class="card-title text-warning">🗑️ Recycle Bin (Under Development)</h5>
        <p class="card-text">This section is still under development. Features like restore and purge will be available soon.</p>
      </div>
    </div>
  </div>

  <script>
    const user_id = <?php echo $_SESSION['user_id']; ?>;
    let categories = [];

    function createCategory() {
      const data = new URLSearchParams();
      data.append('user_id', user_id);
      data.append('category_name', document.getElementById('cat_name').value);
      const parentId = document.getElementById('cat_parent_id').value;
      if (parentId) data.append('parent_id', parentId);

      fetch('../Backend/create_category.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(data => {
          getCategories();
        });
    }

    function getCategories() {
      fetch(`../Backend/get_categories.php?user_id=${user_id}`)
        .then(res => res.json())
        .then(data => {
          categories = data.categories;
          const select = document.getElementById('sale_category');
          const list = document.getElementById('categoryList');
          select.innerHTML = '<option value="">-- Select Category --</option>' +
            categories.map(c => `<option value="${c.id}">${c.category_name}</option>`).join('');
          list.innerHTML = '<ul>' + categories.map(c => `<li>ID: ${c.id} - ${c.category_name}</li>`).join('') + '</ul>';
        });
    }

    function getCategoryNameById(id) {
      const cat = categories.find(c => c.id == id);
      return cat ? `${cat.category_name} (ID: ${id})` : `ID: ${id}`;
    }

    function createSale() {
      const data = new URLSearchParams();
      data.append('user_id', user_id);
      data.append('product_name', document.getElementById('sale_product').value);
      data.append('total_amount', document.getElementById('sale_amount').value);
      data.append('quantity', document.getElementById('sale_quantity').value);
      data.append('category_id', document.getElementById('sale_category').value);
      const now = new Date();
      const offset = now.getTimezoneOffset() * 60000; // Convert to ms
      const localISO = new Date(now.getTime() - offset).toISOString().slice(0, 19).replace('T', ' ');
      data.append('sale_date', localISO);

      data.append('notes', 'dashboard sale');

      fetch('../Backend/create_sale.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(data => {
          getSales();
        });
    }

    function getSales() {
      fetch(`../Backend/get_sales.php?user_id=${user_id}`)
        .then(res => res.json())
        .then(data => {
          const rows = data.sales.map(sale => `
            <tr>
              <td>${sale.id}</td>
              <td>${sale.product_name}</td>
              <td>${sale.total_amount}</td>
              <td>${sale.quantity}</td>
              <td>${getCategoryNameById(sale.category_id)}</td>
              <td>${sale.sale_date}</td>
            </tr>`).join('');
          document.getElementById('salesTable').innerHTML = `
            <table class="table table-bordered">
              <thead><tr><th>ID</th><th>Product</th><th>Amount</th><th>Qty</th><th>Category</th><th>Date</th></tr></thead>
              <tbody>${rows}</tbody>
            </table>`;
        });
    }

    window.onload = function () {
      getCategories();
      getSales();
    };
  </script>
</main>
</div>
<script>
  (function() {
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        backdrop.classList.toggle('active');
      });
    }

    if (backdrop) {
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('active');
        backdrop.classList.remove('active');
      });
    }
  })();
</script>
</body>
</html>
