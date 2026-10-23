<?php
require_once 'config.php';

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
            return 'Name must contain at least 2 letters.';
        }
        if (mb_strlen($val, 'UTF-8') > 50) {
            return 'Name must not exceed 50 characters.';
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

// Only execute the endpoint handling when accessed directly (not included by tests)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');

    // Only allow POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        sendJsonResponse(false, 'Method not allowed');
    }

    // ── Rate Limiting (per IP) ─────────────────────────────────────────────────────
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $regWindow = 3600; // 1 hour
    $maxRegAttempts = 10;

    try {
        $db = new Database();
        $rateStmt = $db->prepare(
            "SELECT COUNT(*) FROM registration_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $rateStmt->execute([$clientIp, $regWindow]);
        $recentAttempts = (int)$rateStmt->fetchColumn();

        if ($recentAttempts >= $maxRegAttempts) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many registration attempts. Please try again later.'
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log('Registration rate limit check error: ' . $e->getMessage());
    }

    // ── Parse Payload & CSRF Protection ──────────────────────────────────────────
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
        exit;
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? ($_POST['csrf_token'] ?? ''));
    if (!verifyCsrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token. Please refresh the page and try again.']);
        exit;
    }

    $fieldErrors = [];
    $errorStep = 1;

    // ── Step 1: Personal Information ───────────────────────────────────────────────
    $rawFirstName = (string)($payload['firstName'] ?? '');
    $rawMiddleInitial = (string)($payload['middleInitial'] ?? '');
    $rawLastName = (string)($payload['lastName'] ?? '');
    $rawSuffix = trim((string)($payload['suffix'] ?? ''));
    $rawSex = strtolower(trim((string)($payload['sex'] ?? '')));
    $rawBirthDate = trim((string)($payload['birthDate'] ?? ''));

    // Validate First Name
    $fnErr = validateNameString($rawFirstName, 'First name', true, 2);
    if ($fnErr) {
        $fieldErrors['firstName'] = $fnErr;
    }

    // Validate Middle Name / Initial (optional)
    if ($rawMiddleInitial !== '') {
        $miErr = validateNameString($rawMiddleInitial, 'Middle name', false, 1);
        if ($miErr) {
            $fieldErrors['middleInitial'] = $miErr;
        }
    }

    // Validate Last Name
    $lnErr = validateNameString($rawLastName, 'Last name', true, 2);
    if ($lnErr) {
        $fieldErrors['lastName'] = $lnErr;
    }

    // Validate Suffix
    $validSuffixes = ['', 'none', 'jr.', 'sr.', 'ii', 'iii', 'iv', 'v', 'jr', 'sr'];
    if ($rawSuffix !== '' && !in_array(strtolower($rawSuffix), $validSuffixes, true)) {
        $fieldErrors['suffix'] = 'Invalid suffix selection';
    }

    // Validate Gender
    if ($rawSex === '') {
        $fieldErrors['sex'] = 'This field is required.';
    } elseif (!in_array($rawSex, ['male', 'female'], true)) {
        $fieldErrors['sex'] = 'Please select a valid option (Male or Female)';
    }

    // Validate Birthdate & Age
    $computedAge = null;
    if ($rawBirthDate === '') {
        $fieldErrors['birthDate'] = 'This field is required.';
    } else {
        $bd = DateTime::createFromFormat('Y-m-d', $rawBirthDate);
        if (!$bd || $bd->format('Y-m-d') !== $rawBirthDate) {
            $fieldErrors['birthDate'] = 'Invalid birthdate format';
        } else {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            if ($bd > $today) {
                $fieldErrors['birthDate'] = 'Birthdate cannot be in the future';
            } else {
                $computedAge = $bd->diff($today)->y;
                if ($computedAge < 18) {
                    $fieldErrors['birthDate'] = 'Age must be at least 18 years old';
                }
            }
        }
    }

    // Sanitized values for DB
    $firstName = sanitizeInput(trim($rawFirstName));
    $middleInitial = sanitizeInput(trim($rawMiddleInitial));
    $lastName = sanitizeInput(trim($rawLastName));
    $suffix = (strtolower($rawSuffix) === 'none') ? '' : sanitizeInput($rawSuffix);
    $sex = $rawSex;
    $birthDate = $rawBirthDate;

    // ── Step 2: Address Information ────────────────────────────────────────────────
    $rawPurok = (string)($payload['purokStreet'] ?? '');
    $rawBarangay = (string)($payload['barangay'] ?? '');
    $rawCity = (string)($payload['city'] ?? '');
    $rawProvince = (string)($payload['province'] ?? '');
    $rawCountry = (string)($payload['country'] ?? 'Philippines');
    $rawZip = (string)($payload['zipCode'] ?? '');

    $purokErr = validateStreetAddress($rawPurok);
    if ($purokErr) {
        $fieldErrors['purokStreet'] = $purokErr;
    }

    $brgyErr = validateLocationString($rawBarangay, 'Barangay');
    if ($brgyErr) {
        $fieldErrors['barangay'] = $brgyErr;
    }

    $cityErr = validateLocationString($rawCity, 'Municipality / City');
    if ($cityErr) {
        $fieldErrors['city'] = $cityErr;
    }

    $provErr = validateLocationString($rawProvince, 'Province');
    if ($provErr) {
        $fieldErrors['province'] = $provErr;
    }

    $countryErr = validateLocationString($rawCountry, 'Country');
    if ($countryErr) {
        $fieldErrors['country'] = $countryErr;
    }

    if ($rawZip === '' || trim($rawZip) === '') {
        $fieldErrors['zipCode'] = 'This field is required.';
    } elseif (!preg_match('/^\d{4}$/', $rawZip)) {
        $fieldErrors['zipCode'] = 'Zip code must be 4 digits.';
    }

    // Sanitized values for DB
    $purokStreet = sanitizeInput(trim($rawPurok));
    $barangay = sanitizeInput(trim($rawBarangay));
    $city = sanitizeInput(trim($rawCity));
    $province = sanitizeInput(trim($rawProvince));
    $country = sanitizeInput(trim($rawCountry));
    $zipCode = trim($rawZip);

    // ── Step 3: Account Credentials ────────────────────────────────────────────────
    $rawEmail = (string)($payload['email'] ?? '');
    $rawUsername = (string)($payload['username'] ?? '');
    $rawPassword = (string)($payload['password'] ?? '');
    $rawConfirmPassword = (string)($payload['confirmPassword'] ?? '');

    // Validate Email
    if ($rawEmail === '' || trim($rawEmail) === '') {
        $fieldErrors['email'] = 'This field is required.';
    } elseif (preg_match('/\s/', $rawEmail)) {
        $fieldErrors['email'] = 'Enter a valid email address.';
    } elseif (strlen($rawEmail) > 254) {
        $fieldErrors['email'] = 'Enter a valid email address.';
    } elseif (!validateEmail(strtolower(trim($rawEmail)))) {
        $fieldErrors['email'] = 'Enter a valid email address.';
    } else {
        // Check email uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([strtolower(trim($rawEmail))]);
        if ($stmt->fetch()) {
            $fieldErrors['email'] = 'This email address is already registered.';
        }
    }

    // Validate Username
    if ($rawUsername === '' || trim($rawUsername) === '') {
        $fieldErrors['username'] = 'This field is required.';
    } elseif (strlen($rawUsername) < 3 || strlen($rawUsername) > 20 || !preg_match('/^[a-zA-Z0-9._]+$/', $rawUsername)) {
        $fieldErrors['username'] = 'Username does not meet the requirements.';
    } else {
        // Check username uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
        $stmt->execute([strtolower(trim($rawUsername))]);
        if ($stmt->fetch()) {
            $fieldErrors['username'] = 'This username is already taken.';
        }
    }

    // Validate Password (do NOT trim)
    if ($rawPassword === '' || trim($rawPassword) === '') {
        $fieldErrors['password'] = 'This field is required.';
    } elseif (strlen($rawPassword) < 8) {
        $fieldErrors['password'] = 'Password must contain at least 8 characters.';
    } elseif (strlen($rawPassword) > 128) {
        $fieldErrors['password'] = 'Password must not exceed 128 characters.';
    } elseif (!preg_match('/[A-Z]/', $rawPassword)) {
        $fieldErrors['password'] = 'Include at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $rawPassword)) {
        $fieldErrors['password'] = 'Include at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $rawPassword)) {
        $fieldErrors['password'] = 'Include at least one number.';
    }

    // Validate Confirm Password
    if ($rawConfirmPassword === '' || trim($rawConfirmPassword) === '') {
        $fieldErrors['confirmPassword'] = 'This field is required.';
    } elseif ($rawConfirmPassword !== $rawPassword) {
        $fieldErrors['confirmPassword'] = 'Passwords do not match.';
    }

    // Sanitized values for DB
    $email = strtolower(trim($rawEmail));
    $username = trim($rawUsername);
    $password = $rawPassword;
    $confirmPassword = $rawConfirmPassword;

    // ── Step 3: Security Questions ─────────────────────────────────────────────────
    $q1 = trim(htmlspecialchars_decode(strip_tags((string)($payload['q1'] ?? '')), ENT_QUOTES));
    $a1 = trim((string)($payload['a1'] ?? ''));
    $q2 = trim(htmlspecialchars_decode(strip_tags((string)($payload['q2'] ?? '')), ENT_QUOTES));
    $a2 = trim((string)($payload['a2'] ?? ''));
    $q3 = trim(htmlspecialchars_decode(strip_tags((string)($payload['q3'] ?? '')), ENT_QUOTES));
    $a3 = trim((string)($payload['a3'] ?? ''));

    if ($q1 === '') $fieldErrors['q1'] = 'Please select Question 1';
    if ($a1 === '') $fieldErrors['a1'] = 'This field is required.';
    if ($q2 === '') $fieldErrors['q2'] = 'Please select Question 2';
    if ($a2 === '') $fieldErrors['a2'] = 'This field is required.';
    if ($q3 === '') $fieldErrors['q3'] = 'Please select Question 3';
    if ($a3 === '') $fieldErrors['a3'] = 'This field is required.';

    if ($q1 !== '' && $q2 !== '' && $q3 !== '') {
        if (count(array_unique([$q1, $q2, $q3])) < 3) {
            $fieldErrors['q3'] = 'All 3 security questions must be unique';
        }
    }

    // If any field errors, determine step and return
    if (!empty($fieldErrors)) {
        $step1Fields = ['firstName', 'middleInitial', 'lastName', 'suffix', 'sex', 'birthDate', 'age'];
        $step2Fields = ['purokStreet', 'barangay', 'city', 'province', 'country', 'zipCode'];

        $hasStep1 = false;
        $hasStep2 = false;
        foreach ($fieldErrors as $k => $v) {
            if (in_array($k, $step1Fields, true)) $hasStep1 = true;
            if (in_array($k, $step2Fields, true)) $hasStep2 = true;
        }

        if ($hasStep1) {
            $targetStep = 1;
        } elseif ($hasStep2) {
            $targetStep = 2;
        } else {
            $targetStep = 3;
        }

        echo json_encode([
            'success' => false,
            'message' => 'Please resolve the highlighted validation errors.',
            'fieldErrors' => $fieldErrors,
            'step' => $targetStep
        ]);
        exit;
    }

    // ── Atomic Insertion & ID Generation ───────────────────────────────────────────
    try {
        $pdo = $db->getConnection();
        $pdo->beginTransaction();

        // Generate and reserve server-side unique ID Number (YYYY-NNNN)
        $currentYear = date('Y');
        $reservedId = null;
        $attempts = 0;
        do {
            $randNum = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $candidateId = $currentYear . '-' . $randNum;
            $chk = $pdo->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1 FOR UPDATE");
            $chk->execute([$candidateId]);
            if (!$chk->fetch()) {
                $reservedId = $candidateId;
                break;
            }
            $attempts++;
        } while ($attempts < 100);

        if (!$reservedId) {
            $reservedId = $currentYear . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        $hashedPassword = hashPassword($password);

        // Insert user: role = 'user', status = 'pending'
        $insertUser = $pdo->prepare("
            INSERT INTO users (
                username, email, password, id_number,
                first_name, middle_name, last_name, extension,
                birth_date, age, sex,
                purok_street, barangay, city, province, country, zip_code,
                status, role, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                'pending', 'user', NOW()
            )
        ");

        $insertUser->execute([
            $username,
            $email,
            $hashedPassword,
            $reservedId,
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
            $zipCode
        ]);

        $userId = (int)$pdo->lastInsertId();

        // Hash security answers (trimmed, lowercase, hashed)
        $a1Hashed = hashPassword(strtolower(trim($a1)));
        $a2Hashed = hashPassword(strtolower(trim($a2)));
        $a3Hashed = hashPassword(strtolower(trim($a3)));

        $insertSQ = $pdo->prepare("
            INSERT INTO user_security_questions (
                user_id, question1, answer1, question2, answer2, question3, answer3, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertSQ->execute([
            $userId,
            $q1, $a1Hashed,
            $q2, $a2Hashed,
            $q3, $a3Hashed
        ]);

        // Record attempt for rate limiting
        $pdo->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)")->execute([$clientIp]);

        // Audit trail log
        logActivity($userId, 'ACCOUNT_REGISTERED', "New user self-registration: @$username ($reservedId)", $clientIp);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Registration complete! Your account is pending administrator approval.',
            'idNumber' => $reservedId
        ]);
        exit;

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Registration transaction error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'A server error occurred while processing your registration. Please try again.'
        ]);
        exit;
    }
}
