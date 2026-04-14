<?php
// ================================================
// Get Transactions API
// GET/POST: merchant_id
// Returns: Last 10 transactions for merchant
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

// Support both GET and POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $merchant_id = isset($data['merchant_id']) ? sanitize_input($data['merchant_id']) : '';
} else {
    $merchant_id = isset($_GET['merchant_id']) ? sanitize_input($_GET['merchant_id']) : '';
}

// Validate merchant_id
if (empty($merchant_id)) {
    send_response(false, [], 'Merchant ID is required', 400);
}

// Verify merchant exists
$check_merchant = "SELECT merchant_id FROM merchants WHERE merchant_id = '$merchant_id'";
$result = $conn->query($check_merchant);
if ($result->num_rows === 0) {
    send_response(false, [], 'Invalid merchant ID', 404);
}

// Fetch last 10 transactions for this merchant
$sql = "SELECT transaction_id, merchant_id, user_phone, amount, status, created_at 
        FROM transactions 
        WHERE merchant_id = '$merchant_id' 
        ORDER BY created_at DESC 
        LIMIT 10";

$result = $conn->query($sql);

$transactions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'id' => $row['transaction_id'],
            'merchantId' => $row['merchant_id'],
            'userPhone' => $row['user_phone'],
            'amount' => floatval($row['amount']),
            'status' => $row['status'],
            'timestamp' => $row['created_at']
        ];
    }
}

send_response(true, [
    'transactions' => $transactions,
    'count' => count($transactions)
], 'Transactions retrieved successfully', 200);
?>
