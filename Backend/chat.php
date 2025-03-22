<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// KoboldAI Endpoint
$apiUrl = 'https://th-dimensions-national-coat.trycloudflare.com/v1';

// Get user input
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

// Format prompt
$prompt = "Cane: $userMessage\nSeraphina: ";

// Build payload
$payload = [
    'prompt' => $prompt,
    'max_new_tokens' => 120,
    'temperature' => 1,
    'top_p' => 0.9,
    'stop' => ["\nCane:"]
];

// Send request
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode(['reply' => '⚠️ No response from AI (cURL error).']);
    exit;
}

// Decode
$result = json_decode($response, true);
$aiReply = $result['choices'][0]['text'] ?? '⚠️ No reply found.';

echo json_encode(['reply' => $aiReply]);
?>
