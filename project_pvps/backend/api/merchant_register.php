<?php
// ================================================
// Merchant Registration API
// POST: shop_name, phone_number, password
// Returns: merchant_id
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
$required_fields = ['shop_name', 'phone_number', 'password'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        send_response(false, [], "Missing required field: $field", 400);
    }
}

// Sanitize inputs
$shop_name = sanitize_input($data['shop_name']);
$phone_number = sanitize_input($data['phone_number']);
$password = $data['password'];

// Validate phone number format (10 digits)
if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
    send_response(false, [], 'Phone number must be 10 digits', 400);
}

// Check if phone number already exists
$check_phone = "SELECT merchant_id FROM merchants WHERE phone_number = '$phone_number'";
$result = $conn->query($check_phone);
if ($result->num_rows > 0) {
    send_response(false, [], 'Phone number already registered', 409);
}

// Generate unique merchant ID (PVPS-MER-XXXXX)
function generate_merchant_id($conn) {
    do {
        $random_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
        $merchant_id = "PVPS-MER-" . $random_code;
        
        // Check if ID already exists
        $check = "SELECT merchant_id FROM merchants WHERE merchant_id = '$merchant_id'";
        $result = $conn->query($check);
    } while ($result->num_rows > 0);
    
    return $merchant_id;
}

$merchant_id = generate_merchant_id($conn);

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert merchant into database
$sql = "INSERT INTO merchants (merchant_id, shop_name, phone_number, password_hash) 
        VALUES ('$merchant_id', '$shop_name', '$phone_number', '$password_hash')";

if ($conn->query($sql) === TRUE) {
    send_response(true, [
        'merchant_id' => $merchant_id,
        'shop_name' => $shop_name,
        'phone_number' => $phone_number
    ], 'Merchant registered successfully', 201);
} else {
    send_response(false, [], 'Registration failed: ' . $conn->error, 500);
}
?>
