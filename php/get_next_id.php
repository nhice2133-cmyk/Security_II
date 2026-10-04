<?php
require_once 'config.php';
header('Content-Type: application/json');
try {
    $db = new Database();
    $currentYear = date('Y');
    $maxAttempts = 100; // Prevent infinite loop
    $attempts = 0;
    $nextId = null;
    
    do {
        // Generate random 4-digit number (0000-9999)
        $randomNum = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $newIdNumber = $currentYear . '-' . $randomNum;
        
        // Check if this ID number already exists
        $stmt = $db->prepare("SELECT 1 FROM users WHERE id_number = ? LIMIT 1");
        $stmt->execute([$newIdNumber]);
        $exists = $stmt->fetch();
        
        $attempts++;
        
        // If ID doesn't exist, use it
        if (!$exists) {
            $nextId = $newIdNumber;
            break;
        }
        
    } while ($attempts < $maxAttempts);
    
    // Fallback: if all attempts failed, use timestamp-based approach
    if (!$nextId) {
        $timestamp = substr(time(), -4);
        $nextId = $currentYear . '-' . $timestamp;
    }
    
    echo json_encode(['success' => true, 'idNumber' => $nextId]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
