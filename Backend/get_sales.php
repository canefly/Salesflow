<?php
// get_sales.php
header('Content-Type: application/json');
require_once '../Database/config.php'; // DB connection

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id     = intval($_GET['user_id'] ?? 0);
    $date        = $_GET['date'] ?? null;
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id.'
        ]);
        exit;
    }

    // Build dynamic query
    $sql = "SELECT * FROM sales WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";

    if (!empty($date)) {
        $sql .= " AND DATE(sale_date) = ?";
        $params[] = $date;
        $types .= "s";
    }

    if (!is_null($category_id)) {
        $sql .= " AND category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }

    $sql .= " ORDER BY sale_date DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $sales = [];

        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }

        $response['success'] = true;
        $response['sales'] = $sales;
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
