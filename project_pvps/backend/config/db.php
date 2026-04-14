<?php
// ================================================
// Database Configuration
// Palm Vein Payment System - Backend
// ================================================

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pvps_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Return JSON error for API compatibility
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => 'Unable to connect to the database server'
    ]);
    exit();
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    return $conn->real_escape_string($data);
}


// Function to send JSON response
function send_response($success, $data = [], $message = '', $http_code = 200) {
    header('Content-Type: application/json');
    http_response_code($http_code);
    
    $response = ['success' => $success];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit();
}
?>
