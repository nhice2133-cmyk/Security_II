<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$username = strtolower(trim(sanitizeInput($_POST['username'] ?? ($json['username'] ?? ''))));

if ($username === '') {
    sendJsonResponse(false, 'Username is required');
}

if (strlen($username) > 50) {
    sendJsonResponse(false, 'Username cannot exceed 50 characters');
}

$db = new Database();

// Check if username exists across all accounts (excluding current user if authenticated)
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? AND id <> ? LIMIT 1");
    $stmt->execute([$username, $_SESSION['user_id']]);
} else {
    $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
    $stmt->execute([$username]);
}
$exists = $stmt->fetch() !== false;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'exists' => $exists,
    'data' => ['exists' => $exists]
]);
exit;
?>


