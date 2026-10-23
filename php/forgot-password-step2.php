<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$idNumber = trim(sanitizeInput($_POST['idNumber'] ?? ''));
$otp = trim(sanitizeInput($_POST['otp'] ?? ''));

if ($idNumber === '' || $otp === '') {
    sendJsonResponse(false, 'Identifier and OTP code are required');
}

if (!preg_match('/^\d{6}$/', $otp)) {
    sendJsonResponse(false, 'OTP must be a 6-digit number');
}

$db = new Database();

// Get User by ID Number, Email, or Username
$stmt = $db->prepare(
    "SELECT id, id_number FROM users 
     WHERE (id_number = ? OR LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)) 
       AND status = 'approved' 
     LIMIT 1"
);
$stmt->execute([$idNumber, $idNumber, $idNumber]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendJsonResponse(false, 'Invalid recovery session');
}

// Fetch the most recent non-expired, unused OTP for this user
$otpStmt = $db->prepare(
    "SELECT id, otp_code, attempts FROM otps 
     WHERE user_id = ? AND used = 0 AND expires_at > NOW() 
     ORDER BY id DESC LIMIT 1"
);
$otpStmt->execute([$user['id']]);
$otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);

if (!$otpRecord) {
    sendJsonResponse(false, 'Invalid or expired OTP. Please request a new verification code.');
}

// Check if max attempts already reached
if ((int)$otpRecord['attempts'] >= 5) {
    // Kill this OTP — force user to request a new one
    $db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);
    sendJsonResponse(false, 'Too many failed attempts. Please request a new OTP code.');
}

// Check if the submitted code matches
if ($otpRecord['otp_code'] !== $otp) {
    $newAttempts = (int)$otpRecord['attempts'] + 1;
    $db->prepare("UPDATE otps SET attempts = attempts + 1 WHERE id = ?")->execute([$otpRecord['id']]);

    // If this was the 5th attempt, also mark it dead
    if ($newAttempts >= 5) {
        $db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);
        sendJsonResponse(false, 'Too many failed attempts. Please request a new OTP code.');
    }

    $remaining = 5 - $newAttempts;
    sendJsonResponse(false, "Invalid OTP code. ($remaining attempt" . ($remaining === 1 ? '' : 's') . " remaining)");
}

// OTP matches — mark as used
$db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);

// Save canonical ID in session so they can proceed
$_SESSION['reset_verified_id'] = $user['id_number'];

// Fetch security questions safely
try {
    $stmt = $db->prepare("SELECT question1, question2, question3 FROM user_security_questions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $questions = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$questions) {
        sendJsonResponse(false, 'No security questions configured for this account. Please contact an administrator.');
    }
} catch (PDOException $e) {
    sendJsonResponse(false, 'No security questions configured for this account (table missing).');
}

// Helper to decode entities and map question keys to human-readable text
function formatSecurityQuestion(string $q): string {
    $map = [
        'best_friend_elementary' => "Who is your best friend in Elementary?",
        'favorite_pet'           => "What is the name of your favorite pet?",
        'favorite_teacher'       => "Who is your favorite teacher in high school?",
        'mother_maiden_name'     => "What is your mother's maiden name?",
        'birth_city'             => "In what city were you born?",
        'first_car'              => "What was the make and model of your first car?"
    ];
    if (isset($map[$q])) {
        return $map[$q];
    }
    return htmlspecialchars_decode($q, ENT_QUOTES | ENT_HTML5);
}

sendJsonResponse(true, 'OTP verified successfully', [
    'idNumber'  => $user['id_number'],
    'questions' => [
        'q1' => formatSecurityQuestion((string)$questions['question1']),
        'q2' => formatSecurityQuestion((string)$questions['question2']),
        'q3' => formatSecurityQuestion((string)$questions['question3'])
    ]
]);
