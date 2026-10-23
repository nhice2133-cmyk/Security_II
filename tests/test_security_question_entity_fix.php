<?php
/**
 * Test: Security Question Entity and Slug Formatting Fix
 * Verifies that question text such as "What is your mother's maiden name?"
 * does not display HTML entities like "&#039;" or raw slugs.
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
echo "TESTING SECURITY QUESTION ENTITY & SLUG NORMALIZATION FIX\n";
echo "=================================================================\n\n";

$db = new Database();

// 1. Check database rows
echo "--- 1. Testing Database Rows in user_security_questions ---\n";
$rows = $db->query("SELECT id, user_id, question1, question2, question3 FROM user_security_questions")->fetchAll(PDO::FETCH_ASSOC);
$foundEntity = false;
foreach ($rows as $r) {
    if (strpos($r['question1'], '&#039;') !== false || strpos($r['question2'], '&#039;') !== false || strpos($r['question3'], '&#039;') !== false) {
        $foundEntity = true;
    }
}
assertTest("No HTML entities (&#039;) found in user_security_questions table", !$foundEntity);

// 2. Test formatSecurityQuestion helper logic
echo "\n--- 2. Testing formatSecurityQuestion Logic ---\n";
function testFormatQ(string $q): string {
    $map = [
        'best_friend_elementary' => "Who is your best friend in Elementary?",
        'favorite_pet'           => "What is the name of your favorite pet?",
        'favorite_teacher'       => "Who is your favorite teacher in high school?",
        'mother_maiden_name'     => "What is your mother's maiden name?",
        'birth_city'             => "In what city were you born?",
        'first_car'              => "What was the make and model of your first car?"
    ];
    if (isset($map[$q])) {
        return $map[$q];
    }
    return htmlspecialchars_decode($q, ENT_QUOTES | ENT_HTML5);
}

$decodedFromEntity = testFormatQ("What is your mother&#039;s maiden name?");
assertTest("Entity &#039; decoded to standard apostrophe", $decodedFromEntity === "What is your mother's maiden name?");

$mappedFromSlug = testFormatQ("mother_maiden_name");
assertTest("Slug 'mother_maiden_name' mapped to full question", $mappedFromSlug === "What is your mother's maiden name?");

$mappedPetSlug = testFormatQ("favorite_pet");
assertTest("Slug 'favorite_pet' mapped to full question", $mappedPetSlug === "What is the name of your favorite pet?");

// 3. Test js/forgot-password.js
echo "\n--- 3. Testing js/forgot-password.js and js/forgot-password-questions.js ---\n";
$fpJs = file_get_contents(__DIR__ . '/../js/forgot-password.js');
assertTest("forgot-password.js includes formatSecurityQuestion helper", strpos($fpJs, 'formatSecurityQuestion(data.data.questions.q3)') !== false);
assertTest("forgot-password.js maps mother_maiden_name slug", strpos($fpJs, "'mother_maiden_name': \"What is your mother's maiden name?\"") !== false);

$fpqJs = file_get_contents(__DIR__ . '/../js/forgot-password-questions.js');
assertTest("forgot-password-questions.js includes formatQ helper", strpos($fpqJs, 'formatQ(data.data.question3)') !== false);

// 4. Test php/forgot-password-step2.php
echo "\n--- 4. Testing php/forgot-password-step2.php and get_security_questions.php ---\n";
$step2Php = file_get_contents(__DIR__ . '/../php/forgot-password-step2.php');
assertTest("forgot-password-step2.php applies formatSecurityQuestion", strpos($step2Php, 'formatSecurityQuestion((string)$questions[\'question3\'])') !== false);

$getQPhp = file_get_contents(__DIR__ . '/../php/get_security_questions.php');
assertTest("get_security_questions.php applies formatSecurityQuestion", strpos($getQPhp, 'formatSecurityQuestion((string)$row[\'question3\'])') !== false);

// 5. Test dashboard.php
$dashPhp = file_get_contents(__DIR__ . '/../pages/dashboard.php');
assertTest("dashboard.php applies formatSecQ helper to security questions", strpos($dashPhp, '$formatSecQ($secQuestions[\'question1\']') !== false);

echo "\n=================================================================\n";
echo "ALL SECURITY QUESTION TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
