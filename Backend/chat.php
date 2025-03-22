<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define endpoints
$koboldBaseUrl = 'https://th-dimensions-national-coat.trycloudflare.com/api';
$openaiBaseUrl   = 'https://th-dimensions-national-coat.trycloudflare.com/v1';

// Function to ping an endpoint (using /extra/version)
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

// Ping the Kobold API first
$koboldConfig = pingEndpoint($koboldBaseUrl);
if (!isset($koboldConfig['error']) && isset($koboldConfig['result'])) {
    $selectedEndpoint = $koboldBaseUrl;
    $endpointType = 'Kobold API';
    $generateSuffix = '/generate'; // Kobold generation endpoint
} else {
    // Fallback to OpenAI Compatible API
    $openaiConfig = pingEndpoint($openaiBaseUrl, '/extra/version');
    if (!isset($openaiConfig['error'])) {
         $selectedEndpoint = $openaiBaseUrl;
         $endpointType = 'OpenAI Compatible API';
         $generateSuffix = '/generate'; // adjust if needed
    } else {
         echo json_encode(['reply' => '⚠️ Error: Neither API endpoint is available.']);
         exit;
    }
}

// Log selected endpoint for debugging
file_put_contents('selected_endpoint.log', "Selected Endpoint: $selectedEndpoint ($endpointType)\n", FILE_APPEND);

// Process user message
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($inputData['message']) ? trim($inputData['message']) : '';

if ($userMessage === '') {
    echo json_encode(['reply' => '⚠️ Error: No message provided.']);
    exit;
}

// Format the prompt as a chat log
$prompt = "[The following is an interesting chat message log between User and KoboldGPT.]\n\nUser: " 
          . $userMessage . "\nKoboldGPT:";

// Build the payload; note the "mode" field forces chat behavior.
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

// Log the input payload for debugging (simulate terminal log)
file_put_contents('kobo_processing.log', "Input Payload: " . json_encode($payload) . "\n", FILE_APPEND);

// Construct the full generation URL.
$generateUrl = $selectedEndpoint . $generateSuffix;

// Initialize cURL for generation request.
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

// Log the raw API response
file_put_contents('api_response.log', "Raw API Response: " . $response . "\n", FILE_APPEND);

if ($response === false || empty($response)) {
    file_put_contents('kobo_processing.log', "cURL Error: $curlError\n", FILE_APPEND);
    echo json_encode(['reply' => "⚠️ cURL Error: " . $curlError]);
    exit;
}

// Decode the API response.
$result = json_decode($response, true);

// Log the usage metrics if available.
if (isset($result['usage'])) {
    $usage = $result['usage'];
    $promptTokens = isset($usage['prompt_tokens']) ? $usage['prompt_tokens'] : 'N/A';
    $completionTokens = isset($usage['completion_tokens']) ? $usage['completion_tokens'] : 'N/A';
    $totalTokens = isset($usage['total_tokens']) ? $usage['total_tokens'] : 'N/A';
    $logLine = "Processing Prompt: $promptTokens tokens, Generating: $completionTokens tokens, Total: $totalTokens tokens\n";
    file_put_contents('kobo_processing.log', $logLine, FILE_APPEND);
}

// Extract the generated text.
if (isset($result['choices'][0]['text'])) {
    $aiReply = trim($result['choices'][0]['text']);
} else {
    $aiReply = "⚠️ No reply found from API.";
}

// Log the output.
file_put_contents('kobo_processing.log', "Output: " . $aiReply . "\n", FILE_APPEND);

// Return the reply as JSON.
echo json_encode(['reply' => $aiReply]);
?>
