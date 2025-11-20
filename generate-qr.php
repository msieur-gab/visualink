<?php
// generate-qr.php - Internal QR code generation endpoint
// Generates QR codes server-side and caches them locally
require_once 'config.php';

// Get the code parameter
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$size = isset($_GET['size']) ? intval($_GET['size']) : 200;

// Validate inputs
if (empty($code) || !preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
    http_response_code(400);
    die('Invalid QR code');
}

if ($size < 50 || $size > 500) {
    $size = 200;
}

// Set security headers
setSecurityHeaders();
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

// Create cache directory
$cacheDir = __DIR__ . '/cache/qrcodes';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache key
$cacheKey = md5($code . $size);
$cachePath = $cacheDir . '/' . $cacheKey . '.png';

// Serve from cache if available
if (file_exists($cachePath)) {
    readfile($cachePath);
    exit;
}

// Load redirect to verify code exists
$redirect = loadRedirect($code);
if ($redirect === null) {
    http_response_code(404);
    die('QR code not found');
}

// Generate QR code URL
$qrUrl = QR_BASE_URL . '/q/' . $code;

// Fetch QR code from external API and cache it locally
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($qrUrl);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $qrApiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$qrImage = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($qrImage)) {
    http_response_code(503);
    die('QR code generation failed');
}

// Cache the image locally
file_put_contents($cachePath, $qrImage);

// Serve the image
echo $qrImage;
exit;
?>
