<?php
require_once 'config.php';

$db = new Database();

// Close login_log entry
if (isset($_SESSION['login_log_id'])) {
    $db->prepare("UPDATE login_logs SET time_out = NOW() WHERE id = ? AND time_out IS NULL")
       ->execute([$_SESSION['login_log_id']]);
}

// If Super Admin, release the lock so another SA can log in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' && isset($_SESSION['login_log_id'])) {
    $db->prepare(
        "UPDATE super_admin_lock
         SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL
         WHERE login_log_id = ?"
    )->execute([$_SESSION['login_log_id']]);
}

// Use the proper session cleanup function
endSession();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page
header('Location: ../pages/login.php');
exit;
?>



