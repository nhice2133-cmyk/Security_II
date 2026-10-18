<?php
// Test login for all 3 seeded accounts

function testLogin($username, $password) {
    $ch = curl_init('http://localhost/Security_II/php/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'password' => $password,
        'remember' => 0
    ]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    return ['httpCode' => $httpCode, 'response' => $json, 'raw' => $response];
}

echo "Testing login for superadmin...\n";
$sa = testLogin('superadmin', 'Admin@12345!');
echo "Superadmin login result: " . json_encode($sa['response']) . "\n\n";

echo "Testing login for admin...\n";
$ad = testLogin('admin', 'Admin@12345!');
echo "Admin login result: " . json_encode($ad['response']) . "\n\n";

echo "Testing login for user...\n";
$us = testLogin('user', 'User@1234');
echo "User login result: " . json_encode($us['response']) . "\n\n";

if (($sa['response']['success'] ?? false) && ($ad['response']['success'] ?? false) && ($us['response']['success'] ?? false)) {
    echo "SUCCESS: All 3 accounts logged in successfully!\n";
} else {
    echo "FAILURE: One or more accounts failed to log in.\n";
    exit(1);
}
