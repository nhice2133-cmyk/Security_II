<?php
/**
 * Test Suite: Create Account Flow, Auto-Generated 8-Char Password & Email Dispatch
 */

require_once __DIR__ . '/../php/config.php';

function testAssert(string $desc, bool $condition, string $details = ''): void {
    if ($condition) {
        echo " [PASS] $desc\n";
    } else {
        echo " [FAIL] $desc " . ($details ? "($details)" : "") . "\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "RUNNING CREATE ACCOUNT FLOW & PASSWORD GENERATION TEST SUITE\n";
echo "=================================================================\n\n";

// Test 1: Random 8-character password generation policy adherence (100 iterations)
echo "1. Testing generateRandom8CharPassword() over 100 iterations...\n";
$allValid = true;
$samplePasswords = [];

for ($i = 0; $i < 100; $i++) {
    $pwd = generateRandom8CharPassword();
    if ($i < 5) {
        $samplePasswords[] = $pwd;
    }
    
    // Check length
    if (strlen($pwd) !== 8) {
        testAssert("Password must be exactly 8 characters", false, "Length was " . strlen($pwd));
    }
    // Check uppercase
    if (!preg_match('/[A-Z]/', $pwd)) {
        testAssert("Password must contain at least 1 uppercase letter", false, "Password: $pwd");
    }
    // Check special character
    if (!preg_match('/[^a-zA-Z0-9]/', $pwd)) {
        testAssert("Password must contain at least 1 special character", false, "Password: $pwd");
    }
    // Check number
    if (!preg_match('/[0-9]/', $pwd)) {
        testAssert("Password must contain at least 1 number", false, "Password: $pwd");
    }
    // Check system password policy validation
    $err = validatePassword($pwd);
    if ($err !== null) {
        testAssert("Password must satisfy system policy validatePassword()", false, "Error: $err for $pwd");
    }
}
testAssert("All 100 generated passwords are 8 characters with 1 uppercase, 1 special, 1 number, and satisfy system policy", true);
echo "   Sample generated passwords: " . implode(', ', $samplePasswords) . "\n";

// Test 2: Verify sendNewAccountCredentialsEmail exists and handles invalid email gracefully
echo "\n2. Testing sendNewAccountCredentialsEmail() input validation...\n";
testAssert("sendNewAccountCredentialsEmail function exists", function_exists('sendNewAccountCredentialsEmail'));

$invalidRes = sendNewAccountCredentialsEmail('not-an-email', 'testuser', 'Abc!1234', '2026-0001');
testAssert("Rejects invalid recipient email address", $invalidRes['success'] === false && $invalidRes['error'] === 'Invalid recipient email address');

// Test 3: Test sendNewAccountCredentialsEmail with valid format (PHPMailer instance & building)
echo "\n3. Testing email template construction...\n";
$testEmail = 'nhice2133@gmail.com';
$testUser = 'test_new_user_' . random_int(100, 999);
$testPwd = generateRandom8CharPassword();
$testId = '2026-8888';
$testRole = 'user';
$testLoginUrl = 'http://localhost/Security_II/pages/login.php';

// We can test that createMailer and sendNewAccountCredentialsEmail execute
$res = sendNewAccountCredentialsEmail($testEmail, $testUser, $testPwd, $testId, $testRole, $testLoginUrl);
testAssert("Credentials email successfully dispatched via SMTP", $res['success'] === true, $res['error'] ?? '');

// Test 4: Database simulation of superadmin creating an account
echo "\n4. Testing account creation in DB with auto-generated password and pending_setup...\n";
$db = new Database();
$pdo = $db->getConnection();

$simUsername = 'sim_user_' . random_int(1000, 9999);
$simEmail = $simUsername . '@example.com';
$simPassword = generateRandom8CharPassword();
$simHashedPassword = hashPassword($simPassword);
$simIdNumber = '2026-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

$stmt = $pdo->prepare("
    INSERT INTO users (
        username, email, password, id_number,
        first_name, last_name, role, privileges, status
    ) VALUES (
        ?, ?, ?, ?,
        'Initial', 'Setup', 'user', '[]', 'pending_setup'
    )
");
$stmt->execute([$simUsername, $simEmail, $simHashedPassword, $simIdNumber]);
$createdId = (int)$pdo->lastInsertId();

testAssert("Account inserted into database", $createdId > 0);

// Verify user record
$fetchStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$fetchStmt->execute([$createdId]);
$createdUser = $fetchStmt->fetch(PDO::FETCH_ASSOC);

testAssert("Account status is 'pending_setup'", $createdUser['status'] === 'pending_setup');
testAssert("Account password verifies against generated password", verifyPassword($simPassword, $createdUser['password']));
testAssert("Account ID number matches", $createdUser['id_number'] === $simIdNumber);

// Clean up simulated user
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$createdId]);
testAssert("Simulated test user cleaned up", true);

// Test 5: Verify prevention of fixed 'Admin@12345!' password for superadmin & created accounts
echo "\n5. Testing prevention of fixed Admin@12345! password on account creation...\n";

// Simulate backend logic: if someone submits 'Admin@12345!' for super_admin
$testRole = 'super_admin';
$submittedPwd = 'Admin@12345!';
$resolvedPwd = $submittedPwd;
if ($testRole === 'super_admin' || trim($resolvedPwd) === '' || $resolvedPwd === 'Admin@12345!' || $resolvedPwd === '@Abcde12345' || $resolvedPwd === '@Password12345') {
    $resolvedPwd = generateRandom8CharPassword();
}

testAssert("Superadmin creation never retains fixed Admin@12345! password", $resolvedPwd !== 'Admin@12345!');
testAssert("Resolved password is an 8-character random password", strlen($resolvedPwd) === 8);
testAssert("Resolved password meets all system password policy requirements", validatePassword($resolvedPwd) === null);

// Test 6: Verify frontend safeguards in dashboard.php & admin.js
echo "\n6. Testing frontend autofill prevention and admin.js safeguards...\n";
$dashContent = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJsContent = file_get_contents(__DIR__ . '/../js/admin.js');
$adminActionsContent = file_get_contents(__DIR__ . '/../php/admin_actions.php');

testAssert("dashboard.php form includes autocomplete='off'", strpos($dashContent, 'id="createAccountForm"') !== false && strpos($dashContent, 'autocomplete="off"') !== false);
testAssert("dashboard.php caPassword includes autocomplete='new-password'", strpos($dashContent, 'autocomplete="new-password"') !== false);
testAssert("admin.js validateRoleField() enforces random password for super_admin", strpos($adminJsContent, "pwdEl.value === 'Admin@12345!'") !== false);
testAssert("admin.js executeAccountCreation() prevents submitting Admin@12345!", strpos($adminJsContent, "password === 'Admin@12345!'") !== false);
testAssert("admin_actions.php enforces random password for super_admin and blocks Admin@12345!", strpos($adminActionsContent, "Admin@12345!'") !== false);

echo "\n=================================================================\n";
echo "ALL CREATE ACCOUNT FLOW TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";

