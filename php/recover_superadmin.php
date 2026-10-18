<?php
/**
 * Emergency Superadmin Recovery Tool (CLI Only)
 * 
 * Provides an authorized, secure recovery mechanism when Superadmin access
 * is unexpectedly lost or locks become stuck.
 * 
 * Usage from terminal / command prompt:
 *   php php/recover_superadmin.php --status
 *   php php/recover_superadmin.php --reset-lock
 *   php php/recover_superadmin.php --appoint=<user_id_or_username>
 *   php php/recover_superadmin.php --help
 */

// Strictly prevent web/HTTP access
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden: This administrative recovery utility can only be executed via the server console (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/config.php';

$db = new Database();
$pdo = $db->getConnection();

$options = getopt('h::s::', ['help::', 'status::', 'reset-lock::', 'appoint::']);

if (isset($options['h']) || isset($options['help']) || empty($options)) {
    echo "=================================================================\n";
    echo "  CYBERAUTH EMERGENCY SUPERADMIN RECOVERY UTILITY (CLI)\n";
    echo "=================================================================\n";
    echo "Usage:\n";
    echo "  php php/recover_superadmin.php --status\n";
    echo "      Displays the currently designated Superadmin and lock status.\n\n";
    echo "  php php/recover_superadmin.php --reset-lock\n";
    echo "      Clears a stuck super_admin_lock row so the current Superadmin can log in.\n\n";
    echo "  php php/recover_superadmin.php --appoint=<user_id_or_username>\n";
    echo "      Appoints a specified account as the single authorized Superadmin.\n";
    echo "      Enforces single-Superadmin invariants and logs the recovery event.\n\n";
    echo "  php php/recover_superadmin.php --help\n";
    echo "      Shows this usage guide.\n";
    echo "=================================================================\n";
    exit(0);
}

if (isset($options['status']) || isset($options['s'])) {
    echo "--- SUPERADMIN ACCOUNT STATUS ---\n";
    $stmt = $pdo->query("SELECT id, id_number, username, email, role, status, is_active, is_former_superadmin, last_login FROM users WHERE role = 'super_admin'");
    $saUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($saUsers)) {
        echo "WARNING: No account currently holds the 'super_admin' role!\n";
        echo "Use --appoint=<user_id_or_username> to designate an administrator as Superadmin.\n\n";
    } else {
        foreach ($saUsers as $sa) {
            echo "ID:            {$sa['id']}\n";
            echo "Employee ID:   {$sa['id_number']}\n";
            echo "Username:      {$sa['username']}\n";
            echo "Email:         {$sa['email']}\n";
            echo "Status:        {$sa['status']} (Active: " . ($sa['is_active'] ? 'YES' : 'NO') . ")\n";
            echo "Last Login:    " . ($sa['last_login'] ?: 'Never') . "\n";
        }
    }
    
    echo "\n--- LOCK STATUS ---\n";
    $lock = $pdo->query("SELECT * FROM super_admin_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if ($lock && $lock['active_user_id'] !== null) {
        $expired = ($lock['expires_at'] !== null && strtotime($lock['expires_at']) < time());
        echo "Lock Held By:  User ID {$lock['active_user_id']} (Login Log ID: {$lock['login_log_id']})\n";
        echo "Expires At:    {$lock['expires_at']} " . ($expired ? '[EXPIRED]' : '[ACTIVE]') . "\n";
    } else {
        echo "Lock State:    Idle / Released (No active session holding lock)\n";
    }
    
    echo "\n--- ELIGIBLE ADMINISTRATORS ---\n";
    $admins = $pdo->query("SELECT id, id_number, username, email, status, is_active FROM users WHERE role = 'admin' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($admins)) {
        echo "No accounts currently hold the 'admin' role.\n";
    } else {
        foreach ($admins as $adm) {
            $st = $adm['status'] . ($adm['is_active'] ? '' : ' [INACTIVE]');
            echo "  [ID: {$adm['id']}] {$adm['username']} ({$adm['email']}) - Status: {$st}\n";
        }
    }
    exit(0);
}

if (isset($options['reset-lock'])) {
    echo "Clearing super_admin_lock...\n";
    $pdo->prepare("UPDATE super_admin_lock SET active_user_id=NULL, login_log_id=NULL, acquired_at=NULL, expires_at=NULL WHERE id=1")->execute();
    logActivity(null, 'SUPERADMIN_RECOVERY', "Emergency CLI lock reset executed.", '127.0.0.1', null, "Manual CLI lock reset", 'SUCCESS');
    echo "Success: Superadmin lock has been released. The authorized Superadmin can now log in.\n";
    exit(0);
}

if (isset($options['appoint'])) {
    $target = trim($options['appoint']);
    if (empty($target)) {
        echo "Error: Target username or user ID must be provided. Example: --appoint=admin or --appoint=14\n";
        exit(1);
    }
    
    // Find target
    if (is_numeric($target)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([(int)$target]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? OR LOWER(email) = ?");
        $stmt->execute([strtolower($target), strtolower($target)]);
    }
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        echo "Error: User '{$target}' was not found in the database.\n";
        exit(1);
    }
    
    $targetId = (int)$targetUser['id'];
    echo "Found account: '{$targetUser['username']}' (ID: {$targetId}, Current Role: {$targetUser['role']}, Status: {$targetUser['status']})\n";
    echo "Proceeding with emergency Superadmin recovery appointment...\n";
    
    $pdo->beginTransaction();
    try {
        // Demote any existing super_admins
        $pdo->prepare("UPDATE users SET role = 'admin', is_former_superadmin = 1, updated_at = NOW() WHERE role = 'super_admin' AND id != ?")
            ->execute([$targetId]);
            
        // Appoint target
        $pdo->prepare("UPDATE users SET role = 'super_admin', status = 'approved', is_active = 1, privileges = NULL, updated_at = NOW() WHERE id = ?")
            ->execute([$targetId]);
            
        // Clear lock
        $pdo->prepare("UPDATE super_admin_lock SET active_user_id = NULL, login_log_id = NULL, acquired_at = NULL, expires_at = NULL WHERE id = 1")
            ->execute();
            
        // Invalidate login logs
        $pdo->prepare("UPDATE login_logs SET time_out = NOW() WHERE time_out IS NULL")->execute();
        
        // Log to succession logs
        $pdo->prepare("INSERT INTO superadmin_succession_logs (outgoing_superadmin_id, incoming_superadmin_id, reason, former_superadmin_status, ip_address, created_at) VALUES (?, ?, ?, 'active', '127.0.0.1', NOW())")
            ->execute([$targetId, $targetId, "Emergency CLI Superadmin Recovery appointment for {$targetUser['username']}"]);
            
        // Log to activity logs
        $pdo->prepare("INSERT INTO activity_logs (user_id, target_user_id, action, details, reason, outcome, previous_state, new_state, ip_address, created_at) VALUES (NULL, ?, 'SUPERADMIN_RECOVERY', ?, ?, 'SUCCESS', ?, 'super_admin', '127.0.0.1', NOW())")
            ->execute([
                $targetId, 
                "Emergency CLI Superadmin Recovery: Account '{$targetUser['username']}' appointed as Superadmin.", 
                "System console recovery procedure",
                $targetUser['role']
            ]);
            
        $saCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();
        if ($saCount !== 1) {
            $pdo->rollBack();
            echo "Error: Invariant violation. Rolling back.\n";
            exit(1);
        }
        
        $pdo->commit();
        echo "\nSUCCESS: Account '{$targetUser['username']}' (ID: {$targetId}) is now the active Superadmin.\n";
        echo "Account status set to Active. Stale locks cleared.\n";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "Error executing recovery: " . $e->getMessage() . "\n";
        exit(1);
    }
    exit(0);
}
