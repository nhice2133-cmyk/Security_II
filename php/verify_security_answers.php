<?php
/**
 * verify_security_answers.php
 *
 * SECURITY: This endpoint may ONLY be called after the user has completed the
 * full OTP verification flow (forgot-password-step2.php sets $_SESSION['reset_verified_id']).
 * It mirrors the same guard used by forgot-password-step3.php.
 *
 * It does NOT perform a password reset itself — password reset is handled
 * exclusively by forgot-password-step4.php which requires $_SESSION['reset_authorized_id'].
 *
 * Attack surface eliminated:
 *  - Without a valid OTP session, security answers cannot authorize a reset.
 *  - The wrong-answer disclosure oracle (which question was wrong) is removed.
 *  - Brute-force protection via per-user attempt counter stored in session.
 */
require_once 'config.php';

header('Content-Type: application/json');

// ── Only accept JSON POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

// ── Require a live OTP-verified session ──────────────────────────────────────
// $_SESSION['reset_verified_id'] is set exclusively by forgot-password-step2.php
// after a valid, one-time OTP is consumed.  Without it this endpoint is a no-op.
if (empty($_SESSION['reset_verified_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please complete OTP verification first.']);
    exit;
}

$sessionIdNumber = $_SESSION['reset_verified_id']; // trusted — came from our OTP flow

// ── Rate-limit security-question attempts in the session ─────────────────────
$maxAttempts = 5;
if (!isset($_SESSION['sq_attempts'])) {
    $_SESSION['sq_attempts'] = 0;
}
if ((int)$_SESSION['sq_attempts'] >= $maxAttempts) {
    // Invalidate the entire recovery session on lockout
    unset($_SESSION['reset_verified_id'], $_SESSION['sq_attempts']);
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please start the recovery process again.']);
    exit;
}

// ── Read answers from payload ────────────────────────────────────────────────
$a1 = trim($payload['answer1'] ?? '');
$a2 = trim($payload['answer2'] ?? '');
$a3 = trim($payload['answer3'] ?? '');

// At least 2 of 3 must be provided
$answersProvided = (int)($a1 !== '') + (int)($a2 !== '') + (int)($a3 !== '');
if ($answersProvided < 2) {
    echo json_encode(['success' => false, 'message' => 'Please answer at least 2 security questions.']);
    exit;
}

// ── Look up the user using the session-trusted ID (not client-supplied) ───────
$db = new Database();
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$sessionIdNumber]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Session ID is stale (user deleted/blocked since OTP was issued)
    unset($_SESSION['reset_verified_id'], $_SESSION['sq_attempts']);
    echo json_encode(['success' => false, 'message' => 'Account not found. Please start the recovery process again.']);
    exit;
}

// ── Fetch security question hashes ───────────────────────────────────────────
$q = $db->prepare("SELECT answer1, answer2, answer3 FROM user_security_questions WHERE user_id = ? LIMIT 1");
$q->execute([$user['id']]);
$row = $q->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No security questions are on file for this account.']);
    exit;
}

// ── Verify: need at least 2 out of 3 correct (generic failure, no oracle) ────
$correctCount = 0;
if ($a1 !== '' && password_verify(strtolower($a1), $row['answer1'])) $correctCount++;
if ($a2 !== '' && password_verify(strtolower($a2), $row['answer2'])) $correctCount++;
if ($a3 !== '' && password_verify(strtolower($a3), $row['answer3'])) $correctCount++;

if ($correctCount < 2) {
    $_SESSION['sq_attempts']++;
    // Generic message — never reveal WHICH answer was wrong
    echo json_encode(['success' => false, 'message' => 'The security answers provided are incorrect.']);
    exit;
}

// ── Success: authorize the password-reset step ────────────────────────────────
// Reset the attempt counter and mark answers as verified.
// The actual reset is done by forgot-password-step4.php which checks this key.
$_SESSION['sq_attempts'] = 0;
$_SESSION['reset_authorized_id'] = $sessionIdNumber;

echo json_encode(['success' => true, 'message' => 'Answers verified.']);
?>
