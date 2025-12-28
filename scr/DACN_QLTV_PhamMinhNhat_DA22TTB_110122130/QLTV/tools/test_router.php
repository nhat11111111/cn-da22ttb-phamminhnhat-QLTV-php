<?php
/**
 * Diagnostic: Test frontend API routing
 */

echo "=== Frontend Router Diagnostic ===\n\n";

// Simulate different request URIs
$testUris = [
    '/api/auth.php',
    '/api/auth',
    '/views/Dangnhap.html',
];

$root = __DIR__ . '/frontend';
echo "Root directory: $root\n\n";

foreach ($testUris as $uri) {
    echo "Testing URI: $uri\n";
    
    // This is what the router does
    if (file_exists($root . $uri) && is_file($root . $uri)) {
        echo "  ✓ Found as static file: $root$uri\n";
    } else {
        echo "  ✗ Not found as static file\n";
    }
    
    // API handling
    if (strpos($uri, '/api/') === 0) {
        $path = str_replace('/api/', '', $uri);
        
        // Try with .php added
        $apiFile = $root . '/api' . $path . '.php';
        echo "  Trying: $apiFile\n";
        if (file_exists($apiFile) && is_file($apiFile)) {
            echo "  ✓ Found API file\n";
        } else {
            echo "  ✗ Not found\n";
        }
        
        // Try without .php added
        $apiFile = $root . '/api' . $path;
        echo "  Trying: $apiFile\n";
        if (file_exists($apiFile) && is_file($apiFile)) {
            echo "  ✓ Found API file\n";
        } else {
            echo "  ✗ Not found\n";
        }
    }
    
    echo "\n";
}

// Now test actual API call
echo "=== Testing Actual API Call ===\n";
echo "Simulating POST to /api/auth.php...\n";

$_POST = ['action' => 'login', 'identifier' => 'nhat', 'password' => '123456'];
$_SERVER['REQUEST_URI'] = '/api/auth.php';
$_SERVER['REQUEST_METHOD'] = 'POST';

// This is what the frontend sees
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
echo "Parsed URI: $uri\n";

// Check static file
if (file_exists($root . $uri) && is_file($root . $uri)) {
    echo "Found as static: Yes\n";
    echo "✓ This would be served directly\n";
} else {
    echo "Found as static: No\n";
    
    // Check API
    if (strpos($uri, '/api/') === 0) {
        $path = str_replace('/api/', '', $uri);
        $apiFile = $root . '/api' . $path . '.php';
        
        if (file_exists($apiFile)) {
            echo "API file path: $apiFile\n";
            echo "✓ API file exists\n";
        } else {
            echo "API file path: $apiFile\n";
            echo "✗ API file does NOT exist\n";
        }
    }
}
