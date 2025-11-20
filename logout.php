<?php
// logout.php - Logout endpoint
session_start();
session_destroy();
header('Location: /login.php');
exit;
