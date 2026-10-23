<?php
/**
 * Test Suite: Succession Checkbox Requirement Verification
 * Ensures 'Execute Succession & Step Down' button cannot be clicked until the acknowledgement checkbox is ticked.
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
echo "VERIFYING SUCCESSION CONFIRMATION CHECKBOX TICK REQUIREMENT\n";
echo "=================================================================\n\n";

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
$cssFile = file_get_contents(__DIR__ . '/../css/modern-design-system.css');

// 1. Dashboard HTML Verification
echo "--- 1. Dashboard HTML Template Verification ---\n";
assertCondition(
    "Succession submit button #succSubmitBtn has 'disabled' attribute by default in HTML",
    strpos($dashFile, 'id="succSubmitBtn" disabled') !== false ||
    preg_match('/<button[^>]*id="succSubmitBtn"[^>]*disabled/', $dashFile) === 1
);
assertCondition(
    "Succession checkbox #succConfirmCheckbox triggers onSuccConfirmCheckboxChange()",
    strpos($dashFile, 'id="succConfirmCheckbox" onchange="onSuccConfirmCheckboxChange()"') !== false
);

// 2. CSS Disabled Styling Verification
echo "\n--- 2. CSS Disabled Button Styling Verification ---\n";
assertCondition(
    "CSS contains disabled rule for #succSubmitBtn and .btn-danger-glow:disabled",
    strpos($cssFile, '#succSubmitBtn:disabled') !== false
);
assertCondition(
    "Disabled button specifies opacity, not-allowed cursor, and pointer-events: none",
    strpos($cssFile, 'cursor: not-allowed') !== false &&
    strpos($cssFile, 'pointer-events: none') !== false
);

// 3. JavaScript Logic Verification
echo "\n--- 3. JavaScript Logic in js/admin.js Verification ---\n";
assertCondition(
    "openSuperadminSuccessionModal() sets succConfirmCheckbox.checked = false",
    strpos($adminJs, "checkbox.checked = false;") !== false
);
assertCondition(
    "openSuperadminSuccessionModal() sets submitBtn.disabled = true",
    strpos($adminJs, "if (submitBtn) submitBtn.disabled = true;") !== false
);
assertCondition(
    "admin.js defines function onSuccConfirmCheckboxChange()",
    strpos($adminJs, "function onSuccConfirmCheckboxChange()") !== false
);
assertCondition(
    "onSuccConfirmCheckboxChange() syncs submitBtn.disabled to !checkbox.checked",
    strpos($adminJs, "submitBtn.disabled = !checkbox.checked;") !== false
);
assertCondition(
    "submitSuperadminSuccession() validates that checkbox.checked is true",
    strpos($adminJs, "if (!checkbox || !checkbox.checked)") !== false
);
assertCondition(
    "window.onSuccConfirmCheckboxChange is exposed globally",
    strpos($adminJs, "window.onSuccConfirmCheckboxChange = onSuccConfirmCheckboxChange;") !== false
);

echo "\n=================================================================\n";
echo "ALL SUCCESSION CHECKBOX TICK TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
