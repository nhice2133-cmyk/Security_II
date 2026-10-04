<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!validateSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// CSRF protection for state-changing operation
requireCsrf();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { 
    echo json_encode(['success' => false, 'message' => 'Invalid payload']); 
    exit; 
}

$db = new Database();
$stmt = $db->prepare('SELECT id, status, role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'pending_setup') {
    echo json_encode(['success' => false, 'message' => 'User is not in pending setup status.']);
    exit;
}

// ── Profile Fields ──
$firstNameRaw = trim((string)($input['firstName'] ?? ''));
$lastNameRaw  = trim((string)($input['lastName'] ?? ''));
$usernameRaw  = trim((string)($input['username'] ?? ''));
$phoneRaw     = trim((string)($input['phone'] ?? ''));
$birthDateRaw = trim((string)($input['birthDate'] ?? ''));
$sexRaw       = trim((string)($input['sex'] ?? ''));
$countryRaw   = trim((string)($input['country'] ?? ''));
$provinceRaw  = trim((string)($input['province'] ?? ''));
$cityRaw      = trim((string)($input['city'] ?? ''));
$barangayRaw  = trim((string)($input['barangay'] ?? ''));
$purokRaw     = trim((string)($input['purok'] ?? ''));
$addressRaw   = trim((string)($input['address'] ?? ''));

// ── Password Fields ──
$newPassword = $input['newPassword'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

// ── Security Questions ──
$q1 = trim((string)($input['q1'] ?? ''));
$a1 = trim((string)($input['a1'] ?? ''));
$q2 = trim((string)($input['q2'] ?? ''));
$a2 = trim((string)($input['a2'] ?? ''));
$q3 = trim((string)($input['q3'] ?? ''));
$a3 = trim((string)($input['a3'] ?? ''));

$errors = [];

// Profile Validations
if ($firstNameRaw === '' || !preg_match("/^[a-zA-Z\s\-'.]+$/", $firstNameRaw)) $errors[] = 'Invalid first name';
if ($lastNameRaw === '' || !preg_match("/^[a-zA-Z\s\-'.]+$/", $lastNameRaw)) $errors[] = 'Invalid last name';
if ($usernameRaw === '' || strlen($usernameRaw) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $usernameRaw)) $errors[] = 'Invalid username';
if ($phoneRaw === '') {
    $errors[] = 'Phone required';
} else {
    $digitsOnlyPhone = preg_replace('/\D/', '', $phoneRaw);
    if (!preg_match('/^09\d{9}$/', $digitsOnlyPhone)) $errors[] = 'Invalid phone format (must be 09 + 9 digits)';
}
if ($sexRaw === '') $errors[] = 'Sex required';
if ($countryRaw === '') $errors[] = 'Country required';
if ($provinceRaw === '') $errors[] = 'Province required';
if ($cityRaw === '') $errors[] = 'City required';
if ($barangayRaw === '') $errors[] = 'Barangay required';
if ($purokRaw === '') $errors[] = 'Purok required';
if ($addressRaw === '') $errors[] = 'Address required';

$computedAge = null;
if ($birthDateRaw === '') {
    $errors[] = 'Birth date required';
} else {
    $bd = DateTime::createFromFormat('Y-m-d', $birthDateRaw);
    if (!$bd) {
        $errors[] = 'Invalid birth date';
    } else {
        $computedAge = $bd->diff(new DateTime())->y;
    }
}

// Check username uniqueness
$chk = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
$chk->execute([$usernameRaw, $user['id']]);
if ($chk->fetch()) {
    $errors[] = 'Username is already in use';
}

// Password Validations
$pwdError = validatePassword($newPassword);
if ($pwdError) {
    $errors[] = $pwdError;
}
if ($newPassword !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}

// Security Questions Validations
if (empty($q1) || empty($a1) || empty($q2) || empty($a2) || empty($q3) || empty($a3)) {
    $errors[] = 'All security questions and answers are required';
}
if (count(array_unique([$q1, $q2, $q3])) < 3) {
    $errors[] = 'You must select 3 distinct security questions';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' | ', $errors)]);
    exit;
}

try {
    $pdo = $db->getConnection();
    $pdo->beginTransaction();

    // 1. Update Profile & Status to approved
    $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, username = ?, phone_number = ?, phone = ?, address = ?, birth_date = ?, age = ?, sex = ?, purok_street = ?, barangay = ?, city = ?, province = ?, country = ?, status = "approved", password = ? WHERE id = ?');
    $hashedPassword = hashPassword($newPassword);
    
    $stmt->execute([
        sanitizeInput($firstNameRaw),
        sanitizeInput($lastNameRaw),
        sanitizeInput($usernameRaw),
        $digitsOnlyPhone,
        $digitsOnlyPhone,
        sanitizeInput($addressRaw),
        $birthDateRaw,
        $computedAge,
        sanitizeInput($sexRaw),
        sanitizeInput($purokRaw),
        sanitizeInput($barangayRaw),
        sanitizeInput($cityRaw),
        sanitizeInput($provinceRaw),
        sanitizeInput($countryRaw),
        $hashedPassword,
        $user['id']
    ]);

    // 2. Insert Security Questions
    // Delete any existing just in case
    $pdo->prepare('DELETE FROM user_security_questions WHERE user_id = ?')->execute([$user['id']]);
    
    // Hash answers for security (like in register_security.php)
    $stmt = $pdo->prepare('INSERT INTO user_security_questions (user_id, question1, answer1, question2, answer2, question3, answer3) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['id'],
        $q1, hashPassword(strtolower(trim($a1))),
        $q2, hashPassword(strtolower(trim($a2))),
        $q3, hashPassword(strtolower(trim($a3)))
    ]);

    $pdo->commit();

    $_SESSION['username'] = sanitizeInput($usernameRaw);
    $_SESSION['status'] = 'approved';
    $_SESSION['is_pending_setup'] = false;

    echo json_encode(['success' => true, 'message' => 'Initialization Complete! Welcome.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Initial setup error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during setup.']);
}
?>
