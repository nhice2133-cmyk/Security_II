<?php
require_once 'config.php';

header('Content-Type: application/json');

// Destroy the session when back button is detected
endSession();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

echo json_encode([
    'success' => true,
    'message' => 'Session destroyed due to back button navigation'
]);
?>
