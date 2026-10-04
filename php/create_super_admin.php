<?php
/**
 * CLI-ONLY: Create a super admin account.
 *
 * Usage:
 *   php create_super_admin.php <SETUP_TOKEN> [username] [password]
 *
 * - SETUP_TOKEN must match the value in .setup_token file or SETUP_TOKEN env var.
 * - If username/password are omitted, defaults are generated.
 * - Passwords are NEVER echoed in a way that leaks into web logs.
 * - Only 1 super admin can exist at a time (enforced).
 */

// ── Block all web access ──────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config.php';

// ── Verify setup token ───────────────────────────────────────────────────
$expectedToken = getenv('SETUP_TOKEN') ?: null;
if (!$expectedToken) {
    $tokenFile = __DIR__ . '/../.setup_token';
    if (file_exists($tokenFile)) {
        $expectedToken = trim(file_get_contents($tokenFile));
    }
}
if (empty($expectedToken)) {
    fwrite(STDERR, "ERROR: No SETUP_TOKEN configured. Set the SETUP_TOKEN env var or create a .setup_token file in the project root.\n");
    exit(1);
}

$providedToken = $argv[1] ?? '';
if (!hash_equals($expectedToken, $providedToken)) {
    fwrite(STDERR, "ERROR: Invalid setup token.\n");
    exit(1);
}

// ── Parameters ───────────────────────────────────────────────────────────
$username = $argv[2] ?? 'superadmin';
$password = $argv[3] ?? bin2hex(random_bytes(12)); // 24-char random password

$db = new Database();

// Check if a super admin already exists
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
$stmt->execute();
if ($stmt->fetchColumn() > 0) {
    fwrite(STDERR, "ERROR: A super admin already exists. Use the dashboard to manage super admin accounts.\n");
    exit(1);
}

$hashedPassword = hashPassword($password);
$idNumber = date('Y') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

$stmt = $db->prepare("
    INSERT INTO users (
        username, email, password, id_number,
        first_name, last_name, status, role
    ) VALUES (
        ?, ?, ?, ?,
        'Initial', 'Setup', 'pending_setup', 'super_admin'
    )
");
$stmt->execute([strtolower($username), strtolower($username) . '@system.local', $hashedPassword, $idNumber]);

echo "Super Admin account created.\n";
echo "Username:  $username\n";
echo "ID Number: $idNumber\n";
echo "\n";
echo ">>> IMPORTANT: Change the password immediately after first login. <<<\n";
echo ">>> The generated password was displayed only at creation time.   <<<\n";
// Only show password if it was auto-generated (not passed as arg)
if (!isset($argv[3])) {
    echo "Generated password: $password\n";
}
echo "\n";
?>
