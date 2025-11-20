<?php
require_once 'config.php';
checkAuth();
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Redirect Manager</title>
    <link rel="stylesheet" href="/assets/dashboard.css">
    <script type="module" src="/assets/dashboard.js"></script>
</head>
<body>
    <qr-dashboard api-url="/api.php" poll-interval="10000"></qr-dashboard>
</body>
</html>
