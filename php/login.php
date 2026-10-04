<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$username = strtolower(sanitizeInput($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($username) || empty($password)) {
    sendJsonResponse(false, 'Username and password are required');
}

$db = new Database();

// ── Server-side login throttling ───────────────────────────────────────────
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$throttleWindow = 900; // 15-minute rolling window

// Check for existing lockout
$lockStmt = $db->prepare(
    "SELECT failed_count, locked_until, first_failed_at FROM login_attempts WHERE username = ? AND ip_address = ? LIMIT 1"
);
$lockStmt->execute([$username, $clientIp]);
$attempt = $lockStmt->fetch(PDO::FETCH_ASSOC);

if ($attempt && $attempt['locked_until'] !== null && strtotime($attempt['locked_until']) > time()) {
    $remaining = strtotime($attempt['locked_until']) - time();
    sendJsonResponse(false, "Too many failed attempts. Try again in {$remaining} seconds.");
}

// If the rolling window expired, reset the counter
if ($attempt && $attempt['first_failed_at'] !== null && (time() - strtotime($attempt['first_failed_at']) > $throttleWindow)) {
    $db->prepare("UPDATE login_attempts SET failed_count = 0, locked_until = NULL, first_failed_at = NOW() WHERE username = ? AND ip_address = ?")
       ->execute([$username, $clientIp]);
    $attempt['failed_count'] = 0;
}
// ────────────────────────────────────────────────────────────────────────────

// Find user by username or email
$stmt = $db->prepare("SELECT * FROM users WHERE (LOWER(username) = ? OR LOWER(email) = ?)");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user) {
    sendJsonResponse(false, 'Invalid username or password');
}

// Verify password FIRST before revealing any account status
// (prevents user enumeration via different error messages)
if (!verifyPassword($password, $user['password'])) {
    // ── Record failed attempt ─────────────────────────────────────────────
    if (!$attempt) {
        $db->prepare(
            "INSERT INTO login_attempts (username, ip_address, failed_count, first_failed_at) VALUES (?, ?, 1, NOW())"
        )->execute([$username, $clientIp]);
        $failedCount = 1;
    } else {
        $db->prepare(
            "UPDATE login_attempts SET failed_count = failed_count + 1, first_failed_at = COALESCE(first_failed_at, NOW()) WHERE username = ? AND ip_address = ?"
        )->execute([$username, $clientIp]);
        $failedCount = (int)$attempt['failed_count'] + 1;
    }

    // After 5 failures, apply lockout with escalating duration
    if ($failedCount >= 5) {
        $lockoutMultiplier = max(1, floor(($failedCount - 4)));
        $lockoutSeconds = min(60 * pow(2, $lockoutMultiplier - 1), 900);
        $db->prepare(
            "UPDATE login_attempts SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE username = ? AND ip_address = ?"
        )->execute([$lockoutSeconds, $username, $clientIp]);
    }
    // ──────────────────────────────────────────────────────────────────────

    sendJsonResponse(false, 'Invalid username or password');
}

// Password is correct — NOW check account status
// (Checking before password gives away that the account exists)
if ($user['status'] === 'pending') {
    sendJsonResponse(false, 'Your account is pending admin approval.');
}

if ($user['status'] === 'blocked') {
    sendJsonResponse(false, 'Your account has been blocked. Please contact an administrator.');
}

// ── Successful login: reset throttle counter ──────────────────────────────
$db->prepare(
    "DELETE FROM login_attempts WHERE username = ? AND ip_address = ?"
)->execute([$username, $clientIp]);
// ──────────────────────────────────────────────────────────────────────────

// ── Super Admin: Atomic Single-Active-Session Lock ─────────────────────────
if ($user['role'] === 'super_admin') {
    try {
        $pdo = $db->getConnection();
        $pdo->beginTransaction();

        // Lock the singleton row — any concurrent login attempt blocks here until we commit/rollback
        $lockRow = $pdo->prepare("SELECT active_user_id, login_log_id, expires_at FROM super_admin_lock WHERE id = 1 FOR UPDATE");
        $lockRow->execute();
        $lock = $lockRow->fetch(PDO::FETCH_ASSOC);

        $lockHeld       = ($lock && $lock['active_user_id'] !== null);
        $lockExpired    = ($lock && $lock['expires_at'] !== null && strtotime($lock['expires_at']) < time());
        $lockOwnedByMe  = ($lock && (int)$lock['active_user_id'] === (int)$user['id']);

        if ($lockHeld && !$lockExpired && !$lockOwnedByMe) {
            // Another Super Admin is active — deny
            $pdo->rollBack();
            sendJsonResponse(false,
                'Another Super Admin is currently active. Please try again after the current Super Admin logs out.',
                ['locked_by_other' => true]
            );
        }

        // Close any open sessions for this user (re-login or stale lock)
        $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
            ->execute([$user['id']]);

        // Create a new login log entry
        $pdo->prepare("INSERT INTO login_logs (user_id) VALUES (?)")->execute([$user['id']]);
        $logId = $pdo->lastInsertId();

        // Acquire / refresh the lock
        $pdo->prepare(
            "UPDATE super_admin_lock
             SET active_user_id = ?,
                 login_log_id   = ?,
                 acquired_at    = NOW(),
                 expires_at     = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE id = 1"
        )->execute([$user['id'], $logId, SESSION_TIMEOUT]);

        $pdo->commit();

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
        sendJsonResponse(false, 'Login failed due to a server error. Please try again.');
    }
} else {
    // ── Non-Super-Admin: standard login log ───────────────────────────────
    $logStmt = $db->prepare("INSERT INTO login_logs (user_id) VALUES (?)");
    $logStmt->execute([$user['id']]);
    $logId = $db->lastInsertId();
}
// ──────────────────────────────────────────────────────────────────────────

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Set session variables
$_SESSION['user_id']          = $user['id'];
$_SESSION['username']         = $user['username'];
$_SESSION['email']            = $user['email'];
$_SESSION['role']             = $user['role'];
$_SESSION['status']           = $user['status'];
$_SESSION['is_pending_setup'] = ($user['status'] === 'pending_setup');
$_SESSION['login_log_id']     = $logId;
$_SESSION['login_time']       = time();
$_SESSION['last_activity']    = time();
$_SESSION['last_regeneration']= time();

// Set remember me cookie if requested — use secure flag
if ($remember) {
    $token   = generateToken();
    $expires = time() + (30 * 24 * 60 * 60);
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? null) == 443;
    setcookie('remember_token', $token, $expires, '/', '', $isSecure, true);
}

// Update last login time
$db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

sendJsonResponse(true, 'Login successful', [
    'user_id'  => $user['id'],
    'username' => $user['username'],
    'email'    => $user['email']
]);
?>

