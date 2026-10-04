<?php
require_once 'config.php';

// Only allow authenticated admin
if (!validateSession() || !isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Prevent caching of protected pages
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to the frontend dashboard
header('Location: ../pages/dashboard.php');
?>


