<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$email = strtolower(sanitizeInput($_POST['email'] ?? ''));

if (empty($email)) {
    sendJsonResponse(false, 'Email is required');
}

if (!validateEmail($email)) {
    sendJsonResponse(false, 'Please enter a valid email address');
}

$db = new Database();

// Check if email exists
$stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND is_active = 1");
$stmt->execute([$email]);
$exists = $stmt->fetch() !== false;

sendJsonResponse(true, '', ['exists' => $exists]);
?>


