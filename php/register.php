<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Support JSON and form-encoded payloads
$input = null;
if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
}

$username = strtolower(sanitizeInput(($input['username'] ?? $_POST['username'] ?? '')));
$email = strtolower(sanitizeInput(($input['email'] ?? $_POST['email'] ?? '')));
$password = ($input['password'] ?? $_POST['password'] ?? '');
$confirmPassword = ($input['confirm_password'] ?? $_POST['confirm_password'] ?? '');
// Security questions are now set up after registration; mark placeholders for now
$securityQuestion = 'pending';
$securityAnswer = 'pending';

// Validation
$errors = [];

if (empty($username)) {
    $errors[] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters';
} elseif (strlen($username) > 50) {
    $errors[] = 'Username must be at most 50 characters';
} elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain lowercase letters, numbers, and underscores';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (strlen($email) > 100) {
    $errors[] = 'Email must be at most 100 characters';
} elseif (!validateEmail($email)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
} elseif (!preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}

// Security questions no longer required at registration

if (!empty($errors)) {
    sendJsonResponse(false, implode(', ', $errors));
}

$db = new Database();

// Check if username already exists
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    sendJsonResponse(false, 'Username is already taken');
}

// Check if email already exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendJsonResponse(false, 'Email is already registered');
}

// Hash password and security answer
$hashedPassword = hashPassword($password);
$hashedSecurityAnswer = hashPassword($securityAnswer);

// Insert new user
try {
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, security_question, security_answer) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $username,
        $email,
        $hashedPassword,
        $securityQuestion,
        $hashedSecurityAnswer
    ]);
    
    $userId = $db->lastInsertId();
    
    sendJsonResponse(true, 'Account created successfully', [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email
    ]);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    sendJsonResponse(false, 'Registration failed. Please try again.');
}
?>
