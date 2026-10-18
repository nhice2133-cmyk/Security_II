<?php
// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auth_system');

// Application Configuration
define('APP_NAME', 'Authentication System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/v2');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour
// Login lockout: server-side throttling via login_attempts table (see php/login.php)

// Email Configuration (PHPMailer SMTP) — credentials loaded from environment variables
// See .env.example for required variable names. Never hardcode credentials here.
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: '');
define('FROM_NAME', getenv('FROM_NAME') ?: 'CyberAuth System');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Error Reporting — never expose PHP errors to the browser in production
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Set to 1 only during LOCAL development
ini_set('log_errors', 1);

// Session Configuration
ini_set('session.use_only_cookies', 1);
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? null) == 443;
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', $secure);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session idle-timeout and hardening
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
    // Idle timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Release the super_admin_lock if this idle-expired session owned it
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' && isset($_SESSION['login_log_id'])) {
            try {
                $dbIdle = new Database();
                $dbIdle->prepare(
                    "UPDATE super_admin_lock SET active_user_id=NULL, login_log_id=NULL, acquired_at=NULL, expires_at=NULL
                     WHERE login_log_id = ?"
                )->execute([$_SESSION['login_log_id']]);
                $dbIdle->prepare("UPDATE login_logs SET time_out=NOW() WHERE id=? AND time_out IS NULL")
                        ->execute([$_SESSION['login_log_id']]);
            } catch (Exception $e) { /* non-fatal */ }
        }
        endSession();
    } else {
        $_SESSION['last_activity'] = time();
        // Regenerate session ID periodically for security (every 15 minutes)
        if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 900)) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

function isAuthenticated(): bool {
    // Require both user_id AND login_log_id — a numeric user_id alone is not sufficient
    return session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['user_id'])
        && is_numeric($_SESSION['user_id'])
        && isset($_SESSION['login_log_id'])
        && is_numeric($_SESSION['login_log_id']);
}

function validateSession() {
    if (session_status() !== PHP_SESSION_ACTIVE || !isAuthenticated()) {
        return false;
    }
    
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        endSession();
        return false;
    }
    
    // Live DB validation for all authenticated users
    if (isset($_SESSION['login_log_id'])) {
        try {
            $dbV = new Database();

            // 1. Check login_log closure and account status (covers blocking, suspension, deactivation + session invalidation for all roles)
            $rowStmt = $dbV->prepare(
                "SELECT l.time_out, u.status, u.is_active, u.role FROM login_logs l JOIN users u ON l.user_id = u.id WHERE l.id = ?"
            );
            $rowStmt->execute([$_SESSION['login_log_id']]);
            $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($row['time_out'] !== null) { endSession(); return false; }   // session closed
                // Check if account is blocked, suspended, or inactive
                if (in_array($row['status'], ['blocked', 'suspended', 'inactive']) || (isset($row['is_active']) && (int)$row['is_active'] === 0)) {
                    endSession(); 
                    return false; 
                } // account blocked/suspended/inactive

                // Verify session role matches live DB role (prevents stale role in session after role demotion/transfer)
                if (isset($_SESSION['role']) && $_SESSION['role'] !== $row['role']) {
                    if ($_SESSION['role'] === 'super_admin' && $row['role'] !== 'super_admin') {
                        endSession();
                        return false;
                    }
                    $_SESSION['role'] = $row['role'];
                }
            } else {
                endSession();
                return false;
            }

            // 2. Additional check for Super Admins: verify they still own the lock and match active_user_id
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
                $lockStmt = $dbV->prepare(
                    "SELECT active_user_id, login_log_id, expires_at FROM super_admin_lock WHERE id = 1"
                );
                $lockStmt->execute();
                $lock = $lockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$lock) { endSession(); return false; } // lock row missing

                // Their session must be the current lock holder AND user id must match
                if ((int)$lock['login_log_id'] !== (int)$_SESSION['login_log_id'] || (int)$lock['active_user_id'] !== (int)$_SESSION['user_id']) {
                    endSession();
                    return false;
                }

                // Lock must not have expired
                if ($lock['expires_at'] !== null && strtotime($lock['expires_at']) < time()) {
                    // Stale lock — release and kick out
                    $dbV->prepare(
                        "UPDATE super_admin_lock SET active_user_id=NULL, login_log_id=NULL, acquired_at=NULL, expires_at=NULL WHERE id=1"
                    )->execute();
                    endSession();
                    return false;
                }

                // Refresh the lock expiry so active admins don't get auto-kicked
                $dbV->prepare(
                    "UPDATE super_admin_lock SET expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = 1"
                )->execute([SESSION_TIMEOUT]);
            }

        } catch (Exception $e) {
            // DB error during session validation → fail closed (deny access)
            error_log('validateSession DB error: ' . $e->getMessage());
            endSession();
            return false;
        }
    }

    return true;
}

function requireAuthJson() {
    if (!validateSession()) {
        http_response_code(401);
        sendJsonResponse(false, 'Session expired or invalid');
    }
}

function requireAuth404() {
    if (!validateSession()) {
        http_response_code(404);
        // Prevent caching of 404 page
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Show 404 error page
        if (file_exists(__DIR__ . '/../pages/404.php')) {
            require_once __DIR__ . '/../pages/404.php';
        } else {
            // Fallback 404 response
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 2rem;
        }
        h1 {
            font-size: 8rem;
            color: #ff0080;
            text-shadow: 0 0 20px rgba(255, 0, 128, 0.5);
            margin-bottom: 1rem;
        }
        h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #00eaff;
        }
        p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }
        a {
            display: inline-block;
            padding: 12px 30px;
            background: #ff0080;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(255, 0, 128, 0.3);
        }
        a:hover {
            background: #ff0066;
            box-shadow: 0 0 25px rgba(255, 0, 128, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you are looking for does not exist or you do not have permission to access it.</p>
        <a href="../pages/login.php">Go to Login</a>
    </div>
</body>
</html>';
        }
        exit();
    }
}

function endSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

// Database Connection Class
class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            // Never expose DB details (credentials, host, etc.) to the browser
            die(json_encode(['success' => false, 'message' => 'Service temporarily unavailable']));
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Utility Functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if (!function_exists('toTitleCase')) {
    function toTitleCase($string) {
        $lower = mb_strtolower($string, 'UTF-8');
        return preg_replace_callback('/(?:^|\s|[-\'])\p{L}/u', function ($m) {
            return mb_strtoupper($m[0], 'UTF-8');
        }, $lower);
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sendJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * validatePassword()
 * Centralised password policy enforced by every endpoint that creates or changes a password.
 * Returns null on success, or an error message string on failure.
 *
 * Policy: min 8 chars, at least one uppercase, one lowercase, one digit, one special character.
 */
function validatePassword(string $password): ?string {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return 'Password must contain at least one special character.';
    }
    return null; // valid
}

/**
 * Validation helper: Name string validation according to required rules
 */
if (!function_exists('validateNameString')) {
    function validateNameString(string $val, string $fieldName = 'Name', bool $isRequired = true, int $minLetters = 2): ?string {
        if ($val === '') {
            return $isRequired ? 'This field is required.' : null;
        }
        if (trim($val) === '') {
            return 'This field is required.';
        }
        // Leading and trailing spaces
        if (mb_substr($val, 0, 1) === ' ' || mb_substr($val, -1) === ' ') {
            return 'Remove spaces at the beginning or end.';
        }
        // Consecutive spaces
        if (preg_match('/ {2,}/', $val)) {
            return 'Double spaces are not allowed.';
        }
        // Three consecutive identical characters (case-insensitive)
        if (preg_match('/(.)\1\1/ui', $val)) {
            return 'Three identical characters in a row are not allowed.';
        }
        // Capitalization: first character must be an uppercase letter
        if (!preg_match('/^\p{Lu}/u', $val)) {
            return 'The first letter must be capitalized.';
        }
        // Allowed characters: Unicode letters, combining marks, spaces, apostrophes (', \u2019), periods, hyphens
        if (!preg_match('/^[\p{L}][\p{L}\p{M} \'\x{2019}.-]*$/u', $val)) {
            return 'Please enter valid characters.';
        }
        // Word capitalization & capital letter count check:
        // Every word should only contain 1 capital letter, and each word's first letter must be capitalized
        $words = preg_split('/[\s\-_.\'\x{2019}]+/u', $val, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            preg_match_all('/\p{L}/u', $w, $wLetters);
            if (empty($wLetters[0])) {
                continue;
            }
            if (!preg_match('/^\p{Lu}/u', $w)) {
                return 'The first letter must be capitalized.';
            }
            preg_match_all('/\p{Lu}/u', $w, $caps);
            if (count($caps[0]) > 1) {
                return 'Each word must contain only 1 capital letter.';
            }
        }
        // Minimum letters
        preg_match_all('/\p{L}/u', $val, $letters);
        if (count($letters[0] ?? []) < $minLetters) {
            return 'Name must contain at least 2 letters.';
        }
        // Maximum 50 characters
        if (mb_strlen($val, 'UTF-8') > 50) {
            return 'Name must not exceed 50 characters.';
        }
        return null;
    }
}

/**
 * Validation helper: Location string (Barangay, City, Province, Country)
 */
if (!function_exists('validateLocationString')) {
    function validateLocationString(string $val, string $fieldName = 'Location'): ?string {
        if ($val === '' || trim($val) === '') {
            return 'This field is required.';
        }
        if (mb_substr($val, 0, 1) === ' ' || mb_substr($val, -1) === ' ') {
            return 'Remove spaces at the beginning or end.';
        }
        if (preg_match('/ {2,}/', $val)) {
            return 'Double spaces are not allowed.';
        }
        if (preg_match('/(.)\1\1/ui', $val)) {
            return 'Three identical characters in a row are not allowed.';
        }
        if (!preg_match('/^\p{Lu}/u', $val)) {
            return 'The first letter must be capitalized.';
        }
        if (!preg_match('/^[\p{L}][\p{L}0-9\p{M} \'\x{2019}.-]*$/u', $val)) {
            return 'Please enter valid characters.';
        }
        $words = preg_split('/[\s\-_.\'\x{2019}]+/u', $val, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            preg_match_all('/\p{L}/u', $w, $wLetters);
            if (empty($wLetters[0])) {
                continue;
            }
            if (!preg_match('/^\p{Lu}/u', $w)) {
                return 'The first letter must be capitalized.';
            }
            preg_match_all('/\p{Lu}/u', $w, $caps);
            if (count($caps[0]) > 1) {
                return 'Each word must contain only 1 capital letter.';
            }
        }
        preg_match_all('/\p{L}/u', $val, $letters);
        if (count($letters[0] ?? []) < 2) {
            return "$fieldName must contain at least 2 letters.";
        }
        if (mb_strlen($val, 'UTF-8') > 50) {
            return "$fieldName must not exceed 50 characters.";
        }
        return null;
    }
}

/**
 * Validation helper: Street address
 */
if (!function_exists('validateStreetAddress')) {
    function validateStreetAddress(string $val): ?string {
        if ($val === '' || trim($val) === '') {
            return 'Purok / Street is required.';
        }
        if (mb_substr($val, 0, 1) === ' ' || mb_substr($val, -1) === ' ') {
            return 'Remove spaces at the beginning or end.';
        }
        if (preg_match('/ {2,}/', $val)) {
            return 'Double spaces are not allowed.';
        }
        if (preg_match('/(.)\1\1/ui', $val)) {
            return 'Three identical characters in a row are not allowed.';
        }
        if (mb_strlen($val, 'UTF-8') < 2) {
            return 'Address must contain at least 2 characters.';
        }
        if (mb_strlen($val, 'UTF-8') > 100) {
            return 'Address must not exceed 100 characters.';
        }
        if (!preg_match('/^[\p{L}0-9\p{M} .,\/\'#\-]+$/u', $val)) {
            return 'Please enter valid characters.';
        }
        return null;
    }
}

// ── CSRF Protection Helpers ───────────────────────────────────────────────────

/**
 * csrfToken() \u2014 return (and initialise) the session CSRF token.
 * Generates a new one if none exists yet.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * verifyCsrf() \u2014 validate the submitted CSRF token against the session value.
 * Uses hash_equals() to prevent timing attacks.
 * Call this at the top of every state-changing endpoint.
 * Returns false (rather than dying) so callers can return a JSON error.
 */
function verifyCsrf(?string $submitted): bool {
    if (empty($submitted) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submitted);
}

/**
 * requireCsrf() \u2014 fail-closed CSRF check with JSON response.
 * Use in all state-changing POST endpoints.
 */
function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrf($token)) {
        http_response_code(403);
        sendJsonResponse(false, 'Invalid or missing CSRF token.');
    }
}
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Log system activity to activity_logs table with full audit trail parameters
 */
function logActivity(
    ?int $userId, 
    string $action, 
    ?string $details = null, 
    ?string $ip = null,
    ?int $targetUserId = null,
    ?string $reason = null,
    ?string $outcome = 'SUCCESS',
    ?string $prevState = null,
    ?string $newState = null
): void {
    try {
        $db = new Database();
        if ($ip === null) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, target_user_id, action, details, reason, outcome, previous_state, new_state, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId, 
            $targetUserId, 
            strtoupper($action), 
            $details, 
            $reason, 
            strtoupper($outcome ?? 'SUCCESS'), 
            $prevState, 
            $newState, 
            $ip
        ]);
    } catch (Exception $e) {
        // Fallback for basic insert
        try {
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, strtoupper($action), $details, $ip]);
        } catch (Exception $e2) {
            error_log("Failed to log activity: " . $e2->getMessage());
        }
    }
}

// Create database tables if they don't exist
function createTables() {
    $db = new Database();
    
    // Users table
    $db->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            id_number VARCHAR(20) UNIQUE NULL,
            password VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20) NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            middle_name VARCHAR(100) NULL,
            extension VARCHAR(10) NULL,
            birth_date DATE NULL,
            age INT NULL,
            sex ENUM('male', 'female', 'other') NULL,
            purok_street VARCHAR(255) NULL,
            barangay VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            province VARCHAR(100) NULL,
            country VARCHAR(100) NULL,
            zip_code VARCHAR(10) NULL,
            role ENUM('super_admin','admin','user') DEFAULT 'user',
            status ENUM('active','inactive','suspended','pending','approved','blocked','pending_setup') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            is_former_superadmin BOOLEAN DEFAULT FALSE
        )
    ");
    
    // Superadmin Succession Logs table
    $db->query("
        CREATE TABLE IF NOT EXISTS superadmin_succession_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            outgoing_superadmin_id INT NOT NULL,
            incoming_superadmin_id INT NOT NULL,
            reason TEXT NOT NULL,
            former_superadmin_status VARCHAR(20) NOT NULL DEFAULT 'active',
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_outgoing (outgoing_superadmin_id),
            INDEX idx_incoming (incoming_superadmin_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Password reset tokens table
    $db->query("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Registration rate-limiting table
    $db->query("
        CREATE TABLE IF NOT EXISTS registration_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip_address, attempted_at)
        )
    ");

    // Activity logs table for all system activity tracking
    $db->query("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            target_user_id INT NULL,
            action VARCHAR(50) NOT NULL,
            details TEXT NULL,
            reason TEXT NULL,
            outcome VARCHAR(50) NOT NULL DEFAULT 'SUCCESS',
            previous_state VARCHAR(255) NULL,
            new_state VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_act_user (user_id),
            INDEX idx_act_target (target_user_id),
            INDEX idx_act_action (action),
            INDEX idx_act_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Attempt to add new columns for existing installations
    try { $db->query("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','suspended','pending','approved','blocked','pending_setup') NOT NULL DEFAULT 'pending'"); } catch (Exception $e) { /* ignore */ }
    try { $db->query("ALTER TABLE users ADD COLUMN is_former_superadmin BOOLEAN DEFAULT FALSE"); } catch (Exception $e) { /* ignore */ }
    try { $db->query("ALTER TABLE users ADD COLUMN id_number VARCHAR(20) UNIQUE NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN address TEXT NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user'"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN privileges TEXT NULL"); } catch (Exception $e) { /* ignore if exists */ }
    
    // Add new registration fields
    try { $db->query("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN middle_name VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN extension VARCHAR(10) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN birth_date DATE NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN age INT NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN sex ENUM('male', 'female', 'other') NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN purok_street VARCHAR(255) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN barangay VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN province VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN country VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore if exists */ }
    try { $db->query("ALTER TABLE users ADD COLUMN zip_code VARCHAR(10) NULL"); } catch (Exception $e) { /* ignore if exists */ }
}

// Initialize database tables only when explicitly requested via CLI / install script.
// Running createTables() (including ALTER TABLE) on every HTTP request is a
// performance and security anti-pattern. Remove this call once schema is stable.
// To re-run during development, call: php php/config.php --init-db
if (PHP_SAPI === 'cli' && in_array('--init-db', $argv ?? [])) {
    createTables();
    echo "Tables created/verified.\n";
}

// seedDefaultAdmin() removed — default credentials must never be seeded in production.
// Use the Super Admin creation flow instead.
