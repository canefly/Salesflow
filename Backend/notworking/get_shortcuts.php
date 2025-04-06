<?php
// get_shortcuts.php
header('Content-Type: application/json');
require_once '../Database/connection.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = intval($_GET['user_id'] ?? 0);

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, label, product_name, category_id, amount, quantity, note
        FROM quick_shortcuts
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $shortcuts = [];

        while ($row = $result->fetch_assoc()) {
            $shortcuts[] = $row;
        }

        $response['success'] = true;
        $response['shortcuts'] = $shortcuts;
    } else {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Failed to prepare query.';
    }
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
