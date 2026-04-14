<?php
// ================================================
// Google Cloud Vision API Handler
// Detects hand/palm presence in images
// ================================================

require_once '../config/vision_config.php';

/**
 * Detect if a hand/palm is present in the given image
 * 
 * @param string $imageBase64 Base64-encoded image data (with or without data URL prefix)
 * @return array ['success' => bool, 'handDetected' => bool, 'confidence' => float, 'message' => string]
 */
function detectHand($imageBase64) {
    // Check if validation is enabled
    if (!ENABLE_VISION_VALIDATION) {
        return [
            'success' => true,
            'handDetected' => true,
            'confidence' => 1.0,
            'message' => 'Vision API validation disabled (testing mode)',
            'labels' => []
        ];
    }

    try {
        // Remove data URL prefix if present (data:image/png;base64,)
        if (strpos($imageBase64, 'data:image') === 0) {
            $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
        }

        // Prepare Vision API request
        $requestData = [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageBase64
                    ],
                    'features' => [
                        [
                            'type' => 'LABEL_DETECTION',
                            'maxResults' => 20
                        ],
                        [
                            'type' => 'OBJECT_LOCALIZATION',
                            'maxResults' => 10
                        ]
                    ]
                ]
            ]
        ];

        // Make API request using cURL
        $url = VISION_API_ENDPOINT . '?key=' . VISION_API_KEY;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            return [
                'success' => false,
                'handDetected' => false,
                'confidence' => 0.0,
                'message' => 'Network error: ' . $curlError,
                'labels' => []
            ];
        }

        // Check HTTP status
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'handDetected' => false,
                'confidence' => 0.0,
                'message' => 'Vision API error (HTTP ' . $httpCode . ')',
                'labels' => []
            ];
        }

        // Parse response
        $result = json_decode($response, true);

        if (!$result || !isset($result['responses'][0])) {
            return [
                'success' => false,
                'handDetected' => false,
                'confidence' => 0.0,
                'message' => 'Invalid API response',
                'labels' => []
            ];
        }

        $apiResponse = $result['responses'][0];

        // Check for API errors
        if (isset($apiResponse['error'])) {
            return [
                'success' => false,
                'handDetected' => false,
                'confidence' => 0.0,
                'message' => 'API error: ' . $apiResponse['error']['message'],
                'labels' => []
            ];
        }

        // Extract labels
        $detectedLabels = [];
        $maxConfidence = 0.0;
        $handDetected = false;

        // Check label annotations
        if (isset($apiResponse['labelAnnotations'])) {
            foreach ($apiResponse['labelAnnotations'] as $label) {
                $labelName = strtolower($label['description']);
                $confidence = $label['score'];
                
                $detectedLabels[] = [
                    'label' => $labelName,
                    'confidence' => $confidence
                ];

                // Check if label matches hand-related keywords
                foreach (HAND_LABELS as $handLabel) {
                    if (strpos($labelName, strtolower($handLabel)) !== false) {
                        $handDetected = true;
                        $maxConfidence = max($maxConfidence, $confidence);
                    }
                }
            }
        }

        // Check object localization
        if (isset($apiResponse['localizedObjectAnnotations'])) {
            foreach ($apiResponse['localizedObjectAnnotations'] as $object) {
                $objectName = strtolower($object['name']);
                $confidence = $object['score'];

                $detectedLabels[] = [
                    'label' => $objectName . ' (object)',
                    'confidence' => $confidence
                ];

                // Check if object matches hand-related keywords
                foreach (HAND_LABELS as $handLabel) {
                    if (strpos($objectName, strtolower($handLabel)) !== false) {
                        $handDetected = true;
                        $maxConfidence = max($maxConfidence, $confidence);
                    }
                }
            }
        }

        // Apply confidence threshold
        if ($handDetected && $maxConfidence < HAND_DETECTION_THRESHOLD) {
            $handDetected = false;
        }

        return [
            'success' => true,
            'handDetected' => $handDetected,
            'confidence' => $maxConfidence,
            'message' => $handDetected 
                ? 'Hand detected with ' . round($maxConfidence * 100) . '% confidence'
                : 'No hand detected in image',
            'labels' => $detectedLabels
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'handDetected' => false,
            'confidence' => 0.0,
            'message' => 'Exception: ' . $e->getMessage(),
            'labels' => []
        ];
    }
}

// If this file is accessed directly, run a test
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    
    echo json_encode([
        'status' => 'Vision API Handler Ready',
        'config' => [
            'endpoint' => VISION_API_ENDPOINT,
            'threshold' => HAND_DETECTION_THRESHOLD,
            'enabled' => ENABLE_VISION_VALIDATION,
            'hand_labels' => HAND_LABELS
        ],
        'message' => 'Send POST request with palm_image_data to test detection'
    ], JSON_PRETTY_PRINT);
}

?>
