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

$password = $_POST['password'] ?? '';

if (empty($password)) {
    sendJsonResponse(false, 'New password is required');
}

// ── Centralised password policy (must match register_stash / change-password) ─
if (strlen($password) < PASSWORD_MIN_LENGTH) {
    sendJsonResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
}
if (!preg_match('/[A-Z]/', $password)) {
    sendJsonResponse(false, 'Password must contain at least one uppercase letter');
}
if (!preg_match('/[a-z]/', $password)) {
    sendJsonResponse(false, 'Password must contain at least one lowercase letter');
}
if (!preg_match('/[0-9]/', $password)) {
    sendJsonResponse(false, 'Password must contain at least one number');
}
if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
    sendJsonResponse(false, 'Password must contain at least one special character');
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
