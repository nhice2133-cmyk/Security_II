<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if ($csrfToken !== null && !verifyCsrf($csrfToken)) {
    sendJsonResponse(false, 'Invalid or expired session security token.');
}

$username = strtolower(trim(sanitizeInput($_POST['username'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$remember = isset($_POST['remember']);

if ($username === '' || trim($password) === '') {
    sendJsonResponse(false, 'Username and password are required');
}

if (strlen($username) > 100 || strlen($password) > 255) {
    sendJsonResponse(false, 'Input exceeds maximum allowed length');
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

if ($user['status'] === 'suspended' || $user['status'] === 'blocked') {
    sendJsonResponse(false, 'Your account has been suspended. Please contact an administrator.');
}

if ($user['status'] === 'inactive' || (isset($user['is_active']) && (int)$user['is_active'] === 0)) {
    sendJsonResponse(false, 'Your account is inactive. Please contact an administrator to request reactivation.');
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

        // Lock the singleton row — any concurrent login attempt blocks here until commit/rollback
        $lockRow = $pdo->prepare("
            SELECT sal.active_user_id, sal.login_log_id, sal.expires_at,
                   (sal.expires_at IS NOT NULL AND sal.expires_at < NOW()) AS is_expired,
                   ll.time_out, u.username, u.status, u.role
            FROM super_admin_lock sal
            LEFT JOIN login_logs ll ON sal.login_log_id = ll.id
            LEFT JOIN users u ON sal.active_user_id = u.id
            WHERE sal.id = 1 FOR UPDATE
        ");
        $lockRow->execute();
        $lock = $lockRow->fetch(PDO::FETCH_ASSOC);

        $hasLockHolder = ($lock && $lock['active_user_id'] !== null);
        $isExpired     = ($lock && ($lock['is_expired'] == 1 || ($lock['expires_at'] !== null && strtotime($lock['expires_at']) < time())));
        $isLogTimedOut = ($lock && $lock['login_log_id'] !== null && $lock['time_out'] !== null);
        $isUserInvalid = ($lock && $lock['active_user_id'] !== null && ($lock['role'] !== 'super_admin' || !in_array($lock['status'], ['approved', 'active'])));

        // If lock was held by a session that expired, timed out, or account is no longer active SA:
        if ($hasLockHolder && ($isExpired || $isLogTimedOut || $isUserInvalid)) {
            $pdo->prepare("
                UPDATE super_admin_lock 
                SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL 
                WHERE id = 1
            ")->execute();
            if ($lock['login_log_id'] && !$isLogTimedOut) {
                $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE id = ? AND time_out IS NULL")
                    ->execute([$lock['login_log_id']]);
            }
            $hasLockHolder = false;
        }

        // Strict Single Super Admin Active Login Enforcement:
        // Only 1 Super Admin can be logged in at a time across the entire system.
        if ($hasLockHolder) {
            $pdo->rollBack();
            $isSameUser = ((int)$lock['active_user_id'] === (int)$user['id']);
            $holderUsername = $lock['username'] ?? 'Super Admin';
            $remaining = max(0, strtotime($lock['expires_at']) - time());

            if ($isSameUser) {
                $msg = 'A Super Admin session is already active for this account. Only 1 Super Admin can be logged in at a time. Please log out from the active session first.';
            } else {
                $msg = "Another Super Admin (@{$holderUsername}) is currently active. Only 1 Super Admin can be logged in at a time. Please try again after the current Super Admin logs out.";
            }

            sendJsonResponse(false, $msg, [
                'locked'            => true,
                'locked_by_other'   => true,
                'is_same_user'      => $isSameUser,
                'active_username'   => $holderUsername,
                'remaining_seconds' => $remaining
            ]);
        }

        // Close any old open sessions in login_logs for this user
        $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
            ->execute([$user['id']]);

        // Create a new login log entry
        $pdo->prepare("INSERT INTO login_logs (user_id) VALUES (?)")->execute([$user['id']]);
        $logId = $pdo->lastInsertId();

        // Acquire the singleton lock
        $lockTimeout = defined('SUPERADMIN_LOCK_TIMEOUT') ? SUPERADMIN_LOCK_TIMEOUT : 120;
        $pdo->prepare("
            UPDATE super_admin_lock
            SET active_user_id = ?,
                login_log_id   = ?,
                acquired_at    = NOW(),
                expires_at     = DATE_ADD(NOW(), INTERVAL ? SECOND)
            WHERE id = 1
        ")->execute([$user['id'], $logId, $lockTimeout]);

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

logActivity($user['id'], 'LOGIN', "User '{$user['username']}' logged in successfully");

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

