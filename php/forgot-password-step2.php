<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$idNumber = sanitizeInput($_POST['idNumber'] ?? '');
$otp = sanitizeInput($_POST['otp'] ?? '');

if (empty($idNumber) || empty($otp)) {
    sendJsonResponse(false, 'ID Number and OTP are required');
}

$db = new Database();

// Get User
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND status = 'approved'");
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if (!$user) {
    sendJsonResponse(false, 'Invalid request');
}

// Fetch the most recent non-expired, unused OTP for this user (regardless of code match)
$otpStmt = $db->prepare(
    "SELECT id, otp_code, attempts FROM otps WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1"
);
$otpStmt->execute([$user['id']]);
$otpRecord = $otpStmt->fetch();

if (!$otpRecord) {
    sendJsonResponse(false, 'Invalid or expired OTP');
}

// Check if max attempts already reached
if ((int)$otpRecord['attempts'] >= 5) {
    // Kill this OTP — force user to request a new one
    $db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);
    sendJsonResponse(false, 'Too many attempts, request a new code.');
}

// Check if the submitted code matches
if ($otpRecord['otp_code'] !== $otp) {
    // Increment attempt counter
    $db->prepare("UPDATE otps SET attempts = attempts + 1 WHERE id = ?")->execute([$otpRecord['id']]);

    // If this was the 5th attempt, also mark it dead
    if ((int)$otpRecord['attempts'] + 1 >= 5) {
        $db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);
        sendJsonResponse(false, 'Too many attempts, request a new code.');
    }

    sendJsonResponse(false, 'Invalid or expired OTP');
}

// OTP matches — mark as used
$db->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);

// Save state in session so they can proceed
$_SESSION['reset_verified_id'] = $idNumber;

// Fetch security questions safely
try {
    $stmt = $db->prepare("SELECT question1, question2, question3 FROM user_security_questions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $questions = $stmt->fetch();

    if (!$questions) {
        sendJsonResponse(false, 'No security questions set for this user.');
    }
} catch (PDOException $e) {
    sendJsonResponse(false, 'No security questions set for this user (table missing).');
}

sendJsonResponse(true, 'OTP verified', [
    'questions' => [
        'q1' => $questions['question1'],
        'q2' => $questions['question2'],
        'q3' => $questions['question3']
    ]
]);
?>

