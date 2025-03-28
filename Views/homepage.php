<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Salesflow</title>

  <!-- Bootstrap CSS for layout and components -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Custom Styles -->
  <link rel="stylesheet" href="../public/css/styles.css" />
  <link rel="stylesheet" href="../public/css/floatingMoney.css">


  <!-- AOS (Animate On Scroll) for scroll animations -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
  <!-- JavaScript for interactivity (navbar, chat, etc.) -->
  <script src="../public/js/main.js"></script>
  <script src="../public/js/floatingMoney.js" defer></script>

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

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
    <div class="container">
      <a class="navbar-brand" href="#">Salesflow</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero d-flex align-items-center justify-content-center text-center">
    <div class="container">
      <h1 class="display-4 fw-bold">Revolutionize Your Business with AI</h1>
      <p class="lead">Track expenses, optimize profits, and stay ahead — all in one powerful platform.</p>
      <a href="login.php" class="btn btn-primary btn-lg mt-3">Get Started</a>
    </div>

    <div class="wave-overlay">
        <svg id="hero-wave" class="data-waves" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">

            <defs>
                <filter id="waveFocusBlur" x="-50%" y="-50%" width="200%" height="200%">
                  <feGaussianBlur in="SourceGraphic" stdDeviation="1" result="blur" />
                  <feComponentTransfer>
                    <feFuncA type="table" tableValues="0 0.5 1 0.5 0" />
                  </feComponentTransfer>
                  <feBlend in="SourceGraphic" in2="blur" mode="normal" />
                </filter>
              </defs>
              
          
          <!-- Wave Line 1 -->
          <path d="M0,160 C360,80 1080,240 1440,160" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="12s" repeatCount="indefinite"
              values="M0,160 C360,80 1080,240 1440,160;
                      M0,140 C400,100 1040,280 1440,180;
                      M0,160 C360,80 1080,240 1440,160" />
          </path>
      
          <!-- Wave Line 2 -->
          <path d="M0,170 C320,100 1060,260 1440,170" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="14s" repeatCount="indefinite"
              values="M0,170 C320,100 1060,260 1440,170;
                      M0,160 C400,80 1040,240 1440,160;
                      M0,170 C320,100 1060,260 1440,170" />
          </path>
      
          <!-- Wave Line 3 -->
          <path d="M0,180 C280,120 1080,250 1440,180" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="16s" repeatCount="indefinite"
              values="M0,180 C280,120 1080,250 1440,180;
                      M0,160 C300,100 1040,230 1440,160;
                      M0,180 C280,120 1080,250 1440,180" />
          </path>
      
          <!-- Wave Line 4 -->
          <path d="M0,190 C250,110 1100,270 1440,190" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="18s" repeatCount="indefinite"
              values="M0,190 C250,110 1100,270 1440,190;
                      M0,170 C260,90 1000,240 1440,170;
                      M0,190 C250,110 1100,270 1440,190" />
          </path>
      
          <!-- Wave Line 5 -->
          <path d="M0,200 C220,130 1120,260 1440,200" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="20s" repeatCount="indefinite"
              values="M0,200 C220,130 1120,260 1440,200;
                      M0,180 C240,110 980,250 1440,180;
                      M0,200 C220,130 1120,260 1440,200" />
          </path>
      
          <!-- Wave Line 6 -->
          <path d="M0,210 C200,140 1140,250 1440,210" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="22s" repeatCount="indefinite"
              values="M0,210 C200,140 1140,250 1440,210;
                      M0,190 C210,110 1040,230 1440,190;
                      M0,210 C200,140 1140,250 1440,210" />
          </path>
      
          <!-- Wave Line 7 -->
          <path d="M0,220 C180,150 1160,240 1440,220" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="24s" repeatCount="indefinite"
              values="M0,220 C180,150 1160,240 1440,220;
                      M0,200 C200,130 1020,220 1440,200;
                      M0,220 C180,150 1160,240 1440,220" />
          </path>
      
          <!-- Wave Line 8 -->
          <path d="M0,230 C160,160 1180,230 1440,230" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="26s" repeatCount="indefinite"
              values="M0,230 C160,160 1180,230 1440,230;
                      M0,210 C180,140 1000,210 1440,210;
                      M0,230 C160,160 1180,230 1440,230" />
          </path>
      
          <!-- Wave Line 9 -->
          <path d="M0,240 C140,170 1200,220 1440,240" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="28s" repeatCount="indefinite"
              values="M0,240 C140,170 1200,220 1440,240;
                      M0,220 C160,150 1000,200 1440,220;
                      M0,240 C140,170 1200,220 1440,240" />
          </path>
      
          <!-- Wave Line 10 -->
          <path d="M0,250 C120,180 1220,210 1440,250" stroke="#ffffff44" stroke-width="1.5" fill="none" filter="url(#waveFocusBlur)">
            <animate attributeName="d" dur="30s" repeatCount="indefinite"
              values="M0,250 C120,180 1220,210 1440,250;
                      M0,230 C140,160 1020,190 1440,230;
                      M0,250 C120,180 1220,210 1440,250" />
          </path>
      
        </svg>
      </div>
      
      
      
      

  </section>

  <!-- Features Section -->
  <section class="features py-5 text-center" data-aos="fade-up">
    <div class="container">
      <h2 class="mb-4">Key Features</h2>
      <div class="row g-4">
        <!-- Feature 1: Sales Tracker -->
        <div class="col-md-4">
          <div class="card h-100 shadow border-0">
            <div class="card-body">
              <i class="fas fa-coins fa-3x text-primary mb-3"></i>
              <h5 class="card-title fw-bold">Sales Tracker</h5>
              <p class="card-text">Easily input product names, income amounts, and dates to monitor your sales performance in real time.</p>
            </div>
          </div>
        </div>

        <!-- Feature 2: Product Analytics -->
        <div class="col-md-4">
          <div class="card h-100 shadow border-0">
            <div class="card-body">
              <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
              <h5 class="card-title fw-bold">Product Performance</h5>
              <p class="card-text">Visual analytics to highlight trending and underperforming products based on income data.</p>
            </div>
          </div>
        </div>

        <!-- Feature 3: AI Insights -->
        <div class="col-md-4">
          <div class="card h-100 shadow border-0">
            <div class="card-body">
              <i class="fas fa-robot fa-3x text-info mb-3"></i>
              <h5 class="card-title fw-bold">AI-Powered Insights</h5>
              <p class="card-text">Smart suggestions and recommendations generated from your input data to support strategic decisions.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">&copy; 2025 Salesflow. All rights reserved.</p>
  </footer>

  <!-- Bootstrap Bundle JS (for dropdowns, collapses, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- AOS JS (scroll animations) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>

</body>
</html>
