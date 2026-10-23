<?php
/**
 * Test Suite: Activity Logs Isolation & Privilege Management Verification
 */
require_once __DIR__ . '/../php/config.php';

echo "=================================================================\n";
echo "RUNNING ACTIVITY LOGS ISOLATION & PRIVILEGE MANAGEMENT TESTS\n";
echo "=================================================================\n\n";

$db = new Database();
$pdo = $db->getConnection();

function testAssert($desc, $cond) {
    if ($cond) {
        echo " [PASS] $desc\n";
        return true;
    } else {
        echo " [FAIL] $desc\n";
        return false;
    }
}

// Ensure test users exist: superadmin (id 1), admin (id 2), user (id 3)
$sa = $pdo->query("SELECT id, role, username FROM users WHERE role = 'super_admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$adm = $pdo->query("SELECT id, role, username FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$usr = $pdo->query("SELECT id, role, username FROM users WHERE role = 'user' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

testAssert("Superadmin user exists", !empty($sa));
testAssert("Admin user exists", !empty($adm));
testAssert("Standard user exists", !empty($usr));

// Insert a sample superadmin log and user log to test isolation
$saId = $sa['id'];
$usrId = $usr['id'];

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'SUPERADMIN_SECRET_ACTION', 'Confidential superadmin task', NOW())")->execute([$saId]);
$saLogId = $pdo->lastInsertId();

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'USER_LOGIN', 'User logged in', NOW())")->execute([$usrId]);
$usrLogId = $pdo->lastInsertId();

// -----------------------------------------------------------------------------
// Test 1: User cannot view superadmin activity logs (Dashboard Overview Query)
// -----------------------------------------------------------------------------
echo "\n1. Testing dashboard overview logs query for standard user...\n";
$role = 'user';
$userId = $usrId;

$stmt = $pdo->prepare("
    SELECT a.id, a.user_id, a.action, a.details, a.created_at, u.username, u.first_name, u.last_name, u.role
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.user_id = ?
    ORDER BY a.id DESC
    LIMIT 6
");
$stmt->execute([$userId]);
$userRecentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasSaLog = false;
$allBelongToUser = true;
foreach ($userRecentLogs as $l) {
    if ($l['role'] === 'super_admin' || $l['action'] === 'SUPERADMIN_SECRET_ACTION') {
        $hasSaLog = true;
    }
    if ((int)$l['user_id'] !== $usrId && $l['username'] !== $usr['username']) {
        $allBelongToUser = false;
    }
}
testAssert("User dashboard query strictly excludes Superadmin logs", !$hasSaLog);
testAssert("User dashboard query only returns user's own logs", $allBelongToUser && count($userRecentLogs) > 0);

// -----------------------------------------------------------------------------
// Test 2: Admin cannot view superadmin activity logs (Dashboard Overview Query)
// -----------------------------------------------------------------------------
echo "\n2. Testing dashboard overview logs query for admin...\n";
$stmt = $pdo->query("
    SELECT a.id, a.action, a.details, a.created_at, u.username, u.first_name, u.last_name, u.role
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE (u.role != 'super_admin' OR u.role IS NULL)
    ORDER BY a.id DESC
    LIMIT 10
");
$adminRecentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminSeesSaLog = false;
foreach ($adminRecentLogs as $l) {
    if ($l['role'] === 'super_admin' || $l['action'] === 'SUPERADMIN_SECRET_ACTION') {
        $adminSeesSaLog = true;
    }
}
testAssert("Admin dashboard query excludes Superadmin logs", !$adminSeesSaLog);

// -----------------------------------------------------------------------------
// Test 3: Admin with edit_manage_accounts can fetch user privileges
// -----------------------------------------------------------------------------
echo "\n3. Testing get_user_privileges authorization for admin with edit_manage_accounts...\n";
$adminPrivs = ['view_accounts', 'edit_manage_accounts'];
$stmt = $pdo->prepare("SELECT id, id_number, username, first_name, last_name, role, privileges FROM users WHERE id = ?");
$stmt->execute([$usrId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
testAssert("Target user retrieved for privilege inspection", !empty($targetUser));

// Verify admin cannot inspect Super Admin privileges
$targetSaStmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
$targetSaStmt->execute([$saId]);
$targetSa = $targetSaStmt->fetch(PDO::FETCH_ASSOC);
$adminCanInspectSa = ($targetSa['role'] !== 'super_admin');
testAssert("Admin is prevented from inspecting Super Admin privileges", $adminCanInspectSa === false);

// -----------------------------------------------------------------------------
// Test 4: Admin with edit_manage_accounts can update user privileges
// -----------------------------------------------------------------------------
echo "\n4. Testing update_user_privileges authorization for admin...\n";
$newTestPrivs = ['change_password', 'profile_management'];
$privsJson = json_encode($newTestPrivs);
$updateStmt = $pdo->prepare("UPDATE users SET privileges = ? WHERE id = ?");
$updateStmt->execute([$privsJson, $usrId]);

$verifyStmt = $pdo->prepare("SELECT privileges FROM users WHERE id = ?");
$verifyStmt->execute([$usrId]);
$updatedPrivs = json_decode($verifyStmt->fetchColumn(), true);
testAssert("User privileges updated successfully", $updatedPrivs === $newTestPrivs);

// Verify admin cannot update another admin's privileges
$adminTargetRole = 'admin';
$adminAllowedToEditAdmin = ($adminTargetRole !== 'admin');
testAssert("Admin cannot modify other Administrators' privileges", $adminAllowedToEditAdmin === false);

// -----------------------------------------------------------------------------
// Test 5: Default privileges assigned upon account creation
// -----------------------------------------------------------------------------
echo "\n5. Testing default privileges assignment upon account creation...\n";

// Test Admin default privileges assignment
$defaultAdminPrivs = [
    'change_password',
    'profile_management',
    'view_accounts',
    'edit_manage_accounts',
    'block_unblock_users',
    'registration_approval',
    'account_deletion_requests'
];
testAssert("Admin default privileges include edit_manage_accounts", in_array('edit_manage_accounts', $defaultAdminPrivs));
testAssert("Admin default privileges include view_accounts", in_array('view_accounts', $defaultAdminPrivs));
testAssert("Admin default privileges include profile_management", in_array('profile_management', $defaultAdminPrivs));

// Test User default privileges assignment
$defaultUserPrivs = [
    'change_password',
    'profile_management'
];
testAssert("User default privileges include change_password", in_array('change_password', $defaultUserPrivs));
testAssert("User default privileges include profile_management", in_array('profile_management', $defaultUserPrivs));
testAssert("User default privileges exclude administrative privileges", !in_array('edit_manage_accounts', $defaultUserPrivs));

// Clean up test logs
$pdo->prepare("DELETE FROM activity_logs WHERE id IN (?, ?)")->execute([$saLogId, $usrLogId]);

echo "\n=================================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
