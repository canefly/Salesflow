<?php
// create_category.php
header('Content-Type: application/json');
require_once '../Database/connection.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = intval($_POST['user_id'] ?? 0);
    $category_name = trim($_POST['category_name'] ?? '');
    $parent_id     = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if ($user_id <= 0 || empty($category_name)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Missing or invalid fields.';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO categories (user_id, category_name, parent_id) VALUES (?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $category_name, $parent_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Category added successfully.';
            $response['category_id'] = $stmt->insert_id;
        } else {
            http_response_code(500);
            $response['success'] = false;
            $response['message'] = 'Failed to insert category.';
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
