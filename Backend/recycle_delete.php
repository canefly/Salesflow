<?php
header('Content-Type: application/json');
require_once '../Database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $recycle_id = intval($_POST['recycle_id'] ?? 0);

    if ($user_id <= 0 || $recycle_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id or recycle_id.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM recycle_bin WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recycle_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Recycle bin entry ID $recycle_id permanently deleted."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete recycle bin entry.'
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
