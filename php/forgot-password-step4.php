<?php
/**
 * forgot-password-step4.php
 *
 * Final step: actually resets the password.
 * Requires both $_SESSION['reset_verified_id'] (OTP passed) AND
 * $_SESSION['reset_authorized_id'] (security answers passed).
 *
 * SECURITY:
 *  - The account ID comes from the session, not from the client.
 *  - Both session keys must be present and consistent.
 *  - Full password policy (length + complexity) is enforced.
 *  - Both recovery session keys are invalidated after a successful reset.
 *  - Session ID is regenerated to prevent session fixation.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// ── Require a fully-authorized recovery session ───────────────────────────────
if (empty($_SESSION['reset_verified_id']) || empty($_SESSION['reset_authorized_id'])) {
    sendJsonResponse(false, 'Unauthorized access. Please complete the full recovery process.');
}

// Both keys must agree on the same account
if ($_SESSION['reset_verified_id'] !== $_SESSION['reset_authorized_id']) {
    // Mismatch — something is wrong; nuke the session
    unset($_SESSION['reset_verified_id'], $_SESSION['reset_authorized_id'], $_SESSION['sq_attempts']);
    sendJsonResponse(false, 'Session mismatch. Please start the recovery process again.');
}

$sessionIdNumber = $_SESSION['reset_authorized_id']; // trusted

$password = (string)($_POST['password'] ?? '');

if (trim($password) === '') {
    sendJsonResponse(false, 'New password is required and cannot be whitespace only');
}

// ── Centralised password policy ─
$pwdErr = validatePassword($password);
if ($pwdErr) {
    sendJsonResponse(false, $pwdErr);
}

$db = new Database();

// ── Use session-trusted ID; never trust client input for identity ─────────────
$stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$sessionIdNumber]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    unset($_SESSION['reset_verified_id'], $_SESSION['reset_authorized_id'], $_SESSION['sq_attempts']);
    sendJsonResponse(false, 'Account not found. Please start the recovery process again.');
}

$hashedPassword = hashPassword($password);

$stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
$success = $stmt->execute([$hashedPassword, $user['id']]);

if ($success) {
    // Invalidate all recovery session state
    unset($_SESSION['reset_verified_id'], $_SESSION['reset_authorized_id'], $_SESSION['sq_attempts']);
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    sendJsonResponse(true, 'Password updated successfully');
} else {
    sendJsonResponse(false, 'Failed to update password. Please try again.');
}
?>
