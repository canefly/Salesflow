<?php
ob_start(); // Start output buffer to catch rogue output

// Enable debug mode for local testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// === CONFIGURATION ===
$useKobold = true; // Set false if you want to test OpenAI fallback instead
$koboldUrl = 'https://besides-wondering-anatomy-haiti.trycloudflare.com/api';
$openaiUrl = 'http://localhost:3000/v1';

// === DETERMINE ENDPOINT ===
function checkEndpoint($url, $suffix = '/extra/version') {
  $ch = curl_init($url . $suffix);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false
  ]);
  $res = curl_exec($ch);
  curl_close($ch);
  return json_decode($res, true);
}

if ($useKobold) {
  $ping = checkEndpoint($koboldUrl);
  if (!$ping || isset($ping['error'])) {
    $useKobold = false; // Fallback if unreachable
  }
}

$endpointUrl = $useKobold ? $koboldUrl . '/generate' : $openaiUrl . '/generate';

// === READ INPUT ===
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$message = isset($input['message']) ? trim($input['message']) : '';

if ($message === '') {
  ob_end_clean();
  echo json_encode(["reply" => "⚠️ No message received."]);
  exit;
}

// === PREPARE PROMPT & PAYLOAD ===
$prompt = "[Chat Log]\nUser: $message\nAI:";
$payload = [
  "prompt" => $prompt,
  "max_length" => 240,
  "temperature" => 0.8,
  "top_p" => 0.9,
  "stop_sequence" => ["User:", "AI:"],
  "n" => 1,
  "mode" => "chat"
];

// === CURL REQUEST ===
$ch = curl_init($endpointUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 20,
  CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response === false || $error) {
  ob_end_clean();
  echo json_encode(["reply" => "⚠️ Server error: $error"]);
  exit;
}

$data = json_decode($response, true);
$reply = isset($data['choices'][0]['text']) ? trim($data['choices'][0]['text']) : ($data['response'] ?? "⚠️ No reply from AI");

// === FINAL CLEAN JSON OUTPUT ===
ob_end_clean();
echo json_encode(["reply" => $reply]);
exit;
?>
