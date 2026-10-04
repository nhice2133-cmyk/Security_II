<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$username = strtolower(sanitizeInput($_POST['username'] ?? ''));

if (empty($username)) {
    sendJsonResponse(false, 'Username is required');
}

$db = new Database();

// Check if username exists
$stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? AND is_active = 1");
$stmt->execute([$username]);
$exists = $stmt->fetch() !== false;

sendJsonResponse(true, '', ['exists' => $exists]);
?>


