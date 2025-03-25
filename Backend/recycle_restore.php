<?php
header('Content-Type: application/json');
require_once '../Database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $recycle_id = intval($_POST['recycle_id'] ?? 0);

    if ($user_id <= 0 || $recycle_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user_id or recycle_id.']);
        exit;
    }

    // Fetch the recycle bin entry
    $stmt = $conn->prepare("SELECT table_name, record_id, backup_data FROM recycle_bin WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recycle_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Recycle bin entry not found.']);
        exit;
    }

    $entry = $result->fetch_assoc();
    $table = $entry['table_name'];
    $backup_data = json_decode($entry['backup_data'], true);

    if ($table === 'sales' && $backup_data) {
        // Restore sale
        $stmt = $conn->prepare("
            INSERT INTO sales (id, user_id, category_id, product_name, quantity, total_amount, sale_date, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiisidssss",
            $backup_data['id'],
            $backup_data['user_id'],
            $backup_data['category_id'],
            $backup_data['product_name'],
            $backup_data['quantity'],
            $backup_data['total_amount'],
            $backup_data['sale_date'],
            $backup_data['notes'],
            $backup_data['created_at'],
            $backup_data['updated_at']
        );

        if ($stmt->execute()) {
            // Remove from recycle bin after restore
            $del = $conn->prepare("DELETE FROM recycle_bin WHERE id = ?");
            $del->bind_param("i", $recycle_id);
            $del->execute();

            echo json_encode(['success' => true, 'message' => 'Sale successfully restored.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to restore sale.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported table or missing data.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
