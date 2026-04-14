<?php
// ================================================
// Merchant Login API
// POST: merchant_id, password
// Returns: merchant profile
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

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
if (empty($data['merchant_id']) || empty($data['password'])) {
    send_response(false, [], 'Merchant ID and password are required', 400);
}

// Sanitize inputs
$merchant_id = sanitize_input($data['merchant_id']);
$password = $data['password'];

// Fetch merchant from database
$sql = "SELECT merchant_id, shop_name, phone_number, password_hash, created_at 
        FROM merchants 
        WHERE merchant_id = '$merchant_id'";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    send_response(false, [], 'Merchant ID not found. Please register first.', 404);
}

$merchant = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $merchant['password_hash'])) {
    send_response(false, [], 'Incorrect password', 401);
}

// Return merchant profile (excluding password hash)
send_response(true, [
    'merchant' => [
        'merchant_id' => $merchant['merchant_id'],
        'shop_name' => $merchant['shop_name'],
        'phone_number' => $merchant['phone_number'],
        'created_at' => $merchant['created_at']
    ]
], 'Login successful', 200);
?>
