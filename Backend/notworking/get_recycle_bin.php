<?php
header('Content-Type: application/json');
require_once '../Database/connection.php';

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid user_id.'
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id, table_name, record_id, deleted_at FROM recycle_bin WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];

while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode([
    'success' => true,
    'entries' => $entries
]);
?>
