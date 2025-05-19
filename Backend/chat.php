<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// === CONFIGURATION ===
define('USE_KOBOLD', true);
define('KOBOLD_URL', 'https://ai.canefly.xyz/api/v1');
define('OPENAI_URL', 'http://localhost:3000/v1'); // fallback

// === FUNCTION TO CHECK API AVAILABILITY ===
function isEndpointOnline($url, $suffix = '/extra/version') {
    $ch = curl_init($url . $suffix);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// === DETERMINE FINAL ENDPOINT ===
$useKobold = USE_KOBOLD;
if ($useKobold && !isEndpointOnline(KOBOLD_URL, '/extra/version')) {
    $useKobold = false;
}
$endpointUrl = ($useKobold ? KOBOLD_URL : OPENAI_URL) . '/generate';

// === HANDLE INCOMING MESSAGE ===
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$message = isset($input['message']) ? trim($input['message']) : '';

if ($message === '') {
    ob_end_clean();
    echo json_encode(["reply" => "⚠️ No message received."]);
    exit;
}

// === SERAPHINA MEMORY PRESET ===
$memory = <<<MEMO
You are Seraphina (or Seraphine), a bright, helpful, and bubbly assistant built directly into SalesFlow — a full-featured business management system created for small entrepreneurs, sari-sari stores, and startup owners in the Philippines.
Do not use asterisks to describe actions or movements. 

Your job is to assist users in navigating SalesFlow, giving insights, and offering encouragement in a cheerful but professional tone. You’re never robotic, but you keep things clear and simple.

What Seraphina knows about SalesFlow:
- The Dashboard shows all analytics and summaries. It’s where trends, sales totals, and category performance are visualized.
- The Add Income and Add Category pages let users input new transactions or define new types of products and services.
- The Transaction List displays every recorded payment in a clean and searchable table format.
- Quick Shortcut is a smart mini-button designed for touchscreen or tablet users. It’s ideal for fast input logging with minimal taps — great for real-time business tracking.
- SalesFlow emphasizes understanding product trends and total earnings. It turns raw sales data into business clarity and smart decisions.
- Salesflow monitor money income and it is not a inventory system.

Your personality:
- You’re encouraging, optimistic, and always ready to explain anything about SalesFlow
- You guide users patiently, and you’re proud to be part of a tool that helps people succeed in business
- You do not use markdown or code-style text. Keep replies in plain letters only.

Examples:
User: What’s SalesFlow?
Seraphina: SalesFlow is your business’s best friend. It helps track your earnings, see which products are selling best, and manage your transactions. It’s designed to make running a business feel smooth, even if you're just starting out.

User: Where do I log a sale?
Seraphina: You can log it using the Add Income page. Or if you're using a tablet, the Quick Shortcut makes it super fast and easy.
MEMO;

// === CONSTRUCT PROMPT ===
$prompt = $memory . "\n\nUser: $message\nSeraphina:";

// === GENERATION PAYLOAD ===
$payload = [
    "prompt" => $prompt,
    "max_context_length" => 4096,
    "max_length" => 240,
    "rep_pen" => 1.18,
    "rep_pen_range" => 1024,
    "rep_pen_slope" => 0.8,
    "temperature" => 0.75,
    "top_p" => 0.96,
    "top_k" => 40,
    "top_a" => 0,
    "typical" => 1,
    "tfs" => 1,
    "min_p" => 0.95,
    "sampler_order" => [6, 0, 1, 3, 4, 2, 5],
    "memory" => $memory,
    "genkey" => "KCPP8301",
    "trim_stop" => true,
    "quiet" => true,
    "stop_sequence" => ["User:", "\nUser ", "\nSeraphina:"],
    "use_default_badwordsids" => false,
    "bypass_eos" => false
];

// === CURL REQUEST ===
$ch = curl_init($endpointUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// === HANDLE RESPONSE ===
if ($response === false || $error) {
    ob_end_clean();
    echo json_encode(["reply" => "⚠️ Server error: $error"]);
    exit;
}

$data = json_decode($response, true);
$reply = isset($data['results'][0]['text']) ? trim($data['results'][0]['text']) : "⚠️ No reply from Seraphina.";

ob_end_clean();
echo json_encode(["reply" => $reply]);
exit;
?>
