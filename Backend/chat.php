<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define the two API endpoints
$openaiApiUrl = 'https://th-dimensions-national-coat.trycloudflare.com/v1';
$koboldApiUrl = 'https://th-dimensions-national-coat.trycloudflare.com/api';

// Helper function: ping an endpoint by calling its extra/version endpoint.
function pingEndpoint($baseUrl, $suffix = '/extra/version') {
    $url = $baseUrl . $suffix;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
         CURLOPT_TIMEOUT => 10,
         CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false || empty($response)) {
         return ['error' => $error];
    }
    return json_decode($response, true);
}

// Try pinging the OpenAI Compatible API endpoint first.
$openaiConfig = pingEndpoint($openaiApiUrl);
if (!isset($openaiConfig['error'])) {
    $selectedEndpoint = $openaiApiUrl;
    $endpointType = 'OpenAI Compatible API';
} else {
    // If that fails, try the Kobold API endpoint.
    $koboldConfig = pingEndpoint($koboldApiUrl);
    if (!isset($koboldConfig['error'])) {
         $selectedEndpoint = $koboldApiUrl;
         $endpointType = 'Kobold API';
    } else {
         echo json_encode(['reply' => '⚠️ Error: Neither API endpoint is available.']);
         exit;
    }
}

// Log which endpoint is selected for debugging.
file_put_contents('selected_endpoint.log', "Selected Endpoint: " . $selectedEndpoint . " (" . $endpointType . ")\n", FILE_APPEND);

// Now process the user's message.
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($inputData['message']) ? trim($inputData['message']) : '';

if ($userMessage === '') {
    echo json_encode(['reply' => '⚠️ Error: No message provided.']);
    exit;
}

// Format the prompt as a chat log.
// (You may adjust character names as needed.)
$prompt = "[The following is an interesting chat message log between User and KoboldGPT.]\n\nUser: " . $userMessage . "\nKoboldGPT:";

// Build the payload. Note the inclusion of a "mode" field.
$payload = array(
    "n" => 1,
    "max_context_length" => 4096,
    "max_length" => 240,
    "rep_pen" => 1.07,
    "temperature" => 0.75,
    "top_p" => 0.92,
    "banned_tokens" => array(),
    "bypass_eos" => false,
    "dynatemp_exponent" => 1,
    "dynatemp_range" => 0,
    "genkey" => "KCPP1059",
    "logit_bias" => new stdClass(),
    "logprobs" => false,
    "memory" => "",
    "min_p" => 0,
    "nsigma" => 0,
    "presence_penalty" => 0,
    "prompt" => $prompt,
    "quiet" => true,
    "render_special" => false,
    "rep_pen_range" => 360,
    "rep_pen_slope" => 0.7,
    "sampler_order" => array(6, 0, 1, 3, 4, 2, 5),
    "smoothing_factor" => 0,
    "stop_sequence" => array("User:", "\nUser ", "\nKoboldGPT: "),
    "tfs" => 1,
    "top_a" => 0,
    "top_k" => 100,
    "trim_stop" => true,
    "typical" => 1,
    "use_default_badwordsids" => false,
    "mode" => "chat"
);

// Determine the generation endpoint.
// For the OpenAI-compatible API, we assume generation is at "/v1/generate".
// If using the Kobold API, adjust the suffix if necessary.
$generateSuffix = ($endpointType === 'OpenAI Compatible API') ? '/generate' : '/v1/generate';
$generateUrl = $selectedEndpoint . $generateSuffix;

// Initialize cURL for the generation request.
$ch = curl_init($generateUrl);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
));

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// Log the raw API response for debugging.
file_put_contents('api_response.log', $response . "\n", FILE_APPEND);

if ($response === false || empty($response)) {
    echo json_encode(['reply' => "⚠️ cURL Error: " . $curlError]);
    exit;
}

// Decode the response.
$result = json_decode($response, true);

// Extract the generated text.
// We expect the text to be in choices[0]['text'] per the OpenAI-compatible format.
if (isset($result['choices'][0]['text'])) {
    $aiReply = trim($result['choices'][0]['text']);
} else {
    $aiReply = "⚠️ No reply found from API.";
}

echo json_encode(['reply' => $aiReply]);
?>
