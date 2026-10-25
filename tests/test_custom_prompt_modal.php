<?php
/**
 * Test Suite: Custom Prompt Modal Verification
 * Verifies that the native browser prompt dialog has been replaced with
 * a modern, themed customPrompt modal for deletion requests and admin prompts.
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
echo "VERIFYING CUSTOM PROMPT MODAL IMPLEMENTATION\n";
echo "=================================================================\n\n";

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');

// 1. Dashboard Modal Template Verification
echo "--- 1. Dashboard Modal Template Verification ---\n";
assertCondition(
    "Dashboard contains #customPromptModal backdrop element",
    strpos($dashFile, 'id="customPromptModal"') !== false
);
assertCondition(
    "Modal has header with #customPromptTitle and #customPromptSubtitle",
    strpos($dashFile, 'id="customPromptTitle"') !== false &&
    strpos($dashFile, 'id="customPromptSubtitle"') !== false
);
assertCondition(
    "Modal contains target user callout #customPromptTargetWrap and #customPromptTargetName",
    strpos($dashFile, 'id="customPromptTargetWrap"') !== false &&
    strpos($dashFile, 'id="customPromptTargetName"') !== false
);
assertCondition(
    "Modal contains #customPromptMessage and textarea #customPromptInput",
    strpos($dashFile, 'id="customPromptMessage"') !== false &&
    strpos($dashFile, 'id="customPromptInput"') !== false
);
assertCondition(
    "Modal contains #customPromptError validation container",
    strpos($dashFile, 'id="customPromptError"') !== false
);
assertCondition(
    "Modal footer contains cancel and submit buttons (#customPromptSubmitBtn)",
    strpos($dashFile, 'onclick="resolveCustomPrompt(null)"') !== false &&
    strpos($dashFile, 'id="customPromptSubmitBtn"') !== false &&
    strpos($dashFile, 'onclick="submitCustomPrompt()"') !== false
);

// 2. JavaScript Logic Verification
echo "\n--- 2. JavaScript Logic in js/admin.js Verification ---\n";
assertCondition(
    "admin.js implements window.customPrompt returning a Promise",
    strpos($adminJs, 'window.customPrompt = function(') !== false &&
    strpos($adminJs, 'return new Promise(resolve => {') !== false
);
assertCondition(
    "admin.js implements window.submitCustomPrompt() with input validation",
    strpos($adminJs, 'window.submitCustomPrompt = function()') !== false &&
    strpos($adminJs, 'Please enter a reason before submitting') !== false
);
assertCondition(
    "admin.js implements window.resolveCustomPrompt()",
    strpos($adminJs, 'window.resolveCustomPrompt = function(') !== false
);
assertCondition(
    "deleteUser() calls customPrompt with rich options (title, subtitle, targetUser)",
    strpos($adminJs, 'title: "REQUEST ACCOUNT DELETION"') !== false &&
    strpos($adminJs, 'subtitle: "Superadmin Deletion Approval Required"') !== false &&
    strpos($adminJs, 'targetUser: targetDisplay') !== false
);
assertCondition(
    "Dropdown actions pass username and fullname to deleteUser",
    strpos($adminJs, 'await deleteUser(uid, true, btn.dataset.username || \'\', btn.dataset.fullname || \'\')') !== false
);
assertCondition(
    "Global window exposures include customPrompt, submitCustomPrompt, resolveCustomPrompt",
    strpos($adminJs, 'window.customPrompt = customPrompt;') !== false &&
    strpos($adminJs, 'window.submitCustomPrompt = submitCustomPrompt;') !== false &&
    strpos($adminJs, 'window.resolveCustomPrompt = resolveCustomPrompt;') !== false
);

echo "\n=================================================================\n";
echo "ALL CUSTOM PROMPT MODAL TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
