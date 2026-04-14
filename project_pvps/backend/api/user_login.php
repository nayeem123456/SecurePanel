<?php
// ================================================
// User Login API
// POST: phone_number, password
// Returns: success, biometric_template, user_data
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/db.php';
require_once 'vision_api.php';

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

// Optional: Hand detection validation
if (isset($data['palm_image_data']) && !empty($data['palm_image_data'])) {
    $visionResult = detectHand($data['palm_image_data']);
    
    if (!$visionResult['success']) {
        send_response(false, [], 'Hand detection failed: ' . $visionResult['message'], 500);
    }
    
    if (!$visionResult['handDetected']) {
        send_response(false, [
            'visionResult' => $visionResult
        ], 'No hand detected. Please place your palm in front of the camera.', 400);
    }
}

// Validate required fields
$required_fields = ['phone_number', 'password'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        send_response(false, [], "Missing required field: $field", 400);
    }
}
$phone_number = trim($data['phone_number']);
$password     = $data['password'];

// Prepare SQL (IMPORTANT FIX)
$stmt = $conn->prepare(
    "SELECT user_id, full_name, phone_number, account_id, password_hash, biometric_template, created_at
     FROM users
     WHERE phone_number = ?"
);

$stmt->bind_param("s", $phone_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, [], 'User not found. Please register first.', 404);
}

$user = $result->fetch_assoc();

// Verify password (this WILL work now)
if (!password_verify($password, $user['password_hash'])) {
    send_response(false, [], 'Incorrect password', 401);
}

// Success response
send_response(true, [
    'user' => [
        'user_id'      => $user['user_id'],
        'full_name'    => $user['full_name'],
        'phone_number' => $user['phone_number'],
        'account_id'   => $user['account_id'],
        'created_at'   => $user['created_at']
    ],
    'biometric_template' => $user['biometric_template']
], 'Login successful', 200);
?>
