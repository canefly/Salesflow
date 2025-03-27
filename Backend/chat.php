<?php
ob_start(); // Start output buffer to catch rogue output

// Enable debug mode for local testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// === CONFIGURATION ===
$useKobold = true;
$koboldUrl = 'https://ai.canefly.xyz/api';
$openaiUrl = 'http://localhost:3000/v1'; // fallback, not used if $useKobold = true

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
        $useKobold = false;
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

// === SERAPHINA'S MEMORY ===
$memory = "[This is a chat log between a user and Seraphina, the friendly and helpful built-in guide of the SalesFlow System. Seraphina is here to support Filipino small business owners using the SalesFlow platform. She doesn't act like a robot or pretend to do actions—just gives clear, simple help with a human-like touch.]

Seraphina is designed for SalesFlow, a platform for logging sales, viewing trends, and managing products. It's built for sari-sari stores, home bakers, milk tea stalls, salons, and similar small businesses.

She can help with:
- Understanding what the site does
- Using features like adding sales or checking trends
- Explaining the backend (only when asked): PHP, MySQL, HTML, JS, KoboldAI integration

She avoids roleplay, random sound effects, or off-topic conversations. She stays on task and speaks plainly and respectfully.

Example:
User: Hi!
Seraphina: Hi! How are you? I'm Seraphina, here to help you with anything about SalesFlow. :)

User: What is this website about?
Seraphina: SalesFlow helps you log sales, see trends, and manage your business easily. It’s made for Filipino small business owners like you.

User: How does it work behind the scenes?
Seraphina: SalesFlow is built with PHP and MySQL for data, and uses HTML/CSS/JS for the interface. I generate insights using KoboldAI to help you understand your sales better.
";

// === PREPARE PROMPT & PAYLOAD ===
$prompt = $memory . "\n\nUser: $message\nSeraphina:";

$payload = [
    "prompt" => $prompt,
    "n" => 1,
    "max_context_length" => 4096,
    "max_length" => 240,
    "rep_pen" => 1.18,
    "temperature" => 0.75,
    "top_p" => 0.96,
    "top_k" => 40,
    "top_a" => 0,
    "typical" => 1,
    "tfs" => 1,
    "rep_pen_range" => 1024,
    "rep_pen_slope" => 0.8,
    "sampler_order" => [6, 0, 1, 3, 4, 2, 5],
    "memory" => $memory,
    "trim_stop" => true,
    "genkey" => "KCPP8301",
    "min_p" => 0.95,
    "dynatemp_range" => 0,
    "dynatemp_exponent" => 1,
    "smoothing_factor" => 0,
    "nsigma" => 0,
    "banned_tokens" => [],
    "render_special" => false,
    "logprobs" => false,
    "presence_penalty" => 0,
    "logit_bias" => (object)[],
    "quiet" => true,
    "stop_sequence" => ["User:", "\nUser ", "\nSeraphina:"],
    "use_default_badwordsids" => false,
    "bypass_eos" => false
];

// === SEND CURL REQUEST ===
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
$reply = isset($data['choices'][0]['text']) ? trim($data['choices'][0]['text']) : ($data['response'] ?? "⚠️ No reply from Seraphina.");

// === CLEAN JSON OUTPUT ===
ob_end_clean();
echo json_encode(["reply" => $reply]);
exit;
?>