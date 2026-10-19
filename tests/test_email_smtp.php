<?php
/**
 * tests/test_email_smtp.php
 *
 * Command-line utility to test PHPMailer Gmail SMTP connectivity and Google App Password.
 * Usage:
 *   php tests/test_email_smtp.php [recipient@gmail.com]
 */

require_once __DIR__ . '/../php/config.php';

echo "=================================================================\n";
echo "CYBERAUTH — GMAIL SMTP & PHPMailer DIAGNOSTIC TOOL\n";
echo "=================================================================\n\n";

$host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
$port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
$user = getCleanSmtpUsername();
$pass = getCleanSmtpPassword();
$from = getCleanFromEmail();

echo "1. Current Configuration:\n";
echo "   - SMTP Host:     $host\n";
echo "   - SMTP Port:     $port (" . ($port === 465 ? 'SMTPS / SSL' : 'STARTTLS') . ")\n";
echo "   - SMTP Username: " . ($user ?: '[EMPTY - Please set in .env]') . "\n";
echo "   - SMTP Password: " . (empty($pass) ? '[EMPTY - Please set in .env]' : (strlen($pass) . " characters (" . substr($pass, 0, 2) . str_repeat('*', max(1, strlen($pass) - 4)) . substr($pass, -2) . ")")) . "\n";
echo "   - From Address:  " . ($from ?: '[EMPTY]') . "\n\n";

if (empty($user) || empty($pass) || $pass === 'your_16_character_app_password' || $pass === 'your-app-password') {
    echo "⚠️  SMTP credentials are not yet configured in .env!\n\n";
    echo "To configure Gmail Google App Password:\n";
    echo "1. Go to https://myaccount.google.com/security\n";
    echo "2. Turn on '2-Step Verification'\n";
    echo "3. Go to https://myaccount.google.com/apppasswords\n";
    echo "4. Generate a 16-character App Password (e.g., 'abcd efgh ijkl mnop')\n";
    echo "5. Edit c:\\xampp\\htdocs\\Security_II\\.env:\n";
    echo "   SMTP_USERNAME=your_email@gmail.com\n";
    echo "   SMTP_PASSWORD=your_16_character_app_password\n";
    echo "   FROM_EMAIL=your_email@gmail.com\n\n";
    exit(1);
}

// Determine recipient
$recipient = $argv[1] ?? $user;
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid recipient email: $recipient\n";
    exit(1);
}

echo "2. Testing socket connection to $host:$port...\n";
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$fp) {
    echo "❌ Failed to connect to $host:$port — $errstr ($errno)\n";
    echo "   Make sure your firewall or network permits outgoing connections on port $port.\n";
    exit(1);
}
fclose($fp);
echo "✅ Socket connection successful!\n\n";

echo "3. Sending Test OTP Email to: $recipient (SMTP Debug Output Enabled)...\n";
echo "-----------------------------------------------------------------\n";

$testOtp = sprintf("%06d", random_int(100000, 999999));
$res = sendSystemOtpEmail($recipient, 'Test Admin', $testOtp, true);

echo "\n-----------------------------------------------------------------\n";
if ($res['success']) {
    echo "🎉 SUCCESS! Test OTP ($testOtp) sent successfully to $recipient!\n";
    echo "Please check your inbox (and Spam folder) for the CyberAuth OTP email.\n";
    exit(0);
} else {
    echo "❌ FAILED: " . $res['error'] . "\n";
    exit(1);
}
