<?php
/**
 * Simple test of frontend auth
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate a login request through the frontend auth API
$_POST['action'] = 'login';
$_POST['identifier'] = 'nhat';
$_POST['password'] = '123456';

echo "=== Testing Frontend Auth API ===\n\n";

// Include the frontend auth API directly
ob_start();
require __DIR__ . '/../frontend/api/auth.php';
$response = ob_get_clean();

echo "Response from frontend/api/auth.php:\n";
echo $response . "\n\n";

// Verify session was set
if (isset($_SESSION['user'])) {
    echo "✓ Session set successfully\n";
    echo "User data:\n";
    var_dump($_SESSION['user']);
} else {
    echo "✗ Session not set\n";
}
