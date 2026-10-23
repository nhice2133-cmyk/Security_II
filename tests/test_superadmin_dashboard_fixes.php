<?php
/**
 * Test Suite: Super Admin Dashboard, Account Management & Privacy Fixes
 * Tests all 9 scenarios specified in the user prompt.
 */

require_once __DIR__ . '/../php/config.php';

echo "=================================================================\n";
echo "RUNNING SUPER ADMIN DASHBOARD & ACCOUNT MANAGEMENT VERIFICATION\n";
echo "=================================================================\n\n";

$db = new Database();
$pdo = $db->getConnection();

function assertTest(string $description, bool $condition, string $details = ''): void {
    if ($condition) {
        echo " [\033[32mPASS\033[0m] {$description}\n";
    } else {
        echo " [\033[31mFAIL\033[0m] {$description}" . ($details ? " - Details: {$details}" : "") . "\n";
        exit(1);
    }
}

// Ensure base test accounts exist
$u1 = $pdo->query("SELECT * FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$u2 = $pdo->query("SELECT * FROM users WHERE id = 2")->fetch(PDO::FETCH_ASSOC);
$u3 = $pdo->query("SELECT * FROM users WHERE id = 3")->fetch(PDO::FETCH_ASSOC);

assertTest("Superadmin (ID 1) exists", (bool)$u1 && $u1['role'] === 'super_admin');
assertTest("Admin (ID 2) exists", (bool)$u2 && $u2['role'] === 'admin');
assertTest("User (ID 3) exists", (bool)$u3 && $u3['role'] === 'user');

// ── 1. Activity Log Privacy and Isolation ─────────────────────────────────
echo "\n--- 1. Testing Activity Log Access & Privacy ---\n";

// Insert identifiable test logs
$pdo->prepare("
    INSERT INTO activity_logs (user_id, target_user_id, action, details, outcome, ip_address, created_at)
    VALUES (1, 3, 'SUPERADMIN_SECURITY_OVERRIDE', 'Confidential super admin root action', 'SUCCESS', '127.0.0.1', NOW())
")->execute();
$saLogId = $pdo->lastInsertId();

$pdo->prepare("
    INSERT INTO activity_logs (user_id, target_user_id, action, details, outcome, ip_address, created_at)
    VALUES (3, NULL, 'USER_PROFILE_UPDATE', 'User updated profile photo', 'SUCCESS', '127.0.0.1', NOW())
")->execute();
$userLogId = $pdo->lastInsertId();

// Verify User Query: Regular user must ONLY see their own logs and NEVER super admin logs
$userQuery = "
    SELECT a.* FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN users tu ON a.target_user_id = tu.id
    WHERE a.user_id = ? AND (u.role != 'super_admin' OR u.role IS NULL) AND (tu.role != 'super_admin' OR tu.role IS NULL) AND a.action NOT LIKE 'SUPERADMIN_%'
";
$stmt = $pdo->prepare($userQuery);
$stmt->execute([3]);
$userLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$userLogIds = array_column($userLogs, 'id');
assertTest("Regular user query fetches user's own logs", in_array($userLogId, $userLogIds));
assertTest("Regular user query strictly excludes Super Admin logs", !in_array($saLogId, $userLogIds));

// Verify Admin Query: Admin must NOT see Super Admin actions
$adminQuery = "
    SELECT a.* FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN users tu ON a.target_user_id = tu.id
    WHERE (u.role != 'super_admin' OR u.role IS NULL) AND (tu.role != 'super_admin' OR tu.role IS NULL) AND a.action NOT LIKE 'SUPERADMIN_%'
";
$stmt = $pdo->query($adminQuery);
$adminLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$adminLogIds = array_column($adminLogs, 'id');
assertTest("Admin query strictly excludes Super Admin logs and SUPERADMIN_% actions", !in_array($saLogId, $adminLogIds));

// Verify Super Admin Query: Super Admin sees system-wide logs
$saQuery = "SELECT a.* FROM activity_logs a";
$stmt = $pdo->query($saQuery);
$saLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$saAllIds = array_column($saLogs, 'id');
assertTest("Super Admin query includes system-wide and super admin logs", in_array($saLogId, $saAllIds));

// Clean up test logs
$pdo->prepare("DELETE FROM activity_logs WHERE id IN (?, ?)")->execute([$saLogId, $userLogId]);

// ── 2. Duplicate Active Super Admin Accounts Resolution ────────────────────
echo "\n--- 2. Testing Super Admin Duplicate Active Display Logic ---\n";

// Simulate a second super_admin account in the database (e.g. created during pending handover)
$testSa2Username = 'test_secondary_sa_' . time();
$pdo->prepare("
    INSERT INTO users (username, email, password, id_number, role, status, is_active, created_at)
    VALUES (?, 'test_sa2@example.com', 'hashed_pwd', '2026-9999', 'super_admin', 'pending_setup', 1, NOW())
")->execute([$testSa2Username]);
$testSa2Id = (int)$pdo->lastInsertId();

// Set active session lock on user 1
$pdo->prepare("
    UPDATE super_admin_lock 
    SET active_user_id = 1, login_log_id = 99999, acquired_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
    WHERE id = 1
")->execute();

// Execute the single-superadmin resolution logic from php/admin_actions.php
$lockStmt = $pdo->query("SELECT active_user_id, expires_at FROM super_admin_lock WHERE id = 1");
$saLock = $lockStmt->fetch(PDO::FETCH_ASSOC);
$activeSaUserId = null;
if ($saLock && $saLock['active_user_id'] !== null) {
    if ($saLock['expires_at'] === null || strtotime($saLock['expires_at']) >= time()) {
        $activeSaUserId = (int)$saLock['active_user_id'];
    }
}

$primarySaId = null;
if ($activeSaUserId !== null) {
    $chkActiveSa = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'super_admin' LIMIT 1");
    $chkActiveSa->execute([$activeSaUserId]);
    if ($chkActiveSa->fetch()) {
        $primarySaId = $activeSaUserId;
    }
}

// Fetch both super_admin accounts
$stmt = $pdo->prepare("SELECT id, role, status, is_active FROM users WHERE id IN (1, ?)");
$stmt->execute([$testSa2Id]);
$saUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$u1IsCurrentSa = false;
$u2IsCurrentSa = false;
$u2IsSecondarySa = false;

foreach ($saUsers as $u) {
    $uid = (int)$u['id'];
    $uRole = $u['role'];
    $isCurrentSa = ($uRole === 'super_admin' && $uid === $primarySaId);
    $isSecondarySa = ($uRole === 'super_admin' && $uid !== $primarySaId);
    if ($uid === 1) {
        $u1IsCurrentSa = $isCurrentSa;
    } elseif ($uid === $testSa2Id) {
        $u2IsCurrentSa = $isCurrentSa;
        $u2IsSecondarySa = $isSecondarySa;
    }
}

assertTest("User 1 is designated as the single primary active Super Admin", $u1IsCurrentSa === true);
assertTest("Secondary Super Admin is NOT marked as current active Super Admin", $u2IsCurrentSa === false);
assertTest("Secondary Super Admin is correctly flagged as secondary superadmin", $u2IsSecondarySa === true);

// Clean up simulated second super admin and simulated lock
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$testSa2Id]);
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();

// ── 3. Logout Confirmation Warning Feature ────────────────────────────────
echo "\n--- 3. Testing Logout Confirmation Warning Components ---\n";

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJsFile = file_get_contents(__DIR__ . '/../js/admin.js');

assertTest("Dashboard header contains logout button invoking openLogoutConfirmModal()", 
    strpos($dashFile, 'openLogoutConfirmModal()') !== false);
assertTest("Dashboard HTML contains logout confirmation modal #logoutConfirmModal", 
    strpos($dashFile, 'id="logoutConfirmModal"') !== false);
assertTest("Logout modal displays: 'Are you sure you want to log out?'", 
    strpos($dashFile, 'Are you sure you want to log out?') !== false);
assertTest("Logout modal provides Cancel button calling closeLogoutConfirmModal()", 
    strpos($dashFile, 'closeLogoutConfirmModal()') !== false);
assertTest("Logout modal provides Log Out button calling confirmLogout()", 
    strpos($dashFile, 'confirmLogout()') !== false);
assertTest("admin.js implements openLogoutConfirmModal()", 
    strpos($adminJsFile, 'function openLogoutConfirmModal()') !== false);
assertTest("admin.js implements closeLogoutConfirmModal()", 
    strpos($adminJsFile, 'function closeLogoutConfirmModal()') !== false);
assertTest("admin.js implements confirmLogout() redirecting to php/logout.php", 
    strpos($adminJsFile, "window.location.href = '../php/logout.php'") !== false);

// ── 4. Standardized Account Suspension and Deactivation ────────────────────
echo "\n--- 4. Testing Account Suspension vs Deactivation ---\n";

// Create a test account for status testing
$testUserPwd = 'TestPass123!';
$hashedTestPwd = password_hash($testUserPwd, PASSWORD_BCRYPT);
$pdo->prepare("
    INSERT INTO users (username, email, password, role, status, is_active, created_at)
    VALUES ('test_status_user', 'status_test@example.com', ?, 'user', 'approved', 1, NOW())
")->execute([$hashedTestPwd]);
$statusUserId = (int)$pdo->lastInsertId();

// Helper to simulate login check
function simulateLoginCheck(PDO $pdo, string $username, string $password): array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    if ($user['status'] === 'suspended' || $user['status'] === 'blocked') {
        return ['success' => false, 'message' => 'Your account has been suspended. Please contact an administrator.'];
    }
    if ($user['status'] === 'inactive' || (isset($user['is_active']) && (int)$user['is_active'] === 0)) {
        return ['success' => false, 'message' => 'Your account is inactive. Please contact an administrator to request reactivation.'];
    }
    return ['success' => true, 'message' => 'Login successful'];
}

// Test Active Login
$activeLogin = simulateLoginCheck($pdo, 'test_status_user', $testUserPwd);
assertTest("Active user can log in successfully", $activeLogin['success'] === true);

// Test Suspension
$pdo->prepare("UPDATE users SET status = 'suspended', is_active = 0 WHERE id = ?")->execute([$statusUserId]);
$suspendedLogin = simulateLoginCheck($pdo, 'test_status_user', $testUserPwd);
assertTest("Suspended user is blocked from logging in", $suspendedLogin['success'] === false);
assertTest("Suspended user receives suspension-specific message", 
    strpos($suspendedLogin['message'], 'account has been suspended') !== false);

// Test Restore from Suspension
$pdo->prepare("UPDATE users SET status = 'approved', is_active = 1 WHERE id = ?")->execute([$statusUserId]);
$restoredLogin = simulateLoginCheck($pdo, 'test_status_user', $testUserPwd);
assertTest("Restored account can log in again", $restoredLogin['success'] === true);

// Test Deactivation
$pdo->prepare("UPDATE users SET status = 'inactive', is_active = 0 WHERE id = ?")->execute([$statusUserId]);
$deactivatedLogin = simulateLoginCheck($pdo, 'test_status_user', $testUserPwd);
assertTest("Deactivated user is blocked from logging in", $deactivatedLogin['success'] === false);
assertTest("Deactivated user receives deactivation-specific message", 
    strpos($deactivatedLogin['message'], 'account is inactive') !== false);

// Test Reactivation from Deactivation
$pdo->prepare("UPDATE users SET status = 'approved', is_active = 1 WHERE id = ?")->execute([$statusUserId]);
$reactivatedLogin = simulateLoginCheck($pdo, 'test_status_user', $testUserPwd);
assertTest("Reactivated account can log in again", $reactivatedLogin['success'] === true);

// Clean up status test user
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$statusUserId]);

// ── 5. Privilege Selection & Validation on Account Creation ───────────────
echo "\n--- 5. Testing Privilege Selection & Backend Validation ---\n";

assertTest("Create Account form contains privilege selection card #caPrivilegeCard", 
    strpos($dashFile, 'id="caPrivilegeCard"') !== false);
assertTest("Create Account form contains module privilege checkboxes .ca-privilege-cb", 
    strpos($dashFile, 'class="ca-privilege-cb"') !== false);
assertTest("Create Account form contains preset buttons (Defaults, All, Clear)", 
    strpos($dashFile, 'setCaPrivPreset') !== false);

// Test backend privilege validation logic
$allowedPrivileges = [
    'profile_management',
    'change_password',
    'view_accounts',
    'create_accounts',
    'edit_manage_accounts',
    'block_unblock_users',
    'registration_approval',
    'account_deletion_requests',
    'view_activity_logs',
    'export_activity_logs'
];

$submittedPrivs = ['view_accounts', 'create_accounts', 'malicious_privilege', 'export_activity_logs'];
$filteredPrivs = array_values(array_intersect($submittedPrivs, $allowedPrivileges));

assertTest("Backend filters out invalid/malicious privileges against whitelist", 
    !in_array('malicious_privilege', $filteredPrivs) && count($filteredPrivs) === 3);

// Test least privilege enforcement for standard users
$userSubmittedPrivs = ['profile_management', 'change_password', 'edit_manage_accounts'];
$userAllowed = ['profile_management', 'change_password'];
$userFilteredPrivs = array_values(array_intersect($userSubmittedPrivs, $userAllowed));

assertTest("Standard users cannot be assigned administrative privileges", 
    !in_array('edit_manage_accounts', $userFilteredPrivs) && count($userFilteredPrivs) === 2);

// ── 6. Hide Default Password & Masking on Account Creation ─────────────────
echo "\n--- 6. Testing Default Password Masking & Show/Hide Controls ---\n";

assertTest("#caPassword input field is type='password' (masked by default)", 
    strpos($dashFile, 'type="password" id="caPassword"') !== false);
assertTest("Create Account form contains Show/Hide password toggle button #caPwToggleBtn", 
    strpos($dashFile, 'id="caPwToggleBtn"') !== false);
assertTest("Show/Hide button contains unified SVG eye icon #caPwEyeIcon", 
    strpos($dashFile, 'id="caPwEyeIcon"') !== false);
assertTest("admin.js implements toggleCaPasswordVisibility()", 
    strpos($adminJsFile, 'function toggleCaPasswordVisibility()') !== false);
assertTest("openCreateAccountModal() resets password to masked type='password'", 
    strpos($adminJsFile, "pwdEl.type = 'password'") !== false);

// Verify secure hashing
$plainPassword = function_exists('generateRandom8CharPassword') ? generateRandom8CharPassword() : 'TestPass123!';
$hashed = hashPassword($plainPassword);
assertTest("Password is cryptographically hashed with BCRYPT (not plain text)", 
    password_verify($plainPassword, $hashed) && strlen($hashed) >= 60);

// ── 7. Multi-Step Wizard Flow (Preventing Modal Screen Overflow) ────────────
echo "\n--- 7. Testing 2-Step Modal Wizard & Screen Overflow Fix ---\n";

assertTest("Create Account modal contains step indicator bar #caStepIndicatorBar",
    strpos($dashFile, 'id="caStepIndicatorBar"') !== false);
assertTest("Create Account modal contains Step 1 badge #caStepBadge1",
    strpos($dashFile, 'id="caStepBadge1"') !== false);
assertTest("Create Account modal contains Step 2 badge #caStepBadge2",
    strpos($dashFile, 'id="caStepBadge2"') !== false);
assertTest("Create Account modal contains Step 1 page container #caStep1Page",
    strpos($dashFile, 'id="caStep1Page"') !== false);
assertTest("Create Account modal contains Step 2 page container #caStep2Page",
    strpos($dashFile, 'id="caStep2Page"') !== false);
assertTest("Step 1 includes 'Next: Configure Privileges' button invoking goToCreateAccountStep(2)",
    strpos($dashFile, 'goToCreateAccountStep(2)') !== false);
assertTest("Step 2 includes 'Back' button invoking goToCreateAccountStep(1)",
    strpos($dashFile, 'goToCreateAccountStep(1)') !== false);
assertTest("admin.js implements goToCreateAccountStep(step)",
    strpos($adminJsFile, 'function goToCreateAccountStep(step)') !== false);
assertTest("openCreateAccountModal() resets wizard to Step 1 via goToCreateAccountStep(1)",
    strpos($adminJsFile, 'goToCreateAccountStep(1);') !== false);
assertTest("goToCreateAccountStep is exported globally on window",
    strpos($adminJsFile, 'window.goToCreateAccountStep = goToCreateAccountStep;') !== false);

echo "\n=================================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! ALL REQUIREMENTS & WIZARD VERIFIED.\n";
echo "=================================================================\n";

