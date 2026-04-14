<?php
// ================================================
// Initiate Payment API
// POST: merchant_id, user_phone, amount
// Returns: transaction_id, status
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
        ], 'No hand detected. Customer must place palm in front of camera.', 400);
    }
}

// Validate required fields
$required_fields = ['merchant_id', 'user_phone', 'amount'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        send_response(false, [], "Missing required field: $field", 400);
    }
}

// Sanitize inputs
$merchant_id = sanitize_input($data['merchant_id']);
$user_phone = sanitize_input($data['user_phone']);
$amount = floatval($data['amount']);

// Validate amount
if ($amount <= 0) {
    send_response(false, [], 'Amount must be greater than 0', 400);
}

// Verify merchant exists
$check_merchant = "SELECT merchant_id FROM merchants WHERE merchant_id = '$merchant_id'";
$result = $conn->query($check_merchant);
if ($result->num_rows === 0) {
    send_response(false, [], 'Invalid merchant ID', 404);
}

// ==================== STRICT MOBILE NUMBER VALIDATION ====================
// Verify that the mobile number is registered in the system
// This is the PRIMARY IDENTIFIER for the account
$check_user = "SELECT user_id, full_name, phone_number FROM users WHERE phone_number = '$user_phone'";
$result = $conn->query($check_user);

if ($result->num_rows === 0) {
    // Mobile number is NOT registered - BLOCK the payment
    send_response(false, [], 'This mobile number is not registered. Please register the number first to proceed with payment.', 403);
}

// Mobile number is registered - Extract user details for transaction
$user_data = $result->fetch_assoc();
$user_id = $user_data['user_id'];
$user_name = $user_data['full_name'];

// Generate unique transaction ID
function generate_transaction_id($conn) {
    do {
        $random_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 9));
        $transaction_id = "TXN" . $random_code;
        
        // Check if ID already exists
        $check = "SELECT transaction_id FROM transactions WHERE transaction_id = '$transaction_id'";
        $result = $conn->query($check);
    } while ($result->num_rows > 0);
    
    return $transaction_id;
}

$transaction_id = generate_transaction_id($conn);

// Insert transaction (status = APPROVED for simulation)
// In real system, status would be PENDING until biometric verification
$sql = "INSERT INTO transactions (transaction_id, merchant_id, user_phone, amount, status) 
        VALUES ('$transaction_id', '$merchant_id', '$user_phone', $amount, 'APPROVED')";

if ($conn->query($sql) === TRUE) {
    send_response(true, [
        'transaction_id' => $transaction_id,
        'amount' => number_format($amount, 2, '.', ''),
        'status' => 'APPROVED',
        'timestamp' => date('Y-m-d H:i:s')
    ], 'Payment initiated successfully', 201);
} else {
    send_response(false, [], 'Payment initiation failed: ' . $conn->error, 500);
}
?>
