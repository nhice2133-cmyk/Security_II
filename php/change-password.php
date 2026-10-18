<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Check if user is logged in
if (!validateSession()) {
    sendJsonResponse(false, 'Session expired or invalid');
}

// CSRF protection
requireCsrf();

// Update session activity
$_SESSION['last_activity'] = time();

// Check privileges if admin or user
if ($_SESSION['role'] !== 'super_admin') {
    $db = new Database();
    $stmtPriv = $db->prepare("SELECT privileges FROM users WHERE id = ?");
    $stmtPriv->execute([$_SESSION['user_id']]);
    $privUser = $stmtPriv->fetch();
    $privs = $privUser && $privUser['privileges'] ? json_decode($privUser['privileges'], true) : [];
    if (!is_array($privs)) $privs = [];
    if (!in_array('change_password', $privs)) {
        sendJsonResponse(false, 'Denied. Missing change_password privilege.');
    }
}

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$confirmNewPassword = (string)($_POST['confirm_new_password'] ?? '');

// Validation
$errors = [];

if (trim($currentPassword) === '') {
    $errors[] = 'Current password is required';
}

if (trim($newPassword) === '') {
    $errors[] = 'New password is required';
} else {
    $pwdError = validatePassword($newPassword);
    if ($pwdError) {
        $errors[] = $pwdError;
    }
}

if ($newPassword !== $confirmNewPassword) {
    $errors[] = 'New passwords do not match';
}

if ($currentPassword !== '' && $currentPassword === $newPassword) {
    $errors[] = 'New password must be different from current password';
}

if (!empty($errors)) {
    sendJsonResponse(false, implode(', ', $errors));
}

$db = new Database();

// Get current user data
$stmt = $db->prepare("SELECT id, username, password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    sendJsonResponse(false, 'User not found');
}

// Verify current password
if (!verifyPassword($currentPassword, $user['password'])) {
    sendJsonResponse(false, 'Current password is incorrect');
}

// Hash new password
$hashedNewPassword = hashPassword($newPassword);

// Update password
try {
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedNewPassword, $_SESSION['user_id']]);
    logActivity($_SESSION['user_id'], 'PASSWORD_CHANGED', "User '{$user['username']}' updated their account password");
    // Keep session active; return success without forcing logout
    sendJsonResponse(true, 'Password updated successfully');
    
} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to update password. Please try again.');
}
?>


