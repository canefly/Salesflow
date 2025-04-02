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
        <h1 class="mb-5 fw-semibold">Dashboard Overview</h1>
        <div class="row">
          <!-- Left Column: Sales Analytics -->
          <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <h5 class="card-title fw-bold mb-4">Sales Analytics</h5>
                <canvas id="salesChart" style="height:300px;"></canvas>
              </div>
            </div>
          </div>
          <!-- Right Column: Sales Summary Overview -->
          <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <h5 class="card-title fw-bold mb-4">Sales Summary Breakdown</h5>
                <canvas id="summaryChart" style="height:300px;"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card shadow-sm border-0 mt-4">
              <div class="card-body">
                <h5 class="card-title fw-bold mb-4">Recent Sales</h5>
                <div id="salesTable" class="table-responsive text-center">
                  <p>Loading recent sales...</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Sales Chart (Line)
      const ctx = document.getElementById('salesChart').getContext('2d');
      const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [{
            label: 'Total Income',
            data: [1200, 2500, 1800, 3000, 2700, 3500],
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            title: { display: true, text: 'Monthly Sales Trend' }
          }
        }
      });

      // Summary Chart (Bar)
      const ctx3 = document.getElementById('summaryChart').getContext('2d');
      const summaryChart = new Chart(ctx3, {
        type: 'bar',
        data: {
          labels: ['Total Income', 'Total Sales', 'Total Items', 'Total Categories'],
          datasets: [{
            label: 'Summary',
            data: [14250, 380, 94, 8],
            backgroundColor: [
              'rgba(255, 99, 132, 0.6)',
              'rgba(255, 159, 64, 0.6)',
              'rgba(75, 192, 192, 0.6)',
              'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(255, 159, 64, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            title: { display: true, text: 'Sales Summary Breakdown' }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      });

      // Dummy category mapper (replace with backend or dynamic version)
      function getCategoryNameById(id) {
        const map = {
          1: 'Food', 2: 'Drink', 3: 'Merch'
        };
        return map[id] || 'Unknown';
      }

      function getSales() {
        const user_id = 1; // replace with actual session user_id if dynamic
        fetch(`../Backend/get_sales.php?user_id=1`)
          .then(res => res.json())
          .then(data => {
            const rows = data.sales.map(sale => `
              <tr>
                <td>${sale.id}</td>
                <td>${sale.product_name}</td>
                <td>${sale.total_amount}</td>
                <td>${sale.quantity || '-'}</td>
                <td>${getCategoryNameById(sale.category_id)}</td>
                <td>${sale.sale_date}</td>
              </tr>`).join('');
            document.getElementById('salesTable').innerHTML = `
              <table class="table table-bordered">
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
          })
          .catch(() => {
            document.getElementById('salesTable').innerHTML = `<p class="text-danger">Failed to load sales data.</p>`;
          });
      }

      // Call fetch for table
      getSales();
    });
  </script>
</body>
</html>