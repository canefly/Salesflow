<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  // Not logged in = get out
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php"); // adjust path if needed
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
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

      @media (max-width: 768px) {
    .sidebar {
      display: none !important;
    }

    .main-content {
      margin-left: 0 !important;
      padding: 20px !important;
    }

    .toggle-btn {
      display: none !important;
    }
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
      padding: 40px;
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
  </style>
</head>
<body>
  <div class="wrapper">
    <?php include '../include/mobile-side-nav.php'; ?>
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>
    <main class="main-content">
      <div class="container-fluid">
        <h1 class="mb-4 fw-semibold">🧾 Transactions</h1>

        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <input type="number" id="delete_sale_id" class="form-control" placeholder="Enter Sale ID to Delete">
              </div>
              <div class="col-md-6">
                <button class="btn btn-danger" onclick="deleteSale()">🗑️ Delete Sale</button>
              </div>
            </div>
            <div id="salesTable">Loading sales...</div>
            <div id="result" class="mt-3 text-muted small"></div>
          </div>
        </div>
      </div>
    </main>

    <script>
      const user_id = 1;

      function getCategoryNameById(categoryId) {
        return `Category ${categoryId}`;
      }

      function getSales() {
        fetch(`../Backend/get_sales.php?user_id=${user_id}`)
          .then(res => res.json())
          .then(data => {
            const rows = data.sales.map(sale => `
              <tr>
                <td>${sale.id}</td>
                <td>${sale.product_name}</td>
                <td>₱${parseFloat(sale.total_amount).toLocaleString()}</td>
                <td>${sale.quantity}</td>
                <td>${getCategoryNameById(sale.category_id)}</td>
                <td>${sale.sale_date}</td>
              </tr>`).join('');
            document.getElementById('salesTable').innerHTML = `
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Qty</th>
                    <th>Category</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>`;
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
          getSales();
        });
      }

      window.onload = () => getSales();
    </script>
  </div>
</body>
</html>