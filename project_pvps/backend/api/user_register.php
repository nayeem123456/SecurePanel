<?php
// ================================================
// User Registration API
// POST: full_name, phone_number, account_id, password, biometric_template
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';
require_once 'palm_recognition.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, [], 'Invalid request method', 405);
}

// Get POST data (support both JSON and form-data)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON parsing failed, use $_POST
if (!$data) {
    $data = $_POST;
}

// Optional: Palm detection validation with Gemini
if (isset($data['palm_image_data']) && !empty($data['palm_image_data'])) {
    $palmAnalysis = analyzePalmWithGemini($data['palm_image_data'], true);
    
    if (!$palmAnalysis['success']) {
        send_response(false, [
            'analysis' => $palmAnalysis
        ], 'Palm analysis failed: ' . $palmAnalysis['message'], 500);
    }
    
    if (!$palmAnalysis['isPalm']) {
        send_response(false, [
            'analysis' => $palmAnalysis,
            'confidence' => $palmAnalysis['confidence'],
            'required_confidence' => PALM_DETECTION_THRESHOLD
        ], $palmAnalysis['message'], 400);
    }
    
    // Store biometric signature from analysis
    if (isset($palmAnalysis['biometric_signature'])) {
        $data['biometric_template'] = $palmAnalysis['biometric_signature'];
    }
}

// Validate required fields
$required_fields = ['full_name', 'phone_number', 'account_id', 'password', 'biometric_template'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        send_response(false, [], "Missing required field: $field", 400);
    }
}

// Sanitize inputs
$full_name = sanitize_input($data['full_name']);
$phone_number = sanitize_input($data['phone_number']);
$account_id = sanitize_input($data['account_id']);
$password = $data['password'];
$biometric_template = sanitize_input($data['biometric_template']);

// Validate phone number format (10 digits)
if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
    send_response(false, [], 'Phone number must be 10 digits', 400);
}

// Check if phone number already exists
$check_phone = "SELECT user_id FROM users WHERE phone_number = '$phone_number'";
$result = $conn->query($check_phone);
if ($result->num_rows > 0) {
    send_response(false, [], 'Phone number already registered', 409);
}

// Check if account ID already exists
$check_account = "SELECT user_id FROM users WHERE account_id = '$account_id'";
$result = $conn->query($check_account);
if ($result->num_rows > 0) {
    send_response(false, [], 'Account ID already exists', 409);
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$sql = "INSERT INTO users (full_name, phone_number, account_id, password_hash, biometric_template) 
        VALUES ('$full_name', '$phone_number', '$account_id', '$password_hash', '$biometric_template')";

if ($conn->query($sql) === TRUE) {
    $user_id = $conn->insert_id;
    send_response(true, [
        'user_id' => $user_id,
        'phone_number' => $phone_number,
        'account_id' => $account_id
    ], 'User registered successfully', 201);
} else {
    send_response(false, [], 'Registration failed: ' . $conn->error, 500);
}
?>
