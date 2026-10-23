<?php
/**
 * Test Suite: Suspend Account Removal Verification
 * Ensures 'Suspend Account' is completely removed in favor of 'Deactivate Account'.
 */

require_once __DIR__ . '/../php/config.php';

function assertCondition(string $name, bool $passed): void {
    if ($passed) {
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "VERIFYING SUSPEND ACCOUNT REMOVAL IN FAVOR OF DEACTIVATE ACCOUNT\n";
echo "=================================================================\n\n";

$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
$dashboard = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminActions = file_get_contents(__DIR__ . '/../php/admin_actions.php');

// 1. Dropdown Menu Verification in admin.js
echo "--- 1. Dropdown Menu Verification in js/admin.js ---\n";
assertCondition(
    "Dropdown menu does NOT contain 'item-suspend'",
    strpos($adminJs, "'item-suspend'") === false
);
assertCondition(
    "Dropdown menu does NOT contain 'Suspend Account'",
    strpos($adminJs, "Suspend Account") === false
);
assertCondition(
    "Dropdown menu does NOT contain 'item-restore'",
    strpos($adminJs, "'item-restore'") === false
);
assertCondition(
    "Dropdown menu does NOT contain 'Restore Account'",
    strpos($adminJs, "Restore Account") === false
);
assertCondition(
    "Dropdown menu includes 'item-deactivate' ('Deactivate Account')",
    strpos($adminJs, "'item-deactivate'") !== false && strpos($adminJs, "Deactivate Account") !== false
);
assertCondition(
    "Dropdown menu includes 'item-reactivate' ('Reactivate Account')",
    strpos($adminJs, "'item-reactivate'") !== false && strpos($adminJs, "Reactivate Account") !== false
);
assertCondition(
    "Dropdown menu includes Change Role dropdown",
    strpos($adminJs, "Change Role:") !== false
);
assertCondition(
    "Dropdown menu includes Delete User option",
    strpos($adminJs, "Delete User") !== false
);
assertCondition(
    "Dropdown menu includes Edit Privileges option",
    strpos($adminJs, "Edit Privileges") !== false
);
assertCondition(
    "Dropdown menu includes View Audit History option",
    strpos($adminJs, "View Audit History") !== false
);

// 2. Status Badge Verification in admin.js
echo "\n--- 2. Status Badge Verification in js/admin.js ---\n";
assertCondition(
    "admin.js maps inactive, suspended, and blocked users to isStatusInactive",
    strpos($adminJs, "const isStatusInactive = (normStatus === 'inactive' || normStatus === 'suspended' || normStatus === 'blocked');") !== false
);
assertCondition(
    "Table status badge does NOT render a separate 'status-suspended' badge",
    strpos($adminJs, "status-suspended") === false
);

// 3. Dashboard Filter and Labels in pages/dashboard.php
echo "\n--- 3. Filter & Privilege Labels in pages/dashboard.php ---\n";
assertCondition(
    "User status filter dropdown does NOT have '<option value=\"suspended\">'",
    strpos($dashboard, '<option value="suspended">') === false
);
assertCondition(
    "User status filter dropdown has 'Active' and 'Inactive' options",
    strpos($dashboard, '<option value="active">Active</option>') !== false &&
    strpos($dashboard, '<option value="inactive">Inactive</option>') !== false
);
assertCondition(
    "Privilege modules refer to 'Deactivate / Reactivate Users'",
    strpos($dashboard, "Deactivate / Reactivate Users") !== false
);

// 4. Backend Actions in php/admin_actions.php
echo "\n--- 4. Backend Actions & Normalization in php/admin_actions.php ---\n";
assertCondition(
    "php/admin_actions.php normalizes inactive/suspended/blocked to 'inactive'",
    strpos($adminActions, "\$uStatus === 'inactive' || \$uStatus === 'suspended' || \$uStatus === 'blocked'") !== false
);
assertCondition(
    "filterStatus === 'inactive' includes inactive, suspended, and blocked accounts",
    strpos($adminActions, "\$query .= \" AND (status IN ('inactive', 'suspended', 'blocked') OR is_active = 0)\";") !== false
);

echo "\n=================================================================\n";
echo "ALL SUSPEND REMOVAL VERIFICATION TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
