<?php
// config.php - Configuration
define('DATA_DIR', __DIR__ . '/data');
// Bcrypt hash of 'SecureAdmin2024!@#$%' - change this password and generate new hash
define('ADMIN_PASSWORD_HASH', '$2y$12$ma2CvELzeKGyAF4hpMs6Ne/HFtD9NxTEfnaliTcdxnKykye8OqpLa');
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

// Validate Host header to prevent Host header injection
function validateHostHeader() {
    $allowedHosts = ['localhost', '127.0.0.1'];
    // Add current host if set via environment
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = explode(':', $_SERVER['HTTP_HOST'])[0]; // Remove port if present
        // Basic validation - only alphanumeric, dots, hyphens
        if (preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
            return;
        }
    }
    // If validation fails, redirect to localhost
    header('Location: http://localhost' . $_SERVER['REQUEST_URI']);
    exit;
}

// Anonymize IP addresses to remove last octet (GDPR compliance)
function anonymizeIp($ip) {
    if (empty($ip) || $ip === 'unknown') {
        return 'unknown';
    }

    // IPv4: mask last octet (192.168.1.123 → 192.168.1.0)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip);
    }

    // IPv6: mask last 80 bits (keep only /48 prefix)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 3)) . ':0:0:0:0:0';
    }

    return 'unknown';
}

// Set security headers
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src \'self\'');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Configure secure session settings
function initializeSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'secure' => true,      // Only send over HTTPS
            'httponly' => true,    // Not accessible to JavaScript
            'samesite' => 'Strict' // CSRF protection
        ]);
        session_start();
    }
}

function checkAuth() {
    // Initialize secure session
    initializeSecureSession();

    // Check if user is authenticated via session
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        header('Location: /login.php');
        exit;
    }
}

function checkAuthAPI() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check session-based auth only
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}
?>
