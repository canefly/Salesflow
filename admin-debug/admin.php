<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Developer Console - SalesFlow</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 2rem;
      color: #333;
    }
    h2 {
      margin-top: 2rem;
      border-bottom: 1px solid #ddd;
      padding-bottom: 0.5rem;
    }
    .section {
      margin-bottom: 2rem;
      background: #fff;
      border: 1px solid #ccc;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    button {
      margin: 0.25rem;
      padding: 0.5rem 1rem;
      border: none;
      background-color: #007bff;
      color: white;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    input, select {
      padding: 0.5rem;
      margin: 0.25rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .response-box {
      background: #f1f1f1;
      padding: 1rem;
      border-radius: 5px;
      font-family: monospace;
      margin-top: 1rem;
      white-space: pre-wrap;
      overflow: auto;
    }
    .placeholder {
      background-color: #fff3cd;
      border: 1px dashed #ffc107;
      padding: 1rem;
      margin-top: 1rem;
      border-radius: 5px;
      color: #856404;
    }
  </style>
</head>
<body>
  <h1>🛠️ Developer Console - SalesFlow</h1>

  <div class="section">
    <h2>📂 Category Management</h2>
    <div>
      <h4>Create Category</h4>
      <input type="text" id="cat_name" placeholder="Category Name" />
      <input type="number" id="cat_parent_id" placeholder="Parent ID (optional)" />
      <button onclick="createCategory()">➕ Create Category</button>
    </div>
    <div>
      <h4>Get Categories</h4>
      <button onclick="getCategories()">📄 Fetch Categories</button>
      <div id="categoryList"></div>
    </div>
  </div>

  <div class="section">
    <h2>📦 Sales Testing</h2>
    <div>
      <h4>Create Sale</h4>
      <input type="text" id="sale_product" placeholder="Product Name" />
      <input type="number" id="sale_amount" placeholder="Total Amount" />
      <input type="number" id="sale_quantity" placeholder="Quantity" value="1" />
      <select id="sale_category">
        <option value="">-- Select Category --</option>
      </select>
      <button onclick="createSale()">➕ Create Sale</button>
    </div>
    <div>
      <h4>Get Sales (Flat)</h4>
      <button onclick="getSales()">📄 Fetch All Sales</button>
    </div>
    <div>
      <h4>Delete Sale</h4>
      <input type="number" id="delete_sale_id" placeholder="Sale ID" />
      <button onclick="deleteSale()">🗑️ Delete Sale</button>
    </div>
  </div>

  <div class="response-box" id="result">Awaiting test run...</div>

  <script>
    const user_id = 1; // Adjust this if needed

    function createCategory() {
      const data = new URLSearchParams();
      data.append('user_id', user_id);
      data.append('category_name', document.getElementById('cat_name').value);
      const parentId = document.getElementById('cat_parent_id').value;
      if (parentId) data.append('parent_id', parentId);

      fetch('../Backend/create_category.php', {
        method: 'POST',
        body: data
      })
        .then(res => res.json())
        .then(data => {
          document.getElementById('result').textContent = JSON.stringify(data, null, 2);
          getCategories();
        });
    }

    function getCategories() {
      fetch(`../Backend/get_categories.php?user_id=${user_id}`)
        .then(res => res.json())
        .then(data => {
          const list = document.getElementById('categoryList');
          const select = document.getElementById('sale_category');

          list.innerHTML = '<ul>' + data.categories.map(c => `<li>ID: ${c.id} - ${c.category_name}</li>`).join('') + '</ul>';
          select.innerHTML = '<option value="">-- Select Category --</option>' +
            data.categories.map(c => `<option value="${c.id}">${c.category_name}</option>`).join('');
        });
    }

    function createSale() {
      const data = new URLSearchParams();
      data.append('user_id', user_id);
      data.append('product_name', document.getElementById('sale_product').value);
      data.append('total_amount', document.getElementById('sale_amount').value);
      data.append('quantity', document.getElementById('sale_quantity').value);
      data.append('category_id', document.getElementById('sale_category').value);
      data.append('sale_date', new Date().toISOString().slice(0, 19).replace('T', ' '));
      data.append('notes', 'admin test sale');

      fetch('../Backend/create_sale.php', {
        method: 'POST',
        body: data
      })
        .then(res => res.json())
        .then(data => {
          document.getElementById('result').textContent = JSON.stringify(data, null, 2);
        });
    }

    function getSales() {
      fetch(`../Backend/get_sales.php?user_id=${user_id}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('result').textContent = JSON.stringify(data, null, 2);
        });
    }

    function deleteSale() {
      const data = new URLSearchParams();
      data.append('user_id', user_id);
      data.append('sale_id', document.getElementById('delete_sale_id').value);

      fetch('../Backend/delete_sale.php', {
        method: 'POST',
        body: data
      })
        .then(res => res.json())
        .then(data => {
          document.getElementById('result').textContent = JSON.stringify(data, null, 2);
        });
    }

    // Load categories on page load for dropdown
    window.onload = getCategories;
  </script>
</body>
</html>
