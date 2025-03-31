<?php
// Set header to return JSON content
header('Content-Type: application/json');

// Include database connection (adjust the path if necessary)
require_once '../Database/connection.php';

// SQL query: group sales by month and sum the income
$sql = "SELECT DATE_FORMAT(date, '%M') AS month, SUM(amount) AS total_income 
        FROM sales 
        GROUP BY MONTH(date) 
        ORDER BY MONTH(date) ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
    exit;
}

$labels = [];
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['month'];
    $data[] = $row['total_income'];
}

// Output the JSON result
echo json_encode([
    "labels" => $labels,
    "data" => $data
]);

// Close the connection
mysqli_close($conn);
?>
