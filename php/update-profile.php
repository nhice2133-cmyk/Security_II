<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!validateSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// CSRF protection for state-changing operations
requireCsrf();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { 
    echo json_encode(['success' => false, 'message' => 'Invalid payload']); 
    exit; 
}

// Check privileges if admin
if ($_SESSION['role'] === 'admin') {
    $db = new Database();
    $stmtPriv = $db->prepare("SELECT privileges FROM users WHERE id = ?");
    $stmtPriv->execute([$_SESSION['user_id']]);
    $privUser = $stmtPriv->fetch();
    $privs = $privUser && $privUser['privileges'] ? json_decode($privUser['privileges'], true) : [];
    if (!is_array($privs)) $privs = [];
    if (!in_array('profile_management', $privs)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Denied. Missing profile_management privilege.']);
        exit;
    }
}

$firstNameRaw = trim((string)($input['firstName'] ?? ''));
$middleNameRaw = trim((string)($input['middleName'] ?? ''));
$lastNameRaw = trim((string)($input['lastName'] ?? ''));
$extensionRaw = trim((string)($input['extension'] ?? ''));
$usernameRaw = trim((string)($input['username'] ?? ''));
$emailRaw = trim((string)($input['email'] ?? ''));
$phoneRaw = trim((string)($input['phone'] ?? ''));
$addressRaw = trim((string)($input['address'] ?? ''));
$birthDateRaw = trim((string)($input['birthDate'] ?? ''));
$sexRaw = trim((string)($input['sex'] ?? ''));
$countryRaw = trim((string)($input['country'] ?? ''));
$provinceRaw = trim((string)($input['province'] ?? ''));
$cityRaw = trim((string)($input['city'] ?? ''));
$barangayRaw = trim((string)($input['barangay'] ?? ''));
$purokRaw = trim((string)($input['purok'] ?? ''));

$errors = [];
$namePattern = "/^[a-zA-Z\s\-'.]+$/";
$locationPattern = "/^[A-Za-z0-9\s\-'\.,]+$/";
$purokPattern = "/^[A-Za-z0-9\s\-]+$/";
$computedAge = null;
$birthDateObj = null;
$digitsOnlyPhone = '';
$normalizedExtension = '';
$sexValue = strtolower($sexRaw);
$provinceValue = trim(preg_replace('/\s+/', ' ', $provinceRaw));
$cityValue = trim(preg_replace('/\s+/', ' ', $cityRaw));
$barangayValue = trim(preg_replace('/\s+/', ' ', $barangayRaw));
$purokValue = preg_replace('/\s+/', ' ', $purokRaw);
$purokValue = preg_replace('/[^A-Za-z0-9\s\-]/', ' ', $purokValue ?? '');
$purokValue = trim($purokValue);
$addressValue = trim(preg_replace('/\s+/', ' ', $addressRaw));

if ($firstNameRaw === '' || !preg_match($namePattern, $firstNameRaw)) {
    $errors[] = 'Invalid first name';
}

if ($lastNameRaw === '' || !preg_match($namePattern, $lastNameRaw)) {
    $errors[] = 'Invalid last name';
}

if ($middleNameRaw !== '' && !preg_match($namePattern, $middleNameRaw)) {
    $errors[] = 'Invalid middle name';
}

if ($extensionRaw !== '') {
    $normalizedExtension = strtolower($extensionRaw);
    $allowedExtensions = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];
    if (!in_array($normalizedExtension, $allowedExtensions, true)) {
        $errors[] = 'Invalid extension';
    }
}

if ($usernameRaw === '' || strlen($usernameRaw) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $usernameRaw)) {
    $errors[] = 'Invalid username';
}
if ($emailRaw === '' || !validateEmail($emailRaw)) {
    $errors[] = 'Invalid email';
}

if ($phoneRaw === '') {
    $errors[] = 'Phone number is required';
} else {
    $digitsOnlyPhone = preg_replace('/\D/', '', $phoneRaw);
    if (!preg_match('/^09\d{9}$/', $digitsOnlyPhone)) {
        $errors[] = 'Phone number must start with 09 and be 11 digits';
    }
}

if ($birthDateRaw === '') {
    $errors[] = 'Birth date is required';
} else {
    $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthDateRaw);
    $dateCheck = DateTime::getLastErrors();
    if (!$birthDateObj || $dateCheck['warning_count'] > 0 || $dateCheck['error_count'] > 0) {
        $errors[] = 'Invalid birth date';
    } else {
        $today = new DateTime();
        if ($birthDateObj > $today) {
            $errors[] = 'Birth date cannot be in the future';
        } else {
            $computedAge = $birthDateObj->diff($today)->y;
            if ($computedAge < 1 || $computedAge > 120) {
                $errors[] = 'Age must be between 1 and 120';
            }
        }
    }
}

if ($sexValue === '') {
    $errors[] = 'Sex is required';
} elseif (!in_array($sexValue, ['male', 'female', 'other'], true)) {
    $errors[] = 'Invalid sex selection';
}

$countryValue = trim(preg_replace('/\s+/', ' ', $countryRaw));
if ($countryValue === '') {
    $errors[] = 'Country is required';
} elseif (!preg_match($locationPattern, $countryValue)) {
    $errors[] = 'Country contains invalid characters';
}

if ($provinceValue === '') {
    $errors[] = 'Province is required';
} elseif (preg_match('/^\d/', $provinceValue)) {
    $errors[] = 'Province cannot start with a number';
} elseif (!preg_match($locationPattern, $provinceValue)) {
    $errors[] = 'Province contains invalid characters';
} elseif (preg_match('/([a-zA-Z])\1\1/', str_replace(' ', '', strtolower($provinceValue)))) {
    $errors[] = 'Province cannot contain 3 consecutive identical letters';
}

if ($cityValue === '') {
    $errors[] = 'City/Municipality is required';
} elseif (preg_match('/^\d/', $cityValue)) {
    $errors[] = 'City/Municipality cannot start with a number';
} elseif (!preg_match($locationPattern, $cityValue)) {
    $errors[] = 'City/Municipality contains invalid characters';
} elseif (preg_match('/([a-zA-Z])\1\1/', str_replace(' ', '', strtolower($cityValue)))) {
    $errors[] = 'City/Municipality cannot contain 3 consecutive identical letters';
}

if ($barangayValue === '') {
    $errors[] = 'Barangay is required';
} elseif (preg_match('/^\d/', $barangayValue)) {
    $errors[] = 'Barangay cannot start with a number';
} elseif (!preg_match($locationPattern, $barangayValue)) {
    $errors[] = 'Barangay contains invalid characters';
} elseif (preg_match('/([a-zA-Z])\1\1/', str_replace(' ', '', strtolower($barangayValue)))) {
    $errors[] = 'Barangay cannot contain 3 consecutive identical letters';
}

if ($purokValue === '') {
    $errors[] = 'Purok/Street is required';
} elseif (!preg_match($purokPattern, $purokValue)) {
    $errors[] = 'Purok/Street contains invalid characters';
}

if ($addressValue === '') {
    $errors[] = 'Address is required';
}
if (!empty($errors)) { 
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]); 
    exit; 
}

$firstName = sanitizeInput($firstNameRaw);
$middleName = sanitizeInput($middleNameRaw);
$lastName = sanitizeInput($lastNameRaw);
$extension = $normalizedExtension !== '' ? strtoupper($normalizedExtension) : '';
$username = sanitizeInput($usernameRaw);
$email = sanitizeInput($emailRaw);
$phone = $digitsOnlyPhone;
$address = sanitizeInput($addressValue);
$birthDate = $birthDateObj ? $birthDateObj->format('Y-m-d') : null;
$sex = sanitizeInput($sexValue);
$country = sanitizeInput($countryValue);
$province = sanitizeInput($provinceValue);
$city = sanitizeInput($cityValue);
$barangay = sanitizeInput($barangayValue);
$purok = sanitizeInput($purokValue);

if ($middleName === '') {
    $middleName = null;
}
if ($extension === '') {
    $extension = null;
}

try {
    $db = new Database();

    // Ensure uniqueness excluding current user
    $chk = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $chk->execute([$username, $_SESSION['user_id']]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username is already in use']);
        exit;
    }

    $formattedExtension = $extension;
    if ($extension !== null) {
        $labelMap = [
            'JR' => 'Jr',
            'SR' => 'Sr',
            'II' => 'II',
            'III' => 'III',
            'IV' => 'IV',
            'V' => 'V'
        ];
        $formattedExtension = $labelMap[$extension] ?? $extension;
    }

    // SECURITY: update-profile.php must NOT approve pending_setup accounts.
    // That transition is the exclusive responsibility of process-initial-setup.php
    // which performs full validation (password, security questions, etc.).
    // Silently promoting status here allows bypassing the initial setup flow.
    $stmt = $db->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, extension = ?, username = ?, email = ?, phone_number = ?, phone = ?, address = ?, birth_date = ?, age = ?, sex = ?, purok_street = ?, barangay = ?, city = ?, province = ?, country = ? WHERE id = ? AND status NOT IN (\'pending_setup\', \'pending\')');
    $stmt->execute([
        $firstName,
        $middleName,
        $lastName,
        $formattedExtension,
        $username,
        $email,
        $phone,
        $phone,
        $address,
        $birthDate,
        $computedAge,
        $sex,
        $purok,
        $barangay,
        $city,
        $province,
        $country,
        $_SESSION['user_id']
    ]);

    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    // SECURITY: Do NOT approve pending_setup status here.
    // Status changes are managed exclusively by process-initial-setup.php.

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    error_log('Update profile error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}

?>
