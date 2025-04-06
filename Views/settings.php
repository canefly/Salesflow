<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: ../Views/login.php");
  exit;
}

require_once '../Database/connection.php';

$user_id = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = '$user_id' LIMIT 1");

if (mysqli_num_rows($query) > 0) {
  $settings = mysqli_fetch_assoc($query);
} else {
  $settings = [
    'use_subcategories' => 0,
    'theme' => 'light',
    'currency_symbol' => 'PHP',
    'timezone' => 'Asia/Manila',
    'time_format' => 12
  ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --sidebar-width: 250px;
      --sidebar-collapsed-width: 70px;
    }

    body {$1
      transition: background 0.5s ease, color 0.5s ease;
    }

    /* Dark theme overrides */
    /* Force dark text & background on <select> & <option> */
    .dark select,
    .dark select option {
      background-color: #4b5563 !important; /* Gray-700 or so */
      color: #f3f4f6 !important; /* Gray-100 text */
    }
    body.dark {
      background: linear-gradient(to bottom right, #3b3b3b, #2c2c2c) !important;
      color: #f3f4f6;
    }

    /* Card background override in dark mode */
    .dark .card-bg {
      background-color: rgba(55, 65, 81, 0.75) !important;
      backdrop-filter: blur(10px);
      color: #f3f4f6;
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
      transition: margin-left 0.3s ease;
      margin-left: var(--sidebar-collapsed-width);
    }

    .sidebar:not(.collapsed) ~ .main-content {
      margin-left: var(--sidebar-width);
    }

    @media (max-width: 768px) {
      .sidebar, .toggle-btn {
        display: none !important;
      }
      .main-content {
        margin-left: 0 !important;
        padding: 20px !important;
      }
    }

    /* Fade-in animation for the card */
    @keyframes fadeUp {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .animate-fadeUp {
      animation: fadeUp 0.6s ease-out forwards;
    }

    /* Light ocean glow around the card */
    .glow-border {
      box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
    }

    /* Toggle switch styling */
    .switch input:checked ~ .slider {
      background-color: #0ea5e9; /* switch track color when checked */
    }
    .switch input:checked ~ .slider:before {
      transform: translateX(1.125rem);
    }
    .slider {
      position: relative;
      display: inline-block;
      width: 2.25rem;
      height: 1.25rem;
      background-color: #e2e8f0;
      border-radius: 9999px;
      transition: background-color 0.2s;
    }
    .slider:before {
      content: "";
      position: absolute;
      width: 0.875rem;
      height: 0.875rem;
      left: 0.1875rem;
      bottom: 0.1875rem;
      background-color: #fff;
      border-radius: 9999px;
      transition: transform 0.2s;
      box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Toast notification styles */
    .toast {
      position: fixed;
      top: 1rem;
      right: 1rem;
      background-color: #10b981; /* green-500 */
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      opacity: 0;
      transform: translateY(-20px);
      transition: opacity 0.3s ease, transform 0.3s ease;
      z-index: 9999;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

  </style>
</head>
<body <?php if($settings['theme'] === 'dark') echo 'class="dark"'; ?>>
  <div class="wrapper flex min-h-screen">
    <?php include '../include/mobile-side-nav.php'; ?>
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>

    <!-- Center the content in the main area -->
    <main class="main-content min-h-screen flex items-center justify-center px-4 py-8">
      <div class="w-full max-w-3xl animate-fadeUp bg-white/80 backdrop-blur-xl rounded-xl glow-border shadow-2xl p-8 card-bg">
        <h1 class="text-4xl font-bold mb-8 flex items-center gap-2 justify-center">
          <i class="fas fa-cog text-blue-500"></i>
          <span>Settings</span>
        </h1>
        <form id="settingsForm" action="../backend/update_settings.php" method="POST" class="space-y-6">
          <!-- Use Subcategories (toggle switch) -->
          <div class="flex items-center justify-between">
            <label for="use_subcategories" class="text-base font-medium">Use Subcategories</label>
            <label class="switch cursor-pointer">
              <input id="use_subcategories" name="use_subcategories" type="checkbox" class="sr-only" <?php if($settings['use_subcategories']) echo 'checked'; ?>>
              <span class="slider"></span>
            </label>
          </div>

          <!-- Theme Selector -->
          <div>
            <label for="theme" class="block text-base font-medium mb-2">Theme</label>
            <select id="theme" name="theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
              <option value="light" <?php if($settings['theme'] === 'light') echo 'selected'; ?>>Light</option>
              <option value="dark" <?php if($settings['theme'] === 'dark') echo 'selected'; ?>>Dark</option>
            </select>
          </div>

          <!-- Currency Symbol Selector -->
          <div>
            <label for="currency_symbol" class="block text-base font-medium mb-2">Currency Symbol</label>
            <select id="currency_symbol" name="currency_symbol" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
              <option value="PHP" <?php if($settings['currency_symbol'] === 'PHP') echo 'selected'; ?>>Philippine Peso (₱)</option>
              <option value="USD" <?php if($settings['currency_symbol'] === 'USD') echo 'selected'; ?>>US Dollar ($)</option>
              <option value="EUR" <?php if($settings['currency_symbol'] === 'EUR') echo 'selected'; ?>>Euro (€)</option>
              <option value="GBP" <?php if($settings['currency_symbol'] === 'GBP') echo 'selected'; ?>>British Pound (£)</option>
              <option value="JPY" <?php if($settings['currency_symbol'] === 'JPY') echo 'selected'; ?>>Japanese Yen (¥)</option>
              <option value="KRW" <?php if($settings['currency_symbol'] === 'KRW') echo 'selected'; ?>>South Korean Won (₩)</option>
              <option value="CNY" <?php if($settings['currency_symbol'] === 'CNY') echo 'selected'; ?>>Chinese Yuan (¥)</option>
              <option value="INR" <?php if($settings['currency_symbol'] === 'INR') echo 'selected'; ?>>Indian Rupee (₹)</option>
              <option value="AUD" <?php if($settings['currency_symbol'] === 'AUD') echo 'selected'; ?>>Australian Dollar (A$)</option>
              <option value="CAD" <?php if($settings['currency_symbol'] === 'CAD') echo 'selected'; ?>>Canadian Dollar (C$)</option>
            </select>
          </div>

          <!-- Timezone Selector -->
          <div>
            <label for="timezone" class="block text-base font-medium mb-2">Timezone</label>
            <select id="timezone" name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
              <option value="Asia/Manila" <?php if($settings['timezone'] === 'Asia/Manila') echo 'selected'; ?>>Asia/Manila (GMT+8)</option>
              <option value="Asia/Tokyo" <?php if($settings['timezone'] === 'Asia/Tokyo') echo 'selected'; ?>>Asia/Tokyo (GMT+9)</option>
              <option value="Asia/Singapore" <?php if($settings['timezone'] === 'Asia/Singapore') echo 'selected'; ?>>Asia/Singapore (GMT+8)</option>
              <option value="Europe/London" <?php if($settings['timezone'] === 'Europe/London') echo 'selected'; ?>>Europe/London (GMT+0)</option>
              <option value="Europe/Paris" <?php if($settings['timezone'] === 'Europe/Paris') echo 'selected'; ?>>Europe/Paris (GMT+1)</option>
              <option value="America/New_York" <?php if($settings['timezone'] === 'America/New_York') echo 'selected'; ?>>America/New York (GMT-5)</option>
              <option value="America/Los_Angeles" <?php if($settings['timezone'] === 'America/Los_Angeles') echo 'selected'; ?>>America/Los Angeles (GMT-8)</option>
            </select>
          </div>

          <!-- Time Format -->
          <div>
            <label class="block text-base font-medium mb-2">Time Format</label>
            <div class="flex gap-8">
              <label class="inline-flex items-center">
                <input type="radio" name="time_format" value="12" class="form-radio text-blue-600 focus:ring-blue-500" <?php if($settings['time_format'] == 12) echo 'checked'; ?>>
                <span class="ml-2 text-sm">12 Hour</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" name="time_format" value="24" class="form-radio text-blue-600 focus:ring-blue-500" <?php if($settings['time_format'] == 24) echo 'checked'; ?>>
                <span class="ml-2 text-sm">24 Hour</span>
              </label>
            </div>
          </div>

          <!-- Save Button -->
          <div class="pt-6 text-center">
            <button id="saveBtn" type="submit" class="inline-flex items-center justify-center px-8 py-3 text-white bg-blue-600 hover:bg-blue-700 font-semibold rounded-md shadow-xl transition-transform transform hover:-translate-y-0.5">
              Save Settings
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast">Settings saved successfully!</div>

  <script>
    // 1) Intercept form, do AJAX submit to avoid full page reload
    const settingsForm = document.getElementById('settingsForm');
    const toastEl = document.getElementById('toast');

    settingsForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(settingsForm);

      fetch(settingsForm.getAttribute('action'), {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not OK');
        }
        return response.text();
      })
      .then(data => {
        // Show success toast
        toastEl.classList.add('show');
        setTimeout(() => {
          toastEl.classList.remove('show');
        }, 3000);
      })
      .catch(error => {
        console.error('Error saving settings:', error);
        // You could show an error toast as well
      });
    });

    // 2) Apply dark mode instantly on select change
    const themeSelect = document.getElementById('theme');
    const cardBg = document.querySelector('.card-bg');

    function applyTheme(theme) {
      if (theme === 'dark') {
        document.body.classList.add('dark');
      } else {
        document.body.classList.remove('dark');
      }
    }

    themeSelect.addEventListener('change', function(e){
      applyTheme(e.target.value);
    });

    // On page load, apply the theme from DB if needed
    // (Already done in the <body> tag with 
    // But if you want immediate effect on page load in JS:
    // applyTheme('<?php echo $settings['theme']; ?>');

  </script>
</body>
</html>
