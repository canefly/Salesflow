<?php
header('Content-Type: application/json');
require_once '../Database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM recycle_bin WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'All recycle bin entries permanently deleted for this user.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to purge recycle bin.'
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
