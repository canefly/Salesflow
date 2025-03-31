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
      <h1>👤 User Profile (Upcoming Feature)</h1>
      <p>This section will allow you to manage and personalize your account on Salesflow. Features include:</p>
      <ul>
        <li>🖼️ Upload and update your profile picture</li>
        <li>📝 Set or edit your full name</li>
        <li>📧 Verify and manage your email address</li>
        <li>🎨 Customize your profile preferences</li>
      </ul>

      <div style="margin-top: 1.5rem; padding: 1rem 1.5rem; border: 1px solid #17a2b8; background-color: #e9f8fb; border-radius: 8px;">
        <h3 style="margin-bottom: 0.5rem;">⏳ Why isn’t it available yet?</h3>
        <p style="margin-bottom: 0.8rem;">
          We're currently laying the foundation for user identity management and account customization. This involves integrating secure authentication and a scalable user settings system.
        </p>
        <p>
          💬 Want to follow development or suggest profile features? Check out the repository on 
          <a href="https://github.com/canefly/Salesflow" target="_blank" style="color: #007bff; text-decoration: underline;">GitHub</a> and stay tuned!
        </p>
      </div>
    </main>
  </div>
</body>
</html>