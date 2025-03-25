<?php
header('Content-Type: application/json');
require_once '../Database/connection.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $sale_id = intval($_POST['sale_id'] ?? 0);

    if ($user_id <= 0 || $sale_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id or sale_id.'
        ]);
        exit;
    }

    // Check if the sale exists and belongs to the user
    $check = $conn->prepare("SELECT id FROM sales WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $sale_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Sale not found or does not belong to this user.'
        ]);
        exit;
    }

        // Fetch full sale data first
    $getSale = $conn->prepare("SELECT * FROM sales WHERE id = ? AND user_id = ?");
    $getSale->bind_param("ii", $sale_id, $user_id);
    $getSale->execute();
    $result = $getSale->get_result();
    $sale_data = $result->fetch_assoc();
    $backup_json = json_encode($sale_data);

    // Insert into recycle bin with full backup
    $insert = $conn->prepare("INSERT INTO recycle_bin (user_id, table_name, record_id, backup_data) VALUES (?, 'sales', ?, ?)");
    $insert->bind_param("iis", $user_id, $sale_id, $backup_json);

    // Delete from sales
    $delete = $conn->prepare("DELETE FROM sales WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $sale_id, $user_id);

    if ($delete->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Sale ID $sale_id moved to recycle bin."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Deletion from sales table failed.'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
