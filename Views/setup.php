<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// If setup is already complete, redirect to dashboard
if (!isset($_SESSION['first_time']) || $_SESSION['first_time'] !== true) {
  header("Location: dashboard.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to SalesFlow — Setup Wizard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .setup-container {
      width: 100%;
      max-width: 600px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 2rem;
      position: relative;
    }
    .step {
      display: none;
    }
    .step.active {
      display: block;
      animation: fadeIn 0.4s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
</head>
<body>
  <div class="setup-container">
    <form id="setupForm">
      <!-- Step 1: Welcome -->
      <div class="step active" id="step1">
        <h2 class="mb-3">Welcome to SalesFlow</h2>
        <p class="mb-4">Powered by AI — Seraphina™</p>
        <button type="button" class="btn btn-primary w-100" onclick="nextStep('#step2')">Begin Setup</button>
      </div>

      <!-- Step 2: Profile Info -->
      <div class="step" id="step2">
        <h4 class="mb-3">Tell us about you</h4>
        <div class="mb-3 text-center">
          <label for="profilePicture" class="form-label">Upload Profile Picture</label>
          <div class="mb-3">
            <img id="previewImage" src="../Assets/Illustrations/noProfile.svg" class="rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ddd;" alt="Preview">
            <input class="form-control mt-2" type="file" id="profilePicture" accept="image/*" onchange="previewProfilePic(this)">
          </div>
        </div>
        <div class="mb-3">
          <label for="fullName" class="form-label">Full Name</label>
          <input class="form-control" type="text" id="fullName" placeholder="Your full name">
        </div>
        <!-- Warning message for empty name -->
        <div id="step2Warning" class="text-danger text-center small mb-2" style="display: none;">
          Please enter your full name or <a href="#step3" onclick="skipName()" class="text-decoration-underline">skip for now</a>.
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="nextStep('#step3')">Next</button>
      </div>

      <!-- Step 3: Preferences -->
      <div class="step" id="step3">
        <h4 class="mb-3">Preferences</h4>
        <div class="mb-3">
          <label class="form-label">Theme</label>
          <select class="form-select" id="theme">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Timezone</label>
          <select class="form-select" id="timezone">
  <option value="Asia/Manila">Asia/Manila (GMT+8)</option>
  <option value="Asia/Tokyo">Asia/Tokyo (GMT+9)</option>
  <option value="Asia/Singapore">Asia/Singapore (GMT+8)</option>
  <option value="Europe/London">Europe/London (GMT+0)</option>
  <option value="Europe/Paris">Europe/Paris (GMT+1)</option>
  <option value="America/New_York">America/New York (GMT-5)</option>
  <option value="America/Los_Angeles">America/Los Angeles (GMT-8)</option>
  <option value="UTC">UTC</option>
</select>
        </div>
        <div class="mb-3">
          <label class="form-label">Time Format</label><br>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="timeFormat" value="12" checked>
            <label class="form-check-label">12-Hour</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="timeFormat" value="24">
            <label class="form-check-label">24-Hour</label>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Currency Symbol</label>
          <select class="form-select" id="currency">
  <option value="PHP">Philippine Peso (₱)</option>
  <option value="USD">US Dollar ($)</option>
  <option value="EUR">Euro (€)</option>
  <option value="GBP">British Pound (£)</option>
  <option value="JPY">Japanese Yen (¥)</option>
  <option value="KRW">South Korean Won (₩)</option>
  <option value="CNY">Chinese Yuan (¥)</option>
  <option value="INR">Indian Rupee (₹)</option>
  <option value="AUD">Australian Dollar (A$)</option>
  <option value="CAD">Canadian Dollar (C$)</option>
</select>
        </div>
        <!-- Terms checkbox with modal trigger -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="agreeTerms">
          <label class="form-check-label" for="agreeTerms">
            I hereby agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms & conditions</a>
          </label>
        </div>

        <!-- Inline warning if checkbox is not checked -->
        <div id="termsWarning" class="text-danger text-center small mb-2" style="display: none;"></div>

        <button type="button" class="btn btn-primary w-100" onclick="startFinalization()">Finish Setup</button>
      </div>

      <!-- Step 4: Fake Loading Progress -->
      <div class="step" id="step4">
        <h4 class="mb-3">Setting up your experience...</h4>
        <div class="progress">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" id="loaderBar" style="width: 0%">0%</div>
        </div>
      </div>
    </form>
  </div>

<script>
  let currentStep = 1;

  // Moves to the next step in the setup
  function nextStep(targetId) {
    const warningBox = document.getElementById('step2Warning');

    // Check for full name on step 2
    if (currentStep === 2) {
      const nameInput = document.getElementById('fullName');
      if (nameInput && nameInput.value.trim() === '') {
        warningBox.style.display = 'block';
        return;
      }
    }

    // Hide warning and transition step
    warningBox.style.display = 'none';
    document.getElementById(`step${currentStep}`).classList.remove('active');
    currentStep++;
    document.getElementById(`step${currentStep}`).classList.add('active');
    window.location.hash = targetId; // Allows Chrome to navigate steps via URL hash
  }

  // Final step logic: validates terms, starts fake progress, and redirects
  function startFinalization() {
    const termsBox = document.getElementById('termsWarning');

    // Ensure terms box is checked
    if (!document.getElementById('agreeTerms').checked) {
      termsBox.textContent = "You must agree to the terms and conditions to continue.";
      termsBox.style.display = 'block';
      setTimeout(() => { termsBox.style.display = 'none'; }, 4000);
      return;
    }

    nextStep('#step4'); // Go to the final step

    let progress = 0;
    const bar = document.getElementById('loaderBar');
    const interval = setInterval(() => {
      progress += Math.floor(Math.random() * 8) + 1;
      if (progress > 100) progress = 100;
      bar.style.width = progress + '%';
      bar.textContent = progress + '%';

      // When finished, redirect to dashboard
      if (progress === 100) {
        clearInterval(interval);
        setTimeout(() => {
          window.location.href = 'dashboard.php';
        }, 800);
      }
    }, 200);
  }

  // Show image preview when profile picture is selected
  function previewProfilePic(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById('previewImage').src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Skip name entry if empty but still move forward
  function skipName() {
    document.getElementById('step2Warning').style.display = 'none';
    document.getElementById(`step${currentStep}`).classList.remove('active');
    currentStep++;
    document.getElementById(`step${currentStep}`).classList.add('active');
    window.location.hash = '#step3';
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// somewhere after setup completes
unset($_SESSION['first_time']);
?>
