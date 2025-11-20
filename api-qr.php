<?php
// api-qr.php - JSON API endpoint for QR code metadata
// Usage: GET /api/qr/{code} - Returns JSON metadata for apps/PWAs
require_once 'config.php';

setSecurityHeaders();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get code from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$code = isset($pathParts[2]) ? $pathParts[2] : null;

if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'Code required']);
    exit;
}

// Load redirect data
$redirect = loadRedirect($code);

if ($redirect === null) {
    http_response_code(404);
    echo json_encode(['error' => 'QR code not found']);
    exit;
}

// Return metadata for apps/PWAs
echo json_encode([
    'success' => true,
    'code' => $code,
    'targetUrl' => $redirect['targetUrl'],
    'description' => $redirect['description'] ?? '',
    'createdAt' => $redirect['createdAt'] ?? null,
    'accessCount' => $redirect['accessCount'] ?? 0,
    'lastAccessed' => $redirect['lastAccessed'] ?? null,
    'urlHistory' => $redirect['urlHistory'] ?? [],
    'qrUrl' => QR_BASE_URL . '/q/' . $code,
    'metadata' => [
        'version' => '1.0',
        'type' => 'redirect'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
