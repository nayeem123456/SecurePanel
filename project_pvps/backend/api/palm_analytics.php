<?php
// ================================================
// Palm Analytics API
// Get detailed analytics for user's palm scans
// ================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_response(false, [], 'Invalid request method', 405);
}

// Get user ID from query parameter
if (empty($_GET['user_id'])) {
    send_response(false, [], 'Missing user_id parameter', 400);
}

$userId = (int)$_GET['user_id'];

// Verify user exists
$checkUser = "SELECT user_id, full_name, phone_number, palm_registered, last_palm_scan 
              FROM users WHERE user_id = $userId";
$result = $conn->query($checkUser);

if ($result->num_rows === 0) {
    send_response(false, [], 'User not found', 404);
}

$user = $result->fetch_assoc();

// Get all palm scans
$getScans = "SELECT 
    ps.scan_id,
    ps.scan_type,
    ps.confidence_score,
    ps.scan_timestamp,
    pa.vein_pattern_score,
    pa.palm_lines_score,
    pa.skin_texture_score,
    pa.finger_geometry_score,
    pa.palm_shape_score,
    pa.image_quality_score,
    pa.overall_confidence,
    pa.analysis_data
FROM palm_scans ps
LEFT JOIN palm_analytics pa ON ps.scan_id = pa.scan_id
WHERE ps.user_id = $userId
ORDER BY ps.scan_timestamp DESC";

$scansResult = $conn->query($getScans);
$scans = [];

while ($scan = $scansResult->fetch_assoc()) {
    $scans[] = [
        'scan_id' => $scan['scan_id'],
        'scan_type' => $scan['scan_type'],
        'confidence_score' => (float)$scan['confidence_score'],
        'scan_timestamp' => $scan['scan_timestamp'],
        'analytics' => [
            'vein_pattern_score' => $scan['vein_pattern_score'] ? (float)$scan['vein_pattern_score'] : null,
            'palm_lines_score' => $scan['palm_lines_score'] ? (float)$scan['palm_lines_score'] : null,
            'skin_texture_score' => $scan['skin_texture_score'] ? (float)$scan['skin_texture_score'] : null,
            'finger_geometry_score' => $scan['finger_geometry_score'] ? (float)$scan['finger_geometry_score'] : null,
            'palm_shape_score' => $scan['palm_shape_score'] ? (float)$scan['palm_shape_score'] : null,
            'image_quality_score' => $scan['image_quality_score'] ? (float)$scan['image_quality_score'] : null,
            'overall_confidence' => $scan['overall_confidence'] ? (float)$scan['overall_confidence'] : null
        ],
        'detailed_analysis' => $scan['analysis_data'] ? json_decode($scan['analysis_data'], true) : null
    ];
}

// Get match history
$getMatches = "SELECT 
    pm.match_id,
    pm.match_score,
    pm.is_match,
    pm.match_type,
    pm.match_timestamp,
    ps.scan_type,
    ps.confidence_score
FROM palm_matches pm
JOIN palm_scans ps ON pm.scan_id = ps.scan_id
WHERE pm.user_id = $userId
ORDER BY pm.match_timestamp DESC
LIMIT 50";

$matchesResult = $conn->query($getMatches);
$matches = [];

while ($match = $matchesResult->fetch_assoc()) {
    $matches[] = [
        'match_id' => $match['match_id'],
        'match_score' => (float)$match['match_score'],
        'is_match' => (bool)$match['is_match'],
        'match_type' => $match['match_type'],
        'match_timestamp' => $match['match_timestamp'],
        'scan_confidence' => (float)$match['confidence_score']
    ];
}

// Calculate statistics
$totalScans = count($scans);
$totalMatches = count($matches);
$successfulMatches = 0;
$failedMatches = 0;

foreach ($matches as $match) {
    if ($match['is_match']) {
        $successfulMatches++;
    } else {
        $failedMatches++;
    }
}

// Calculate average scores
$avgConfidence = 0;
$avgVeinScore = 0;
$avgPalmLinesScore = 0;
$avgSkinScore = 0;
$avgFingerScore = 0;
$avgShapeScore = 0;
$avgQualityScore = 0;

$validScans = 0;

foreach ($scans as $scan) {
    if ($scan['confidence_score'] > 0) {
        $avgConfidence += $scan['confidence_score'];
        $validScans++;
    }
    
    if ($scan['analytics']['vein_pattern_score']) {
        $avgVeinScore += $scan['analytics']['vein_pattern_score'];
    }
    if ($scan['analytics']['palm_lines_score']) {
        $avgPalmLinesScore += $scan['analytics']['palm_lines_score'];
    }
    if ($scan['analytics']['skin_texture_score']) {
        $avgSkinScore += $scan['analytics']['skin_texture_score'];
    }
    if ($scan['analytics']['finger_geometry_score']) {
        $avgFingerScore += $scan['analytics']['finger_geometry_score'];
    }
    if ($scan['analytics']['palm_shape_score']) {
        $avgShapeScore += $scan['analytics']['palm_shape_score'];
    }
    if ($scan['analytics']['image_quality_score']) {
        $avgQualityScore += $scan['analytics']['image_quality_score'];
    }
}

if ($validScans > 0) {
    $avgConfidence = $avgConfidence / $validScans;
    $avgVeinScore = $avgVeinScore / $validScans;
    $avgPalmLinesScore = $avgPalmLinesScore / $validScans;
    $avgSkinScore = $avgSkinScore / $validScans;
    $avgFingerScore = $avgFingerScore / $validScans;
    $avgShapeScore = $avgShapeScore / $validScans;
    $avgQualityScore = $avgQualityScore / $validScans;
}

// Success rate
$successRate = $totalMatches > 0 ? ($successfulMatches / $totalMatches) * 100 : 0;

// Response
send_response(true, [
    'user' => [
        'user_id' => $user['user_id'],
        'full_name' => $user['full_name'],
        'phone_number' => $user['phone_number'],
        'palm_registered' => (bool)$user['palm_registered'],
        'last_palm_scan' => $user['last_palm_scan']
    ],
    'statistics' => [
        'total_scans' => $totalScans,
        'total_matches' => $totalMatches,
        'successful_matches' => $successfulMatches,
        'failed_matches' => $failedMatches,
        'success_rate' => round($successRate, 2),
        'average_scores' => [
            'confidence' => round($avgConfidence, 4),
            'vein_pattern' => round($avgVeinScore, 4),
            'palm_lines' => round($avgPalmLinesScore, 4),
            'skin_texture' => round($avgSkinScore, 4),
            'finger_geometry' => round($avgFingerScore, 4),
            'palm_shape' => round($avgShapeScore, 4),
            'image_quality' => round($avgQualityScore, 4)
        ]
    ],
    'recent_scans' => array_slice($scans, 0, 10),
    'recent_matches' => array_slice($matches, 0, 10),
    'all_scans_count' => $totalScans,
    'all_matches_count' => $totalMatches
], 'Analytics retrieved successfully', 200);

?>
