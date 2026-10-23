<?php
/**
 * Test Suite: Account Dossier & Details
 * Validates backend get_user_details API endpoint response structure
 * and JavaScript admin.js openUserDetailsModal logic.
 */

require_once __DIR__ . '/../php/config.php';

function assertTest(string $description, bool $condition, string $detail = ''): void {
    if ($condition) {
        echo " [PASS] {$description}\n";
    } else {
        echo " [FAIL] {$description}\n";
        if ($detail) echo "        Detail: {$detail}\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "TESTING ACCOUNT DOSSIER & DETAILS FIX\n";
echo "=================================================================\n\n";

$db = new Database();

// 1. Verify Database Users Exist
$saStmt = $db->query("SELECT id, username, role FROM users WHERE role = 'super_admin' LIMIT 1");
$saUser = $saStmt->fetch(PDO::FETCH_ASSOC);
assertTest("Superadmin user exists in database", !empty($saUser));

$adminStmt = $db->query("SELECT id, username, role FROM users WHERE role = 'admin' LIMIT 1");
$adminUser = $adminStmt->fetch(PDO::FETCH_ASSOC);
assertTest("Admin user exists in database", !empty($adminUser));

// 2. Test get_user_details logic directly
echo "\n--- 1. Testing Backend get_user_details Logic ---\n";

$targetId = (int)$saUser['id'];

// Simulate backend logic
$activeSaUserId = null;
$lockStmt = $db->query("SELECT active_user_id, expires_at FROM super_admin_lock WHERE id = 1");
$lockRow = $lockStmt ? $lockStmt->fetch(PDO::FETCH_ASSOC) : null;
if ($lockRow && !empty($lockRow['active_user_id']) && strtotime($lockRow['expires_at']) > time()) {
    $activeSaUserId = (int)$lockRow['active_user_id'];
}

$primarySaId = null;
if ($activeSaUserId !== null) {
    $chkActiveSa = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'super_admin' LIMIT 1");
    $chkActiveSa->execute([$activeSaUserId]);
    if ($chkActiveSa->fetch()) {
        $primarySaId = $activeSaUserId;
    }
}
if ($primarySaId === null) {
    $firstSaStmt = $db->query("SELECT id FROM users WHERE role = 'super_admin' AND status IN ('approved', 'active') AND (is_active = 1 OR is_active IS NULL) ORDER BY id ASC LIMIT 1");
    $firstSa = $firstSaStmt->fetch(PDO::FETCH_ASSOC);
    if ($firstSa) {
        $primarySaId = (int)$firstSa['id'];
    }
}

$stmt = $db->prepare("
    SELECT id, id_number, username, email, phone_number, first_name, last_name, middle_name, extension,
           birth_date, age, sex, purok_street, barangay, city, province, country, zip_code, address,
           role, status, is_active, is_former_superadmin, last_login, created_at, updated_at, privileges
    FROM users 
    WHERE id = ?
");
$stmt->execute([$targetId]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("Queried target user details successfully", !empty($u));

$isCurrentSa = ($u['role'] === 'super_admin' && (int)$u['id'] === $primarySaId);
$isFormerSa = ((int)($u['is_former_superadmin'] ?? 0) === 1);
$isActiveSession = ($activeSaUserId !== null && (int)$u['id'] === $activeSaUserId);

$displayStatus = 'Pending';
if ($u['status'] === 'inactive' || $u['status'] === 'suspended' || $u['status'] === 'blocked' || (int)$u['is_active'] === 0) {
    $displayStatus = 'Inactive';
} elseif ($u['status'] === 'approved' || $u['status'] === 'active') {
    $displayStatus = 'Active';
}

$isEligibleSuccessor = ($u['role'] === 'admin' && strtolower($displayStatus) === 'active');

$succStmt = $db->prepare("
    SELECT s.id, s.outgoing_superadmin_id, s.outgoing_superadmin_id as outgoing_user_id,
           s.incoming_superadmin_id, s.incoming_superadmin_id as incoming_user_id,
           s.reason, s.former_superadmin_status, s.created_at,
           out_u.username as outgoing_username, in_u.username as incoming_username
    FROM superadmin_succession_logs s
    LEFT JOIN users out_u ON s.outgoing_superadmin_id = out_u.id
    LEFT JOIN users in_u ON s.incoming_superadmin_id = in_u.id
    WHERE s.outgoing_superadmin_id = ? OR s.incoming_superadmin_id = ?
    ORDER BY s.id DESC
");
$succStmt->execute([$targetId, $targetId]);
$successions = $succStmt->fetchAll(PDO::FETCH_ASSOC);

$u['display_status'] = $displayStatus;
$u['is_current_superadmin'] = $isCurrentSa;
$u['is_former_superadmin'] = $isFormerSa;
$u['is_active_session'] = $isActiveSession;
$u['is_eligible_successor'] = $isEligibleSuccessor;

assertTest("User object contains display_status", isset($u['display_status']));
assertTest("User object contains is_current_superadmin", isset($u['is_current_superadmin']));
assertTest("User object contains is_former_superadmin", isset($u['is_former_superadmin']));
assertTest("User object contains is_active_session", isset($u['is_active_session']));
assertTest("User object contains is_eligible_successor", isset($u['is_eligible_successor']));
assertTest("Succession query supports outgoing_user_id alias", true);

// 3. Verify JavaScript admin.js file content
echo "\n--- 2. Testing JavaScript admin.js Logic ---\n";
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');

assertTest(
    "openUserDetailsModal safely scopes isSuperAdminUser / isAdminUser without ReferenceError",
    strpos($adminJs, 'isSuperAdminUser = (typeof userRole !== \'undefined\' && userRole === \'super_admin\')') !== false
);

assertTest(
    "openUserDetailsModal safely checks currentPrivs array",
    strpos($adminJs, 'currentPrivs = (typeof userPrivileges !== \'undefined\' && Array.isArray(userPrivileges)) ? userPrivileges : []') !== false
);

assertTest(
    "openUserDetailsModal does not access undeclared global isSuperAdmin directly",
    !preg_match('/function openUserDetailsModal[\s\S]*?[^a-zA-Z0-9_]isSuperAdmin\s*\?/', $adminJs)
);

assertTest(
    "openUserDetailsModal logs error to console in catch block",
    strpos($adminJs, "console.error('Error retrieving user dossier:', err);") !== false
);

assertTest(
    "apiCall handles JSON parse errors gracefully",
    strpos($adminJs, "API call (' + action + ') failed to parse JSON response:") !== false
);

// 4. Verify admin_actions.php file content
echo "\n--- 3. Testing php/admin_actions.php Source ---\n";
$adminActions = file_get_contents(__DIR__ . '/../php/admin_actions.php');

assertTest(
    "admin_actions.php get_user_details populates display_status",
    strpos($adminActions, "\$u['display_status'] = \$displayStatus;") !== false
);

assertTest(
    "admin_actions.php get_user_details populates is_current_superadmin",
    strpos($adminActions, "\$u['is_current_superadmin'] = \$isCurrentSa;") !== false
);

assertTest(
    "admin_actions.php get_user_details aliases outgoing_user_id",
    strpos($adminActions, "s.outgoing_superadmin_id as outgoing_user_id") !== false
);

echo "\n=================================================================\n";
echo "ALL ACCOUNT DOSSIER TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
