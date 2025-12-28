<?php
/**
 * Test Frontend Auth Flow
 * This simulates what the JavaScript does when logging in
 */

// Simulate session start
$_SESSION = [];

// Test 1: Simulate login request
echo "=== Test Frontend Auth Flow ===\n\n";

// Start output buffering to capture auth API response
ob_start();

// Simulate POST data
$_POST['action'] = 'login';
$_POST['identifier'] = 'nhat';
$_POST['password'] = '123456';

// Change to frontend directory to test API
chdir(__DIR__ . '/frontend');

// Load auth API
require 'api/auth.php';

// Get the output
$authOutput = ob_get_clean();
echo "Auth API Response:\n";
echo $authOutput . "\n\n";

// Parse response
$authResult = json_decode($authOutput, true);
if ($authResult['success'] ?? false) {
    echo "✓ Login successful\n";
    echo "Session should now contain: " . json_encode($_SESSION['user'] ?? null) . "\n\n";
    
    // Test 2: Check session
    echo "Test 2: Check Session\n";
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($_POST);
    require 'api/check-session.php';
    $sessionOutput = ob_get_clean();
    echo "Session Check Response:\n";
    echo $sessionOutput . "\n\n";
    
    $sessionResult = json_decode($sessionOutput, true);
    if ($sessionResult['loggedIn'] ?? false) {
        echo "✓ Session check successful\n";
        echo "✓ Frontend can now access: " . ($sessionResult['user']['role'] == '2' ? 'Trangchuuser.html' : 'role=' . $sessionResult['user']['role']) . "\n";
    }
} else {
    echo "✗ Login failed: " . ($authResult['message'] ?? 'Unknown error') . "\n";
}
