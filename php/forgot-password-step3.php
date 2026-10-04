<?php
/**
 * forgot-password-step3.php
 *
 * Verifies security-question answers after OTP has been validated.
 * Requires $_SESSION['reset_verified_id'] set by step 2.
 *
 * SECURITY:
 *  - The account is looked up from the session (not client input) so the
 *    client cannot supply a different idNumber to reset another account.
 *  - Generic failure message — never reveals which answer was wrong.
 *  - Per-session attempt counter limits brute-force guessing.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// ── Require a valid OTP-verified session ──────────────────────────────────────
if (empty($_SESSION['reset_verified_id'])) {
    sendJsonResponse(false, 'Unauthorized. Please verify OTP first.');
}

$sessionIdNumber = $_SESSION['reset_verified_id']; // trusted

// ── Per-session attempt limiting ──────────────────────────────────────────────
$maxAttempts = 5;
if (!isset($_SESSION['sq_attempts'])) {
    $_SESSION['sq_attempts'] = 0;
}
if ((int)$_SESSION['sq_attempts'] >= $maxAttempts) {
    unset($_SESSION['reset_verified_id'], $_SESSION['sq_attempts']);
    sendJsonResponse(false, 'Too many failed attempts. Please start the recovery process again.');
}

// ── Read answers (ignore any client-supplied idNumber) ────────────────────────
$ans1 = trim($_POST['ans1'] ?? '');
$ans2 = trim($_POST['ans2'] ?? '');
$ans3 = trim($_POST['ans3'] ?? '');

if (empty($ans1) || empty($ans2) || empty($ans3)) {
    sendJsonResponse(false, 'All answers are required');
}

$db = new Database();

// ── Look up using session-trusted ID ─────────────────────────────────────────
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$sessionIdNumber]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['reset_verified_id'], $_SESSION['sq_attempts']);
    sendJsonResponse(false, 'Invalid request');
}

// ── Fetch security question hashes ────────────────────────────────────────────
$stmt = $db->prepare("SELECT answer1, answer2, answer3 FROM user_security_questions WHERE user_id = ?");
$stmt->execute([$user['id']]);
$questions = $stmt->fetch();

if (!$questions) {
    sendJsonResponse(false, 'No security questions found.');
}

// ── Verify: require at least 2/3 correct; generic failure message ─────────────
$correct = 0;
if (password_verify(strtolower($ans1), $questions['answer1'])) $correct++;
if (password_verify(strtolower($ans2), $questions['answer2'])) $correct++;
if (password_verify(strtolower($ans3), $questions['answer3'])) $correct++;

if ($correct < 2) {
    $_SESSION['sq_attempts']++;
    // Generic message — do NOT reveal which answer was wrong
    sendJsonResponse(false, 'The security answers provided are incorrect.');
}

// ── Success ────────────────────────────────────────────────────────────────────
$_SESSION['sq_attempts'] = 0;
$_SESSION['reset_authorized_id'] = $sessionIdNumber;
sendJsonResponse(true, 'Answers verified');
?>
