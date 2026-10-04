<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$idNumber = sanitizeInput($_POST['idNumber'] ?? '');

if (empty($idNumber)) {
    sendJsonResponse(false, 'ID Number is required');
}

$db = new Database();
$stmt = $db->prepare("SELECT id, email FROM users WHERE id_number = ? AND status = 'approved'");
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if (!$user) {
    // Fail securely: return generic success to prevent user enumeration
    sendJsonResponse(true, 'If the ID number exists in our system, an OTP has been sent.');
}

// Rate-limit: reject if a non-expired, unused OTP was issued within the last 60 seconds
$cooldownStmt = $db->prepare(
    "SELECT id FROM otps WHERE user_id = ? AND used = 0 AND expires_at > NOW() AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1"
);
$cooldownStmt->execute([$user['id']]);
if ($cooldownStmt->fetch()) {
    // Return the same success message to prevent user-enumeration timing attacks
    sendJsonResponse(true, 'If the ID number exists in our system, an OTP has been sent.');
}

// Generate 6-digit OTP using cryptographically secure random
$otpCode = sprintf("%06d", random_int(0, 999999));

// Store OTP in database using MySQL time to prevent timezone mismatch
$stmt = $db->prepare("INSERT INTO otps (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
$stmt->execute([$user['id'], $otpCode]);

// If SMTP credentials are not configured, log to PHP error log (NOT a web-accessible file)
if (empty(SMTP_PASSWORD) || SMTP_PASSWORD === 'your-app-password') {
    error_log("[CyberAuth OTP DEV] User ID {$user['id']} / Email: {$user['email']} — OTP: $otpCode");
} else {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(FROM_EMAIL ?: 'noreply@localhost', FROM_NAME);
        $mail->addAddress($user['email'], $user['first_name'] ?? 'User');

        $mail->isHTML(true);
        $mail->Subject = 'CyberAuth OTP Verification';
        $mail->Body    = '<html><body>
                            <h2>Neural Network Access Request</h2>
                            <p>Your authorization code is: <strong>' . $otpCode . '</strong></p>
                            <p>This code will expire in 15 minutes.</p>
                          </body></html>';

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        sendJsonResponse(false, 'Failed to send OTP email. Please check server configuration.');
    }
}

sendJsonResponse(true, 'If the ID number exists in our system, an OTP has been sent.');
?>

