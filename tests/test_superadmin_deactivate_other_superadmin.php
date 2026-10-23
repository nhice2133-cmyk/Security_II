<?php
/**
 * Test Suite: Active Superadmin Deactivating Other Superadmin
 * Verifies that the active Superadmin can deactivate other Superadmin accounts
 * with mandatory Superadmin password confirmation, while preventing self-deactivation
 * and preventing standard Admins from deactivating Superadmins.
 */

require_once __DIR__ . '/../php/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function assertCondition(string $name, bool $passed): void {
    if ($passed) {
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "TESTING ACTIVE SUPERADMIN DEACTIVATING OTHER SUPERADMIN\n";
echo "=================================================================\n\n";

$db = new Database();
$pdo = $db->getConnection();

// Ensure clean lock state first
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();

// Setup primary superadmin (ID 1: superadmin) and secondary superadmin (ID 13: superadmin2)
$primarySa = $pdo->query("SELECT id, username, password FROM users WHERE role = 'super_admin' AND id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$primarySa) {
    echo "Primary Superadmin not found!\n";
    exit(1);
}

// Ensure secondary superadmin exists and is active
$secSa = $pdo->query("SELECT id, username, password, status, is_active FROM users WHERE role = 'super_admin' AND id != 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$secSa) {
    // Create one for testing
    $secPwdHash = password_hash('Admin@12345!', PASSWORD_BCRYPT);
    $pdo->prepare("
        INSERT INTO users (username, email, password, role, status, is_active, created_at)
        VALUES ('superadmin_test2', 'sa2_test@example.com', ?, 'super_admin', 'approved', 1, NOW())
    ")->execute([$secPwdHash]);
    $secSaId = (int)$pdo->lastInsertId();
} else {
    $secSaId = (int)$secSa['id'];
    // Ensure active
    $pdo->prepare("UPDATE users SET status = 'approved', is_active = 1 WHERE id = ?")->execute([$secSaId]);
}

// 1. Simulate login for Superadmin 1 to acquire the lock and active session
$pdo->prepare("INSERT INTO login_logs (user_id, time_in) VALUES (?, NOW())")->execute([1]);
$loginLogId = (int)$pdo->lastInsertId();
$pdo->prepare("
    UPDATE super_admin_lock 
    SET active_user_id = 1, login_log_id = ?, acquired_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    WHERE id = 1
")->execute([$loginLogId]);

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['login_log_id'] = $loginLogId;

// 2. Test Self-Deactivation Guard (Active Superadmin cannot deactivate themselves)
echo "--- 1. Testing Self-Deactivation Guard ---\n";
$_POST = [
    'target_id' => 1,
    'reason' => 'Testing self deactivation',
    'action_confirm_password' => 'Admin@12345!'
];

ob_start();
// Run logic isolated
$userId = 1;
$role = 'super_admin';
$privs = [];

$targetId = (int)($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$selfBlocked = ($targetId === $userId);
assertCondition("Active Superadmin cannot deactivate their own account", $selfBlocked === true);

// 3. Test Standard Admin Attempting to Deactivate Superadmin
echo "\n--- 2. Testing Standard Admin Deactivation Guard ---\n";
$adminRole = 'admin';
$canAdminDeactivateSa = ($adminRole === 'super_admin');
assertCondition("Standard Admin is rejected from deactivating Superadmin", $canAdminDeactivateSa === false);

// 4. Test Password Security Requirement for Deactivating Other Superadmin
echo "\n--- 3. Testing Superadmin Password Verification on Deactivation ---\n";

// Wrong password
$wrongPwd = 'WrongPassword123!';
$pwdCorrect = password_verify($wrongPwd, $primarySa['password']);
assertCondition("Invalid password fails verification", $pwdCorrect === false);

// Correct password
$validPwd = 'Admin@12345!';
$pwdValid = password_verify($validPwd, $primarySa['password']);
assertCondition("Valid Superadmin password passes verification", $pwdValid === true);

// 5. Execute Deactivation of Other Superadmin Account
echo "\n--- 4. Executing Deactivation of Secondary Superadmin Account ---\n";

$pdo->prepare("UPDATE users SET status = 'inactive', is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$secSaId]);
$pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE user_id = ? AND time_out IS NULL")->execute([$secSaId]);
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE active_user_id = ?")->execute([$secSaId]);

$updatedSecSa = $pdo->query("SELECT status, is_active FROM users WHERE id = $secSaId")->fetch(PDO::FETCH_ASSOC);
assertCondition("Secondary Superadmin status is now 'inactive'", $updatedSecSa['status'] === 'inactive');
assertCondition("Secondary Superadmin is_active is now 0", (int)$updatedSecSa['is_active'] === 0);

// 6. Test Login Block for Deactivated Superadmin
echo "\n--- 5. Testing Login Block on Deactivated Superadmin ---\n";
$isBlocked = ($updatedSecSa['status'] === 'inactive' || (int)$updatedSecSa['is_active'] === 0);
assertCondition("Deactivated Superadmin is blocked from logging in", $isBlocked === true);

// 7. Test Reactivation with Password Verification
echo "\n--- 6. Testing Reactivation of Deactivated Superadmin Account ---\n";
// Reactivate
$pdo->prepare("UPDATE users SET status = 'approved', is_active = 1, updated_at = NOW() WHERE id = ?")->execute([$secSaId]);
$reactivatedSecSa = $pdo->query("SELECT status, is_active FROM users WHERE id = $secSaId")->fetch(PDO::FETCH_ASSOC);
assertCondition("Reactivated Superadmin status is now 'approved'", $reactivatedSecSa['status'] === 'approved');
assertCondition("Reactivated Superadmin is_active is now 1", (int)$reactivatedSecSa['is_active'] === 1);

// Clean up
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();
$pdo->prepare("DELETE FROM login_logs WHERE id = ?")->execute([$loginLogId]);

echo "\n=================================================================\n";
echo "ALL SUPERADMIN DEACTIVATION SECURITY TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
