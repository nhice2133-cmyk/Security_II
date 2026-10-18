<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$id = trim(sanitizeInput($_POST['id'] ?? ''));

if ($id === '') {
    sendJsonResponse(false, 'ID is required');
}

if (!preg_match('/^\d{4}-\d{4}$/', $id) && !is_numeric($id)) {
    sendJsonResponse(false, 'Invalid ID format');
}

$db = new Database();

// Check if ID number exists across all accounts
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? LIMIT 1");
$stmt->execute([$id]);
$exists = $stmt->fetch() !== false;

sendJsonResponse(true, '', ['exists' => $exists]);
?>


