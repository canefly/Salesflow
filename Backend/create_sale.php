<?php
// create_sale.php
header('Content-Type: application/json');
require_once '../Database/config.php'; // your DB connection

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $user_id       = intval($_POST['user_id'] ?? 0);
    $category_id   = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $product_name  = trim($_POST['product_name'] ?? '');
    $quantity      = intval($_POST['quantity'] ?? 1);
    $total_amount  = floatval($_POST['total_amount'] ?? 0.00);
    $sale_date     = $_POST['sale_date'] ?? date('Y-m-d H:i:s');
    $notes         = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($user_id <= 0 || empty($product_name) || $total_amount <= 0) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Missing or invalid required fields.';
        echo json_encode($response);
        exit;
    }

    // Prepare statement
    $stmt = $conn->prepare("
        INSERT INTO sales (user_id, category_id, product_name, quantity, total_amount, sale_date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "iisidss",
            $user_id,
            $category_id,
            $product_name,
            $quantity,
            $total_amount,
            $sale_date,
            $notes
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Sale recorded successfully.';
            $response['sale_id'] = $stmt->insert_id;
        } else {
            http_response_code(500);
            $response['success'] = false;
            $response['message'] = 'Failed to insert sale: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Statement preparation failed.';
    }
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
