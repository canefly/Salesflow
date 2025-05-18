<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

require_once '../Database/connection.php';  // your mysqli connection

$user_id = $_SESSION['user_id'];
$date    = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$start   = $date . ' 00:00:00';
$end     = $date . ' 23:59:59';

$sql = "
  SELECT 
    s.product_name, 
    s.quantity, 
    s.total_amount AS amount, 
    s.sale_date AS datetime, 
    COALESCE(c.category_name, 'Uncategorized') AS category_name
  FROM sales s
  LEFT JOIN categories c 
    ON s.category_id = c.id
  WHERE s.user_id = ? 
    AND s.sale_date BETWEEN ? AND ?
  ORDER BY s.sale_date ASC
";

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param('iss', $user_id, $start, $end);
  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  $total_income = 0.0;
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'product'  => $row['product_name'],
      'quantity' => (int)$row['quantity'],
      'amount'   => (float)$row['amount'],
      'datetime' => $row['datetime'],
      'category' => $row['category_name']
    ];
    $total_income += (float)$row['amount'];
  }

  echo json_encode([
    'total_income' => round($total_income, 2),
    'data'         => $data
  ]);
  $stmt->close();
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Query prepare failed']);
}
$conn->close();
