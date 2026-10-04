<?php
require_once 'config.php';

requireAuthJson();

// Update session activity
$_SESSION['last_activity'] = time();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendJsonResponse(false, 'Invalid payload');
}

$q1 = $input['q1'] ?? '';
$a1 = trim($input['a1'] ?? '');
$q2 = $input['q2'] ?? '';
$a2 = trim($input['a2'] ?? '');
$q3 = $input['q3'] ?? '';
$a3 = trim($input['a3'] ?? '');

if (!$q1 || !$q2 || !$q3 || $a1 === '' || $a2 === '' || $a3 === '') {
    sendJsonResponse(false, 'All questions and answers are required');
}

// store hashed answers for privacy
$a1h = hashPassword($a1);
$a2h = hashPassword($a2);
$a3h = hashPassword($a3);

$db = new Database();

// ensure table exists
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

$stmt = $db->prepare("INSERT INTO user_security_questions (user_id, question1, answer1, question2, answer2, question3, answer3)
VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
question1 = VALUES(question1), answer1 = VALUES(answer1),
question2 = VALUES(question2), answer2 = VALUES(answer2),
question3 = VALUES(question3), answer3 = VALUES(answer3)");

$stmt->execute([
    $_SESSION['user_id'], $q1, $a1h, $q2, $a2h, $q3, $a3h
]);

sendJsonResponse(true, 'Security questions saved');

?>


