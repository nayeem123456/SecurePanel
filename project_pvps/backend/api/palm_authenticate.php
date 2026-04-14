<?php
// ================================================
// Palm Authentication API
// Verify user identity using palm matching
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
if (empty($data['phone_number']) && empty($data['user_id'])) {
    send_response(false, [], 'Missing phone_number or user_id', 400);
}

if (empty($data['palm_image_data'])) {
    send_response(false, [], 'Missing palm_image_data', 400);
}

$palmImageData = $data['palm_image_data'];
$authType = isset($data['auth_type']) ? $data['auth_type'] : 'login';

// Find user
if (isset($data['user_id'])) {
    $userId = (int)$data['user_id'];
    $query = "SELECT user_id, full_name, phone_number, account_id, palm_registered, palm_image_path 
              FROM users WHERE user_id = $userId";
} else {
    $phoneNumber = $conn->real_escape_string($data['phone_number']);
    $query = "SELECT user_id, full_name, phone_number, account_id, palm_registered, palm_image_path 
              FROM users WHERE phone_number = '$phoneNumber'";
}

$result = $conn->query($query);

if ($result->num_rows === 0) {
    send_response(false, [], 'User not found', 404);
}

$user = $result->fetch_assoc();
$userId = $user['user_id'];

// Check if palm is registered
if (!$user['palm_registered']) {
    send_response(false, [], 'Palm not registered for this user. Please register your palm first.', 400);
}

// Step 1: Analyze new palm scan
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

// Step 2: Get registered palm scan
$getRegisteredScan = "SELECT scan_id, image_path, biometric_signature, confidence_score 
                      FROM palm_scans 
                      WHERE user_id = $userId AND scan_type = 'registration' 
                      ORDER BY scan_timestamp DESC 
                      LIMIT 1";

$scanResult = $conn->query($getRegisteredScan);

if ($scanResult->num_rows === 0) {
    send_response(false, [], 'No registered palm found. Please register your palm first.', 404);
}

$registeredScan = $scanResult->fetch_assoc();
$referenceScanId = $registeredScan['scan_id'];

// Step 3: Load registered palm image
$registeredImagePath = __DIR__ . '/../../' . $registeredScan['image_path'];

if (!file_exists($registeredImagePath)) {
    send_response(false, [], 'Registered palm image not found. Please re-register your palm.', 404);
}

$registeredImageData = base64_encode(file_get_contents($registeredImagePath));

// Step 4: Compare palm images
$matchResult = comparePalmImages($registeredImageData, $palmImageData);

if (!$matchResult['success']) {
    send_response(false, [
        'match_result' => $matchResult
    ], 'Palm matching failed: ' . $matchResult['message'], 500);
}

// Step 5: Save authentication scan
$saveResult = savePalmImage($palmImageData, $userId, 'authentication');
$scanId = null;

if ($saveResult['success']) {
    $imagePath = $conn->real_escape_string($saveResult['path']);
    $imageHash = $conn->real_escape_string($saveResult['hash']);
    $biometricSig = $conn->real_escape_string($analysis['biometric_signature']);
    $confidence = $analysis['confidence'];
    
    $insertScan = "INSERT INTO palm_scans 
        (user_id, scan_type, image_path, image_hash, biometric_signature, confidence_score) 
        VALUES 
        ($userId, 'authentication', '$imagePath', '$imageHash', '$biometricSig', $confidence)";
    
    if ($conn->query($insertScan)) {
        $scanId = $conn->insert_id;
    }
}

// Step 6: Record match attempt
$matchScore = $matchResult['matchScore'];
$isMatch = $matchResult['isMatch'];
$matchTypeMap = [
    'login' => 'login',
    'transaction' => 'transaction',
    'verification' => 'verification'
];
$matchType = isset($matchTypeMap[$authType]) ? $matchTypeMap[$authType] : 'login';

if ($scanId) {
    $insertMatch = "INSERT INTO palm_matches 
        (user_id, scan_id, reference_scan_id, match_score, is_match, match_type) 
        VALUES 
        ($userId, $scanId, $referenceScanId, $matchScore, " . ($isMatch ? '1' : '0') . ", '$matchType')";
    
    $conn->query($insertMatch);
}

// Step 7: Update user's last scan time
$updateUser = "UPDATE users SET last_palm_scan = NOW() WHERE user_id = $userId";
$conn->query($updateUser);

// Response
if ($isMatch) {
    send_response(true, [
        'authenticated' => true,
        'user_id' => $userId,
        'full_name' => $user['full_name'],
        'phone_number' => $user['phone_number'],
        'account_id' => $user['account_id'],
        'match_score' => round($matchScore * 100, 2),
        'confidence' => round($analysis['confidence'] * 100, 2),
        'scan_id' => $scanId,
        'match_details' => [
            'vein_pattern_match' => isset($matchResult['matchDetails']['vein_pattern_similarity']) 
                ? $matchResult['matchDetails']['vein_pattern_similarity'] : 'N/A',
            'palm_lines_match' => isset($matchResult['matchDetails']['palm_lines_similarity']) 
                ? $matchResult['matchDetails']['palm_lines_similarity'] : 'N/A',
            'overall_similarity' => round($matchScore * 100, 2) . '%'
        ]
    ], 'Authentication successful - Palm verified', 200);
} else {
    send_response(false, [
        'authenticated' => false,
        'match_score' => round($matchScore * 100, 2),
        'required_score' => round(PALM_MATCH_THRESHOLD * 100, 2),
        'message' => 'Palm does not match registered palm'
    ], 'Authentication failed - Palm verification failed', 401);
}

?>
