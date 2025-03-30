<?php
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === CONFIGURATION ===
$useKobold = true;
$koboldUrl = 'https://ai.canefly.xyz/api';
$openaiUrl = 'http://localhost:3000/v1';

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

if ($useKobold && (!checkEndpoint($koboldUrl))) {
    $useKobold = false;
}
$endpointUrl = $useKobold ? $koboldUrl . '/generate' : $openaiUrl . '/generate';

// === READ INCOMING DATA ===
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';

if ($userMessage === '') {
    ob_end_clean();
    echo json_encode(["reply" => "⚠️ No message received."]);
    exit;
}

// === DEFINE MEMORY — permanent system persona context ===
$memory = <<<EOT
[This is a chat log between a user and Seraphina, the friendly and helpful built-in guide of the SalesFlow System. She's smart, warm, and knows the ins and outs of the platform.]

Seraphina helps Filipino small business owners manage their stores better using SalesFlow. She's good at:
- Logging sales
- Explaining sales trends
- Teaching how the backend works: PHP, MySQL, HTML, JS, AI

Avoids: roleplay, fantasy, or unrelated convos. She's always focused and helpful.

Example:
User: Hi!
Seraphina: Hello there! I'm Seraphina, your guide to mastering SalesFlow. 😊
EOT;

// === BUILD FULL PROMPT SEPARATELY ===
$fullPrompt = "User: {$userMessage}\nSeraphina:";

// === FINAL PAYLOAD ===
$payload = [
    "memory" => $memory,
    "prompt" => $fullPrompt,
    "n" => 1,
    "max_context_length" => 4096,
    "max_length" => 240,
    "rep_pen" => 1.18,
    "temperature" => 0.75,
    "top_p" => 0.96,
    "top_k" => 40,
    "typical" => 1,
    "sampler_order" => [6, 0, 1, 3, 4, 2, 5],
    "trim_stop" => true,
    "stop_sequence" => ["User:", "\nUser ", "\nSeraphina:"]
];

// === SEND REQUEST ===
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

$reply = "⚠️ No reply from Seraphina.";
if (!$error && $response) {
    $data = json_decode($response, true);
    $reply = isset($data['choices'][0]['text']) ? trim($data['choices'][0]['text']) : ($data['response'] ?? $reply);
}

ob_end_clean();
echo json_encode(["reply" => $reply]);
exit;
