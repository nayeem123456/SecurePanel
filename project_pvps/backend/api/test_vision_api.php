<?php
// ================================================
// Vision API Test Script
// Tests if Google Cloud Vision API is working
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/vision_config.php';
require_once 'vision_api.php';

// Test with a sample base64 image (1x1 transparent PNG)
$testImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

// Test 1: Check configuration
echo "=== VISION API TEST RESULTS ===\n\n";

echo "1. Configuration Check:\n";
echo "   API Key: " . (VISION_API_KEY ? substr(VISION_API_KEY, 0, 20) . "..." : "NOT SET") . "\n";
echo "   Endpoint: " . VISION_API_ENDPOINT . "\n";
echo "   Validation Enabled: " . (ENABLE_VISION_VALIDATION ? "YES" : "NO") . "\n";
echo "   Threshold: " . HAND_DETECTION_THRESHOLD . "\n\n";

// Test 2: Test API connectivity
echo "2. API Connectivity Test:\n";

if (!ENABLE_VISION_VALIDATION) {
    echo "   Status: SKIPPED (validation disabled)\n";
    echo "   To enable, set ENABLE_VISION_VALIDATION = true in vision_config.php\n\n";
} else {
    echo "   Testing with sample image...\n";
    
    $result = detectHand($testImage);
    
    echo "   API Call Success: " . ($result['success'] ? "YES" : "NO") . "\n";
    
    if ($result['success']) {
        echo "   Hand Detected: " . ($result['handDetected'] ? "YES" : "NO") . "\n";
        echo "   Confidence: " . round($result['confidence'] * 100) . "%\n";
        echo "   Message: " . $result['message'] . "\n";
        
        if (!empty($result['labels'])) {
            echo "   Detected Labels: " . count($result['labels']) . "\n";
            echo "   Top 3 Labels:\n";
            foreach (array_slice($result['labels'], 0, 3) as $label) {
                echo "      - " . $label['label'] . " (" . round($label['confidence'] * 100) . "%)\n";
            }
        }
    } else {
        echo "   Error: " . $result['message'] . "\n";
        echo "\n   TROUBLESHOOTING:\n";
        echo "   - Check your internet connection\n";
        echo "   - Verify API key is valid in Google Cloud Console\n";
        echo "   - Ensure Vision API is enabled in your GCP project\n";
        echo "   - Check API quota limits\n";
    }
    echo "\n";
}

// Test 3: Integration status
echo "3. Backend Integration Status:\n";

$integrationFiles = [
    'user_register.php' => file_exists(__DIR__ . '/user_register.php'),
    'user_login.php' => file_exists(__DIR__ . '/user_login.php'),
    'initiate_payment.php' => file_exists(__DIR__ . '/initiate_payment.php')
];

foreach ($integrationFiles as $file => $exists) {
    if ($exists) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        $integrated = strpos($content, 'vision_api.php') !== false;
        echo "   ✓ $file: " . ($integrated ? "INTEGRATED" : "NOT integrated") . "\n";
    } else {
        echo "   ✗ $file: NOT FOUND\n";
    }
}

echo "\n=== END OF TEST ===\n";

?>
