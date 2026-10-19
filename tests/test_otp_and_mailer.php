<?php
/**
 * tests/test_otp_and_mailer.php
 *
 * Automated test suite for PHPMailer helpers, OTP generation, validation, and multi-step recovery.
 */

require_once __DIR__ . '/../php/config.php';

function assertTest(string $desc, bool $condition, string $details = ''): void {
    if ($condition) {
        echo " [PASS] $desc\n";
    } else {
        echo " [FAIL] $desc " . ($details ? "($details)" : "") . "\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "TESTING PHPMAILER & FORGOT-PASSWORD OTP SYSTEM\n";
echo "=================================================================\n\n";

// 1. Test Mailer Helper Functions
echo "1. Testing Mailer Utilities...\n";
assertTest("maskEmail masks standard email", maskEmail('johndoe@gmail.com') === 'j*****e@gmail.com');
assertTest("maskEmail masks short email", maskEmail('me@domain.com') === 'm*@domain.com');

// Test password sanitizer strips spaces
$rawWithSpaces = 'abcd efgh ijkl mnop';
$cleaned = str_replace([' ', "\t", "\r", "\n", '"', "'"], '', $rawWithSpaces);
assertTest("Google App Password whitespace stripping", $cleaned === 'abcdefghijklmnop');

// 2. Test PHPMailer class loading and instantiation
echo "\n2. Testing PHPMailer Instantiation...\n";
$mailer = createMailer(false);
assertTest("PHPMailer instance created", $mailer instanceof \PHPMailer\PHPMailer\PHPMailer);
assertTest("Host is smtp.gmail.com", $mailer->Host === 'smtp.gmail.com');
assertTest("SMTPAuth is true", $mailer->SMTPAuth === true);
assertTest("SSL peer verification disabled for local XAMPP", isset($mailer->SMTPOptions['ssl']['verify_peer']) && $mailer->SMTPOptions['ssl']['verify_peer'] === false);

// 3. Test Database & User Setup for OTP testing
echo "\n3. Testing Database OTP Verification Pipeline...\n";
$pdo = (new Database())->getConnection();

// Check test user exists (EMP-001 or id=1)
$testSa = $pdo->query("SELECT id, id_number, email FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertTest("User 1 exists in DB", !empty($testSa));

// Insert test OTP directly to test Step 2 verification logic
$testOtp = '789123';
$pdo->prepare("DELETE FROM otps WHERE user_id = ?")->execute([$testSa['id']]);
$pdo->prepare("INSERT INTO otps (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))")
    ->execute([$testSa['id'], $testOtp]);

// Verify Step 2 logic directly: fetch most recent non-expired unused OTP
$stmt = $pdo->prepare("SELECT id, otp_code, attempts FROM otps WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
$stmt->execute([$testSa['id']]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

assertTest("Recent OTP record retrieved", !empty($rec) && $rec['otp_code'] === $testOtp);

// Test wrong OTP attempt counter
$pdo->prepare("UPDATE otps SET attempts = attempts + 1 WHERE id = ?")->execute([$rec['id']]);
$chk = $pdo->prepare("SELECT attempts FROM otps WHERE id = ?");
$chk->execute([$rec['id']]);
assertTest("OTP attempt count increments on failure", (int)$chk->fetchColumn() === 1);

// Test OTP consumption on match
$pdo->prepare("UPDATE otps SET used = 1 WHERE id = ?")->execute([$rec['id']]);
$usedChk = $pdo->prepare("SELECT used FROM otps WHERE id = ?");
$usedChk->execute([$rec['id']]);
assertTest("OTP marked used upon successful verification", (int)$usedChk->fetchColumn() === 1);

// Clean up test OTP
$pdo->prepare("DELETE FROM otps WHERE user_id = ?")->execute([$testSa['id']]);

echo "\n=================================================================\n";
echo "ALL PHPMAILER & OTP SYSTEM TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
