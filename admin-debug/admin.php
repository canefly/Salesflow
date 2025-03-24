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
    <h2>📦 Sales Testing</h2>
    <button onclick="runTest('create_sale')">Create Sale</button>
    <button onclick="runTest('get_sales')">Get All Sales</button>
    <button onclick="runTest('group_sales')">Grouped Sales by Day</button>
    <button onclick="runTest('delete_sale')">Delete Sale (ID=1)</button>
  </div>

  <div class="section">
    <h2>📂 Category Management</h2>
    <button onclick="runTest('create_category')">Create Category</button>
    <button onclick="runTest('get_categories')">Get Categories</button>
    <div class="placeholder">📌 Subcategory (Level 1) UI Placeholder – Coming Soon!</div>
  </div>

  <div class="section">
    <h2>⭐ Shortcut System</h2>
    <button onclick="runTest('create_shortcut')">Create Shortcut</button>
    <button onclick="runTest('get_shortcuts')">Get Shortcuts</button>
  </div>

  <div class="section">
    <h2>📊 Dashboard Data</h2>
    <button onclick="runTest('get_summary')">Get Summary (Totals + Graph)</button>
  </div>

  <div class="section">
    <h2>🤖 AI Handler Simulator</h2>
    <button onclick="runTest('ai_home')">Home AI Insight</button>
    <button onclick="runTest('ai_motivation')">Motivational AI Prompt</button>
    <button onclick="runTest('ai_chat')">Chat AI Simulation</button>
    <div class="placeholder">🧠 Dynamic API Key / Persona Manager Integration – Soon!</div>
  </div>

  <div class="response-box" id="result">Awaiting test run...</div>

  <script>
    function runTest(type) {
      const result = document.getElementById('result');
      result.textContent = '⏳ Running ' + type + '...';

      // Simulated testing map (you'll replace with real fetch calls)
      setTimeout(() => {
        result.textContent = JSON.stringify({
          test: type,
          status: 'success',
          message: `This is a simulated response for "${type}".`
        }, null, 2);
      }, 700);
    }
  </script>
</body>
</html>
