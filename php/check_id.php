<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$id = sanitizeInput($_POST['id'] ?? '');

if (empty($id)) {
    sendJsonResponse(false, 'ID is required');
}

if (!is_numeric($id)) {
    sendJsonResponse(false, 'Invalid ID format');
}

$db = new Database();

// Check if government/employee ID number exists (matches users.id_number)
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND is_active = 1");
$stmt->execute([$id]);
$exists = $stmt->fetch() !== false;

sendJsonResponse(true, '', ['exists' => $exists]);
?>


