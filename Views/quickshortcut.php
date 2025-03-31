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
    <?php include '../include/sidenav.php'; ?>
    <main class="main-content">
      <h1>🔧 Quick Shortcut (Upcoming Feature)</h1>
      <p>This feature will allow you to log common sales with a single click — perfect for frequently ordered items. Instead of entering sale data manually, you'll be able to assign shortcuts and instantly populate sales into the system.</p>

      <div style="margin-top: 1.5rem; padding: 1rem 1.5rem; border: 1px solid #ffc107; background-color: #fff8e1; border-radius: 8px;">
        <h3 style="margin-bottom: 0.5rem;">⏳ Why isn’t it available yet?</h3>
        <p style="margin-bottom: 0.8rem;">
          We’re currently refining the database structure to reduce redundancy and ensure scalability. This involves optimizing how shortcut-linked items interact with your sales records, ensuring consistency and performance.
        </p>
        <p>
          💬 Want to follow progress or contribute ideas? Check out the development journey on 
          <a href="https://github.com/canefly/Salesflow" target="_blank" style="color: #007bff; text-decoration: underline;">GitHub</a> and stay tuned!
        </p>
      </div>
    </main>
  </div>
</body>
</html>