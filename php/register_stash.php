
<?php
require_once 'config.php';

header('Content-Type: application/json');

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
// Optional ID number (format XXXX-XXXX)
$idNumber = sanitizeInput($payload['idNumber'] ?? '');
// Phone number
$phoneNumber = sanitizeInput($payload['phoneNumber'] ?? ($payload['phone_number'] ?? ''));
// Personal information
$firstName = toTitleCase(sanitizeInput($payload['firstName'] ?? ''));
$lastName = toTitleCase(sanitizeInput($payload['lastName'] ?? ''));
$middleName = toTitleCase(sanitizeInput($payload['middleName'] ?? ''));
$extension = sanitizeInput($payload['extension'] ?? '');
$birthDate = sanitizeInput($payload['birthDate'] ?? '');
$age = sanitizeInput($payload['age'] ?? '');
$sex = sanitizeInput($payload['sex'] ?? '');
// Address information
$purok = sanitizeInput($payload['purok'] ?? '');
$barangay = sanitizeInput($payload['barangay'] ?? '');
$city = sanitizeInput($payload['city'] ?? '');
$province = sanitizeInput($payload['province'] ?? '');
$country = sanitizeInput($payload['country'] ?? '');
$zipCode = sanitizeInput($payload['zipCode'] ?? '');

$errors = [];
$fieldErrors = [];

// Username validation - format must be "lowercase.#" (e.g., john.1)
if ($username === '') {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username must be at least 3 characters (e.g., john.1)';
} elseif (strlen($username) > 50) {
    $errors[] = 'Invalid username';
    $fieldErrors['username'] = 'Username must be at most 50 characters';
} elseif (!preg_match('/^[a-z]+\.[0-9]+$/', $username)) {
    // Check for uppercase letters
    if (preg_match('/[A-Z]/', $username)) {
        $errors[] = 'Invalid username';
        $fieldErrors['username'] = 'Username format must be "lowercase.#" (e.g., john.1). Uppercase letters are not allowed.';
    } else {
        $errors[] = 'Invalid username';
        $fieldErrors['username'] = 'Username format must be "lowercase.#" (e.g., john.1) - lowercase letters, a dot, then numbers';
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

// Required personal information validation
if ($firstName === '') {
    $errors[] = 'First name is required';
    $fieldErrors['firstName'] = 'First name is required';
}

if ($lastName === '') {
    $errors[] = 'Last name is required';
    $fieldErrors['lastName'] = 'Last name is required';
}

if ($birthDate === '') {
    $errors[] = 'Birth date is required';
    $fieldErrors['birthDate'] = 'Birth date is required';
}

if ($age === '' || !is_numeric($age) || $age < 1 || $age > 120) {
    $errors[] = 'Valid age is required';
    $fieldErrors['age'] = 'Age must be between 1 and 120';
}

if ($sex === '') {
    $errors[] = 'Sex is required';
    $fieldErrors['sex'] = 'Please select your sex';
}

// Required address validation
if ($purok === '') {
    $errors[] = 'Purok/Street is required';
    $fieldErrors['purok'] = 'Purok/Street is required';
}

if ($barangay === '') {
    $errors[] = 'Barangay is required';
    $fieldErrors['barangay'] = 'Barangay is required';
}

if ($city === '') {
    $errors[] = 'City is required';
    $fieldErrors['city'] = 'City is required';
}

if ($province === '') {
    $errors[] = 'Province is required';
    $fieldErrors['province'] = 'Province is required';
}

if ($country === '') {
    $errors[] = 'Country is required';
    $fieldErrors['country'] = 'Country is required';
}

// Zip code validation
if ($zipCode === '') {
    $errors[] = 'Zip code is required';
    $fieldErrors['zipCode'] = 'Zip code is required';
} elseif (!preg_match('/^\d{4}$/', $zipCode)) {
    $errors[] = 'Invalid zip code format';
    $fieldErrors['zipCode'] = 'Zip code must be exactly 4 digits';
}

// Check duplicates for username, email, id_number
try {
    $db = new Database();

    function generateNextIdNumber($db) {
        $currentYear = date('Y');
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            // Use cryptographically secure random number generation
            $randomNum = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $newIdNumber = $currentYear . '-' . $randomNum;
            
            $stmt = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
            $stmt->execute([$newIdNumber]);
            $exists = $stmt->fetch();
            
            $attempts++;
            
            if (!$exists) {
                return $newIdNumber;
            }
            
        } while ($attempts < $maxAttempts);
        
        // Fallback: cryptographically random suffix
        return $currentYear . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    if (empty($idNumber)) {
        $idNumber = generateNextIdNumber($db);
    }

    // Check username specifically
    $chkUsername = $db->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $chkUsername->execute([$username]);
    if ($chkUsername->fetch()) {
        $errors[] = 'Username already exists';
        $fieldErrors['username'] = 'This username is already taken';
    }

    // Check email specifically
    $chkEmail = $db->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $chkEmail->execute([$email]);
    if ($chkEmail->fetch()) {
        $errors[] = 'Email already exists';
        $fieldErrors['email'] = 'This email is already registered';
    }

    // Check ID number specifically
    if ($idNumber !== '') {
        $chkIdNumber = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
        $chkIdNumber->execute([$idNumber]);
        if ($chkIdNumber->fetch()) {
            $errors[] = 'ID number already exists';
            $fieldErrors['idNumber'] = 'This ID number is already registered';
        }
    }

    // Check phone number specifically
    $chkPhone = $db->prepare("SELECT 1 FROM users WHERE phone_number = ? LIMIT 1");
    $chkPhone->execute([$phoneNumber]);
    if ($chkPhone->fetch()) {
        $errors[] = 'Phone number already exists';
        $fieldErrors['phoneNumber'] = 'This phone number is already registered';
    }

    // Password-reuse oracle against other users' hashes removed:
    // Checking a submitted password against every stored hash is O(N) bcrypt
    // work per unauthenticated request (DoS/information disclosure vector).
    // Per-user password history (for the same account only) can be added post-registration.

    // Security questions validation
    $q1 = sanitizeInput($payload['question1'] ?? '');
    $a1 = $payload['answer1'] ?? '';
    $q2 = sanitizeInput($payload['question2'] ?? '');
    $a2 = $payload['answer2'] ?? '';
    $q3 = sanitizeInput($payload['question3'] ?? '');
    $a3 = $payload['answer3'] ?? '';

    if (empty($q1) || empty($a1) || empty($q2) || empty($a2) || empty($q3) || empty($a3)) {
        $errors[] = 'All 3 security questions and answers are required';
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'fieldErrors' => $fieldErrors
        ]);
        exit;
    }

    // Insert user with 'pending' status
    $hashedPassword = hashPassword($password);
    $stmt = $db->prepare("
        INSERT INTO users (
            username, email, password, id_number, phone_number,
            first_name, last_name, middle_name, extension, birth_date,
            age, sex, purok_street, barangay, city, province, country, zip_code,
            status, role
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            'pending', 'user'
        )
    ");

    $stmt->execute([
        $username, $email, $hashedPassword, $idNumber, $phoneNumber,
        $firstName, $lastName, $middleName, $extension, $birthDate,
        $age, $sex, $purok, $barangay, $city, $province, $country, $zipCode
    ]);

    $userId = $db->lastInsertId();

    // Insert security questions
    $stmtQs = $db->prepare("
        INSERT INTO user_security_questions (
            user_id, question1, answer1, question2, answer2, question3, answer3
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtQs->execute([
        $userId,
        $q1, hashPassword($a1),
        $q2, hashPassword($a2),
        $q3, hashPassword($a3)
    ]);

    echo json_encode([
        'success' => true,
        'idNumber' => $idNumber,
        'message' => 'Registration successful. Waiting for admin approval.'
    ]);
    exit;
} catch (Throwable $e) {
    error_log('register_stash error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
    exit;
}
?>


