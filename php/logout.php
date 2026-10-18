<?php
require_once 'config.php';

// Prevent caching of protected pages and prevent back-button retrieval
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$db = new Database();

$isSuperAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin');
$userId = $_SESSION['user_id'] ?? null;
$uName = $_SESSION['username'] ?? 'User';

// Close login_log entry
if (isset($_SESSION['login_log_id'])) {
    $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE id = ? AND time_out IS NULL")
       ->execute([$_SESSION['login_log_id']]);
}

// ── Check for Confirmed Pending Superadmin Handover Deactivation ─────────
$handoverDeactivated = false;
$newSaUsername = '';
if ($userId && $isSuperAdmin) {
    $hoStmt = $db->prepare("
        SELECT h.*, u.username AS new_sa_username 
        FROM superadmin_handover_settings h
        JOIN users u ON h.new_superadmin_id = u.id
        WHERE h.initiator_user_id = ? AND h.deactivate_on_logout = 1 AND h.status = 'pending'
        ORDER BY h.id DESC LIMIT 1
    ");
    $hoStmt->execute([$userId]);
    $pendingHandover = $hoStmt->fetch(PDO::FETCH_ASSOC);

    if ($pendingHandover) {
        $handoverDeactivated = true;
        $newSaUsername = $pendingHandover['new_sa_username'] ?? 'new Superadmin';

        $pdo = $db->getConnection();
        $pdo->beginTransaction();
        try {
            // 1. Mark handover setting as executed with timestamp
            $pdo->prepare("UPDATE superadmin_handover_settings SET status = 'executed', executed_at = NOW() WHERE id = ?")
                ->execute([$pendingHandover['id']]);

            // 2. Set former Superadmin account status to Inactive and role to Admin (former Superadmin)
            $defaultAdminPrivs = json_encode([
                'change_password',
                'profile_management',
                'view_accounts',
                'edit_manage_accounts',
                'block_unblock_users',
                'registration_approval',
                'account_deletion_requests'
            ]);

            $pdo->prepare("
                UPDATE users 
                SET status = 'inactive', 
                    is_active = 0, 
                    role = 'admin', 
                    is_former_superadmin = 1,
                    privileges = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ")->execute([$defaultAdminPrivs, $userId]);

            // 3. Invalidate ALL open sessions for the former Superadmin
            $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
                ->execute([$userId]);

            // 4. Release active session lock
            $pdo->prepare("
                UPDATE super_admin_lock 
                SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL 
                WHERE id = 1
            ")->execute();

            // 5. Record detailed handover deactivation audit log
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $deactReason = "Authorized handover deactivation after creating new Superadmin '@{$newSaUsername}'";
            $deactDetails = "Former Superadmin '{$uName}' logged out with confirmed handover. Account marked Inactive, role assigned as Admin, and all sessions invalidated.";

            $pdo->prepare("
                INSERT INTO activity_logs (user_id, target_user_id, action, details, reason, outcome, previous_state, new_state, ip_address, created_at)
                VALUES (?, ?, 'SUPERADMIN_HANDOVER_DEACTIVATION', ?, ?, 'SUCCESS', 'active', 'inactive', ?, NOW())
            ")->execute([
                $userId,
                $userId,
                $deactDetails,
                $deactReason,
                $clientIp
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}

// If Super Admin and NOT already released above, release active session lock
if (!$handoverDeactivated && ($isSuperAdmin || (isset($userId) && (int)$userId === 1))) {
    $db->prepare(
        "UPDATE super_admin_lock
         SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL
         WHERE id = 1"
    )->execute();
}

// Record ordinary logout in audit logs (only if NOT handover deactivated)
if ($userId && !$handoverDeactivated) {
    if ($isSuperAdmin) {
        logActivity($userId, 'SUPERADMIN_LOGOUT', "Superadmin '{$uName}' logged out safely. Account remains Active.", null, null, null, 'SUCCESS');
    } else {
        logActivity($userId, 'LOGOUT', "User '{$uName}' logged out", null, null, null, 'SUCCESS');
    }
}

// Clear remember me cookie securely
if (isset($_COOKIE['remember_token'])) {
    $params = session_get_cookie_params();
    setcookie('remember_token', '', time() - 3600, '/', $params['domain'], $params['secure'], true);
    unset($_COOKIE['remember_token']);
}

// Clear session cookie securely
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Use the proper session cleanup function
endSession();

// Redirect to login page
$redirectUrl = '../pages/login.php';
if ($handoverDeactivated) {
    $redirectUrl .= '?notice=handover_deactivated';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'rights_transferred') {
    $redirectUrl .= '?notice=rights_transferred';
}
header('Location: ' . $redirectUrl);
exit;



