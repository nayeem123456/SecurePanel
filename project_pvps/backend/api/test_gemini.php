<?php
// Test script to see what OpenRouter AI actually returns
require_once '../config/vision_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get image data from POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['palm_image_data'])) {
    echo json_encode(['error' => 'No image data provided']);
    exit();
}

$imageBase64 = $input['palm_image_data'];
$imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);

// Enhanced prompt that MUST return detected objects
$prompt = "Analyze this image and tell me EXACTLY what you see. You MUST respond in JSON format with these fields:

{
  \"detected_objects\": [\"list every object/person/thing you see\"],
  \"is_palm_image\": true or false,
  \"description\": \"detailed description of what's in the image\",
  \"confidence\": 0.0 to 1.0,
  \"why_not_palm\": \"if not a palm, explain what you see instead\"
}

Be very specific about what you detect. If you see a person, say 'person'. If you see a face, say 'face'. If you see a hand, say 'hand'. If you see a palm, say 'palm'. List EVERYTHING you can identify.

IMPORTANT: Respond ONLY with valid JSON format.";

// OpenRouter API request format
$requestData = [
    'model' => AI_MODEL,
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $prompt
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:image/jpeg;base64,' . $imageBase64
                    ]
                ]
            ]
        ]
    ],
    'temperature' => 0.1,
    'max_tokens' => 2048
];

$url = GEMINI_API_ENDPOINT;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . GEMINI_API_KEY,
    'HTTP-Referer: https://palm-vein-payment.local',
    'X-Title: Palm Vein Payment System'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Return everything for debugging
echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'raw_response' => json_decode($response, true),
    'parsed_text' => null
], JSON_PRETTY_PRINT);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        $analysisText = $result['choices'][0]['message']['content'];
        $analysis = json_decode($analysisText, true);
        
        echo "\n\n=== PARSED ANALYSIS ===\n";
        echo json_encode($analysis, JSON_PRETTY_PRINT);
    }
}
?>
