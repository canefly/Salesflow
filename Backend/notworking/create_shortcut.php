<?php
// create_shortcut.php
header('Content-Type: application/json');
require_once '../Database/connection.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = intval($_POST['user_id'] ?? 0);
    $label        = trim($_POST['label'] ?? '');
    $product_name = trim($_POST['product_name'] ?? '');
    $category_id  = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $amount       = floatval($_POST['amount'] ?? 0);
    $quantity     = intval($_POST['quantity'] ?? 1);
    $note         = trim($_POST['note'] ?? '');

    if ($user_id <= 0 || empty($label) || empty($product_name) || $amount <= 0) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Missing or invalid fields.';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO quick_shortcuts 
        (user_id, label, product_name, category_id, amount, quantity, note) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param("issidis", $user_id, $label, $product_name, $category_id, $amount, $quantity, $note);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Shortcut created successfully.';
            $response['shortcut_id'] = $stmt->insert_id;
        } else {
            http_response_code(500);
            $response['success'] = false;
            $response['message'] = 'Failed to insert shortcut.';
        }
        $stmt->close();
    } else {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Failed to prepare statement.';
    }
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
