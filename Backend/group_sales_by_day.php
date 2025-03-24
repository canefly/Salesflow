<?php
// group_sales_by_day.php
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

    // Fetch sales grouped by date
    $sql = "
        SELECT 
            DATE(sale_date) AS sale_day,
            SUM(total_amount) AS day_total
        FROM sales
        WHERE user_id = ?
        GROUP BY sale_day
        ORDER BY sale_day DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];

    while ($row = $result->fetch_assoc()) {
        $sale_day = $row['sale_day'];
        $day_total = floatval($row['day_total']);

        // Get all entries for this day
        $entry_sql = "
            SELECT id, product_name, quantity, total_amount, notes, category_id, sale_date
            FROM sales
            WHERE user_id = ? AND DATE(sale_date) = ?
            ORDER BY sale_date DESC
        ";

        $entry_stmt = $conn->prepare($entry_sql);
        $entry_stmt->bind_param("is", $user_id, $sale_day);
        $entry_stmt->execute();
        $entry_result = $entry_stmt->get_result();

        $entries = [];
        while ($entry = $entry_result->fetch_assoc()) {
            $entries[] = $entry;
        }

        $groups[] = [
            'date' => $sale_day,
            'total' => $day_total,
            'entries' => $entries
        ];
    }

    $response['success'] = true;
    $response['groups'] = $groups;
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
