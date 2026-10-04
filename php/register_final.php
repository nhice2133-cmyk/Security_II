<?php
require_once 'config.php';

header('Content-Type: application/json');

// Expect JSON
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

if (!isset($_SESSION['reg_step1'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

$step1 = $_SESSION['reg_step1'];

$q1 = sanitizeInput($payload['question1'] ?? '');
$a1 = $payload['answer1'] ?? '';
$q2 = sanitizeInput($payload['question2'] ?? '');
$a2 = $payload['answer2'] ?? '';
$q3 = sanitizeInput($payload['question3'] ?? '');
$a3 = $payload['answer3'] ?? '';

$errors = [];
$fieldErrors = [];

if (empty($q1) || empty($a1) || empty($q2) || empty($a2) || empty($q3) || empty($a3)) {
    $errors[] = 'All 3 security questions and answers are required';
    if (empty($a1)) $fieldErrors['answer1'] = 'Answer required';
    if (empty($a2)) $fieldErrors['answer2'] = 'Answer required';
    if (empty($a3)) $fieldErrors['answer3'] = 'Answer required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors), 'fieldErrors' => $fieldErrors]);
    exit;
}

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
        
        // Fallback: use a cryptographically random suffix
        $timestamp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return $currentYear . '-' . $timestamp;
    }

    $idNumber = $step1['idNumber'];
    if (empty($idNumber)) {
        $idNumber = generateNextIdNumber($db);
    }

    // Use the pre-hashed password from step1 session (never stored in plaintext)
    $hashedPassword = $step1['password_hash'] ?? hashPassword($step1['password'] ?? '');
    if (empty($hashedPassword)) {
        echo json_encode(['success' => false, 'message' => 'Session data is incomplete. Please start registration again.']);
        exit;
    }
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
        $step1['username'], $step1['email'], $hashedPassword, $idNumber, $step1['phoneNumber'],
        $step1['firstName'], $step1['lastName'], $step1['middleName'], $step1['extension'], $step1['birthDate'],
        $step1['age'], $step1['sex'], $step1['purok'], $step1['barangay'], $step1['city'], $step1['province'], $step1['country'], $step1['zipCode']
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

    // Clear session
    unset($_SESSION['reg_step1']);

    echo json_encode([
        'success' => true,
        'idNumber' => $idNumber,
        'message' => 'Registration successful. Waiting for admin approval.'
    ]);
    exit;

} catch (Throwable $e) {
    error_log('register_final error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
