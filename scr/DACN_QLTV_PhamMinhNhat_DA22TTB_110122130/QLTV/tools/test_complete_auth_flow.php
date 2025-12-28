<?php
/**
 * Test complete frontend authentication flow
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "=== Complete Frontend Auth Flow Test ===\n\n";

// Step 1: Test Login
echo "Step 1: Simulate Login Request\n";
$_POST['action'] = 'login';
$_POST['identifier'] = 'nhat';
$_POST['password'] = '123456';

ob_start();
// Suppress headers
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
require __DIR__ . '/../frontend/api/auth.php';
$authResponse = ob_get_clean();

echo "Auth Response: $authResponse\n";
$authData = json_decode($authResponse, true);

if ($authData['success']) {
    echo "✓ Login successful\n\n";
    
    // Step 2: Test Session Check
    echo "Step 2: Check Session\n";
    ob_start();
    require __DIR__ . '/../frontend/api/check-session.php';
    $sessionResponse = ob_get_clean();
    
    echo "Session Response: $sessionResponse\n";
    $sessionData = json_decode($sessionResponse, true);
    
    if ($sessionData['loggedIn'] ?? false) {
        echo "✓ Session check successful\n";
        echo "✓ User role: " . ($sessionData['user']['role'] ?? 'unknown') . "\n";
        echo "✓ Should redirect to: ";
        
        $role = (string)($sessionData['user']['role'] ?? '');
        if ($role === '2') {
            echo "Trangchuuser.html\n";
        } elseif ($role === '3') {
            echo "Trangchuthuthu.html\n";
        } elseif ($role === '1') {
            echo "Trangchuadmin.html\n";
        } else {
            echo "Trangchuuser.html (default)\n";
        }
    } else {
        echo "✗ Session check failed\n";
    }
} else {
    echo "✗ Login failed: " . ($authData['message'] ?? 'unknown error') . "\n";
}

echo "\n=== Test Complete ===\n";
