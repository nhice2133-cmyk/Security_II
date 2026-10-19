<?php
/**
 * php/forgot-password.php
 *
 * Wrapper endpoint for forgot-password requests, forwarding to the modern OTP system.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$query = trim(sanitizeInput($_POST['email'] ?? $_POST['idNumber'] ?? ''));

if ($query === '') {
    sendJsonResponse(false, 'Please enter your ID Number, Email address, or Username.');
}

// Delegate to forgot-password-step1.php logic
$_POST['idNumber'] = $query;
require __DIR__ . '/forgot-password-step1.php';
