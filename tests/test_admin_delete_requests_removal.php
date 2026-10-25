<?php
/**
 * Test Suite: Admin Delete Requests Removal Verification
 * Ensures Deletion Requests tab, action cards, and user-row request deletion actions
 * are completely removed for standard Administrators, and strictly restricted to Superadmin.
 */

function assertCondition(string $name, bool $passed): void {
    if ($passed) {
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "VERIFYING DELETE REQUESTS REMOVAL ON ADMIN\n";
echo "=================================================================\n\n";

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
$adminActions = file_get_contents(__DIR__ . '/../php/admin_actions.php');

// 1. Dashboard Template Verification
echo "--- 1. Dashboard HTML Template Verification ---\n";
assertCondition(
    "Sidebar Deletion Requests tab only checks role === 'super_admin'",
    strpos($dashFile, "<?php if (\$role === 'super_admin'): ?>\n            <button class=\"tab-btn\" data-tab=\"delete_reqs\"") !== false ||
    preg_match('/<\?php\s+if\s*\(\$role\s*===\s*\'super_admin\'\)\s*:\s*\?>\s*<button[^>]*data-tab="delete_reqs"/', $dashFile) === 1
);
assertCondition(
    "Action card Deletion Requests only checks role === 'super_admin'",
    preg_match('/<\?php\s+if\s*\(\$role\s*===\s*\'super_admin\'\)\s*:\s*\?>\s*<button[^>]*onclick="showTab\(\'delete_reqs\'\)"/', $dashFile) === 1
);
assertCondition(
    "Tab-content container #delete_reqs only renders for role === 'super_admin'",
    preg_match('/<\?php\s+if\s*\(\$role\s*===\s*\'super_admin\'\)\s*:\s*\?>\s*<div\s+id="delete_reqs"\s+class="tab-content"/', $dashFile) === 1
);
assertCondition(
    "No dashboard tab checks still allow admin for delete_reqs",
    strpos($dashFile, "admin' && in_array('account_deletion_requests'") === false
);

// 2. JavaScript UI Verification
echo "\n--- 2. JavaScript Logic in js/admin.js Verification ---\n";
assertCondition(
    "Actions dropdown menu does NOT add 'Request Deletion' (req_delete) for admins",
    strpos($adminJs, "makeDropdownItem('<span>🗑️</span> Request Deletion', 'item-delete', 'req_delete'") === false
);
assertCondition(
    "Only isSuperAdmin is given delete action in actions dropdown",
    strpos($adminJs, "if (isSuperAdmin && !isTargetCurrentSa) {") !== false &&
    strpos($adminJs, "makeDropdownItem('<span>🗑️</span> Delete User', 'item-delete', 'delete'") !== false
);
assertCondition(
    "loadDeleteRequests() guards execution for super_admin role only",
    strpos($adminJs, "async function loadDeleteRequests() {\n    if (userRole !== 'super_admin') return;") !== false
);

// 3. Backend Authorization Verification
echo "\n--- 3. Backend Authorization in php/admin_actions.php Verification ---\n";
assertCondition(
    "get_delete_requests requires requireSuperAdmin()",
    preg_match('/if\s*\(\$action\s*===\s*\'get_delete_requests\'\)\s*\{\s*requireSuperAdmin\(\);/', $adminActions) === 1
);
assertCondition(
    "handle_delete_request requires requireSuperAdminWithPassword()",
    preg_match('/if\s*\(\$action\s*===\s*\'handle_delete_request\'\)\s*\{\s*requireSuperAdminWithPassword\(\);/', $adminActions) === 1
);
assertCondition(
    "delete_user rejects non-super_admin with access denied",
    strpos($adminActions, "if (\$role !== 'super_admin') {\n        sendJsonResponse(false, 'Access denied. Account deletion is restricted to Super Admin only.');\n    }") !== false
);

echo "\n=================================================================\n";
echo "ALL ADMIN DELETE REQUESTS REMOVAL TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
