<?php
// redirect.php - Public redirect endpoint (QR codes point here)
require_once 'config.php';

setSecurityHeaders();
// Allow CORS for POC apps to record scans
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the code from URL parameter
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    http_response_code(404);
    die('Invalid QR code');
}

// Load redirect
$redirect = loadRedirect($code);

if ($redirect === null) {
    http_response_code(404);
    die('QR code not found');
}

// Validate redirect URL - only allow http:// and https://
$targetUrl = $redirect['targetUrl'] ?? '';
$parsed = parse_url($targetUrl);
if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
    http_response_code(400);
    die('Invalid redirect URL');
}

// Initialize fields if missing
if (!isset($redirect['accessCount'])) {
    $redirect['accessCount'] = 0;
}
if (!isset($redirect['currentUrlScans'])) {
    $redirect['currentUrlScans'] = 0;
}
if (!isset($redirect['urlHistory'])) {
    $redirect['urlHistory'] = [];
}
if (!isset($redirect['accessLog'])) {
    $redirect['accessLog'] = [];
}

// Handle URL history tracking
$currentUrl = $redirect['targetUrl'];
$lastHistoryEntry = end($redirect['urlHistory']);

// If urlHistory is empty or URL changed, create new history entry
if (empty($redirect['urlHistory']) || $lastHistoryEntry['url'] !== $currentUrl) {
    // Close previous entry if exists
    if (!empty($redirect['urlHistory'])) {
        $lastKey = count($redirect['urlHistory']) - 1;
        $redirect['urlHistory'][$lastKey]['endDate'] = date('c');
    }

    // Create new history entry
    $redirect['urlHistory'][] = [
        'url' => $currentUrl,
        'startDate' => date('c'),
        'endDate' => null,
        'scans' => 0
    ];
}

// Update access counts
$redirect['accessCount']++;
$redirect['currentUrlScans']++;
$redirect['lastAccessed'] = date('c');

// Log access with anonymized IP
$redirect['accessLog'][] = [
    'timestamp' => date('c'),
    'ip' => anonymizeIp($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// Keep only last 100 accesses
if (count($redirect['accessLog']) > 100) {
    $redirect['accessLog'] = array_slice($redirect['accessLog'], -100);
}

// Increment scans for current URL history entry
if (!empty($redirect['urlHistory'])) {
    $lastKey = count($redirect['urlHistory']) - 1;
    $redirect['urlHistory'][$lastKey]['scans']++;
}

// Save updated redirect
saveRedirect($code, $redirect);

// Redirect to target URL
header('Location: ' . $targetUrl);
exit;
?>
