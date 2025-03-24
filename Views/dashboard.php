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
    <title>Dashboard - Money Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(to right, #242124, #003366); color: white; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #1e1e1e; padding-top: 20px; }
        .sidebar a { padding: 15px; text-decoration: none; font-size: 18px; display: block; color: white; }
        .sidebar a:hover { background: #444; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card-custom { background: rgba(255, 255, 255, 0.1); border: none; padding: 20px; border-radius: 10px; text-align: center; color: white; }
        .table th, .table td { color: white !important; }
        .chart-label { color: white !important; font-weight: bold; }

        /* Chat Widget */
        #chat-container { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
        #chat-bubble { width: 50px; height: 50px; background: #007bff; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center; font-size: 24px; cursor: pointer; }
        #chat-window { width: 300px; height: 400px; background: white; color: black; border-radius: 10px;
            display: none; flex-direction: column; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.3); }
        #chat-header { background: #007bff; color: white; padding: 10px; display: flex; justify-content: space-between; }
        #chat-messages { flex: 1; padding: 10px; overflow-y: auto; }
        #chat-input-container { display: flex; padding: 10px; }
        #chat-input { flex: 1; padding: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="text-center">Money Manager</h3>
        <a href="#">Dashboard</a>
        <a href="income.html">Income</a>
        <a href="#">Product Trends</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
        <a href="#">Logout</a>
    </div>
    
    <div class="main-content">
        <h2>Dashboard Overview</h2>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card-custom"><h4>Total Income</h4><p>₱7,500.00</p></div>
            </div>
            <div class="col-md-6">
                <div class="card-custom"><h4>Trending Product</h4><p>Wireless Headphones</p></div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6"><canvas id="incomeChart"></canvas></div>
            <div class="col-md-6"><canvas id="productChart"></canvas></div>
        </div>
    </div>

    <!-- Chat Widget -->
    <div id="chat-container">
        <div id="chat-bubble" onclick="toggleChat()">💬</div>
        <div id="chat-window">
            <div id="chat-header">
                <span>Chat Support</span>
                <button onclick="toggleChat()">×</button>
            </div>
            <div id="chat-messages"></div>
            <div id="chat-input-container">
                <input type="text" id="chat-input" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>
    
    <script>
        function toggleChat() {
            var chatWindow = document.getElementById('chat-window');
            chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
        }
        function handleKeyPress(event) {
            if (event.key === 'Enter') sendMessage();
        }
        function sendMessage() {
            var input = document.getElementById('chat-input');
            if (input.value.trim() !== '') {
                var messages = document.getElementById('chat-messages');
                var newMessage = document.createElement('div');
                newMessage.textContent = input.value;
                messages.appendChild(newMessage);
                input.value = '';
                messages.scrollTop = messages.scrollHeight;
            }
        }

        var ctx1 = document.getElementById('incomeChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: { labels: ['Wireless Headphones', 'Smartwatches', 'Gaming Mouse', 'Mechanical Keyboards'],
                datasets: [{ data: [4000, 2000, 1000, 500], backgroundColor: ['#66ff66', '#4d94ff', '#ff9900', '#cccccc'] }] },
            options: { plugins: { legend: { labels: { color: 'white' } } } }
        });

        var ctx2 = document.getElementById('productChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: { labels: ['Wireless Headphones', 'Smartwatches', 'Gaming Mouse', 'Mechanical Keyboards'],
                datasets: [{ label: 'Sales', data: [120, 90, 75, 60], backgroundColor: ['#66ff66', '#4d94ff', '#ff9900', '#ff4d4d'] }] },
            options: { scales: { x: { ticks: { color: 'white' } }, y: { ticks: { color: 'white' } } } }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
