<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$email = strtolower(trim(sanitizeInput($_POST['email'] ?? ($json['email'] ?? ''))));

if ($email === '') {
    sendJsonResponse(false, 'Email is required');
}

if (strlen($email) > 254) {
    sendJsonResponse(false, 'Email cannot exceed 254 characters');
}

if (!validateEmail($email)) {
    sendJsonResponse(false, 'Please enter a valid email address');
}

$db = new Database();

// Check if email exists across all accounts (active, pending, or inactive)
$stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
$stmt->execute([$email]);
$exists = $stmt->fetch() !== false;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'exists' => $exists,
    'data' => ['exists' => $exists]
]);
exit;
?>


