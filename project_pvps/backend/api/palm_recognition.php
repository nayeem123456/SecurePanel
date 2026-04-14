<?php
// ================================================
// Advanced Palm Recognition API - Gemini 2.5 Flash Lite
// Deep Learning Palm Vein Authentication System
// ================================================

require_once '../config/vision_config.php';

/**
 * Analyze palm image using Gemini 2.5 Flash Lite
 * Performs deep learning analysis with strict validation
 * 
 * @param string $imageBase64 Base64-encoded image data
 * @param bool $deepAnalysis Enable comprehensive deep learning analysis
 * @return array Analysis results with confidence scores
 */
function analyzePalmWithGemini($imageBase64, $deepAnalysis = true) {
    // STRICT VALIDATION - NO BYPASS ALLOWED
    // This function ALWAYS validates with Gemini AI
    
    try {
        // Remove data URL prefix if present
        if (strpos($imageBase64, 'data:image') === 0) {
            $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
        }

        // Validate image is not empty
        if (empty($imageBase64)) {
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => 'No image data provided',
                'analytics' => []
            ];
        }

        // Validate image size
        $imageSize = strlen(base64_decode($imageBase64));
        if ($imageSize > MAX_IMAGE_SIZE) {
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => 'Image size exceeds maximum allowed (' . (MAX_IMAGE_SIZE / 1024 / 1024) . 'MB)',
                'analytics' => []
            ];
        }

        if ($imageSize < 1000) { // Less than 1KB is suspicious
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => 'Image file is too small or corrupted',
                'analytics' => []
            ];
        }

        // Build OpenRouter API request (OpenAI-compatible format)
        $prompt = PALM_ANALYSIS_PROMPT;
        
        if ($deepAnalysis && ENABLE_DEEP_ANALYSIS) {
            $prompt .= "\n\nProvide detailed analysis including:\n";
            foreach (PALM_ANALYSIS_FEATURES as $feature) {
                $prompt .= "- " . str_replace('_', ' ', ucfirst($feature)) . "\n";
            }
        }

        // OpenRouter uses OpenAI-compatible format with messages
        $requestData = [
            'model' => AI_MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt . "\n\nIMPORTANT: Respond ONLY with valid JSON format."
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

        // Make API request to OpenRouter
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
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => 'Network error: ' . $curlError,
                'analytics' => []
            ];
        }

        // Check HTTP status
        if ($httpCode !== 200) {
            $errorMsg = 'Gemini API error (HTTP ' . $httpCode . ')';
            if ($response) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error']['message'])) {
                    $errorMsg .= ': ' . $errorData['error']['message'];
                }
            }
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => $errorMsg,
                'analytics' => []
            ];
        }

        // Parse response
        $result = json_decode($response, true);

        // OpenRouter uses OpenAI-compatible format: choices[0].message.content
        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'isPalm' => false,
                'confidence' => 0.0,
                'message' => 'Invalid OpenRouter API response',
                'analytics' => [],
                'raw_response' => $result
            ];
        }

        // Extract analysis from OpenRouter response
        $analysisText = $result['choices'][0]['message']['content'];
        $analysis = json_decode($analysisText, true);

        if (!$analysis) {
            // Try to extract JSON from markdown code blocks
            if (preg_match('/```json\s*(.*?)\s*```/s', $analysisText, $matches)) {
                $analysis = json_decode($matches[1], true);
            } else {
                return [
                    'success' => false,
                    'isPalm' => false,
                    'confidence' => 0.0,
                    'message' => 'Failed to parse AI analysis',
                    'analytics' => [],
                    'raw_text' => $analysisText
                ];
            }
        }

        // Validate hand detection (updated from palm to hand)
        $isValidHand = false;
        $confidence = 0.0;

        // Check if analysis confirms valid hand
        if (isset($analysis['is_valid_hand'])) {
            $isValidHand = (bool)$analysis['is_valid_hand'];
        } elseif (isset($analysis['valid_hand'])) {
            $isValidHand = (bool)$analysis['valid_hand'];
        } elseif (isset($analysis['hand_detected'])) {
            $isValidHand = (bool)$analysis['hand_detected'];
        } elseif (isset($analysis['is_valid_palm'])) {
            // Fallback for backward compatibility
            $isValidHand = (bool)$analysis['is_valid_palm'];
        }

        // Get confidence score
        if (isset($analysis['confidence'])) {
            $confidence = (float)$analysis['confidence'];
        } elseif (isset($analysis['confidence_score'])) {
            $confidence = (float)$analysis['confidence_score'];
        } elseif (isset($analysis['overall_confidence'])) {
            $confidence = (float)$analysis['overall_confidence'];
        }

        // Convert percentage to decimal if needed
        if ($confidence > 1.0) {
            $confidence = $confidence / 100.0;
        }

        // Apply threshold
        if ($confidence < PALM_DETECTION_THRESHOLD) {
            $isValidHand = false;
        }

        // Enhanced object detection with intelligent suggestions
        $detectedObjects = isset($analysis['detected_objects']) ? $analysis['detected_objects'] : [];
        $suggestion = isset($analysis['suggestion']) ? $analysis['suggestion'] : '';
        
        // Check for rejected keywords and provide specific feedback
        if (!empty($detectedObjects)) {
            foreach ($detectedObjects as $object) {
                $objectLower = strtolower($object);
                foreach (REJECTED_KEYWORDS as $rejected) {
                    if (strpos($objectLower, $rejected) !== false) {
                        // Generate intelligent suggestion based on detected object
                        $intelligentSuggestion = generateIntelligentSuggestion($object, $detectedObjects);
                        
                        return [
                            'success' => true,
                            'isPalm' => false,
                            'isValidHand' => false,
                            'confidence' => 0.0,
                            'message' => 'Invalid image: ' . ucfirst($object) . ' detected.',
                            'analytics' => $analysis,
                            'detected_objects' => $detectedObjects,
                            'rejection_reason' => 'Contains non-hand content: ' . $object,
                            'suggestion' => $intelligentSuggestion
                        ];
                    }
                }
            }
        }

        // Build detailed message based on analysis
        $detectedObjectsStr = '';
        if (!empty($detectedObjects)) {
            $detectedObjectsStr = ' Detected objects: ' . implode(', ', $detectedObjects) . '.';
        }
        
        $message = '';
        if ($isValidHand) {
            $message = 'Valid hand detected with ' . round($confidence * 100) . '% confidence.' . $detectedObjectsStr;
        } else {
            // Provide specific feedback based on what was detected
            if (!empty($detectedObjects)) {
                $message = 'No valid hand detected.' . $detectedObjectsStr;
                if (!empty($suggestion)) {
                    $message .= ' ' . $suggestion;
                } else {
                    $message .= ' Please show only your hand with fingers clearly visible.';
                }
            } else {
                $message = 'No valid hand detected. Please ensure your hand is clearly visible with fingers spread open in good lighting.';
            }
            
            // Add rejection reason if available
            if (isset($analysis['rejection_reason']) && !empty($analysis['rejection_reason'])) {
                $message .= ' Reason: ' . $analysis['rejection_reason'];
            }
        }

        return [
            'success' => true,
            'isPalm' => $isValidHand,  // Keep for backward compatibility
            'isValidHand' => $isValidHand,
            'confidence' => $confidence,
            'message' => $message,
            'analytics' => $analysis,
            'detected_objects' => $detectedObjects,
            'suggestion' => $suggestion,
            'biometric_signature' => isset($analysis['biometric_signature']) 
                ? $analysis['biometric_signature'] 
                : generateBiometricSignature($analysis)
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'isPalm' => false,
            'confidence' => 0.0,
            'message' => 'Exception: ' . $e->getMessage(),
            'analytics' => []
        ];
    }
}

/**
 * Compare two palm images for matching
 * 
 * @param string $palmImage1 First palm image (base64)
 * @param string $palmImage2 Second palm image (base64)
 * @return array Match results with score
 */
function comparePalmImages($palmImage1, $palmImage2) {
    // STRICT MATCHING - NO BYPASS ALLOWED
    // This function ALWAYS compares palms with Gemini AI
    
    try {
        // Validate both images are provided
        if (empty($palmImage1) || empty($palmImage2)) {
            return [
                'success' => false,
                'isMatch' => false,
                'matchScore' => 0.0,
                'message' => 'Both palm images are required for comparison'
            ];
        }

        // Remove data URL prefixes
        $palmImage1 = preg_replace('/^data:image\/\w+;base64,/', '', $palmImage1);
        $palmImage2 = preg_replace('/^data:image\/\w+;base64,/', '', $palmImage2);

        // Build comparison request for OpenRouter
        $requestData = [
            'model' => AI_MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => PALM_MATCHING_PROMPT . "\n\nIMPORTANT: Respond ONLY with valid JSON format."
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $palmImage1
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Compare with this second palm image:'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $palmImage2
                            ]
                        ]
                    ]
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 1024
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
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'isMatch' => false,
                'matchScore' => 0.0,
                'message' => 'API error during palm matching'
            ];
        }

        $result = json_decode($response, true);
        $analysisText = $result['choices'][0]['message']['content'] ?? '';
        $matchData = json_decode($analysisText, true);

        if (!$matchData) {
            if (preg_match('/```json\s*(.*?)\s*```/s', $analysisText, $matches)) {
                $matchData = json_decode($matches[1], true);
            }
        }

        $matchScore = 0.0;
        if (isset($matchData['match_score'])) {
            $matchScore = (float)$matchData['match_score'];
            if ($matchScore > 1.0) {
                $matchScore = $matchScore / 100.0;
            }
        }

        $isMatch = $matchScore >= PALM_MATCH_THRESHOLD;

        return [
            'success' => true,
            'isMatch' => $isMatch,
            'matchScore' => $matchScore,
            'message' => $isMatch 
                ? 'Palm match confirmed (' . round($matchScore * 100) . '% similarity)'
                : 'Palm does not match (' . round($matchScore * 100) . '% similarity)',
            'matchDetails' => $matchData
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'isMatch' => false,
            'matchScore' => 0.0,
            'message' => 'Exception during matching: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate biometric signature from analysis data
 * 
 * @param array $analysis Analysis data from Gemini
 * @return string Unique biometric signature
 */
function generateBiometricSignature($analysis) {
    $features = [];
    
    // Extract key features for hand biometrics
    $featureKeys = [
        'vein_pattern', 'palm_lines', 'skin_texture',
        'finger_geometry', 'palm_shape', 'palm_width',
        'palm_height', 'finger_count', 'dominant_lines',
        'finger_analysis', 'hand_geometry', 'palm_patterns'
    ];
    
    foreach ($featureKeys as $key) {
        if (isset($analysis[$key])) {
            $features[$key] = $analysis[$key];
        }
    }
    
    // Create hash-based signature
    $signature = hash('sha256', json_encode($features) . time());
    
    return $signature;
}

/**
 * Generate intelligent suggestion based on detected objects
 * 
 * @param string $primaryObject The main detected object
 * @param array $allObjects All detected objects
 * @return string Helpful suggestion for the user
 */
function generateIntelligentSuggestion($primaryObject, $allObjects = []) {
    $objectLower = strtolower($primaryObject);
    
    // Category-based suggestions
    if (strpos($objectLower, 'face') !== false || strpos($objectLower, 'person') !== false || strpos($objectLower, 'people') !== false) {
        return "Please show only your hand, not your face or body. Position your hand in front of the camera with fingers spread open.";
    }
    
    if (strpos($objectLower, 'animal') !== false || strpos($objectLower, 'cat') !== false || strpos($objectLower, 'dog') !== false || strpos($objectLower, 'bird') !== false) {
        return "An animal was detected. Please remove any pets from the frame and show only your hand.";
    }
    
    if (strpos($objectLower, 'vehicle') !== false || strpos($objectLower, 'car') !== false || strpos($objectLower, 'bike') !== false) {
        return "A vehicle was detected in the image. Please ensure only your hand is visible in the camera frame.";
    }
    
    if (strpos($objectLower, 'building') !== false || strpos($objectLower, 'landscape') !== false || strpos($objectLower, 'outdoor') !== false) {
        return "Background objects detected. Please use a plain background and show only your hand with fingers clearly visible.";
    }
    
    if (strpos($objectLower, 'food') !== false || strpos($objectLower, 'drink') !== false) {
        return "Food or drink detected. Please remove these items and show only your hand.";
    }
    
    if (strpos($objectLower, 'text') !== false || strpos($objectLower, 'document') !== false || strpos($objectLower, 'paper') !== false) {
        return "Text or document detected. Please show your hand instead of any papers or documents.";
    }
    
    if (strpos($objectLower, 'screen') !== false || strpos($objectLower, 'monitor') !== false || strpos($objectLower, 'phone') !== false || strpos($objectLower, 'computer') !== false) {
        return "Electronic device detected. Please show your hand directly to the camera, not a screen or device.";
    }
    
    if (strpos($objectLower, 'furniture') !== false || strpos($objectLower, 'table') !== false || strpos($objectLower, 'chair') !== false) {
        return "Furniture detected in the frame. Please position your hand closer to the camera with a plain background.";
    }
    
    // Generic suggestion for other objects
    $objectsStr = implode(', ', $allObjects);
    return "Detected: {$objectsStr}. Please show only your hand with fingers clearly spread open against a plain background.";
}

/**
 * Save palm image to storage
 * 
 * @param string $imageBase64 Base64 image data
 * @param int $userId User ID
 * @param string $scanType Type of scan (registration/authentication/transaction)
 * @return array File path and hash
 */
function savePalmImage($imageBase64, $userId, $scanType = 'registration') {
    if (!STORE_ORIGINAL_IMAGES) {
        return [
            'success' => false,
            'message' => 'Image storage disabled'
        ];
    }

    try {
        // Create directory if it doesn't exist
        $baseDir = __DIR__ . '/' . PALM_IMAGE_DIRECTORY;
        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // Create user subdirectory
        $userDir = $baseDir . 'user_' . $userId . '/';
        if (!file_exists($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // Remove data URL prefix
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
        $imageData = base64_decode($imageData);

        // Generate filename
        $timestamp = time();
        $hash = hash('sha256', $imageData);
        $filename = $scanType . '_' . $timestamp . '_' . substr($hash, 0, 8) . '.jpg';
        $filepath = $userDir . $filename;

        // Save image
        file_put_contents($filepath, $imageData);

        // Return relative path
        $relativePath = 'palm_images/user_' . $userId . '/' . $filename;

        return [
            'success' => true,
            'path' => $relativePath,
            'hash' => $hash,
            'filename' => $filename
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to save image: ' . $e->getMessage()
        ];
    }
}

// Handle direct API requests
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    // Handle POST request for palm analysis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action']) && $input['action'] === 'analyze') {
            if (!isset($input['palm_image_data'])) {
                echo json_encode([
                    'success' => false,
                    'isPalm' => false,
                    'confidence' => 0.0,
                    'message' => 'No palm image data provided'
                ]);
                exit();
            }
            
            // Analyze palm with Gemini
            $result = analyzePalmWithGemini($input['palm_image_data'], true);
            echo json_encode($result);
            exit();
        }
    }
    
    // Default GET response - API status
    echo json_encode([
        'status' => 'Palm Recognition API Ready',
        'model' => 'NVIDIA Nemotron Nano 12B 2 VL (OpenRouter)',
        'provider' => 'OpenRouter',
        'config' => [
            'endpoint' => GEMINI_API_ENDPOINT,
            'model_id' => AI_MODEL,
            'palm_threshold' => PALM_DETECTION_THRESHOLD,
            'match_threshold' => PALM_MATCH_THRESHOLD,
            'deep_analysis' => ENABLE_DEEP_ANALYSIS,
            'palm_matching' => ENABLE_PALM_MATCHING,
            'analytics' => ENABLE_ANALYTICS
        ],
        'features' => PALM_ANALYSIS_FEATURES,
        'message' => 'Send POST request with action=analyze and palm_image_data to analyze palm'
    ], JSON_PRETTY_PRINT);
}

?>
