<?php
// logout.php - Logout endpoint
require_once 'config.php';
initializeSecureSession();
setSecurityHeaders();
session_destroy();
header('Location: /login.php');
exit;
