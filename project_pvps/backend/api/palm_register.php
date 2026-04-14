<?php
// ================================================
// Palm Registration API
// Register user palm with deep learning analysis
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/db.php';
require_once 'palm_recognition.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, [], 'Invalid request method', 405);
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// Validate required fields
if (empty($data['user_id'])) {
    send_response(false, [], 'Missing user_id', 400);
}

if (empty($data['palm_image_data'])) {
    send_response(false, [], 'Missing palm_image_data', 400);
}

$userId = (int)$data['user_id'];
$palmImageData = $data['palm_image_data'];

// Verify user exists
$checkUser = "SELECT user_id, full_name, palm_registered FROM users WHERE user_id = $userId";
$result = $conn->query($checkUser);

if ($result->num_rows === 0) {
    send_response(false, [], 'User not found', 404);
}

$user = $result->fetch_assoc();

// Step 1: Analyze palm with Gemini
$analysis = analyzePalmWithGemini($palmImageData, true);

if (!$analysis['success']) {
    send_response(false, [
        'analysis' => $analysis
    ], 'Palm analysis failed: ' . $analysis['message'], 500);
}

if (!$analysis['isPalm']) {
    send_response(false, [
        'analysis' => $analysis
    ], $analysis['message'], 400);
}

// Step 2: Save palm image
$saveResult = savePalmImage($palmImageData, $userId, 'registration');

if (!$saveResult['success']) {
    send_response(false, [], 'Failed to save palm image: ' . $saveResult['message'], 500);
}

// Step 3: Store palm scan in database
$imagePath = $conn->real_escape_string($saveResult['path']);
$imageHash = $conn->real_escape_string($saveResult['hash']);
$biometricSig = $conn->real_escape_string($analysis['biometric_signature']);
$confidence = $analysis['confidence'];

$insertScan = "INSERT INTO palm_scans 
    (user_id, scan_type, image_path, image_hash, biometric_signature, confidence_score) 
    VALUES 
    ($userId, 'registration', '$imagePath', '$imageHash', '$biometricSig', $confidence)";

if (!$conn->query($insertScan)) {
    send_response(false, [], 'Failed to store palm scan: ' . $conn->error, 500);
}

$scanId = $conn->insert_id;

// Step 4: Store analytics data
if (ENABLE_ANALYTICS && isset($analysis['analytics'])) {
    $analytics = $analysis['analytics'];
    
    $veinScore = isset($analytics['vein_pattern_score']) ? (float)$analytics['vein_pattern_score'] : null;
    $palmLinesScore = isset($analytics['palm_lines_score']) ? (float)$analytics['palm_lines_score'] : null;
    $skinScore = isset($analytics['skin_texture_score']) ? (float)$analytics['skin_texture_score'] : null;
    $fingerScore = isset($analytics['finger_geometry_score']) ? (float)$analytics['finger_geometry_score'] : null;
    $shapeScore = isset($analytics['palm_shape_score']) ? (float)$analytics['palm_shape_score'] : null;
    $qualityScore = isset($analytics['image_quality_score']) ? (float)$analytics['image_quality_score'] : null;
    
    $analyticsJson = $conn->real_escape_string(json_encode($analytics));
    
    $insertAnalytics = "INSERT INTO palm_analytics 
        (scan_id, vein_pattern_score, palm_lines_score, skin_texture_score, 
         finger_geometry_score, palm_shape_score, image_quality_score, 
         overall_confidence, analysis_data) 
        VALUES 
        ($scanId, " . 
        ($veinScore !== null ? $veinScore : 'NULL') . ", " .
        ($palmLinesScore !== null ? $palmLinesScore : 'NULL') . ", " .
        ($skinScore !== null ? $skinScore : 'NULL') . ", " .
        ($fingerScore !== null ? $fingerScore : 'NULL') . ", " .
        ($shapeScore !== null ? $shapeScore : 'NULL') . ", " .
        ($qualityScore !== null ? $qualityScore : 'NULL') . ", " .
        "$confidence, '$analyticsJson')";
    
    $conn->query($insertAnalytics);
}

// Step 5: Update user record
$updateUser = "UPDATE users 
    SET palm_image_path = '$imagePath', 
        palm_registered = TRUE,
        biometric_template = '$biometricSig',
        last_palm_scan = NOW()
    WHERE user_id = $userId";

if (!$conn->query($updateUser)) {
    send_response(false, [], 'Failed to update user: ' . $conn->error, 500);
}

// Success response
send_response(true, [
    'user_id' => $userId,
    'scan_id' => $scanId,
    'confidence' => $analysis['confidence'],
    'message' => 'Palm registered successfully',
    'analytics' => [
        'vein_pattern_detected' => isset($analytics['vein_pattern_detected']) ? $analytics['vein_pattern_detected'] : false,
        'palm_lines_detected' => isset($analytics['palm_lines_detected']) ? $analytics['palm_lines_detected'] : false,
        'finger_count' => isset($analytics['finger_count']) ? $analytics['finger_count'] : 0,
        'image_quality' => isset($analytics['image_quality']) ? $analytics['image_quality'] : 'unknown',
        'overall_confidence' => round($analysis['confidence'] * 100, 2) . '%'
    ]
], 'Palm registered successfully with ' . round($analysis['confidence'] * 100) . '% confidence', 201);

?>
