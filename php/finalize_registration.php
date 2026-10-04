<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['pending_registration'])) {
    error_log("No pending registration found in session");
    echo json_encode(['success' => false, 'message' => 'No pending registration']);
    exit;
}

$pending = $_SESSION['pending_registration'];

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) { 
    error_log("Invalid JSON payload received");
    echo json_encode(['success' => false, 'message' => 'Invalid payload']); 
    exit; 
}

$q1 = $payload['q1'] ?? '';
$a1 = trim($payload['a1'] ?? '');
$q2 = $payload['q2'] ?? '';
$a2 = trim($payload['a2'] ?? '');
$q3 = $payload['q3'] ?? '';
$a3 = trim($payload['a3'] ?? '');

if (!$q1 || !$q2 || !$q3 || $a1 === '' || $a2 === '' || $a3 === '') {
    error_log("Missing questions or answers: q1=$q1, q2=$q2, q3=$q3, a1=$a1, a2=$a2, a3=$a3");
    echo json_encode(['success' => false, 'message' => 'All questions and answers are required']);
    exit;
}

$db = new Database();

// Ensure username/email uniqueness just before insert
$chk = $db->prepare("SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1");
$chk->execute([$pending['username'], $pending['email']]);
if ($chk->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
    exit;
}

try {
    // Insert user into database with all fields
    error_log("Attempting to insert user: " . $pending['username'] . " with email: " . $pending['email']);
    $stmt = $db->prepare("INSERT INTO users (username, email, id_number, password, security_question, security_answer, phone_number, first_name, last_name, middle_name, extension, birth_date, age, sex, purok_street, barangay, city, province, country, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $normalizedIdNumber = (isset($pending['id_number']) && $pending['id_number'] !== '') ? $pending['id_number'] : null;
    $normalizedMiddleName = (isset($pending['middle_name']) && $pending['middle_name'] !== '') ? $pending['middle_name'] : null;
    $normalizedExtension = (isset($pending['extension']) && $pending['extension'] !== '') ? $pending['extension'] : null;
    
    $stmt->execute([
        $pending['username'], 
        $pending['email'], 
        $normalizedIdNumber, 
        $pending['password_hash'], 
        'pending', 
        'pending',
        $pending['phone_number'],
        $pending['first_name'],
        $pending['last_name'],
        $normalizedMiddleName,
        $normalizedExtension,
        $pending['birth_date'],
        $pending['age'],
        $pending['sex'],
        $pending['purok_street'],
        $pending['barangay'],
        $pending['city'],
        $pending['province'],
        $pending['country'],
        $pending['zip_code'] ?? null
    ]);
    $userId = $db->lastInsertId();
    error_log("User inserted successfully with ID: " . $userId);

    // Ensure user_security_questions table exists (for fresh installs)
    $db->query("CREATE TABLE IF NOT EXISTS user_security_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        question1 VARCHAR(100) NOT NULL,
        answer1 VARCHAR(255) NOT NULL,
        question2 VARCHAR(100) NOT NULL,
        answer2 VARCHAR(255) NOT NULL,
        question3 VARCHAR(100) NOT NULL,
        answer3 VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ");

    // Insert security questions
    error_log("Inserting security questions for user ID: " . $userId);
    $insertQ = $db->prepare("INSERT INTO user_security_questions (user_id, question1, answer1, question2, answer2, question3, answer3)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        question1 = VALUES(question1), answer1 = VALUES(answer1),
        question2 = VALUES(question2), answer2 = VALUES(answer2),
        question3 = VALUES(question3), answer3 = VALUES(answer3)");

    $insertQ->execute([
        $userId,
        $q1, 
        password_hash($a1, PASSWORD_DEFAULT),
        $q2, 
        password_hash($a2, PASSWORD_DEFAULT),
        $q3, 
        password_hash($a3, PASSWORD_DEFAULT)
    ]);
    error_log("Security questions inserted successfully");

    // Cleanup pending registration
    unset($_SESSION['pending_registration']);

    echo json_encode(['success' => true, 'message' => 'Registration completed']);
} catch (PDOException $e) {
    error_log("Database error in finalize_registration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
} catch (Exception $e) {
    error_log("General error in finalize_registration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

?>


