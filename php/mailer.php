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
function createMailer(bool $debug = false, ?int $overridePort = null): PHPMailer {
    $mail = new PHPMailer(true);

    $username = getCleanSmtpUsername();
    $password = getCleanSmtpPassword();
    $host     = defined('SMTP_HOST') && SMTP_HOST ? trim((string)SMTP_HOST) : 'smtp.gmail.com';
    $port     = $overridePort ?? (defined('SMTP_PORT') ? (int)SMTP_PORT : 465);
    if ($port <= 0) $port = 465;

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
function sendSystemOtpEmail(string $recipientEmail, string $recipientName, string $otpCode, bool $debug = false, ?int $port = null): array {
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
        $mail = createMailer($debug, $port);
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

        // Auto-failover: If connection to SMTP host failed/timed out, automatically retry with alternate port (587 <-> 465)
        if ($port === null && (stripos($rawError, 'Could not connect') !== false || stripos($rawError, 'timed out') !== false || stripos($rawError, '10060') !== false)) {
            $currentPort = $mail->Port ?? 465;
            $fallbackPort = ($currentPort === 465) ? 587 : 465;
            error_log("[CyberAuth PHPMailer] Port $currentPort failed. Auto-retrying with fallback port $fallbackPort...");
            return sendSystemOtpEmail($recipientEmail, $recipientName, $otpCode, $debug, $fallbackPort);
        }

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

/**
 * Sends a stylized CyberAuth initial account credentials email when an account
 * is created by a Super Admin.
 *
 * @param string $recipientEmail
 * @param string $recipientUsername
 * @param string $plainPassword
 * @param string $idNumber
 * @param string $role
 * @param string|null $loginUrl
 * @param bool $debug
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendNewAccountCredentialsEmail(
    string $recipientEmail,
    string $recipientUsername,
    string $plainPassword,
    string $idNumber,
    string $role = 'user',
    ?string $loginUrl = null,
    bool $debug = false,
    ?int $port = null
): array {
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid recipient email address'];
    }

    $username = getCleanSmtpUsername();
    $password = getCleanSmtpPassword();

    // Check if SMTP is configured
    if (empty($username) || empty($password) || $password === 'your-app-password' || $password === 'your_16_char_google_app_password') {
        error_log("[CyberAuth Account Email DEV] Missing SMTP credentials. Recipient: $recipientEmail — Username: $recipientUsername");
        return [
            'success' => false,
            'error'   => 'SMTP credentials are not configured in .env. Please set your Gmail address (SMTP_USERNAME) and 16-character Google App Password (SMTP_PASSWORD).'
        ];
    }

    // Determine readable role label
    $roleMap = [
        'super_admin' => 'Super Administrator',
        'admin'       => 'Administrator',
        'user'        => 'Standard User'
    ];
    $roleLabel = $roleMap[$role] ?? ucfirst($role);

    // Default login URL if omitted
    if (empty($loginUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $loginUrl = $protocol . $host . '/Security_II/pages/login.php';
    }

    try {
        $mail = createMailer($debug, $port);
        $safeName = trim($recipientUsername) !== '' ? $recipientUsername : 'User';
        $mail->addAddress($recipientEmail, $safeName);

        $mail->isHTML(true);
        $mail->Subject = 'CyberAuth — Your Account Credentials (@' . $safeName . ')';

        $safeUsername = htmlspecialchars($recipientUsername, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');
        $safeIdNumber = htmlspecialchars($idNumber, ENT_QUOTES, 'UTF-8');
        $safeRole     = htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

        // Stylized Cyberpunk Dark Email Template
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your CyberAuth Account Credentials</title>
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
      max-width: 580px;
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
    .details-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      background: #0d1117;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #30363d;
    }
    .details-table td {
      padding: 12px 16px;
      border-bottom: 1px solid #21262d;
      font-size: 14px;
    }
    .details-table tr:last-child td {
      border-bottom: none;
    }
    .details-label {
      color: #8b949e;
      font-weight: 600;
      width: 38%;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 1px;
    }
    .details-value {
      color: #ffffff;
      font-weight: 600;
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    }
    .password-container {
      background: rgba(0, 255, 204, 0.05);
      border: 2px dashed #00ffcc;
      border-radius: 10px;
      padding: 20px 16px;
      text-align: center;
      margin: 24px 0;
    }
    .password-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #00ffcc;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .password-digits {
      font-family: 'Courier New', Courier, monospace;
      font-size: 30px;
      font-weight: 800;
      letter-spacing: 6px;
      color: #ffffff;
      margin: 0;
      padding: 4px 0;
    }
    .btn-wrap {
      text-align: center;
      margin: 28px 0;
    }
    .login-btn {
      display: inline-block;
      background: linear-gradient(135deg, #00ffcc 0%, #00ccaa 100%);
      color: #0d1117 !important;
      font-weight: 800;
      font-size: 15px;
      text-decoration: none;
      padding: 14px 34px;
      border-radius: 8px;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 15px rgba(0, 255, 204, 0.3);
    }
    .info-box {
      background-color: rgba(56, 139, 253, 0.1);
      border-left: 3px solid #388bfd;
      padding: 14px 16px;
      border-radius: 4px;
      font-size: 13px;
      line-height: 1.6;
      color: #79c0ff;
      margin: 24px 0 16px 0;
    }
    .alert-box {
      background-color: rgba(255, 123, 114, 0.08);
      border-left: 3px solid #f85149;
      padding: 12px 16px;
      border-radius: 4px;
      font-size: 13px;
      line-height: 1.5;
      color: #ff7b72;
      margin: 16px 0 20px 0;
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
        <h2 class="title">Account Created Successfully</h2>
        <p class="paragraph">Hello <strong>@{$safeUsername}</strong>,</p>
        <p class="paragraph">An account has been created for you by the Super Administrator on CyberAuth System. You can use the temporary credentials below to log in:</p>
        
        <table class="details-table">
          <tr>
            <td class="details-label">User ID</td>
            <td class="details-value">{$safeIdNumber}</td>
          </tr>
          <tr>
            <td class="details-label">Username</td>
            <td class="details-value">@{$safeUsername}</td>
          </tr>
          <tr>
            <td class="details-label">Role</td>
            <td class="details-value">{$safeRole}</td>
          </tr>
        </table>

        <div class="password-container">
          <div class="password-label">Auto-Generated Temporary Password</div>
          <div class="password-digits">{$safePassword}</div>
        </div>

        <div class="btn-wrap">
          <a href="{$safeLoginUrl}" class="login-btn" target="_blank">Sign In to CyberAuth</a>
        </div>

        <div class="info-box">
          <strong>Mandatory First-Time Setup:</strong> Your account is currently in <strong>Pending Setup</strong> status. Upon your first login with this temporary password, the system will guide you to complete your profile, set a personalized password, and answer security questions before access is activated.
        </div>

        <div class="alert-box">
          <strong>Security Notice:</strong> Keep these credentials confidential. CyberAuth administrators will never ask for your password.
        </div>
      </div>
      <div class="footer">
        &copy; 2026 CyberAuth System &bull; High-Security Authentication
      </div>
    </div>
  </div>
</body>
</html>
HTML;

        // Plain text alternative
        $mail->AltBody = "CYBERAUTH SYSTEM — ACCOUNT CREATION NOTICE\n\n"
            . "Hello @{$recipientUsername},\n\n"
            . "An account has been created for you on CyberAuth System by the Super Administrator.\n\n"
            . "ACCOUNT CREDENTIALS:\n"
            . "--------------------------------------------------\n"
            . "User ID:            {$idNumber}\n"
            . "Username:           @{$recipientUsername}\n"
            . "Assigned Role:      {$roleLabel}\n"
            . "Temporary Password: {$plainPassword}\n"
            . "--------------------------------------------------\n\n"
            . "Log In URL: {$loginUrl}\n\n"
            . "FIRST-TIME SETUP REQUIREMENT:\n"
            . "Your account is in Pending Setup status. On your first login, you will be required to update your profile, change your temporary password, and configure security questions.\n\n"
            . "SECURITY REMINDER:\n"
            . "Never share this password with anyone.\n";

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        $rawError = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
        error_log("[CyberAuth Account Email Error] " . $rawError);

        // Auto-failover: If connection to SMTP host failed/timed out, automatically retry with alternate port (587 <-> 465)
        if ($port === null && (stripos($rawError, 'Could not connect') !== false || stripos($rawError, 'timed out') !== false || stripos($rawError, '10060') !== false)) {
            $currentPort = $mail->Port ?? 465;
            $fallbackPort = ($currentPort === 465) ? 587 : 465;
            error_log("[CyberAuth Account Email] Port $currentPort failed. Auto-retrying with fallback port $fallbackPort...");
            return sendNewAccountCredentialsEmail($recipientEmail, $recipientUsername, $plainPassword, $idNumber, $role, $loginUrl, $debug, $fallbackPort);
        }

        $friendlyError = $rawError;
        if (stripos($rawError, 'Username and Password not accepted') !== false || stripos($rawError, '535') !== false || stripos($rawError, 'Could not authenticate') !== false) {
            $friendlyError = 'Gmail authentication failed. Please ensure 2-Step Verification is ON and a valid 16-character Google App Password is set in .env.';
        } elseif (stripos($rawError, 'Could not connect to SMTP host') !== false) {
            $friendlyError = 'Could not connect to Gmail SMTP server. Please verify internet connection and port ' . ($mail->Port ?? 587) . '.';
        } elseif (stripos($rawError, 'Invalid address') !== false) {
            $friendlyError = 'The recipient email address is invalid.';
        }

        return ['success' => false, 'error' => $friendlyError];
    }
}

