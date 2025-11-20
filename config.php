<?php
// config.php - Configuration
define('DATA_DIR', __DIR__ . '/data');
define('ADMIN_PASSWORD', 'AdminPassword1234!@#$'); // CHANGE THIS!
// Dynamic QR_BASE_URL - auto-detect from request
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
define('QR_BASE_URL', $protocol . $host); // Auto-detect base URL

// Initialize data directory
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

function getRedirectFile($code) {
    return DATA_DIR . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '.json';
}

function loadRedirect($code) {
    $file = getRedirectFile($code);
    if (!file_exists($file)) {
        return null;
    }
    return json_decode(file_get_contents($file), true);
}

function saveRedirect($code, $data) {
    $file = getRedirectFile($code);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function deleteRedirect($code) {
    $file = getRedirectFile($code);
    if (file_exists($file)) {
        unlink($file);
        return true;
    }
    return false;
}

function listAllRedirects() {
    $redirects = [];
    $files = glob(DATA_DIR . '/*.json');
    foreach ($files as $file) {
        $code = basename($file, '.json');
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $redirects[$code] = $data;
        }
    }
    return $redirects;
}

function checkAuth() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || 
        !isset($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASSWORD) {
        header('WWW-Authenticate: Basic realm="QR Admin"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access denied';
        exit;
    }
}
?>
