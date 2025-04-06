<?php
// get_summary.php
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

    // Get current month and week range
    $current_month = date('Y-m');
    $start_of_week = date('Y-m-d', strtotime('monday this week'));
    $today = date('Y-m-d');

    // 1. Monthly total sales
    $stmt1 = $conn->prepare("
        SELECT SUM(total_amount) AS total_month 
        FROM sales 
        WHERE user_id = ? AND DATE_FORMAT(sale_date, '%Y-%m') = ?
    ");
    $stmt1->bind_param("is", $user_id, $current_month);
    $stmt1->execute();
    $stmt1->bind_result($total_month);
    $stmt1->fetch();
    $stmt1->close();

    // 2. Weekly total sales
    $stmt2 = $conn->prepare("
        SELECT SUM(total_amount) AS total_week 
        FROM sales 
        WHERE user_id = ? AND DATE(sale_date) BETWEEN ? AND ?
    ");
    $stmt2->bind_param("iss", $user_id, $start_of_week, $today);
    $stmt2->execute();
    $stmt2->bind_result($total_week);
    $stmt2->fetch();
    $stmt2->close();

    // 3. Last 5 sales
    $stmt3 = $conn->prepare("
        SELECT id, product_name, total_amount, quantity, sale_date, notes, category_id 
        FROM sales 
        WHERE user_id = ? 
        ORDER BY sale_date DESC 
        LIMIT 5
    ");
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    $recent_sales = [];

    while ($row = $result3->fetch_assoc()) {
        $recent_sales[] = $row;
    }

    // Optional: Daily sales data for graph
    $stmt4 = $conn->prepare("
        SELECT DATE(sale_date) AS sale_day, SUM(total_amount) AS day_total 
        FROM sales 
        WHERE user_id = ? AND DATE_FORMAT(sale_date, '%Y-%m') = ? 
        GROUP BY sale_day
        ORDER BY sale_day ASC
    ");
    $stmt4->bind_param("is", $user_id, $current_month);
    $stmt4->execute();
    $result4 = $stmt4->get_result();
    $daily_totals = [];

    while ($row = $result4->fetch_assoc()) {
        $daily_totals[] = $row;
    }

    // Package response
    $response['success'] = true;
    $response['summary'] = [
        'total_month' => floatval($total_month),
        'total_week' => floatval($total_week),
        'recent_sales' => $recent_sales,
        'daily_totals' => $daily_totals
    ];
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
