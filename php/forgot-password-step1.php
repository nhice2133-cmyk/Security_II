<?php
/**
 * php/forgot-password-step1.php
 *
 * Initiates the password recovery workflow:
 * 1. Accepts ID Number, registered Email, or Username
 * 2. Looks up the active approved account
 * 3. Enforces rate limiting
 * 4. Generates a secure 6-digit OTP
 * 5. Sends the OTP via PHPMailer (Gmail SMTP / Google App Password)
 * 6. Stores the hashed/tracked OTP in the database
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$query = trim(sanitizeInput($_POST['idNumber'] ?? $_POST['query'] ?? ''));

if ($query === '') {
    sendJsonResponse(false, 'Please enter your ID Number, Email address, or Username.');
}

if (strlen($query) > 100) {
    sendJsonResponse(false, 'Input exceeds maximum allowed length.');
}

$db = new Database();

// Search user by ID Number, Email, or Username
$stmt = $db->prepare(
    "SELECT id, id_number, username, email, first_name, last_name, status, is_active 
     FROM users 
     WHERE (id_number = ? OR LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)) 
     LIMIT 1"
);
$stmt->execute([$query, $query, $query]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendJsonResponse(false, 'No active account found matching the provided ID Number, Email, or Username.');
}

// Check account status
if ($user['status'] !== 'approved' || (isset($user['is_active']) && (int)$user['is_active'] === 0)) {
    sendJsonResponse(false, 'Your account is not active or approved. Please contact an administrator.');
}

if (empty($user['email']) || !validateEmail($user['email'])) {
    sendJsonResponse(false, 'This account does not have a valid email address configured.');
}

// Rate-limiting: prevent requesting more than one OTP every 60 seconds
$cooldownStmt = $db->prepare(
    "SELECT id FROM otps 
     WHERE user_id = ? AND used = 0 AND expires_at > NOW() 
       AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) 
     LIMIT 1"
);
$cooldownStmt->execute([$user['id']]);
if ($cooldownStmt->fetch()) {
    sendJsonResponse(false, 'An OTP was recently requested. Please check your inbox or wait 60 seconds before requesting a new code.');
}

// Generate 6-digit OTP code using cryptographically secure random_int
$otpCode = sprintf("%06d", random_int(0, 999999));

// Send the OTP email first
$recipientName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'User');
$sendResult = sendSystemOtpEmail($user['email'], $recipientName, $otpCode);

if (!$sendResult['success']) {
    sendJsonResponse(false, 'Email sending failed: ' . $sendResult['error']);
}

// Mark previous unused OTPs as expired/used so only the newest OTP is valid
$db->prepare("UPDATE otps SET used = 1 WHERE user_id = ? AND used = 0")->execute([$user['id']]);

// Store the newly issued OTP in the database (valid for 15 minutes)
$insStmt = $db->prepare(
    "INSERT INTO otps (user_id, otp_code, expires_at, created_at) 
     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())"
);
$insStmt->execute([$user['id'], $otpCode]);

$masked = maskEmail($user['email']);

sendJsonResponse(true, "A 6-digit OTP has been sent to your registered email ({$masked}).", [
    'idNumber'    => $user['id_number'],
    'maskedEmail' => $masked,
]);
