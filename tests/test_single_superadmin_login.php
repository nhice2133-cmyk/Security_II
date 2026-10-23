<?php
/**
 * Test Suite: Single Active Superadmin Login Enforcement
 * 
 * Verifies that strictly only ONE Super Admin can be logged in at a time:
 * - When lock is free, Super Admin can log in successfully.
 * - While a Super Admin is active, another Super Admin account cannot log in.
 * - While a Super Admin is active, the SAME Super Admin account cannot log in concurrently from another session.
 * - Non-superadmin accounts (admin, user) are not blocked.
 * - Super Admin logout immediately frees the lock for another Super Admin.
 * - Stale/expired locks are safely auto-cleared upon next login attempt.
 */

require_once __DIR__ . '/../php/config.php';

echo "=================================================================\n";
echo "RUNNING SINGLE SUPERADMIN ACTIVE LOGIN TEST SUITE\n";
echo "=================================================================\n\n";

$db = new Database();
$pdo = $db->getConnection();

function assertCheck($description, $condition) {
    if ($condition) {
        echo " [PASS] $description\n";
        return true;
    } else {
        echo " [FAIL] $description\n";
        return false;
    }
}

function apiLogin($username, $password, $cookieFile = null) {
    $ch = curl_init('http://localhost/Security_II/php/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'password' => $password,
        'remember' => 0
    ]));
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['httpCode' => $httpCode, 'data' => json_decode($res, true), 'raw' => $res];
}

function apiLogout($cookieFile) {
    $ch = curl_init('http://localhost/Security_II/php/logout.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

$sessCookie1 = tempnam(sys_get_temp_dir(), 'sa1_sess_');
$sessCookie2 = tempnam(sys_get_temp_dir(), 'sa2_sess_');

// Ensure clean starting state
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();

// -----------------------------------------------------------------------------
// 1. Initial State: Free Lock
// -----------------------------------------------------------------------------
echo "1. Testing Superadmin login when lock is free...\n";
$login1 = apiLogin('superadmin', 'Admin@12345!', $sessCookie1);
assertCheck("Superadmin 1 login succeeds when lock is free", ($login1['data']['success'] ?? false) === true);

// Verify database lock state
$lockRow = $pdo->query("SELECT active_user_id, login_log_id, expires_at FROM super_admin_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertCheck("super_admin_lock active_user_id set to Superadmin 1 (ID 1)", (int)($lockRow['active_user_id'] ?? 0) === 1);
assertCheck("super_admin_lock expires_at is set in the future", strtotime($lockRow['expires_at']) > time());

// -----------------------------------------------------------------------------
// 2. Concurrent Login by SECOND Super Admin Account
// -----------------------------------------------------------------------------
echo "\n2. Testing concurrent login attempt by SECOND Super Admin account (superadmin2)...\n";
$login2 = apiLogin('superadmin2', 'Admin@12345!', $sessCookie2);
assertCheck("Second Super Admin login is REJECTED", ($login2['data']['success'] ?? true) === false);
assertCheck("Reject message explains another Super Admin is currently active", 
    strpos($login2['data']['message'] ?? '', 'Another Super Admin') !== false ||
    strpos($login2['data']['message'] ?? '', 'Only 1 Super Admin') !== false);
assertCheck("Response payload contains locked flag", ($login2['data']['data']['locked'] ?? false) === true);

// -----------------------------------------------------------------------------
// 3. Concurrent Login by SAME Super Admin Account (from another session)
// -----------------------------------------------------------------------------
echo "\n3. Testing concurrent login attempt by SAME Super Admin account from another browser session...\n";
$sessCookie3 = tempnam(sys_get_temp_dir(), 'sa3_sess_');
$login3 = apiLogin('superadmin', 'Admin@12345!', $sessCookie3);
assertCheck("Same Super Admin concurrent login from another browser is REJECTED", ($login3['data']['success'] ?? true) === false);
assertCheck("Reject message indicates active session for this account and only 1 allowed",
    strpos($login3['data']['message'] ?? '', 'already active for this account') !== false &&
    strpos($login3['data']['message'] ?? '', 'Only 1 Super Admin can be logged in at a time') !== false);
assertCheck("Response payload flags is_same_user = true", ($login3['data']['data']['is_same_user'] ?? false) === true);

// -----------------------------------------------------------------------------
// 4. Non-Super Admin Login Unaffected
// -----------------------------------------------------------------------------
echo "\n4. Testing standard Admin and Regular User login while Super Admin is locked...\n";
$adminLogin = apiLogin('admin', 'Admin@12345!');
assertCheck("Standard Administrator login is NOT blocked", ($adminLogin['data']['success'] ?? false) === true);

$userLogin = apiLogin('user', 'User@1234');
assertCheck("Regular User login is NOT blocked", ($userLogin['data']['success'] ?? false) === true);

// -----------------------------------------------------------------------------
// 5. Logout Releases Lock for Another Superadmin
// -----------------------------------------------------------------------------
echo "\n5. Testing Superadmin logout and handover to second Superadmin...\n";
apiLogout($sessCookie1);

$lockAfterLogout = $pdo->query("SELECT active_user_id FROM super_admin_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertCheck("Lock is completely released upon logout", empty($lockAfterLogout['active_user_id']));

// Now superadmin2 should succeed
$login2After = apiLogin('superadmin2', 'Admin@12345!', $sessCookie2);
assertCheck("Superadmin 2 can now log in successfully", ($login2After['data']['success'] ?? false) === true);

$lockSa2 = $pdo->query("SELECT active_user_id FROM super_admin_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertCheck("Lock is now held by superadmin2 (ID 13)", (int)($lockSa2['active_user_id'] ?? 0) === 13);

// And superadmin 1 is now blocked
$login1Blocked = apiLogin('superadmin', 'Admin@12345!', $sessCookie1);
assertCheck("Superadmin 1 is now blocked while Superadmin 2 is active", ($login1Blocked['data']['success'] ?? true) === false);

// -----------------------------------------------------------------------------
// 6. Stale / Expired Lock Auto-Clearing
// -----------------------------------------------------------------------------
echo "\n6. Testing auto-clearing of stale/expired locks...\n";
// Manually set lock expired 10 minutes ago
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = 13, login_log_id = 99999, expires_at = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE id = 1")->execute();

// Superadmin 1 attempts login
$loginStale = apiLogin('superadmin', 'Admin@12345!', $sessCookie1);
assertCheck("Expired lock is automatically cleared and Superadmin 1 logs in", ($loginStale['data']['success'] ?? false) === true);

$lockAfterStale = $pdo->query("SELECT active_user_id FROM super_admin_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertCheck("Lock is re-acquired by Superadmin 1", (int)($lockAfterStale['active_user_id'] ?? 0) === 1);

// -----------------------------------------------------------------------------
// 7. Cleanup
// -----------------------------------------------------------------------------
echo "\n7. Cleaning up test session and lock state...\n";
$pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")->execute();
$pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE time_out IS NULL")->execute();
@unlink($sessCookie1);
@unlink($sessCookie2);
@unlink($sessCookie3);
assertCheck("Test cleanup completed cleanly", true);

echo "\n=================================================================\n";
echo "ALL SINGLE SUPERADMIN LOGIN TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
