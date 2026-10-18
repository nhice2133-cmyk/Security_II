<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!validateSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) { 
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']); 
    exit; 
}

// CSRF protection: check header, json body, or post
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? ($_POST['csrf_token'] ?? ''));
if (!verifyCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token. Please refresh the page and try again.']);
    exit;
}

$db = new Database();
$stmt = $db->prepare('SELECT id, status, role, id_number FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'pending_setup') {
    echo json_encode(['success' => false, 'message' => 'User is not in pending setup status.']);
    exit;
}

$fieldErrors = [];

// ── Step 1: Personal Information ───────────────────────────────────────────────
$idNumberCandidate = trim(sanitizeInput($input['idNumber'] ?? ''));
$firstName = trim(sanitizeInput($input['firstName'] ?? ''));
$middleInitial = trim(sanitizeInput($input['middleInitial'] ?? ''));
if (strlen($middleInitial) === 1) {
    $middleInitial = strtoupper($middleInitial);
}
$lastName = trim(sanitizeInput($input['lastName'] ?? ''));
$suffix = trim(sanitizeInput($input['suffix'] ?? ''));
$sex = strtolower(trim(sanitizeInput($input['sex'] ?? '')));
$birthDate = trim(sanitizeInput($input['birthDate'] ?? ''));

// ID Number check: if existing user had no ID, use the generated one
$finalIdNumber = !empty($user['id_number']) ? $user['id_number'] : $idNumberCandidate;
if ($finalIdNumber === '') {
    $fieldErrors['idNumber'] = 'Please click to generate an ID number';
}

$rawFirstName = (string)($input['firstName'] ?? '');
$fnErr = validateNameString($rawFirstName, 'First name', true, 2);
if ($fnErr) {
    $fieldErrors['firstName'] = $fnErr;
}

$rawMiddleInitial = (string)($input['middleInitial'] ?? '');
if (trim($rawMiddleInitial) !== '') {
    $miErr = validateNameString($rawMiddleInitial, 'Middle name', false, 1);
    if ($miErr) {
        $fieldErrors['middleInitial'] = $miErr;
    }
}

$rawLastName = (string)($input['lastName'] ?? '');
$lnErr = validateNameString($rawLastName, 'Last name', true, 2);
if ($lnErr) {
    $fieldErrors['lastName'] = $lnErr;
}

if ($suffix !== '') {
    $validSuffixes = ['none', 'jr.', 'sr.', 'ii', 'iii', 'iv', 'v', 'jr', 'sr'];
    if (!in_array(strtolower($suffix), $validSuffixes, true)) {
        $fieldErrors['suffix'] = 'Invalid suffix selection';
    }
}
if (strtolower($suffix) === 'none') {
    $suffix = '';
}

if ($sex === '') {
    $fieldErrors['sex'] = 'Gender is required';
} elseif (!in_array($sex, ['male', 'female'], true)) {
    $fieldErrors['sex'] = 'Please select Male or Female';
}

$computedAge = null;
if ($birthDate === '') {
    $fieldErrors['birthDate'] = 'Birthdate is required';
} else {
    $bd = DateTime::createFromFormat('Y-m-d', $birthDate);
    if (!$bd || $bd->format('Y-m-d') !== $birthDate) {
        $fieldErrors['birthDate'] = 'Invalid birthdate format';
    } else {
        $today = new DateTime();
        if ($bd > $today) {
            $fieldErrors['birthDate'] = 'Birthdate cannot be in the future';
        } else {
            $computedAge = $bd->diff($today)->y;
            if ($computedAge < 18) {
                $fieldErrors['birthDate'] = 'Age must be at least 18 years old';
                $fieldErrors['age'] = 'Age must be at least 18 years old';
            }
        }
    }
}

// ── Step 2: Address Information ────────────────────────────────────────────────
$rawPurok = (string)($input['purokStreet'] ?? '');
$psErr = validateStreetAddress($rawPurok);
if ($psErr) {
    $fieldErrors['purokStreet'] = $psErr;
}

$rawBarangay = (string)($input['barangay'] ?? '');
$brgyErr = validateLocationString($rawBarangay, 'Barangay');
if ($brgyErr) {
    $fieldErrors['barangay'] = $brgyErr;
}

$rawCity = (string)($input['city'] ?? '');
$cityErr = validateLocationString($rawCity, 'Municipality / City');
if ($cityErr) {
    $fieldErrors['city'] = $cityErr;
}

$rawProvince = (string)($input['province'] ?? '');
$provErr = validateLocationString($rawProvince, 'Province');
if ($provErr) {
    $fieldErrors['province'] = $provErr;
}

$rawCountry = (string)($input['country'] ?? 'Philippines');
$cntryErr = validateLocationString($rawCountry, 'Country');
if ($cntryErr) {
    $fieldErrors['country'] = $cntryErr;
}

$purokStreet = trim(sanitizeInput($rawPurok));
$barangay = trim(sanitizeInput($rawBarangay));
$city = trim(sanitizeInput($rawCity));
$province = trim(sanitizeInput($rawProvince));
$country = trim(sanitizeInput($rawCountry));
$zipCode = trim(sanitizeInput($input['zipCode'] ?? ''));

if ($zipCode === '') {
    $fieldErrors['zipCode'] = 'Postal / Zip code is required';
} elseif (!preg_match('/^\d{4}$/', $zipCode)) {
    $fieldErrors['zipCode'] = 'Zip code must be 4 digits';
}

// ── Step 3: Account Credentials & Password ─────────────────────────────────────
$username = trim(sanitizeInput($input['username'] ?? ''));
$newPassword = $input['newPassword'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

if ($username === '') {
    $fieldErrors['username'] = 'Username is required';
} elseif (strlen($username) < 3 || strlen($username) > 20) {
    $fieldErrors['username'] = 'Username must be between 3 and 20 characters';
} elseif (!preg_match('/^[a-zA-Z0-9._]+$/', $username)) {
    $fieldErrors['username'] = 'Letters, numbers, dots, and underscores only';
} else {
    // Check username uniqueness
    $chk = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $chk->execute([$username, $user['id']]);
    if ($chk->fetch()) {
        $fieldErrors['username'] = 'Username is already taken';
    }
}

if (trim($newPassword) === '') {
    $fieldErrors['password'] = 'Password is required and cannot be whitespace only';
} else {
    $pwdErr = validatePassword($newPassword);
    if ($pwdErr) {
        $fieldErrors['password'] = $pwdErr;
    }
}

if ($newPassword !== $confirmPassword) {
    $fieldErrors['confirmPassword'] = 'Passwords do not match';
}

// ── Step 3: Security Questions ─────────────────────────────────────────────────
$q1 = trim(sanitizeInput($input['q1'] ?? ''));
$a1 = trim((string)($input['a1'] ?? ''));
$q2 = trim(sanitizeInput($input['q2'] ?? ''));
$a2 = trim((string)($input['a2'] ?? ''));
$q3 = trim(sanitizeInput($input['q3'] ?? ''));
$a3 = trim((string)($input['a3'] ?? ''));

if ($q1 === '') $fieldErrors['q1'] = 'Security Question 1 is required';
if ($a1 === '') $fieldErrors['a1'] = 'Answer 1 is required';
if ($q2 === '') $fieldErrors['q2'] = 'Security Question 2 is required';
if ($a2 === '') $fieldErrors['a2'] = 'Answer 2 is required';
if ($q3 === '') $fieldErrors['q3'] = 'Security Question 3 is required';
if ($a3 === '') $fieldErrors['a3'] = 'Answer 3 is required';

if ($q1 !== '' && $q2 !== '' && $q3 !== '') {
    if (count(array_unique([$q1, $q2, $q3])) < 3) {
        $fieldErrors['q3'] = 'All 3 security questions must be unique';
    }
}

// ── Check if any errors ────────────────────────────────────────────────────────
if (!empty($fieldErrors)) {
    $step1Fields = ['idNumber', 'firstName', 'middleInitial', 'lastName', 'suffix', 'sex', 'birthDate', 'age'];
    $step2Fields = ['purokStreet', 'barangay', 'city', 'province', 'country', 'zipCode'];

    $hasStep1 = false;
    $hasStep2 = false;
    foreach ($fieldErrors as $k => $v) {
        if (in_array($k, $step1Fields, true)) $hasStep1 = true;
        if (in_array($k, $step2Fields, true)) $hasStep2 = true;
    }

    $targetStep = $hasStep1 ? 1 : ($hasStep2 ? 2 : 3);

    echo json_encode([
        'success' => false,
        'message' => 'Please resolve the highlighted validation errors.',
        'fieldErrors' => $fieldErrors,
        'step' => $targetStep
    ]);
    exit;
}

// ── Execute Database Updates ───────────────────────────────────────────────────
try {
    $pdo = $db->getConnection();
    $pdo->beginTransaction();

    $fullAddress = implode(', ', array_filter([$purokStreet, $barangay, $city, $province, $country]));
    $hashedPassword = hashPassword($newPassword);

    $updateStmt = $pdo->prepare('
        UPDATE users SET
            id_number = COALESCE(NULLIF(?, ""), id_number),
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            extension = ?,
            birth_date = ?,
            age = ?,
            sex = ?,
            purok_street = ?,
            barangay = ?,
            city = ?,
            province = ?,
            country = ?,
            zip_code = ?,
            address = ?,
            username = ?,
            password = ?,
            status = "approved"
        WHERE id = ?
    ');

    $updateStmt->execute([
        $finalIdNumber,
        $firstName,
        $middleInitial,
        $lastName,
        $suffix,
        $birthDate,
        $computedAge,
        $sex,
        $purokStreet,
        $barangay,
        $city,
        $province,
        $country,
        $zipCode,
        $fullAddress,
        $username,
        $hashedPassword,
        $user['id']
    ]);

    // Insert or update security questions
    $pdo->prepare('DELETE FROM user_security_questions WHERE user_id = ?')->execute([$user['id']]);

    $insertSQ = $pdo->prepare('
        INSERT INTO user_security_questions (
            user_id, question1, answer1, question2, answer2, question3, answer3, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ');

    $insertSQ->execute([
        $user['id'],
        $q1, hashPassword(strtolower(trim($a1))),
        $q2, hashPassword(strtolower(trim($a2))),
        $q3, hashPassword(strtolower(trim($a3)))
    ]);

    $pdo->commit();

    $_SESSION['username'] = $username;
    $_SESSION['status'] = 'approved';
    $_SESSION['is_pending_setup'] = false;

    logActivity($user['id'], 'SETUP_COMPLETED', "User '{$username}' completed initial setup and configured credentials");

    echo json_encode([
        'success' => true,
        'message' => 'Account setup complete! Redirecting to dashboard...',
        'idNumber' => $finalIdNumber
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Initial setup transaction error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while finalizing setup. Please try again.'
    ]);
    exit;
}
