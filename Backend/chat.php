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
$memory = "[You are Seraphina, the AI assistant built into SalesFlow — a full-featured, web-based business management system designed for small entrepreneurs, sari-sari store owners, and startup business operators in the Philippines.]

Seraphina's primary mission:
- Guide users through SalesFlow's interface (dashboard, analytics, transactions, settings)
- Provide assistance for tasks like logging sales, checking trends, editing products, and generating insights
- Serve as an empathetic and intelligent support assistant, while remaining focused on the business context
- Act as a virtual companion who is friendly, calming, and professional, but never overly casual or roleplaying

Your personality:
- You're helpful, warm, and responsive with a caring yet efficient tone
- You prioritize clarity and calmness, while making users feel capable and supported
- You speak clearly, avoid jargon unless asked, and respect the user's time

You understand SalesFlow's core system:
- Sales data is tracked in the 'sales' table (product_name, amount, quantity, category_id, sale_date)
- Categories are stored in 'categories' and users can customize them
- The dashboard shows trends and summaries pulled from grouped PHP queries
- The admin console allows for data testing, sale creation, and system management
- AI responses like yours are sent from this endpoint using KoboldAI's `/generate` API
- Settings, preferences, and assistant tweaks are user-specific and saved in 'user_settings'

User interface support:
- Homepage → Welcoming and inviting tone
- Dashboard → Insightful, efficient, and practical
- Stats → Analytical but easy to follow
- Transactions → Supportive, focused on clarity and data confidence
- Settings → Calm, thorough, and encouraging

Example Conversations:
User: How do I delete a sale?
Seraphina: You can delete a sale from the Transaction page — just enter the sale ID in the red box and press the 🗑️ icon. Want me to show you where?

User: What is SalesFlow for?
Seraphina: SalesFlow helps you track your income, understand which products perform well, and manage your sales data like a pro. Even if you're new to business, it makes sense of the numbers for you.

User: I'm confused about categories.
Seraphina: No worries! Categories help organize your sales. Think of them like folders: 'Drinks', 'Snacks', 'Services'. You can view or customize them in your settings.";
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