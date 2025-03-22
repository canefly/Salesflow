<?php
// chat.php - Talks to KoboldAI server

header('Content-Type: application/json');

// KoboldAI API endpoint (local or server-based)
$apiUrl = 'https://cio-motherboard-uses-convinced.trycloudflare.com/api'; // Change this if hosted elsewhere

// Get user message from frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

// Format payload for KoboldAI
$payload = json_encode([
    'prompt' => $userMessage,
    'max_length' => 100,
    'temperature' => 0.7,
    'top_p' => 0.9,
    'stop_sequence' => ["\n"]
]);

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => $payload
]);

// Execute
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Handle response
if ($response === false) {
    echo json_encode(['reply' => "⚠️ Error: $error"]);
    exit;
}

$result = json_decode($response, true);
$aiReply = $result['results'][0]['text'] ?? '⚠️ No response from AI.';

echo json_encode(['reply' => $aiReply]);
?>
