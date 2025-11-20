<?php
// router.php - Routes for PHP development server
// This file handles URL routing when .htaccess is not processed

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove leading slash
$path = ltrim($request_uri, '/');

// Serve login and logout pages
if (preg_match('#^(login|logout)(\.php)?$#', $path)) {
    $page = preg_replace('/\.php$/', '', explode('/', $path)[0]);
    include $page . '.php';
    return true;
}

// Serve static assets from assets/ directory
if (preg_match('#^assets/(.+)$#', $path, $matches)) {
    $file = __DIR__ . '/assets/' . $matches[1];
    if (file_exists($file) && is_file($file)) {
        // Set proper content type
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['js' => 'application/javascript', 'css' => 'text/css'];
        if (isset($types[$ext])) {
            header('Content-Type: ' . $types[$ext]);
        }
        readfile($file);
        return true;
    }
    return false;
}

// Route /q/* to redirect.php
if (preg_match('#^q/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
    $_GET['code'] = $matches[1];
    include 'redirect.php';
    return true;
}

// Route /api/qr/* to api-qr.php (JSON metadata endpoint)
if (preg_match('#^api/qr/([a-zA-Z0-9_-]+)$#', $path)) {
    include 'api-qr.php';
    return true;
}

// Route /api.php or /api/* or /api/code to api.php (Admin API)
if (preg_match('#^api(\.php)?/?([a-zA-Z0-9_-]*)?$#', $path)) {
    include 'api.php';
    return true;
}

// Default to index.php for admin interface
return false;
?>
