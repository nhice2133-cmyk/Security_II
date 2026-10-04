<?php
require_once 'config.php';

if (!validateSession() || !isset($_SESSION['role'])) {
    sendJsonResponse(false, 'Unauthorized');
}

// CSRF protection: apply to all state-changing (POST) admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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


if ($action === 'get_users') {
    if ($role !== 'super_admin' && $role !== 'admin') {
        sendJsonResponse(false, 'Permission denied');
    }
    if ($role === 'admin' && !in_array('view_accounts', $privs)) {
        sendJsonResponse(false, 'Denied. Missing view_accounts privilege.');
    }
    
    $filterEmpId = sanitizeInput($_GET['emp_id'] ?? '');
    $page = (int)($_GET['page'] ?? 1);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit > 1000) $limit = 1000;
    if ($limit < 1) $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT id, id_number, username, first_name, last_name, role, status, privileges FROM users WHERE id != ? AND role != 'super_admin'";
    $params = [$userId];
    
    if ($filterEmpId) {
        $query .= " AND id_number LIKE ?";
        $params[] = "%$filterEmpId%";
    }
    
    // Count total
    $countStmt = $db->prepare(str_replace("SELECT id, id_number, username, first_name, last_name, role, status, privileges", "SELECT COUNT(*)", $query));
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(true, 'Success', ['users' => $users, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'update_user_status') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    // All privileged actions require password confirmation
    if ($role === 'super_admin') requireSuperAdminWithPassword();
    if ($role === 'admin') requireActionPassword();
    $targetId = (int)($_POST['target_id'] ?? 0);
    if ($targetId === 0) sendJsonResponse(false, 'Invalid target');
    // Super Admin cannot change their own role this way (use transfer flow)
    if ((int)$targetId === (int)$userId) sendJsonResponse(false, 'You cannot change your own role.');
    $status = $_POST['status'] ?? ''; // pending, approved, blocked
    if (!in_array($status, ['pending', 'approved', 'blocked'])) sendJsonResponse(false, 'Invalid status');
    
    // Privilege check for admin role
    if ($role === 'admin') {
        $stmtTarget = $db->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        
        if ($targetUser && $targetUser['role'] === 'super_admin') {
            sendJsonResponse(false, 'Denied. Admins cannot modify the status of Super Admin accounts.');
        }

        $currentStatus = $targetUser ? $targetUser['status'] : '';

        // Check block_unblock_users privilege
        if ($status === 'blocked' || $currentStatus === 'blocked') {
            if (!in_array('block_unblock_users', $privs)) {
                sendJsonResponse(false, 'Denied. You do not have the privilege to block/unblock users.');
            }
        }
        
        // Check registration_approval privilege
        if ($currentStatus === 'pending' && $status === 'approved') {
            if (!in_array('registration_approval', $privs)) {
                sendJsonResponse(false, 'Denied. You do not have the privilege to approve registrations.');
            }
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $targetId]);

    // If user is being blocked, immediately invalidate all their active sessions
    // so they are kicked out even if currently logged in
    if ($status === 'blocked') {
        $stmt = $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL");
        $stmt->execute([$targetId]);
    }

    sendJsonResponse(true, 'Status updated');
}

if ($action === 'update_user_role') {
    requireSuperAdminWithPassword();
    $targetId = $_POST['target_id'] ?? 0;
    $newRole = $_POST['role'] ?? '';
    if (!in_array($newRole, ['super_admin', 'admin', 'user'])) sendJsonResponse(false, 'Invalid role');
    
    if ($newRole === 'super_admin' && (int)$targetId !== (int)$userId) {
        $confirmTransfer = isset($_POST['confirm_transfer']) && ($_POST['confirm_transfer'] === 'true' || $_POST['confirm_transfer'] === '1');
        
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
        $stmtCount->execute();
        $superAdminCount = (int)$stmtCount->fetchColumn();
        
        if ($superAdminCount >= 1 && !$confirmTransfer) {
            sendJsonResponse(false, 'Rule Notice: Only 1 Super Administrator can be active at a time. Promoting this user will transfer your Super Admin rights to them. Do you wish to proceed?', [
                'requires_confirmation' => true
            ]);
        }
        
        if ($confirmTransfer) {
            // Transfer current super_admin to standard admin
            $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$userId]);
            
            // Release the super_admin_lock since they are no longer a super admin
            $db->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();
            
            $_SESSION['role'] = 'admin';
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $targetId]);
    sendJsonResponse(true, 'Role updated');
}

if ($action === 'get_user_privileges') {
    requireSuperAdmin();
    $targetId = (int)($_GET['target_id'] ?? $_POST['target_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, username, first_name, last_name, role, privileges FROM users WHERE id = ?");
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
    
    $stmt = $db->prepare("SELECT id, role, username FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
    if (!$targetUser) sendJsonResponse(false, 'User not found');
    
    $privsJson = json_encode(array_values($privileges));
    $updateStmt = $db->prepare("UPDATE users SET privileges = ? WHERE id = ?");
    $updateStmt->execute([$privsJson, $targetId]);
    
    sendJsonResponse(true, 'Privileges updated successfully for ' . $targetUser['username'], [
        'privileges_count' => count($privileges)
    ]);
}

if ($action === 'update_user_info') {
    if ($role !== 'super_admin' && $role !== 'admin') sendJsonResponse(false, 'Denied');
    $targetId = $_POST['target_id'] ?? 0;
    
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
    $targetId = $_POST['target_id'] ?? 0;
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone_number'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email)) {
        sendJsonResponse(false, 'Missing required fields');
    }
    
    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $email, $phone, $targetId]);
    
    if ($role === 'super_admin' && !empty($newPassword)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtPw = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtPw->execute([$hashed, $targetId]);
    }
    
    sendJsonResponse(true, 'User information updated successfully');
}

if ($action === 'delete_user') {
    $targetId = $_POST['target_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    if ($role === 'super_admin') {
        requireSuperAdminWithPassword();
        // Direct delete
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        sendJsonResponse(true, 'User deleted');
    } elseif ($role === 'admin') {
        if (!in_array('account_deletion_requests', $privs)) sendJsonResponse(false, 'Denied. Missing account_deletion_requests privilege.');
        requireActionPassword();
        
        // Prevent admin from requesting deletion of a super admin
        $stmtTarget = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        if ($targetUser && $targetUser['role'] === 'super_admin') {
            sendJsonResponse(false, 'Denied. Admins cannot request deletion of Super Admin accounts.');
        }
        
        // Request delete
        if (empty($reason)) sendJsonResponse(false, 'Reason is required for delete request.');
        $stmt = $db->prepare("INSERT INTO delete_requests (target_user_id, requested_by_admin_id, reason) VALUES (?, ?, ?)");
        $stmt->execute([$targetId, $userId, $reason]);
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
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $filterMonth = sanitizeInput($_GET['month'] ?? '');
    $filterDate = sanitizeInput($_GET['date'] ?? '');
    
    $query = "SELECT l.time_in, l.time_out, u.id_number, u.first_name, u.last_name, u.role 
              FROM login_logs l JOIN users u ON l.user_id = u.id WHERE 1=1";
    $params = [];
    
    // Visibility
    if ($role === 'admin') {
        // Can view admin and users (not super admin)
        $query .= " AND u.role != 'super_admin'";
    } elseif ($role === 'user') {
        // Can view only self
        $query .= " AND l.user_id = ?";
        $params[] = $userId;
    }
    
    // Filtering
    if ($filterMonth) {
        $query .= " AND DATE_FORMAT(l.time_in, '%Y-%m') = ?";
        $params[] = $filterMonth;
    }
    if ($filterDate) {
        $query .= " AND DATE(l.time_in) = ?";
        $params[] = $filterDate;
    }
    
    // Count total
    $countQuery = str_replace("SELECT l.time_in, l.time_out, u.id_number, u.first_name, u.last_name, u.role", "SELECT COUNT(*)", $query);
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    $query .= " ORDER BY l.time_in DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    sendJsonResponse(true, 'Success', ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'create_privileged_account') {
    requireSuperAdminWithPassword();
    
    $accountRole = sanitizeInput($_POST['account_role'] ?? 'admin');
    if (!in_array($accountRole, ['admin', 'super_admin'])) {
        sendJsonResponse(false, 'Invalid account role selected.');
    }
    
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $privilegesRaw = $_POST['privileges'] ?? '[]';
    $privileges = is_array($privilegesRaw) ? $privilegesRaw : json_decode($privilegesRaw, true);
    if (!is_array($privileges)) {
        $privileges = [];
    }

    // Validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        sendJsonResponse(false, 'Username must be between 3 and 50 characters.');
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        sendJsonResponse(false, 'Username can only contain letters, numbers, dots, underscores, and dashes.');
    }
    if (strlen($password) < 8) {
        sendJsonResponse(false, 'Default Password must be at least 8 characters long.');
    }
    if ($password !== $confirmPassword) {
        sendJsonResponse(false, 'Password and Confirm Password do not match.');
    }

    // Check username uniqueness
    $chkStmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
    $chkStmt->execute([$username]);
    if ($chkStmt->fetch()) {
        sendJsonResponse(false, 'The username "' . $username . '" is already taken.');
    }

    // Rule: Only 1 Super Admin active at a time
    if ($accountRole === 'super_admin') {
        $confirmTransfer = isset($_POST['confirm_transfer']) && ($_POST['confirm_transfer'] === 'true' || $_POST['confirm_transfer'] === '1');
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
        $stmtCount->execute();
        $superAdminCount = (int)$stmtCount->fetchColumn();
        
        if ($superAdminCount >= 1 && !$confirmTransfer) {
            sendJsonResponse(false, 'Rule Notice: Only 1 Super Administrator can be active at a time. Creating a new Super Administrator will transfer Super Admin rights. Do you wish to proceed?', [
                'requires_confirmation' => true
            ]);
        }
        
        if ($confirmTransfer) {
            // Transfer current super_admin to standard admin
            $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$userId]);
            
            // Release the super_admin_lock since they are no longer a super admin
            $db->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();
            
            $_SESSION['role'] = 'admin';
        }
    }

    // Auto-generate Unique ID Number using cryptographically secure random
    $currentYear = date('Y');
    $newIdNumber = null;
    for ($i = 0; $i < 50; $i++) {
        $candidate = $currentYear . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $cstmt = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
        $cstmt->execute([$candidate]);
        if (!$cstmt->fetch()) {
            $newIdNumber = $candidate;
            break;
        }
    }
    if (!$newIdNumber) {
        $newIdNumber = $currentYear . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Hash password & store
    $hashedPassword = hashPassword($password);
    $defaultEmail = $username . '@system.local';
    $privilegesJson = json_encode(array_values($privileges));

    $insertStmt = $db->prepare("
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
        $defaultEmail,
        $hashedPassword,
        $newIdNumber,
        $accountRole,
        $privilegesJson
    ]);

    $newId = $db->lastInsertId();

    sendJsonResponse(true, 'Account created successfully with status Pending Setup!', [
        'id' => $newId,
        'username' => $username,
        'id_number' => $newIdNumber,
        'role' => $accountRole,
        'privileges_count' => count($privileges)
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
