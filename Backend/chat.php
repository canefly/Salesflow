<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ New KoboldAI endpoint (v1!)
$apiUrl = 'https://th-dimensions-national-coat.trycloudflare.com/v1';

// 🌐 Grab the user message from JS
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

// 📦 Prepare the payload based on KoboldAPI v1
$payload = [
    'prompt' => $userMessage,
    'max_length' => 120,
    'temperature' => 0.7,
    'top_p' => 0.9,
    'stop_sequence' => ["\n"]
];

// 🌍 cURL request
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(['reply' => '⚠️ Error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

// 🐾 Try multiple known structures
file_put_contents('kobo_debug.json', $response); // log for dev

$result = json_decode($response, true);
$aiReply = $result['results'][0]['text']
        ?? $result['text']
        ?? '⚠️ No response from AI.';

echo json_encode(['reply' => $aiReply]);
?>
