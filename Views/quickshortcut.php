<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Salesflow — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
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

    .shortcut-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
      max-width: 800px;
      margin-top: 2rem;
      gap: 1rem;
    }

    .shortcut-button {
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #007bff;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-weight: 500;
      padding: 1rem;
    }

    .shortcut-button:hover {
      background-color: #0056b3;
    }

    .shortcut-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .shortcut-modal-content {
      display: flex;
      flex-direction: column;
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      width: 300px;
      position: relative;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .shortcut-modal-content .close-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      cursor: pointer;
      font-size: 1.5rem;
      color: #333;
    }

    .contextMenu {
      display: none;
      position: absolute;
      z-index: 1000;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .contextMenu a {
      display: block;
      padding: 8px 12px;
      color: #333;
      text-decoration: none;
    }

    .contextMenu a:hover {
      background-color: #f1f1f1;
    }

    .being-dragged {
      opacity: 0.4;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: var(--sidebar-collapsed-width);
      }

      .main-content {
        margin-left: var(--sidebar-collapsed-width);
        padding: 20px;
      }

      .shortcut-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .shortcut-grid {
        grid-template-columns: repeat(1, 1fr);
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
  <div class="wrapper">
    <?php include '../include/chat.html'; ?>
    <?php include '../include/sidenav.php'; ?>
    <main class="main-content">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-weight: 600;">Quick Shortcuts</h2>
        <div>
          <button id="exitReorganizeBtn" class="btn btn-dark me-2" style="display: none !important;">Return</button>
          <button id="addShortcutBtn" class="btn btn-primary">➕ Add Shortcut</button>
        </div>
      </div>
      <p style="color: #6c757d;">Speed up your sales process by logging frequent products in a single click.</p>

      <div class="shortcut-grid">
        <div class="shortcut-button">
          <div style="text-align: center;">
            <div style="font-weight: 600;">Okinawa Milk Tea</div>
            <div style="font-size: 0.9rem;">₱89 — 2 pcs</div>
          </div>
        </div>
        <div class="shortcut-button">
          <div style="text-align: center;">
            <div style="font-weight: 600;">Tuna Bun</div>
            <div style="font-size: 0.9rem;">₱35 — 1 pc</div>
          </div>
        </div>
        <div class="shortcut-button">
          <div style="text-align: center;">
            <div style="font-weight: 600;">Fries w/ Dip</div>
            <div style="font-size: 0.9rem;">₱49 — 1 pc</div>
          </div>
        </div>
      </div>

      <div id="shortcutModal" class="shortcut-modal" style="display: none;">
        <div class="shortcut-modal-content">
          <span class="close-btn" id="closeShortcutModal">&times;</span>
          <h5>Add/Edit Shortcut</h5>
          <label for="productName">Product Name</label>
          <input type="text" id="productName" class="form-control" />
          <label for="category">Category</label>
          <input type="text" id="category" class="form-control" />
          <label for="subcategory">Subcategory</label>
          <input type="text" id="subcategory" class="form-control" />
          <label for="color">Color</label>
          <input type="color" id="color" class="form-control" />
          <label for="productAmount">Product Amount</label>
          <input type="number" id="productAmount" class="form-control" />
          <label for="quantity">Quantity (e.g. 2 pcs)</label>
          <input type="text" id="quantity" class="form-control" />
          <button id="saveShortcutBtn" class="btn btn-success" style="margin-top: 1rem;">Save</button>
        </div>
      </div>

      <div id="deleteConfirmationModal" class="shortcut-modal" style="display: none;">
        <div class="shortcut-modal-content">
          <h5>Delete Confirmation</h5>
          <p>Are you sure you want to delete this shortcut?</p>
          <div style="display: flex; justify-content: center; gap: 1rem;">
            <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
            <button id="cancelDeleteBtn" class="btn btn-secondary">Cancel</button>
          </div>
        </div>
      </div>

      <div id="contextMenu" class="contextMenu">
        <a href="#" id="editShortcut">Edit</a>
        <a href="#" id="deleteShortcut">Delete</a>
        <a href="#" id="reorganizeShortcut">Reorganize</a>
      </div>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
  <script>
    let sortableInstance = Sortable.create(document.querySelector('.shortcut-grid'), {
      animation: 150,
      ghostClass: 'being-dragged',
      disabled: true,
      onEnd: function (evt) {
        console.log("Shortcut moved from index", evt.oldIndex, "to", evt.newIndex);
      }
    });

    $(document).ready(function() {
      $('#addShortcutBtn').click(function() {
        $('#shortcutModal').fadeIn(100);
      });

      $('#saveShortcutBtn').click(function() {
        // Logic to save shortcut
        $('#shortcutModal').fadeOut(100);
      });

      $('#closeShortcutModal').click(function() {
        $('#shortcutModal').fadeOut(100);
      });

      $('.shortcut-button').contextmenu(function(e) {
        e.preventDefault();
        $('#contextMenu').css({ top: e.pageY, left: e.pageX }).fadeIn(100);
      });

      $('#editShortcut').click(function() {
        $('#contextMenu').fadeOut(100);
        $('#shortcutModal').fadeIn(100);
      });

      $('#deleteShortcut').click(function() {
        $('#contextMenu').fadeOut(100);
        $('#deleteConfirmationModal').fadeIn(100);
      });

      $('#confirmDeleteBtn').click(function() {
        // Logic to delete shortcut
        $('#deleteConfirmationModal').fadeOut(100);
      });

      $('#cancelDeleteBtn').click(function() {
        $('#deleteConfirmationModal').fadeOut(100);
      });

      $(document).click(function() {
        $('#contextMenu').fadeOut(100);
      });

      $('#reorganizeShortcut').click(function () {
        $('#contextMenu').fadeOut(100);
        sortableInstance.option("disabled", false);
        $('#exitReorganizeBtn').fadeIn(100);
      });

      $('#exitReorganizeBtn').click(function () {
        sortableInstance.option("disabled", true);
        $(this).fadeOut(100);
      });
    });
  </script>
</body>
</html>