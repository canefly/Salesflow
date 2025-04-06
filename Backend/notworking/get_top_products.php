<?php
// Set header to return JSON content
header('Content-Type: application/json');

// Include database connection (adjust the path if necessary)
require_once '../Database/connection.php';

// SQL query: group sales by product and count the number of sales, then get the top 5 products
$sql = "SELECT product, COUNT(*) AS total_sales 
        FROM sales 
        GROUP BY product 
        ORDER BY total_sales DESC 
        LIMIT 5";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
    exit;
}

$labels = [];
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['product'];
    $data[] = $row['total_sales'];
}

// Output the JSON result
echo json_encode([
    "labels" => $labels,
    "data" => $data
]);

// Close the connection
mysqli_close($conn);
?>