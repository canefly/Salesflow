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
    .nav-header {
      background-color: #343a40;
      color: white;
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .section {
      margin-bottom: 2rem;
    }
  </style>
</head>
<body>
  <script src="../public/js/main.js"></script>
  <div class="nav-header">
    <h4 class="mb-0">SalesFlow</h4>
    <a href="../Backend/logout.php" class="btn btn-outline-light">Logout</a>
  </div>

    <!-- Floating Chat Button -->
  <button
    id="ai-chat-button"
    class="btn btn-primary rounded-circle chat-toggle-btn position-fixed"
    style="bottom: 30px; right: 30px; z-index: 999; width: 60px; height: 60px;"
  >
    <i class="fas fa-comment-dots"></i>
  </button>

  <!-- Messenger-Style Chat Panel -->
  <!-- 
       This 'aside' element represents a complementary UI panel.
       The .chat-panel class will handle positioning & transitions (in CSS).
       hidden attribute: we'll toggle this with JS to show/hide.
  -->
    <!-- Remove hidden here. Just rely on CSS. -->
    <aside
    id="ai-chat-panel"
    class="chat-panel position-fixed shadow"
    role="dialog"
    aria-modal="true"
    aria-labelledby="chat-panel-title"
    >

    <!-- Panel Header -->
    <header class="chat-header d-flex align-items-center justify-content-between bg-primary text-white px-3 py-2">
      <h5 class="mb-0" id="chat-panel-title">Salesflow AI Assistant</h5>
      <button class="btn-close btn-close-white" id="chat-close-btn" aria-label="Close chat"></button>
    </header>

    <!-- Chat Messages Container -->
    <div class="chat-body bg-white p-2" id="chat-messages">
      <!-- Chat bubbles inserted by JS -->
    </div>

    <!-- Chat Footer: Input & Send -->
    <footer class="chat-footer p-2 bg-light">
      <div class="input-group">
        <textarea
          class="form-control"
          rows="1"
          id="chat-input"
          placeholder="Ask something..."
        ></textarea>
        <button class="btn btn-primary" id="chat-send-btn">Send</button>
      </div>
    </footer>
  </aside>

  <div class="container py-4">
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
</body>
</html>
