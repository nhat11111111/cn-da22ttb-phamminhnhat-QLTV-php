<?php
/**
 * Full browser simulation test
 * Tests login and session check with proper cookie handling
 */

echo "=== Full Browser Flow Test ===\n\n";

// Simulate browser session
$cookies = [];
$sessionCookie = null;

// Step 1: Login
echo "Step 1: Login Request (POST /api/auth.php)\n";
$ch = curl_init('http://127.0.0.1:8000/api/auth.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'identifier' => 'nhat',
    'password' => '123456'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);  // Get headers too
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');  // Save cookies

$fullResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Split headers and body
list($headers, $body) = explode("\r\n\r\n", $fullResponse, 2);
echo "HTTP Code: $httpCode\n";
echo "Response: " . trim($body) . "\n";

$loginData = json_decode($body, true);
if ($loginData['success'] ?? false) {
    echo "✓ Login successful\n\n";
    
    // Step 2: Check session
    echo "Step 2: Session Check (GET /api/check-session.php)\n";
    $ch = curl_init('http://127.0.0.1:8000/api/check-session.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');  // Use saved cookies
    
    $response = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode2\n";
    echo "Response: " . trim($response) . "\n";
    
    $sessionData = json_decode($response, true);
    if ($sessionData['loggedIn'] ?? false) {
        echo "✓ Session check successful\n";
        echo "✓ User: " . ($sessionData['user']['username'] ?? 'unknown') . "\n";
        echo "✓ Role: " . ($sessionData['user']['role'] ?? 'unknown') . "\n";
        echo "\n✓ LOGIN FLOW WORKS CORRECTLY\n";
    } else {
        echo "✗ Session check failed\n";
        echo "Debugging: " . json_encode($sessionData) . "\n";
    }
} else {
    echo "✗ Login failed: " . ($loginData['message'] ?? 'unknown') . "\n";
}
