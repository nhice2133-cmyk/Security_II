<?php
// Test verification for profile save alert design and security questions hiding

$tests = [];
function assertCondition($name, $cond) {
    global $tests;
    if ($cond) {
        $tests[] = "[PASS] $name";
    } else {
        $tests[] = "[FAIL] $name";
        echo "[FAIL] $name\n";
    }
}

// 1. Check pages/dashboard.php
$dash = file_get_contents(__DIR__ . '/../pages/dashboard.php');
assertCondition("dashboard.php contains modern customAlertModal", strpos($dash, 'id="customAlertModal"') !== false);
assertCondition("customAlertModal uses modern-modal-card", strpos($dash, 'class="modern-modal-card"') !== false);
assertCondition("customAlertModal has customAlertBadge and customAlertTitle", strpos($dash, 'id="customAlertBadge"') !== false && strpos($dash, 'id="customAlertTitle"') !== false);
assertCondition("customAlertModal has customAlertOkBtn", strpos($dash, 'id="customAlertOkBtn"') !== false);
assertCondition("saveDashProfileDetails calls customAlert on success", strpos($dash, "customAlert('Profile updated successfully!', 'success'") !== false);
assertCondition("saveDashProfileDetails calls showToast on success", strpos($dash, "showToast('Profile updated successfully!', 'success')") !== false);
assertCondition("window.alert is safely overridden in dashboard.php", strpos($dash, "window.alert = function(msg)") !== false);

// 2. Check js/admin.js
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
assertCondition("admin.js window.customAlert supports type, title, subtitle", strpos($adminJs, "window.customAlert = function(msg, type = 'info', title = null, subtitle = null)") !== false);
assertCondition("admin.js configures success, error, warning, info themes", strpos($adminJs, "configs = {") !== false && strpos($adminJs, "badgeBg: 'rgba(16, 185, 129, 0.16)'") !== false);
assertCondition("admin.js exposes window.showToast", strpos($adminJs, "window.showToast = showToast;") !== false);

// 3. Check js/cyberpunk-initial-setup.js
$setupJs = file_get_contents(__DIR__ . '/../js/cyberpunk-initial-setup.js');
assertCondition("cyberpunk-initial-setup.js sets opt.hidden = true for excluded questions", strpos($setupJs, "opt.hidden = true;") !== false);
assertCondition("cyberpunk-initial-setup.js sets opt.style.display = 'none'", strpos($setupJs, "opt.style.display = 'none';") !== false);
assertCondition("cyberpunk-initial-setup.js sets opt.disabled = true", strpos($setupJs, "opt.disabled = true;") !== false);
assertCondition("cyberpunk-initial-setup.js adds sq-option-hidden class", strpos($setupJs, "opt.classList.add('sq-option-hidden');") !== false);
assertCondition("cyberpunk-initial-setup.js restores options when unselected", strpos($setupJs, "opt.hidden = false;") !== false && strpos($setupJs, "opt.disabled = false;") !== false);

// 4. Check js/cyberpunk-register.js
$regJs = file_get_contents(__DIR__ . '/../js/cyberpunk-register.js');
assertCondition("cyberpunk-register.js sets opt.hidden = true for excluded questions", strpos($regJs, "opt.hidden = true;") !== false);
assertCondition("cyberpunk-register.js sets opt.style.display = 'none'", strpos($regJs, "opt.style.display = 'none';") !== false);
assertCondition("cyberpunk-register.js sets opt.disabled = true", strpos($regJs, "opt.disabled = true;") !== false);
assertCondition("cyberpunk-register.js runs syncSecurityQuestions immediately on startup", strpos($regJs, "// Run sync immediately on startup\n    syncSecurityQuestions();") !== false);

// 5. Check js/cyberpunk-security.js
$secJs = file_get_contents(__DIR__ . '/../js/cyberpunk-security.js');
assertCondition("cyberpunk-security.js sets option.hidden = true", strpos($secJs, "option.hidden = true;") !== false);
assertCondition("cyberpunk-security.js sets option.style.display = 'none'", strpos($secJs, "option.style.display = 'none';") !== false);

// 6. Check js/cyberpunk-questions.js
$qJs = file_get_contents(__DIR__ . '/../js/cyberpunk-questions.js');
assertCondition("cyberpunk-questions.js performs bidirectional exclusion for q1, q2, q3", strpos($qJs, "rebuild(q1, [s2, s3].filter(Boolean), s1);") !== false);
assertCondition("cyberpunk-questions.js attaches listener to q3", strpos($qJs, "q3.addEventListener('change', refresh);") !== false);

// 7. Check CSS files
$cssReg = file_get_contents(__DIR__ . '/../css/cyberpunk-register.css');
assertCondition("cyberpunk-register.css has disabled option styling fallback", strpos($cssReg, "select.sq-select option:disabled") !== false);
assertCondition("cyberpunk-register.css hides hidden/sq-option-hidden options", strpos($cssReg, "select.sq-select option[hidden]") !== false);

$cssPortal = file_get_contents(__DIR__ . '/../css/cyberpunk-portal.css');
assertCondition("cyberpunk-portal.css has disabled option styling fallback", strpos($cssPortal, "select.sq-select option:disabled") !== false);
assertCondition("cyberpunk-portal.css hides hidden/sq-option-hidden options", strpos($cssPortal, "select.sq-select option[hidden]") !== false);

echo "\nSummary of Test Results:\n";
foreach ($tests as $t) {
    echo $t . "\n";
}

$hasFailures = false;
foreach ($tests as $t) {
    if (strpos($t, '[FAIL]') !== false) $hasFailures = true;
}

if ($hasFailures) {
    exit(1);
} else {
    echo "\nALL TESTS PASSED SUCCESSFULLY (" . count($tests) . "/" . count($tests) . ")\n";
    exit(0);
}
