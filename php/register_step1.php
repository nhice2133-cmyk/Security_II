<?php
require_once 'config.php';

header('Content-Type: application/json');

// ── Per-IP registration rate limit ────────────────────────────────────────────
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$regWindow = 3600; // 1 hour
$maxRegAttempts = 10;

try {
    $dbRate = new Database();
    $rateStmt = $dbRate->prepare(
        "SELECT COUNT(*) FROM registration_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    $rateStmt->execute([$clientIp, $regWindow]);
    $recentAttempts = (int)$rateStmt->fetchColumn();

    if ($recentAttempts >= $maxRegAttempts) {
        echo json_encode(['success' => false, 'message' => 'Too many registration attempts. Please try again later.']);
        exit;
    }

    // Record this attempt
    $dbRate->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)")->execute([$clientIp]);
} catch (Exception $e) {
    // If the table doesn't exist yet, silently skip rate-limiting rather than blocking
    error_log('Registration rate limit error: ' . $e->getMessage());
}
// ──────────────────────────────────────────────────────────────────────────────

// Expect JSON
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$username = sanitizeInput($payload['username'] ?? '');
$email = sanitizeInput($payload['email'] ?? '');
$password = $payload['password'] ?? '';
$confirmPassword = $payload['confirm_password'] ?? '';
$idNumber = sanitizeInput($payload['idNumber'] ?? '');
$phoneNumber = sanitizeInput($payload['phoneNumber'] ?? ($payload['phone_number'] ?? ''));
$firstName = toTitleCase(sanitizeInput($payload['firstName'] ?? ''));
$lastName = toTitleCase(sanitizeInput($payload['lastName'] ?? ''));
$middleName = toTitleCase(sanitizeInput($payload['middleName'] ?? ''));
$extension = sanitizeInput($payload['extension'] ?? '');
$birthDate = sanitizeInput($payload['birthDate'] ?? '');
$age = sanitizeInput($payload['age'] ?? '');
$sex = sanitizeInput($payload['sex'] ?? '');
$purok = sanitizeInput($payload['purok'] ?? '');
$barangay = sanitizeInput($payload['barangay'] ?? '');
$city = sanitizeInput($payload['city'] ?? '');
$province = sanitizeInput($payload['province'] ?? '');
$country = sanitizeInput($payload['country'] ?? '');
$zipCode = sanitizeInput($payload['zipCode'] ?? '');

$errors = [];
$fieldErrors = [];

// Username validation
if ($username === '') {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username must be at least 3 characters';
} elseif (strlen($username) > 50) {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username must be at most 50 characters';
} elseif (!preg_match('/^[a-z]+\.[0-9]+$/', $username)) {
    if (preg_match('/[A-Z]/', $username)) {
        $errors[] = 'Invalid username';
        $fieldErrors['username'] = 'Username format must be "lowercase.#". Uppercase not allowed.';
    } else {
        $errors[] = 'Invalid username';
        $fieldErrors['username'] = 'Username format must be "lowercase.#"';
    }
}

// Email validation
if ($email === '' || !validateEmail($email)) {
    $errors[] = 'Invalid email';
    $fieldErrors['email'] = 'Please enter a valid email address';
}

// Password validation
if ($password === '' || strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = 'Weak password';
    $fieldErrors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
    $fieldErrors['confirmPassword'] = 'Passwords do not match';
}

// ID number validation
if ($idNumber !== '' && !preg_match('/^\d{4}-\d{4}$/', $idNumber)) {
    $errors[] = 'Invalid ID number format';
    $fieldErrors['idNumber'] = 'ID number must be in format XXXX-XXXX';
}

// Phone number validation
if ($phoneNumber === '') {
    $errors[] = 'Phone number is required';
    $fieldErrors['phoneNumber'] = 'Phone number is required';
} else {
    $digitsOnlyPhone = preg_replace('/\D/', '', $phoneNumber);
    if (!preg_match('/^09\d{9}$/', $digitsOnlyPhone)) {
        $errors[] = 'Phone number must start with 09 and be 11 digits';
        $fieldErrors['phoneNumber'] = 'Phone number must start with 09 and be 11 digits';
    } else {
        $phoneNumber = $digitsOnlyPhone;
    }
}

// Required personal information
if ($firstName === '') { $errors[] = 'First name is required'; $fieldErrors['firstName'] = 'First name is required'; }
if ($lastName === '') { $errors[] = 'Last name is required'; $fieldErrors['lastName'] = 'Last name is required'; }
if ($birthDate === '') { $errors[] = 'Birth date is required'; $fieldErrors['birthDate'] = 'Birth date is required'; }
if ($age === '' || !is_numeric($age) || $age < 1 || $age > 120) {
    $errors[] = 'Valid age is required';
    $fieldErrors['age'] = 'Age must be between 1 and 120';
}
if ($sex === '') { $errors[] = 'Sex is required'; $fieldErrors['sex'] = 'Please select your sex'; }

// Required address validation
if ($purok === '') { $errors[] = 'Purok is required'; $fieldErrors['purok'] = 'Purok is required'; }
if ($barangay === '') { $errors[] = 'Barangay is required'; $fieldErrors['barangay'] = 'Barangay is required'; }
if ($city === '') { $errors[] = 'City is required'; $fieldErrors['city'] = 'City is required'; }
if ($province === '') { $errors[] = 'Province is required'; $fieldErrors['province'] = 'Province is required'; }
if ($country === '') { $errors[] = 'Country is required'; $fieldErrors['country'] = 'Country is required'; }

// Zip code
if ($zipCode === '') {
    $errors[] = 'Zip code is required';
    $fieldErrors['zipCode'] = 'Zip code is required';
} elseif (!preg_match('/^\d{4}$/', $zipCode)) {
    $errors[] = 'Invalid zip code';
    $fieldErrors['zipCode'] = 'Zip code must be exactly 4 digits';
}

try {
    $db = new Database();

    if ($idNumber !== '') {
        $chkId = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
        $chkId->execute([$idNumber]);
        if ($chkId->fetch()) {
            $errors[] = 'ID already exists';
            $fieldErrors['idNumber'] = 'This ID is already registered';
        }
    }

    $chkUser = $db->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $chkUser->execute([$username]);
    if ($chkUser->fetch()) {
        $errors[] = 'Username exists';
        $fieldErrors['username'] = 'This username is already taken';
    }

    $chkEmail = $db->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $chkEmail->execute([$email]);
    if ($chkEmail->fetch()) {
        $errors[] = 'Email exists';
        $fieldErrors['email'] = 'This email is already registered';
    }

    $chkPhone = $db->prepare("SELECT 1 FROM users WHERE phone_number = ? LIMIT 1");
    $chkPhone->execute([$phoneNumber]);
    if ($chkPhone->fetch()) {
        $errors[] = 'Phone exists';
        $fieldErrors['phoneNumber'] = 'This phone number is already registered';
    }

    // Password-reuse oracle against other users' hashes removed:
    // Checking a submitted password against every stored hash is O(N) bcrypt
    // work per unauthenticated request (DoS vector) and reveals whether another
    // account uses the same password (information disclosure).
    // If per-user password history is needed, associate it with THIS user only,
    // and only after the account is created.

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors), 'fieldErrors' => $fieldErrors]);
        exit;
    }

    // Save to session — store hashed password, NEVER plaintext
    $_SESSION['reg_step1'] = [
        'username'    => $username,
        'email'       => $email,
        'password_hash' => hashPassword($password), // Hashed at stash time; never store plaintext
        'idNumber'    => $idNumber,
        'phoneNumber' => $phoneNumber,
        'firstName'   => $firstName,
        'lastName'    => $lastName,
        'middleName'  => $middleName,
        'extension'   => $extension,
        'birthDate'   => $birthDate,
        'age'         => $age,
        'sex'         => $sex,
        'purok'       => $purok,
        'barangay'    => $barangay,
        'city'        => $city,
        'province'    => $province,
        'country'     => $country,
        'zipCode'     => $zipCode
    ];

    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {
    error_log('register_step1 error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
