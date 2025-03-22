<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set your KoboldAI API endpoint (update as needed)
$apiUrl = 'https://th-dimensions-national-coat.trycloudflare.com/v1';

// Read incoming JSON from the frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

// Format the prompt (adjust as needed)
$prompt = "Cane: $userMessage\nSeraphina: ";

// Build the payload for KoboldAI
$payload = [
    'prompt' => $prompt,
    'max_new_tokens' => 120,
    'temperature' => 1,
    'top_p' => 0.9,
    'stop' => ["\nCane:"]
];

// Initialize cURL and set options
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification for testing (not recommended in production)
    CURLOPT_TIMEOUT => 30,            // Timeout after 30 seconds
]);

// Execute the request
$response = curl_exec($ch);

// If the response is false, log and report the error
if ($response === false) {
    $error = curl_error($ch);
    file_put_contents('kobo_debug.log', "cURL Error: " . $error . "\n", FILE_APPEND);
    echo json_encode(['reply' => '⚠️ cURL Error: ' . $error]);
    curl_close($ch);
    exit;
}

// Close cURL
curl_close($ch);

// Log the raw API response for debugging
file_put_contents('kobo_debug.log', "Response: " . $response . "\n", FILE_APPEND);

// Decode the response
$result = json_decode($response, true);

// Try to extract the AI reply from known structures
$aiReply = $result['choices'][0]['text'] 
           ?? $result['text'] 
           ?? '⚠️ No reply found from API.';

// Log the parsed reply
file_put_contents('kobo_debug.log', "Parsed reply: " . $aiReply . "\n", FILE_APPEND);

// Return the reply to the frontend
echo json_encode(['reply' => $aiReply]);
?>
