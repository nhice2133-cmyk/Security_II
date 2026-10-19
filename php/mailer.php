<?php
/**
 * php/mailer.php
 *
 * Centralized PHPMailer SMTP service for CyberAuth System.
 * Tailored for Gmail SMTP with Google App Passwords.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Returns a sanitized 16-character Google App Password (strips all whitespace/newlines).
 */
function getCleanSmtpPassword(): string {
    $raw = defined('SMTP_PASSWORD') ? (string)SMTP_PASSWORD : '';
    return str_replace([' ', "\t", "\r", "\n", '"', "'"], '', $raw);
}

/**
 * Returns a sanitized SMTP username (full Gmail address).
 */
function getCleanSmtpUsername(): string {
    $raw = defined('SMTP_USERNAME') ? (string)SMTP_USERNAME : '';
    return trim(str_replace(['"', "'"], '', $raw));
}

/**
 * Returns a valid From email address.
 * Falls back to SMTP_USERNAME because Gmail rejects sending from localhost/arbitrary domains.
 */
function getCleanFromEmail(): string {
    $from = defined('FROM_EMAIL') ? trim(str_replace(['"', "'"], '', (string)FROM_EMAIL)) : '';
    if (!empty($from) && filter_var($from, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($from), 'localhost')) {
        return $from;
    }
    return getCleanSmtpUsername();
}

/**
 * Creates and configures a PHPMailer instance for Gmail SMTP.
 *
 * @param bool $debug If true, prints SMTP transaction output
 * @return PHPMailer
 */
function createMailer(bool $debug = false): PHPMailer {
    $mail = new PHPMailer(true);

    $username = getCleanSmtpUsername();
    $password = getCleanSmtpPassword();
    $host     = defined('SMTP_HOST') && SMTP_HOST ? trim((string)SMTP_HOST) : 'smtp.gmail.com';
    $port     = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
    if ($port <= 0) $port = 587;

    // SMTP Protocol
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $username;
    $mail->Password   = $password;

    // Encryption & Port
    if ($port === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;
    }

    // Windows / Localhost SSL certificate compatibility (prevents certificate verify failed errors on XAMPP)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ];

    // Connection timeout (seconds)
    $mail->Timeout = 20;

    // Sender details
    $fromEmail = getCleanFromEmail();
    $fromName  = defined('FROM_NAME') && FROM_NAME ? trim((string)FROM_NAME) : 'CyberAuth System';

    if (!empty($fromEmail)) {
        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($fromEmail, $fromName);
    }

    // Character set & encoding
    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';

    if ($debug) {
        $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'echo';
    } else {
        $mail->SMTPDebug   = SMTP::DEBUG_OFF;
    }

    return $mail;
}

/**
 * Sends a stylized CyberAuth One-Time Password (OTP) verification email.
 *
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $otpCode
 * @param bool $debug
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendSystemOtpEmail(string $recipientEmail, string $recipientName, string $otpCode, bool $debug = false): array {
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid recipient email address'];
    }

    $username = getCleanSmtpUsername();
    $password = getCleanSmtpPassword();

    // Check if SMTP is configured
    if (empty($username) || empty($password) || $password === 'your-app-password' || $password === 'your_16_char_google_app_password') {
        error_log("[CyberAuth OTP DEV] Missing SMTP credentials. Recipient: $recipientEmail — OTP: $otpCode");
        return [
            'success' => false,
            'error'   => 'SMTP credentials are not configured in .env. Please set your Gmail address (SMTP_USERNAME) and 16-character Google App Password (SMTP_PASSWORD).'
        ];
    }

    try {
        $mail = createMailer($debug);
        $safeName = trim($recipientName) !== '' ? $recipientName : 'User';
        $mail->addAddress($recipientEmail, $safeName);

        $mail->isHTML(true);
        $mail->Subject = 'CyberAuth — Password Recovery Verification Code (' . $otpCode . ')';

        $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
        $safeUserName = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');

        // CyberAuth Neon / Dark Email Template
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CyberAuth Verification Code</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #0d1117;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #c9d1d9;
    }
    .wrapper {
      width: 100%;
      background-color: #0d1117;
      padding: 40px 10px;
      box-sizing: border-box;
    }
    .card {
      max-width: 560px;
      margin: 0 auto;
      background-color: #161b22;
      border: 1px solid #30363d;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
    }
    .header {
      background: linear-gradient(135deg, #090d16 0%, #1a1f2c 100%);
      padding: 30px 24px;
      text-align: center;
      border-bottom: 2px solid #00ffcc;
    }
    .brand {
      font-size: 24px;
      font-weight: 800;
      letter-spacing: 2px;
      color: #ffffff;
      margin: 0;
    }
    .brand-accent {
      color: #00ffcc;
    }
    .content {
      padding: 32px 28px;
    }
    .title {
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
      margin: 0 0 14px 0;
    }
    .paragraph {
      font-size: 15px;
      line-height: 1.6;
      color: #8b949e;
      margin: 0 0 20px 0;
    }
    .otp-container {
      background: rgba(0, 255, 204, 0.04);
      border: 2px dashed #00ffcc;
      border-radius: 10px;
      padding: 24px 16px;
      text-align: center;
      margin: 28px 0;
    }
    .otp-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #00ffcc;
      font-weight: 700;
      margin-bottom: 10px;
    }
    .otp-digits {
      font-family: 'Courier New', Courier, monospace;
      font-size: 40px;
      font-weight: 800;
      letter-spacing: 12px;
      color: #ffffff;
      margin: 0;
      padding-left: 12px;
    }
    .alert-box {
      background-color: rgba(255, 123, 114, 0.08);
      border-left: 3px solid #f85149;
      padding: 12px 16px;
      border-radius: 4px;
      font-size: 13px;
      line-height: 1.5;
      color: #ff7b72;
      margin: 24px 0 16px 0;
    }
    .footer {
      padding: 20px 24px;
      background-color: #090d16;
      text-align: center;
      font-size: 12px;
      color: #484f58;
      border-top: 1px solid #21262d;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <div class="brand">⚡ CYBER<span class="brand-accent">AUTH</span></div>
      </div>
      <div class="content">
        <h2 class="title">Password Recovery Verification</h2>
        <p class="paragraph">Hello <strong>{$safeUserName}</strong>,</p>
        <p class="paragraph">A request was made to verify your identity and reset your CyberAuth account password. Use the authorization code below to complete verification:</p>
        
        <div class="otp-container">
          <div class="otp-label">One-Time Verification Code</div>
          <div class="otp-digits">{$safeOtp}</div>
        </div>

        <p class="paragraph">This code will expire in <strong>15 minutes</strong>.</p>

        <div class="alert-box">
          <strong>Security Notice:</strong> Never share this verification code with anyone. CyberAuth administrators will never ask for your code.
        </div>

        <p class="paragraph" style="font-size: 13px; color: #6e7681; margin-bottom: 0;">
          If you did not initiate this request, someone may be trying to access your account. You can disregard this email or review your security credentials.
        </p>
      </div>
      <div class="footer">
        &copy; 2026 CyberAuth System &bull; High-Security Authentication
      </div>
    </div>
  </div>
</body>
</html>
HTML;

        // Plain text alternative for anti-spam and text-only mail clients
        $mail->AltBody = "CYBERAUTH SYSTEM — PASSWORD RECOVERY VERIFICATION\n\n"
            . "Hello {$safeName},\n\n"
            . "Your One-Time Password (OTP) verification code is: {$otpCode}\n\n"
            . "This code is valid for 15 minutes.\n\n"
            . "SECURITY NOTICE: Never share this code with anyone.\n"
            . "If you did not request this verification code, please ignore this email.\n";

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        $rawError = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
        error_log("[CyberAuth PHPMailer Error] " . $rawError);

        // Friendly error parsing for Gmail
        $friendlyError = $rawError;
        if (stripos($rawError, 'Username and Password not accepted') !== false || stripos($rawError, '535') !== false || stripos($rawError, 'Could not authenticate') !== false) {
            $friendlyError = 'Gmail authentication failed. Please ensure 2-Step Verification is ON in your Google Account and that you are using a 16-character Google App Password (not your regular Gmail password) in .env.';
        } elseif (stripos($rawError, 'Could not connect to SMTP host') !== false) {
            $friendlyError = 'Could not connect to Gmail SMTP server. Please check your internet connection and verify that port ' . ($mail->Port ?? 587) . ' is not blocked by a firewall.';
        } elseif (stripos($rawError, 'Invalid address') !== false) {
            $friendlyError = 'The recipient email address is invalid.';
        }

        return ['success' => false, 'error' => $friendlyError];
    }
}

/**
 * Obfuscates an email address for display (e.g., "u***r@gmail.com").
 */
function maskEmail(string $email): string {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = $user[0] . '*';
    } else {
        $maskedUser = $user[0] . str_repeat('*', min(6, $len - 2)) . $user[$len - 1];
    }
    return $maskedUser . '@' . $domain;
}
