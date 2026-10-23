<?php
require_once 'config.php';

header('Content-Type: application/json');

$idNumber = '';
$email = '';
$userId = null;
if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
    $idNumber = sanitizeInput($payload['idNumber'] ?? '');
    $email = sanitizeInput($payload['email'] ?? '');
    $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : null;
} else {
    $idNumber = sanitizeInput($_POST['idNumber'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
}

if (!$idNumber && !$email && !$userId) { echo json_encode(['success' => false, 'message' => 'ID number, email, or user_id is required']); exit; }
if ($email && !validateEmail($email)) { echo json_encode(['success' => false, 'message' => 'Invalid email']); exit; }
if ($idNumber && !preg_match('/^\d{4}-\d{4}$/', $idNumber)) { echo json_encode(['success' => false, 'message' => 'Invalid ID number format']); exit; }

$db = new Database();
if ($idNumber) {
    $stmt = $db->prepare("SELECT id FROM users WHERE id_number = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$idNumber]);
} elseif ($email) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$email]);
} else {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$userId]);
}
$user = $stmt->fetch();
if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

$q = $db->prepare("SELECT question1, question2, question3 FROM user_security_questions WHERE user_id = ? LIMIT 1");
$q->execute([$user['id']]);
$row = $q->fetch();
if (!$row) { echo json_encode(['success' => false, 'message' => 'No security questions set for this account.']); exit; }

function formatSecurityQuestion(string $q): string {
    $map = [
        'best_friend_elementary' => "Who is your best friend in Elementary?",
        'favorite_pet'           => "What is the name of your favorite pet?",
        'favorite_teacher'       => "Who is your favorite teacher in high school?",
        'mother_maiden_name'     => "What is your mother's maiden name?",
        'birth_city'             => "In what city were you born?",
        'first_car'              => "What was the make and model of your first car?"
    ];
    if (isset($map[$q])) {
        return $map[$q];
    }
    return htmlspecialchars_decode($q, ENT_QUOTES | ENT_HTML5);
}

$row['question1'] = formatSecurityQuestion((string)$row['question1']);
$row['question2'] = formatSecurityQuestion((string)$row['question2']);
$row['question3'] = formatSecurityQuestion((string)$row['question3']);

echo json_encode(['success' => true, 'data' => $row]);
?>


