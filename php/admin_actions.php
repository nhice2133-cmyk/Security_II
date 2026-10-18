<?php
require_once 'config.php';

if (!validateSession() || !isset($_SESSION['role'])) {
    sendJsonResponse(false, 'Unauthorized');
}

// CSRF protection: apply to all state-changing (POST) admin actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    requireCsrf();
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$db = new Database();

/**
 * requireSuperAdmin()
 * Ensures the calling session is the ONE currently active Super Admin.
 * Checks both the role and that this session's login_log_id matches
 * the super_admin_lock holder — preventing stale/replayed SA sessions.
 */
function requireSuperAdmin(): void {
    global $db, $role;
    if ($role !== 'super_admin') {
        sendJsonResponse(false, 'Access denied. Super Admin only.');
    }
    // Verify this session owns the active lock
    $logId = $_SESSION['login_log_id'] ?? null;
    if (!$logId) {
        sendJsonResponse(false, 'Access denied. No active Super Admin session.');
    }
    $stmt = $db->prepare("SELECT login_log_id FROM super_admin_lock WHERE id = 1");
    $stmt->execute();
    $lock = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lock || (int)$lock['login_log_id'] !== (int)$logId) {
        sendJsonResponse(false, 'Access denied. Your Super Admin session is no longer active.');
    }
}

/**
 * requireSuperAdminWithPassword()
 * Extends requireSuperAdmin() by also verifying the SA re-entered their password.
 * Expects `action_confirm_password` in POST for write/destructive actions.
 */
function requireSuperAdminWithPassword(): void {
    requireSuperAdmin(); // all lock checks first
    requireActionPassword(); // verifies the password
}

/**
 * requireActionPassword()
 * Verifies the caller re-entered their password. Works for both Admin and Super Admin.
 * Expects `action_confirm_password` in POST.
 */
function requireActionPassword(): void {
    global $db, $userId;
    $confirmPwd = $_POST['action_confirm_password'] ?? '';
    if (empty($confirmPwd)) {
        sendJsonResponse(false, 'Password confirmation required.', ['needs_password' => true]);
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !verifyPassword($confirmPwd, $row['password'])) {
        sendJsonResponse(false, 'Incorrect password. Action denied.', ['wrong_password' => true]);
    }
}

/**
 * requirePrivilege()
 * Ensures the caller is either a Super Admin (with an active lock) OR an Admin with the given privilege.
 */
function requirePrivilege(string $privilegeName): void {
    global $db, $userId, $role, $privs; // $privs is populated below
    if ($role === 'super_admin') {
        requireSuperAdmin();
        return;
    }
    
    // Fallback if called before $privs is defined
    if (!isset($privs)) {
        $stmt = $db->prepare("SELECT privileges FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $privsStr = $stmt->fetchColumn();
        $loadedPrivs = $privsStr ? json_decode($privsStr, true) : [];
        if (!is_array($loadedPrivs)) $loadedPrivs = [];
        if (!in_array($privilegeName, $loadedPrivs)) {
            sendJsonResponse(false, 'Access denied. Missing required privilege.');
        }
        return;
    }

    if (!is_array($privs) || !in_array($privilegeName, $privs)) {
        sendJsonResponse(false, 'Access denied. Missing required privilege.');
    }
}

function requirePrivilegeWithPassword(string $privilegeName): void {
    requirePrivilege($privilegeName);
    requireActionPassword();
}

// Load privileges for admin
$privs = [];
if ($role === 'admin') {
    $stmtPriv = $db->prepare("SELECT privileges FROM users WHERE id = ?");
    $stmtPriv->execute([$userId]);
    $privUser = $stmtPriv->fetch();
    $privs = $privUser && $privUser['privileges'] ? json_decode($privUser['privileges'], true) : [];
    if (!is_array($privs)) $privs = [];
}

if ($action === 'get_dashboard_stats') {
    $statsStmt = $db->query("
        SELECT 
            COUNT(*) AS total_users,
            SUM(CASE WHEN (status IN ('approved', 'active')) AND (is_active = 1 OR is_active IS NULL) THEN 1 ELSE 0 END) AS active_users,
            SUM(CASE WHEN status = 'inactive' OR is_active = 0 THEN 1 ELSE 0 END) AS inactive_users,
            SUM(CASE WHEN status IN ('suspended', 'blocked') THEN 1 ELSE 0 END) AS suspended_users,
            SUM(CASE WHEN status IN ('pending', 'pending_setup') THEN 1 ELSE 0 END) AS pending_users,
            SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS sa_count,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_count,
            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS user_count
        FROM users
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $delCountStmt = $db->query("SELECT COUNT(*) FROM delete_requests WHERE status = 'pending'");
    $pendingDel = (int)$delCountStmt->fetchColumn();

    $todayLogsStmt = $db->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    $todayLogs = (int)$todayLogsStmt->fetchColumn();

    $recentLogsStmt = $db->query("
        SELECT a.id, a.action, a.details, a.created_at, u.username, u.first_name, u.last_name, u.role
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.id DESC
        LIMIT 6
    ");
    $recentLogs = $recentLogsStmt->fetchAll(PDO::FETCH_ASSOC);

    sendJsonResponse(true, 'Stats loaded', [
        'stats' => $stats,
        'pending_deletions' => $pendingDel,
        'today_logs' => $todayLogs,
        'recent_logs' => $recentLogs
    ]);
}

if ($action === 'get_users') {
    if ($role !== 'super_admin' && $role !== 'admin') {
        sendJsonResponse(false, 'Permission denied');
    }
    if ($role === 'admin' && !in_array('view_accounts', $privs)) {
        sendJsonResponse(false, 'Denied. Missing view_accounts privilege.');
    }
    
    $filterEmpId = sanitizeInput($_GET['emp_id'] ?? '');
    $filterRole = sanitizeInput($_GET['role'] ?? $_GET['role_filter'] ?? '');
    $filterStatus = sanitizeInput($_GET['status'] ?? $_GET['status_filter'] ?? '');
    $page = (int)($_GET['page'] ?? 1);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit > 1000) $limit = 1000;
    if ($limit < 1) $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Retrieve current active Super Admin lock holder
    $lockStmt = $db->prepare("SELECT active_user_id, login_log_id, expires_at FROM super_admin_lock WHERE id = 1");
    $lockStmt->execute();
    $saLock = $lockStmt->fetch(PDO::FETCH_ASSOC);
    $activeSaUserId = null;
    if ($saLock && $saLock['active_user_id'] !== null) {
        if ($saLock['expires_at'] === null || strtotime($saLock['expires_at']) >= time()) {
            $activeSaUserId = (int)$saLock['active_user_id'];
        }
    }
    
    // Query ALL accounts: Current Superadmin, Former Superadmins, Admins, and Users
    $query = "SELECT id, id_number, username, first_name, last_name, role, status, is_active, is_former_superadmin, privileges, email, phone_number, created_at, last_login FROM users WHERE 1=1";
    $params = [];
    
    if ($filterEmpId) {
        $query .= " AND (id_number LIKE ? OR username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $params[] = "%$filterEmpId%";
        $params[] = "%$filterEmpId%";
        $params[] = "%$filterEmpId%";
        $params[] = "%$filterEmpId%";
        $params[] = "%$filterEmpId%";
    }

    if ($filterRole) {
        $query .= " AND role = ?";
        $params[] = $filterRole;
    }

    if ($filterStatus) {
        if ($filterStatus === 'active') {
            $query .= " AND status IN ('approved', 'active') AND (is_active = 1 OR is_active IS NULL)";
        } elseif ($filterStatus === 'inactive') {
            $query .= " AND (status = 'inactive' OR is_active = 0)";
        } elseif ($filterStatus === 'suspended') {
            $query .= " AND status IN ('suspended', 'blocked')";
        } elseif ($filterStatus === 'pending') {
            $query .= " AND status IN ('pending', 'pending_setup')";
        }
    }
    
    // Count total
    $countQuery = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Order: Current Super Admin first, then Former Super Admins, then Admins, then Users, then newest
    $query .= " ORDER BY (CASE WHEN role = 'super_admin' THEN 1 WHEN is_former_superadmin = 1 THEN 2 WHEN role = 'admin' THEN 3 ELSE 4 END) ASC, created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rawUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count eligible successors (Active Admins, excluding self)
    $succStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id != ? AND status IN ('approved', 'active') AND (is_active = 1 OR is_active IS NULL)");
    $succStmt->execute([$userId]);
    $eligibleCount = (int)$succStmt->fetchColumn();

    $currentSuperadminUser = null;
    $users = [];
    foreach ($rawUsers as $u) {
        $uid = (int)$u['id'];
        $uRole = $u['role'] ?? 'user';
        $uStatus = $u['status'] ?? 'pending';
        $uIsActive = (int)($u['is_active'] ?? 1);
        $isFormerSa = ((int)($u['is_former_superadmin'] ?? 0) === 1);
        $isCurrentSa = ($uRole === 'super_admin');

        // Normalize status representation for frontend
        $displayStatus = 'pending';
        if ($uStatus === 'inactive' || $uIsActive === 0) {
            $displayStatus = 'inactive';
        } elseif ($uStatus === 'suspended' || $uStatus === 'blocked') {
            $displayStatus = 'suspended';
        } elseif ($uStatus === 'approved' || $uStatus === 'active') {
            $displayStatus = 'active';
        } elseif ($uStatus === 'pending_setup') {
            $displayStatus = 'pending_setup';
        } else {
            $displayStatus = 'pending';
        }

        if ($isCurrentSa) {
            $currentSuperadminUser = [
                'id' => $uid,
                'username' => $u['username'],
                'full_name' => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))
            ];
        }

        $users[] = [
            'id' => $uid,
            'id_number' => $u['id_number'] ?: '----',
            'username' => $u['username'],
            'first_name' => $u['first_name'] ?? '',
            'last_name' => $u['last_name'] ?? '',
            'email' => $u['email'] ?? '',
            'phone_number' => $u['phone_number'] ?? '',
            'role' => $uRole,
            'status' => $uStatus,
            'display_status' => $displayStatus,
            'is_active' => $uIsActive,
            'is_former_superadmin' => $isFormerSa,
            'is_current_superadmin' => $isCurrentSa,
            'is_active_session' => ($activeSaUserId !== null && $uid === $activeSaUserId),
            'is_me' => ($uid === (int)$userId),
            'is_eligible_successor' => ($uRole === 'admin' && in_array($displayStatus, ['active']) && $uid !== (int)$userId),
            'created_at' => $u['created_at'],
            'last_login' => $u['last_login'],
            'privileges' => $u['privileges']
        ];
    }
    
    sendJsonResponse(true, 'Success', [
        'users' => $users,
        'total' => $total,
        'page' => $page,
        'pages' => (int)ceil($total / $limit),
        'limit' => $limit,
        'current_superadmin' => $currentSuperadminUser,
        'eligible_successors_count' => $eligibleCount,
        'my_role' => $role,
        'my_id' => (int)$userId
    ]);
}

if ($action === 'get_eligible_successors') {
    requireSuperAdmin();
    
    $stmt = $db->prepare("
        SELECT id, id_number, username, first_name, last_name, email, role, status, is_former_superadmin
        FROM users 
        WHERE LOWER(role) = 'admin' 
          AND id != ? 
          AND LOWER(status) IN ('approved', 'active') 
          AND (is_active = 1 OR is_active IS NULL)
        ORDER BY first_name ASC, username ASC
    ");
    $stmt->execute([$userId]);
    $successors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(true, 'Eligible successors loaded', [
        'successors' => $successors,
        'count' => count($successors)
    ]);
}

if ($action === 'transfer_superadmin_role') {
    requireSuperAdminWithPassword();
    
    $successorId = (int)($_POST['successor_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    $outgoingStatus = sanitizeInput($_POST['outgoing_status'] ?? 'active'); // 'active' or 'inactive'
    $confirmTransfer = isset($_POST['confirm_transfer']) && ($_POST['confirm_transfer'] === 'true' || $_POST['confirm_transfer'] === '1');
    
    if ($successorId <= 0) {
        sendJsonResponse(false, 'Please select an eligible Administrator to receive Superadmin privileges.');
    }
    if ($successorId === (int)$userId) {
        sendJsonResponse(false, 'You cannot transfer Superadmin rights to yourself.');
    }
    if (strlen($reason) < 5) {
        sendJsonResponse(false, 'Please provide a formal reason for stepping down / transferring Superadmin rights (minimum 5 characters).');
    }
    if (!in_array($outgoingStatus, ['active', 'inactive'], true)) {
        $outgoingStatus = 'active';
    }
    if (!$confirmTransfer) {
        sendJsonResponse(false, 'Explicit confirmation is required to execute the Superadmin succession.');
    }
    
    $pdo = $db->getConnection();
    $pdo->beginTransaction();
    try {
        // 1. Lock and verify acting user (current Superadmin)
        $stmtMe = $pdo->prepare("SELECT id, role, username, first_name, last_name FROM users WHERE id = ? FOR UPDATE");
        $stmtMe->execute([$userId]);
        $me = $stmtMe->fetch(PDO::FETCH_ASSOC);
        
        if (!$me || $me['role'] !== 'super_admin') {
            $pdo->rollBack();
            sendJsonResponse(false, 'Access denied. You do not hold the active Superadmin role.');
        }
        
        // 2. Lock and verify successor account
        $stmtSucc = $pdo->prepare("SELECT id, id_number, username, first_name, last_name, role, status, is_active FROM users WHERE id = ? FOR UPDATE");
        $stmtSucc->execute([$successorId]);
        $successor = $stmtSucc->fetch(PDO::FETCH_ASSOC);
        
        if (!$successor) {
            $pdo->rollBack();
            sendJsonResponse(false, 'Selected successor account was not found.');
        }
        if ($successor['role'] !== 'admin') {
            $pdo->rollBack();
            sendJsonResponse(false, 'The successor must be an authorized Administrator. Selected account has role: ' . $successor['role']);
        }
        if (!in_array($successor['status'], ['approved', 'active']) || (int)($successor['is_active'] ?? 1) !== 1) {
            $pdo->rollBack();
            sendJsonResponse(false, 'The successor account is Inactive, Suspended, or Pending. Only an Active Administrator can become Superadmin.');
        }
        
        // 3. Promote successor to Superadmin (master clearance, privileges column reset)
        $pdo->prepare("UPDATE users SET role = 'super_admin', privileges = NULL, updated_at = NOW() WHERE id = ?")
            ->execute([$successorId]);
            
        // 4. Update outgoing Superadmin account:
        //    Assign approved lower-level role (Admin) with full default admin privileges.
        //    If deactivation explicitly requested, set status to 'inactive' & is_active = 0.
        //    Otherwise keep account Active with role 'admin'.
        //    Mark is_former_superadmin = 1.
        $newOutgoingStatus = ($outgoingStatus === 'inactive') ? 'inactive' : 'approved';
        $newOutgoingIsActive = ($outgoingStatus === 'inactive') ? 0 : 1;
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
            SET role = 'admin', 
                status = ?, 
                is_active = ?, 
                is_former_superadmin = 1, 
                privileges = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ")->execute([$newOutgoingStatus, $newOutgoingIsActive, $defaultAdminPrivs, $userId]);
        
        // 5. Release super_admin_lock so the incoming Super Admin can acquire it on login
        $pdo->prepare("
            UPDATE super_admin_lock 
            SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL 
            WHERE id = 1
        ")->execute();
        
        // 6. Immediately invalidate all active sessions of the former Superadmin
        $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
            ->execute([$userId]);
            
        // 7. Insert succession record in superadmin_succession_logs
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pdo->prepare("
            INSERT INTO superadmin_succession_logs 
            (outgoing_superadmin_id, incoming_superadmin_id, reason, former_superadmin_status, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([$userId, $successorId, $reason, $newOutgoingStatus, $clientIp]);
        
        // 8. Log the complete transfer in activity_logs
        $pdo->prepare("
            INSERT INTO activity_logs 
            (user_id, target_user_id, action, details, reason, outcome, previous_state, new_state, ip_address, created_at) 
            VALUES (?, ?, 'SUPERADMIN_TRANSFER', ?, ?, 'SUCCESS', 'super_admin', 'admin', ?, NOW())
        ")->execute([
            $userId, 
            $successorId, 
            "Transferred Superadmin role from '{$me['username']}' to '{$successor['username']}'. Former Superadmin assigned role Admin (Status: {$newOutgoingStatus}).", 
            $reason, 
            $clientIp
        ]);
        
        // 9. Verify successor successfully holds Superadmin role before commit
        $succCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $succCheck->execute([$successorId]);
        if ($succCheck->fetchColumn() !== 'super_admin') {
            $pdo->rollBack();
            sendJsonResponse(false, 'Succession failed: Successor account was not granted Superadmin role. Rolling back.');
        }
        
        $pdo->commit();
        
        // Terminate outgoing Superadmin's session immediately
        endSession();
        
        sendJsonResponse(true, 'Superadmin succession executed successfully. You will now be redirected to the sign in page.', [
            'transferred' => true,
            'redirect' => '../pages/login.php?notice=rights_transferred'
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Superadmin transfer exception: ' . $e->getMessage());
        sendJsonResponse(false, 'Superadmin transfer failed: ' . $e->getMessage());
    }
}

if ($action === 'deactivate_account') {
    requireActionPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID.');
    if ($targetId === (int)$userId) sendJsonResponse(false, 'You cannot deactivate your own account.');
    if (strlen($reason) < 3) sendJsonResponse(false, 'A reason for deactivation is required (minimum 3 characters).');
    
    $stmt = $db->prepare("SELECT id, username, role, status, is_active FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'Target account not found.');
    
    // Cannot deactivate the currently active Superadmin account
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'The active Superadmin account cannot be deactivated. You must transfer the Superadmin role to an eligible successor first.');
    }
    
    // Admin privilege check
    if ($role === 'admin') {
        if (!in_array('block_unblock_users', $privs)) {
            sendJsonResponse(false, 'Denied. Missing block_unblock_users privilege.');
        }
        if ($targetUser['role'] === 'admin') {
            sendJsonResponse(false, 'Admins cannot deactivate other Administrator accounts.');
        }
    }
    
    $prevStatus = $targetUser['status'];
    $db->prepare("UPDATE users SET status = 'inactive', is_active = 0, updated_at = NOW() WHERE id = ?")
       ->execute([$targetId]);
       
    // Invalidate target user's active sessions immediately
    $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
       ->execute([$targetId]);
       
    logActivity(
        $userId, 
        'ACCOUNT_DEACTIVATED', 
        "Deactivated account '{$targetUser['username']}' [{$targetUser['role']}]. Reason: {$reason}", 
        null, 
        $targetId, 
        $reason, 
        'SUCCESS', 
        $prevStatus, 
        'inactive'
    );
    
    sendJsonResponse(true, "Account '{$targetUser['username']}' has been deactivated.");
}

if ($action === 'reactivate_account') {
    requireActionPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID.');
    if (strlen($reason) < 3) sendJsonResponse(false, 'A reason for reactivation is required (minimum 3 characters).');
    
    $stmt = $db->prepare("SELECT id, username, role, status, is_active, is_former_superadmin FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'Target account not found.');
    
    // Admin privilege check: Admins cannot reactivate Admin or Former Superadmin accounts
    if ($role === 'admin') {
        if (!in_array('block_unblock_users', $privs)) {
            sendJsonResponse(false, 'Denied. Missing block_unblock_users privilege.');
        }
        if ($targetUser['role'] === 'admin' || (int)$targetUser['is_former_superadmin'] === 1) {
            sendJsonResponse(false, 'Only the Superadmin can reactivate an Administrator or Former Superadmin account.');
        }
    }
    
    // CRITICAL SECURITY RULE:
    // Reactivating a former Superadmin (or any user) restores their account to Active status
    // with their approved non-Superadmin role (e.g. Admin or User).
    // It must NEVER automatically restore the Superadmin role.
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'Target account is already marked as Superadmin.');
    }
    
    $prevStatus = $targetUser['status'];
    $db->prepare("UPDATE users SET status = 'approved', is_active = 1, updated_at = NOW() WHERE id = ?")
       ->execute([$targetId]);

    // Update superadmin_handover_settings if this account was deactivated via handover
    $db->prepare("
        UPDATE superadmin_handover_settings 
        SET reactivated_at = NOW(), reactivated_by = ?, reactivation_reason = ? 
        WHERE initiator_user_id = ? AND reactivated_at IS NULL
    ")->execute([$userId, $reason, $targetId]);
       
    logActivity(
        $userId, 
        'ACCOUNT_REACTIVATED', 
        "Reactivated account '{$targetUser['username']}' [{$targetUser['role']}]. Role remains '{$targetUser['role']}'. Reason: {$reason}", 
        null, 
        $targetId, 
        $reason, 
        'SUCCESS', 
        $prevStatus, 
        'active'
    );
    
    sendJsonResponse(true, "Account '{$targetUser['username']}' has been successfully reactivated with role " . strtoupper($targetUser['role']) . ".");
}

if ($action === 'suspend_account') {
    requireActionPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID.');
    if ($targetId === (int)$userId) sendJsonResponse(false, 'You cannot suspend your own account.');
    if (strlen($reason) < 3) sendJsonResponse(false, 'A reason for suspension is required (minimum 3 characters).');
    
    $stmt = $db->prepare("SELECT id, username, role, status, is_active FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'Target account not found.');
    
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'The active Superadmin account cannot be suspended. You must transfer the Superadmin role to an eligible successor first.');
    }
    
    if ($role === 'admin') {
        if (!in_array('block_unblock_users', $privs)) {
            sendJsonResponse(false, 'Denied. Missing block_unblock_users privilege.');
        }
        if ($targetUser['role'] === 'admin') {
            sendJsonResponse(false, 'Admins cannot suspend other Administrator accounts.');
        }
    }
    
    $prevStatus = $targetUser['status'];
    $db->prepare("UPDATE users SET status = 'suspended', is_active = 0, updated_at = NOW() WHERE id = ?")
       ->execute([$targetId]);
       
    // Invalidate target user's active sessions immediately
    $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
       ->execute([$targetId]);
       
    logActivity(
        $userId, 
        'ACCOUNT_SUSPENDED', 
        "Suspended account '{$targetUser['username']}' [{$targetUser['role']}]. Reason: {$reason}", 
        null, 
        $targetId, 
        $reason, 
        'SUCCESS', 
        $prevStatus, 
        'suspended'
    );
    
    sendJsonResponse(true, "Account '{$targetUser['username']}' has been suspended.");
}

if ($action === 'restore_suspended_account') {
    requireActionPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID.');
    if (strlen($reason) < 3) sendJsonResponse(false, 'A reason for restoring this suspended account is required.');
    
    $stmt = $db->prepare("SELECT id, username, role, status, is_active FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'Target account not found.');
    
    if ($role === 'admin') {
        if (!in_array('block_unblock_users', $privs)) {
            sendJsonResponse(false, 'Denied. Missing block_unblock_users privilege.');
        }
        if ($targetUser['role'] === 'admin') {
            sendJsonResponse(false, 'Admins cannot restore Administrator accounts.');
        }
    }
    
    $prevStatus = $targetUser['status'];
    $db->prepare("UPDATE users SET status = 'approved', is_active = 1, updated_at = NOW() WHERE id = ?")
       ->execute([$targetId]);
       
    logActivity(
        $userId, 
        'ACCOUNT_RESTORED', 
        "Restored suspended account '{$targetUser['username']}'. Reason: {$reason}", 
        null, 
        $targetId, 
        $reason, 
        'SUCCESS', 
        $prevStatus, 
        'active'
    );
    
    sendJsonResponse(true, "Account '{$targetUser['username']}' has been restored to Active status.");
}

if ($action === 'get_user_details') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    if ($role === 'admin' && !in_array('view_accounts', $privs)) sendJsonResponse(false, 'Denied. Missing privilege.');
    
    $targetId = (int)($_GET['target_id'] ?? $_POST['target_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid user ID.');
    
    $stmt = $db->prepare("
        SELECT id, id_number, username, email, phone_number, first_name, last_name, middle_name, extension,
               birth_date, age, sex, purok_street, barangay, city, province, country, zip_code, address,
               role, status, is_active, is_former_superadmin, last_login, created_at, updated_at, privileges
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$targetId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) sendJsonResponse(false, 'User not found.');
    
    // Check if user is the current active Superadmin
    $isCurrentSa = ($u['role'] === 'super_admin');
    $isFormerSa = ((int)$u['is_former_superadmin'] === 1);
    
    // Fetch succession history if relevant
    $successions = [];
    $succStmt = $db->prepare("
        SELECT s.id, s.outgoing_superadmin_id, s.incoming_superadmin_id, s.reason, s.former_superadmin_status, s.created_at,
               out_u.username as outgoing_username, in_u.username as incoming_username
        FROM superadmin_succession_logs s
        LEFT JOIN users out_u ON s.outgoing_superadmin_id = out_u.id
        LEFT JOIN users in_u ON s.incoming_superadmin_id = in_u.id
        WHERE s.outgoing_superadmin_id = ? OR s.incoming_superadmin_id = ?
        ORDER BY s.id DESC
    ");
    $succStmt->execute([$targetId, $targetId]);
    $successions = $succStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $privList = $u['privileges'] ? json_decode($u['privileges'], true) : [];
    if (!is_array($privList)) $privList = [];
    
    sendJsonResponse(true, 'User details loaded', [
        'user' => $u,
        'is_current_superadmin' => $isCurrentSa,
        'is_former_superadmin' => $isFormerSa,
        'privileges_list' => $privList,
        'succession_history' => $successions
    ]);
}

if ($action === 'get_user_audit_history') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    if ($role === 'admin' && !in_array('view_accounts', $privs)) sendJsonResponse(false, 'Denied. Missing privilege.');
    
    $targetId = (int)($_GET['target_id'] ?? $_POST['target_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid user ID.');
    
    $stmt = $db->prepare("
        SELECT a.id, a.user_id, a.target_user_id, a.action, a.details, a.reason, a.outcome, a.previous_state, a.new_state, a.created_at,
               u.username as actor_username, u.role as actor_role,
               tu.username as target_username
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN users tu ON a.target_user_id = tu.id
        WHERE a.user_id = ? OR a.target_user_id = ?
        ORDER BY a.id DESC
        LIMIT 50
    ");
    $stmt->execute([$targetId, $targetId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(true, 'Audit history loaded', [
        'logs' => $logs,
        'target_id' => $targetId
    ]);
}

if ($action === 'update_user_status') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    if ($role === 'super_admin') requireSuperAdminWithPassword();
    if ($role === 'admin') requireActionPassword();
    
    $targetId = (int)($_POST['target_id'] ?? $_POST['user_id'] ?? 0);
    if ($targetId === 0) sendJsonResponse(false, 'Invalid target');
    if ((int)$targetId === (int)$userId) sendJsonResponse(false, 'You cannot change your own status.');
    
    $status = $_POST['status'] ?? ''; // active, approved, inactive, suspended, blocked, pending
    if (!in_array($status, ['active', 'approved', 'inactive', 'suspended', 'blocked', 'pending'])) {
        sendJsonResponse(false, 'Invalid status.');
    }
    
    $stmtTarget = $db->prepare("SELECT id, role, status, username, is_active FROM users WHERE id = ?");
    $stmtTarget->execute([$targetId]);
    $targetUser = $stmtTarget->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'User not found.');
    
    // Prevent modifying the active Superadmin account status
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'Denied. The active Superadmin account status cannot be modified.');
    }
    
    if ($role === 'admin') {
        if (!in_array('block_unblock_users', $privs)) {
            sendJsonResponse(false, 'Denied. Missing block_unblock_users privilege.');
        }
        if ($targetUser['role'] === 'admin') {
            sendJsonResponse(false, 'Denied. Admins cannot modify the status of other Admin accounts.');
        }
    }
    
    $isActive = in_array($status, ['active', 'approved']) ? 1 : 0;
    $db->prepare("UPDATE users SET status = ?, is_active = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$status, $isActive, $targetId]);
       
    if ($isActive === 0) {
        $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")
           ->execute([$targetId]);
    }
    
    logActivity($userId, 'STATUS_UPDATED', "Updated status of '{$targetUser['username']}' from '{$targetUser['status']}' to '{$status}'", null, $targetId, null, 'SUCCESS', $targetUser['status'], $status);
    sendJsonResponse(true, 'Status updated successfully.');
}

if ($action === 'update_user_role') {
    requireSuperAdminWithPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $newRole = $_POST['role'] ?? '';
    
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID.');
    if (!in_array($newRole, ['super_admin', 'admin', 'user'], true)) sendJsonResponse(false, 'Invalid role.');
    
    $stmtTarget = $db->prepare("SELECT id, role, username FROM users WHERE id = ?");
    $stmtTarget->execute([$targetId]);
    $targetUser = $stmtTarget->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'User not found.');
    
    // Prevent modifying the active Superadmin account through ordinary role select
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'The active Superadmin role cannot be changed here. Use the formal Superadmin Role Transfer workflow.');
    }
    
    // CRITICAL: Prevent promoting to Superadmin via basic update_user_role!
    // Superadmin promotion MUST use the formal Succession workflow with all checks and reason.
    if ($newRole === 'super_admin') {
        sendJsonResponse(false, 'Superadmin role assignment is restricted. Please use the "Transfer Superadmin Role" workflow to appoint a successor.', [
            'use_transfer_workflow' => true
        ]);
    }
    
    if ($newRole === 'user') {
        $stmt = $db->prepare("UPDATE users SET role = ?, privileges = NULL, updated_at = NOW() WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
    }
    $stmt->execute([$newRole, $targetId]);
    
    logActivity($userId, 'ROLE_UPDATED', "Updated role for '{$targetUser['username']}' from '{$targetUser['role']}' to '{$newRole}'", null, $targetId, null, 'SUCCESS', $targetUser['role'], $newRole);
    sendJsonResponse(true, 'Role updated successfully.');
}

if ($action === 'get_user_privileges') {
    requireSuperAdmin();
    $targetId = (int)($_GET['target_id'] ?? $_POST['target_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, id_number, username, first_name, last_name, role, privileges FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetUser) sendJsonResponse(false, 'User not found');
    
    $privs = $targetUser['privileges'] ? json_decode($targetUser['privileges'], true) : [];
    if (!is_array($privs)) $privs = [];
    
    sendJsonResponse(true, 'Success', [
        'user' => $targetUser,
        'privileges' => $privs
    ]);
}

if ($action === 'update_user_privileges') {
    requireSuperAdminWithPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    $privilegesRaw = $_POST['privileges'] ?? '[]';
    $privileges = is_array($privilegesRaw) ? $privilegesRaw : json_decode($privilegesRaw, true);
    if (!is_array($privileges)) $privileges = [];

    // Privilege whitelist validation - all permitted system modules
    $allowedPrivs = [
        'registration_approval',
        'account_deletion_requests',
        'edit_manage_accounts',
        'block_unblock_users',
        'profile_management',
        'change_password',
        'view_accounts'
    ];
    $privileges = array_values(array_intersect($privileges, $allowedPrivs));
    
    $stmt = $db->prepare("SELECT id, id_number, role, username FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
    if (!$targetUser) sendJsonResponse(false, 'User not found');

    // Prevent modifying Super Admin permissions (master clearance)
    if ($targetUser['role'] === 'super_admin') {
        sendJsonResponse(false, 'Super Admin accounts hold permanent master clearance. Individual privileges cannot be modified.');
    }
    
    $privsJson = json_encode(array_values($privileges));
    $updateStmt = $db->prepare("UPDATE users SET privileges = ? WHERE id = ?");
    $updateStmt->execute([$privsJson, $targetId]);

    // Record privilege update in activity logs
    $privListDesc = empty($privileges) ? 'None (All Revoked)' : implode(', ', $privileges);
    logActivity($userId, 'PRIVILEGES_UPDATED', "Updated module permissions for '{$targetUser['username']}' [{$targetUser['role']}]: {$privListDesc}");
    
    sendJsonResponse(true, 'Privileges updated successfully for ' . $targetUser['username'], [
        'privileges_count' => count($privileges),
        'privileges' => $privileges
    ]);
}

if ($action === 'update_user_info') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    $targetId = (int)($_POST['target_id'] ?? 0);
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID');
    
    if ($role === 'admin') {
        if (!in_array('edit_manage_accounts', $privs)) sendJsonResponse(false, 'Denied. Missing edit_manage_accounts privilege.');
        requireActionPassword();
        
        // Prevent admin from editing a super admin
        $stmtTarget = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        if ($targetUser && $targetUser['role'] === 'super_admin') {
            sendJsonResponse(false, 'Denied. Admins cannot edit Super Admin accounts.');
        }
    }
    if ($role === 'super_admin') requireSuperAdminWithPassword();

    $firstName = trim(sanitizeInput($_POST['first_name'] ?? ''));
    $lastName = trim(sanitizeInput($_POST['last_name'] ?? ''));
    $email = trim(sanitizeInput($_POST['email'] ?? ''));
    $phone = trim(sanitizeInput($_POST['phone_number'] ?? ''));
    $newPassword = (string)($_POST['new_password'] ?? '');
    
    if ($firstName === '' || $lastName === '' || $email === '') {
        sendJsonResponse(false, 'First name, last name, and email are required');
    }
    if (strlen($firstName) > 100 || strlen($lastName) > 100) {
        sendJsonResponse(false, 'Name fields cannot exceed 100 characters');
    }
    if (strlen($email) > 100 || !validateEmail($email)) {
        sendJsonResponse(false, 'Please enter a valid email address');
    }

    // Check email uniqueness against other users
    $chkEmail = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ? LIMIT 1");
    $chkEmail->execute([strtolower($email), $targetId]);
    if ($chkEmail->fetch()) {
        sendJsonResponse(false, 'Email address is already in use by another account');
    }
    
    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $email, $phone, $targetId]);
    
    if ($role === 'super_admin' && trim($newPassword) !== '') {
        $pwdErr = validatePassword($newPassword);
        if ($pwdErr) {
            sendJsonResponse(false, $pwdErr);
        }
        $hashed = hashPassword($newPassword);
        $stmtPw = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtPw->execute([$hashed, $targetId]);
    }
    
    sendJsonResponse(true, 'User information updated successfully');
}

if ($action === 'delete_user') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    $reason = trim(sanitizeInput($_POST['reason'] ?? ''));
    if ($targetId <= 0) sendJsonResponse(false, 'Invalid target user ID');
    
    if ($role === 'super_admin') {
        requireSuperAdminWithPassword();
        // Direct delete
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        logActivity($userId, 'ACCOUNT_DELETED', "Directly deleted user ID {$targetId}");
        sendJsonResponse(true, 'User deleted');
    } elseif ($role === 'admin') {
        if (!in_array('account_deletion_requests', $privs)) sendJsonResponse(false, 'Denied. Missing account_deletion_requests privilege.');
        requireActionPassword();
        
        // Prevent admin from requesting deletion of a super admin
        $stmtTarget = $db->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        if ($targetUser && $targetUser['role'] === 'super_admin') {
            sendJsonResponse(false, 'Denied. Admins cannot request deletion of Super Admin accounts.');
        }
        
        // Request delete
        if ($reason === '') sendJsonResponse(false, 'Reason is required for delete request.');
        $stmt = $db->prepare("INSERT INTO delete_requests (target_user_id, requested_by_admin_id, reason) VALUES (?, ?, ?)");
        $stmt->execute([$targetId, $userId, $reason]);
        $tName = $targetUser['username'] ?? "ID #{$targetId}";
        logActivity($userId, 'DELETE_REQUESTED', "Requested deletion for account '{$tName}'. Reason: {$reason}");
        sendJsonResponse(true, 'Deletion request sent to Super Admin');
    } else {
        sendJsonResponse(false, 'Denied');
    }
}

if ($action === 'get_delete_requests') {
    requirePrivilege('account_deletion_requests');
    $stmt = $db->prepare("
        SELECT dr.id, dr.reason, dr.status, t.username as target_username, t.id_number as target_id_number, a.username as admin_username
        FROM delete_requests dr
        JOIN users t ON dr.target_user_id = t.id
        JOIN users a ON dr.requested_by_admin_id = a.id
        WHERE dr.status = 'pending'
    ");
    $stmt->execute();
    sendJsonResponse(true, 'Success', ['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'handle_delete_request') {
    requirePrivilegeWithPassword('account_deletion_requests');
    $reqId = $_POST['request_id'] ?? 0;
    $decision = $_POST['decision'] ?? ''; // approved, rejected
    
    $stmt = $db->prepare("SELECT target_user_id FROM delete_requests WHERE id = ?");
    $stmt->execute([$reqId]);
    $req = $stmt->fetch();
    if (!$req) sendJsonResponse(false, 'Not found');
    
    if ($decision === 'approved') {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$req['target_user_id']]);
        $db->prepare("UPDATE delete_requests SET status = 'approved' WHERE id = ?")->execute([$reqId]);
        sendJsonResponse(true, 'Request approved and user deleted');
    } else {
        $db->prepare("UPDATE delete_requests SET status = 'rejected' WHERE id = ?")->execute([$reqId]);
        sendJsonResponse(true, 'Request rejected');
    }
}

if ($action === 'get_logs') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit > 1000) $limit = 1000;
    if ($limit < 1) $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $filterMonth = sanitizeInput($_GET['month'] ?? '');
    $filterDate = sanitizeInput($_GET['date'] ?? '');
    $filterAction = sanitizeInput($_GET['action_filter'] ?? '');
    $filterSearch = sanitizeInput($_GET['search'] ?? '');
    
    $query = "SELECT a.id, a.user_id, a.target_user_id, a.action, a.details, a.reason, a.outcome, a.previous_state, a.new_state, a.ip_address, a.created_at, 
                     COALESCE(u.id_number, 'SYSTEM') as id_number, 
                     COALESCE(u.first_name, 'System') as first_name, 
                     COALESCE(u.last_name, '') as last_name, 
                     COALESCE(u.username, 'system') as username, 
                     COALESCE(u.role, 'system') as role,
                     tu.username as target_username,
                     tu.id_number as target_id_number,
                     tu.first_name as target_first_name,
                     tu.last_name as target_last_name
              FROM activity_logs a 
              LEFT JOIN users u ON a.user_id = u.id 
              LEFT JOIN users tu ON a.target_user_id = tu.id
              WHERE 1=1";
    $params = [];
    
    // Visibility
    if ($role === 'admin') {
        // Can view admin, users, and system actions (not super admin specific)
        $query .= " AND (u.role != 'super_admin' OR u.role IS NULL)";
    } elseif ($role === 'user') {
        // Can view only self
        $query .= " AND a.user_id = ?";
        $params[] = $userId;
    }
    
    // Filtering
    if ($filterAction) {
        $query .= " AND a.action = ?";
        $params[] = $filterAction;
    }
    if ($filterSearch) {
        $query .= " AND (u.username LIKE ? OR u.id_number LIKE ? OR a.details LIKE ? OR a.action LIKE ?)";
        $params[] = "%$filterSearch%";
        $params[] = "%$filterSearch%";
        $params[] = "%$filterSearch%";
        $params[] = "%$filterSearch%";
    }
    if ($filterMonth) {
        $query .= " AND DATE_FORMAT(a.created_at, '%Y-%m') = ?";
        $params[] = $filterMonth;
    }
    if ($filterDate) {
        $query .= " AND DATE(a.created_at) = ?";
        $params[] = $filterDate;
    }
    
    // Count total
    $countQuery = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    $query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    sendJsonResponse(true, 'Success', ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $limit), 'limit' => $limit]);
}

if ($action === 'check_username_email') {
    $username = strtolower(trim($_POST['username'] ?? ($_GET['username'] ?? '')));
    $email = strtolower(trim($_POST['email'] ?? ($_GET['email'] ?? '')));
    $uTaken = false;
    $eTaken = false;
    if ($username !== '') {
        $stmt = $db->prepare("SELECT 1 FROM users WHERE LOWER(username) = ? LIMIT 1");
        $stmt->execute([$username]);
        $uTaken = (bool)$stmt->fetch();
    }
    if ($email !== '') {
        $stmt = $db->prepare("SELECT 1 FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $eTaken = (bool)$stmt->fetch();
    }
    sendJsonResponse(true, 'Checked', ['username_taken' => $uTaken, 'email_taken' => $eTaken]);
}

if ($action === 'create_privileged_account' || $action === 'create_account') {
    requireSuperAdminWithPassword();
    
    $accountRole = sanitizeInput($_POST['account_role'] ?? $_POST['role'] ?? 'user');
    if (!in_array($accountRole, ['user', 'admin', 'super_admin'], true)) {
        sendJsonResponse(false, 'Invalid account role selected.');
    }
    
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (trim($password) === '') {
        $password = '@Abcde12345';
    }
    $customIdNumber = trim(sanitizeInput($_POST['id_number'] ?? ''));
    
    $privilegesRaw = $_POST['privileges'] ?? '[]';
    $privileges = is_array($privilegesRaw) ? $privilegesRaw : json_decode($privilegesRaw, true);
    if (!is_array($privileges)) {
        $privileges = [];
    }

    // Validation
    if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
        sendJsonResponse(false, 'Username must be between 3 and 50 characters.');
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        sendJsonResponse(false, 'Username can only contain letters, numbers, dots, underscores, and dashes.');
    }
    if ($email === '' || strlen($email) > 100 || !validateEmail($email)) {
        sendJsonResponse(false, 'Valid email address is required (maximum 100 characters).');
    }
    
    $pwdErr = validatePassword($password);
    if ($pwdErr) {
        sendJsonResponse(false, 'Password policy error: ' . $pwdErr);
    }

    // Check username uniqueness
    $chkStmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
    $chkStmt->execute([$username]);
    if ($chkStmt->fetch()) {
        sendJsonResponse(false, 'The username "' . $username . '" is already taken.');
    }

    // Check email uniqueness
    $chkEmail = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
    $chkEmail->execute([$email]);
    if ($chkEmail->fetch()) {
        sendJsonResponse(false, 'The email address "' . $email . '" is already registered.');
    }

    // Direct creation of Super Admin account: automatically grant full system clearance
    $deactivateOnLogout = false;
    if ($accountRole === 'super_admin') {
        $handoverConfirmed = isset($_POST['handover_confirmed']) && ($_POST['handover_confirmed'] === '1' || $_POST['handover_confirmed'] === 'true');
        if (!$handoverConfirmed) {
            sendJsonResponse(false, 'Explicit confirmation of Superadmin handover warnings is required before creating a Superadmin account.');
        }

        $deactivateOnLogout = isset($_POST['deactivate_on_logout']) && ($_POST['deactivate_on_logout'] === '1' || $_POST['deactivate_on_logout'] === 'true');

        $privileges = [
            'change_password',
            'profile_management',
            'view_accounts',
            'edit_manage_accounts',
            'block_unblock_users',
            'registration_approval',
            'account_deletion_requests'
        ];
    }

    // Handle ID Number
    $newIdNumber = null;
    $currentYear = date('Y');
    if (!empty($customIdNumber) && preg_match('/^\d{4}-\d{4}$/', $customIdNumber)) {
        $cstmt = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
        $cstmt->execute([$customIdNumber]);
        if (!$cstmt->fetch()) {
            $newIdNumber = $customIdNumber;
        }
    }
    if (!$newIdNumber) {
        for ($i = 0; $i < 50; $i++) {
            $candidate = $currentYear . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $cstmt = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
            $cstmt->execute([$candidate]);
            if (!$cstmt->fetch()) {
                $newIdNumber = $candidate;
                break;
            }
        }
    }
    if (!$newIdNumber) {
        $newIdNumber = $currentYear . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Hash password & store
    $hashedPassword = hashPassword($password);
    $privilegesJson = json_encode(array_values($privileges));

    $pdo = $db->getConnection();
    $pdo->beginTransaction();
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password, id_number,
                first_name, last_name, role, privileges, status
            ) VALUES (
                ?, ?, ?, ?,
                'Initial', 'Setup', ?, ?, 'pending_setup'
            )
        ");
        $insertStmt->execute([
            $username,
            $email,
            $hashedPassword,
            $newIdNumber,
            $accountRole,
            $privilegesJson
        ]);

        $newId = (int)$pdo->lastInsertId();

        if ($accountRole === 'super_admin') {
            // Cancel any previously pending handover settings for this initiator
            $pdo->prepare("UPDATE superadmin_handover_settings SET status = 'cancelled' WHERE initiator_user_id = ? AND status = 'pending'")
                ->execute([$userId]);

            // Insert new handover setting record
            $hoStatus = $deactivateOnLogout ? 'pending' : 'executed';
            $pdo->prepare("
                INSERT INTO superadmin_handover_settings 
                (initiator_user_id, new_superadmin_id, deactivate_on_logout, status, confirmed_at, created_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ")->execute([
                $userId,
                $newId,
                $deactivateOnLogout ? 1 : 0,
                $hoStatus
            ]);

            // Audit log for handover initiation
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $details = "Initiated Superadmin creation for '@{$username}' (ID: {$newIdNumber}). Handover deactivation on logout: " . ($deactivateOnLogout ? 'ENABLED' : 'DISABLED') . ".";
            $reason = $deactivateOnLogout ? 'Handover deactivation enabled upon logout' : 'Handover deactivation disabled';
            $pdo->prepare("
                INSERT INTO activity_logs (user_id, target_user_id, action, details, reason, outcome, ip_address, created_at)
                VALUES (?, ?, 'SUPERADMIN_HANDOVER_INITIATED', ?, ?, 'SUCCESS', ?, NOW())
            ")->execute([
                $userId,
                $newId,
                $details,
                $reason,
                $clientIp
            ]);
        } else {
            // Standard activity log for non-superadmin creation
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $pdo->prepare("
                INSERT INTO activity_logs (user_id, target_user_id, action, details, outcome, ip_address, created_at)
                VALUES (?, ?, 'ACCOUNT_CREATED', ?, 'SUCCESS', ?, NOW())
            ")->execute([
                $userId,
                $newId,
                "Created {$accountRole} account '{$username}' (ID: {$newIdNumber}, Email: {$email})",
                $clientIp
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(false, 'Failed to create account: ' . $e->getMessage());
    }

    sendJsonResponse(true, 'Account created successfully with status Pending Setup!', [
        'id' => $newId,
        'username' => $username,
        'email' => $email,
        'id_number' => $newIdNumber,
        'role' => $accountRole,
        'deactivate_on_logout' => $deactivateOnLogout,
        'transferred' => false
    ]);
}

// ─────────────────────────────────────────────────────────────
// get_super_admins — returns all SA accounts with Active/Offline
// status derived from super_admin_lock (Super Admin only)
// ─────────────────────────────────────────────────────────────
if ($action === 'get_super_admins') {
    requireSuperAdmin();

    // Get the current lock holder (if any)
    $lockStmt = $db->prepare("SELECT active_user_id, login_log_id, expires_at FROM super_admin_lock WHERE id = 1");
    $lockStmt->execute();
    $lock = $lockStmt->fetch(PDO::FETCH_ASSOC);

    $activeUserId = null;
    if ($lock && $lock['active_user_id'] !== null) {
        // Verify lock hasn't expired
        $expired = ($lock['expires_at'] !== null && strtotime($lock['expires_at']) < time());
        if (!$expired) {
            $activeUserId = (int)$lock['active_user_id'];
        }
    }

    $saStmt = $db->prepare(
        "SELECT id, id_number, username, first_name, last_name, status, last_login
         FROM users
         WHERE role = 'super_admin'
         ORDER BY id ASC"
    );
    $saStmt->execute();
    $superAdmins = $saStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($superAdmins as &$sa) {
        $sa['is_active_session'] = ($activeUserId !== null && (int)$sa['id'] === $activeUserId);
    }
    unset($sa);

    sendJsonResponse(true, 'Success', [
        'super_admins'   => $superAdmins,
        'active_user_id' => $activeUserId
    ]);
}

// ─────────────────────────────────────────────────────────────
// get_sa_lock_status — lightweight poll endpoint, any SA can call
// Returns who holds the lock without full SA list
// ─────────────────────────────────────────────────────────────
if ($action === 'get_sa_lock_status') {
    if ($role !== 'super_admin') sendJsonResponse(false, 'Denied');

    $lockStmt = $db->prepare(
        "SELECT sal.active_user_id, sal.expires_at,
                u.username, u.first_name, u.last_name
         FROM super_admin_lock sal
         LEFT JOIN users u ON sal.active_user_id = u.id
         WHERE sal.id = 1"
    );
    $lockStmt->execute();
    $lock = $lockStmt->fetch(PDO::FETCH_ASSOC);

    $isLocked = false;
    $holder   = null;
    if ($lock && $lock['active_user_id'] !== null) {
        $expired  = ($lock['expires_at'] !== null && strtotime($lock['expires_at']) < time());
        if (!$expired) {
            $isLocked = true;
            $holder   = [
                'user_id'    => (int)$lock['active_user_id'],
                'username'   => $lock['username'],
                'first_name' => $lock['first_name'],
                'last_name'  => $lock['last_name'],
            ];
        }
    }

    sendJsonResponse(true, 'Success', [
        'locked'  => $isLocked,
        'holder'  => $holder,
        'my_id'   => (int)$userId,
        'i_am_active' => ($holder !== null && (int)$holder['user_id'] === (int)$userId),
    ]);
}

// ─────────────────────────────────────────────────────────────
// verify_action_password — allows frontend to pre-verify password
// before submitting a complex destructive action (Admin & Super Admin)
// ─────────────────────────────────────────────────────────────
if ($action === 'verify_action_password') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    if ($role === 'super_admin') {
        requireSuperAdmin(); // checks lock
    }
    
    $confirmPwd = $_POST['action_confirm_password'] ?? '';
    if (empty($confirmPwd)) {
        sendJsonResponse(false, 'Password required.');
    }
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || !verifyPassword($confirmPwd, $row['password'])) {
        sendJsonResponse(false, 'Incorrect password.');
    }
    
    sendJsonResponse(true, 'Password verified.');
}

sendJsonResponse(false, 'Invalid action');
?>
