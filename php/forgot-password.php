<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$email = sanitizeInput($_POST['email'] ?? '');

if (empty($email)) {
    sendJsonResponse(false, 'Email is required');
}

if (!validateEmail($email)) {
    sendJsonResponse(false, 'Please enter a valid email address');
}

$db = new Database();

// Check if email exists
$stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Don't reveal if email exists or not for security
    sendJsonResponse(true, 'If the email exists, a password reset link has been sent.');
}

// Generate reset token
$token = generateToken();
$expiresAt = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour

// Store reset token
$stmt = $db->prepare("
    INSERT INTO password_reset_tokens (user_id, token, expires_at) 
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $token, $expiresAt]);

// In a real application, you would send an email here
// For demo purposes, we'll just log the reset link
$resetLink = APP_URL . "/reset-password.php?token=" . $token;
error_log("Password reset link for " . $email . ": " . $resetLink);

// TODO: Implement actual email sending
// sendPasswordResetEmail($email, $resetLink);

sendJsonResponse(true, 'If the email exists, a password reset link has been sent.');
?>


