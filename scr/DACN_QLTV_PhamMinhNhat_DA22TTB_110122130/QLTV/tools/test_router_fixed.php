<?php
/**
 * Test fixed router logic
 */

$root = __DIR__ . '/frontend';
$testUris = ['/api/auth.php', '/api/auth', '/views/Dangnhap.html'];

echo "=== Testing Fixed Router ===\n";
echo "Root: $root\n\n";

foreach ($testUris as $uri) {
    echo "URI: $uri\n";
    
    // Check static file first
    if (file_exists($root . $uri) && is_file($root . $uri)) {
        echo "  ✓ Found as static file\n";
        continue;
    }
    
    // API handling
    if (strpos($uri, '/api/') === 0) {
        // Try with .php extension if not already present
        if (!preg_match('/\.php$/', $uri)) {
            $apiFile = $root . $uri . '.php';
            echo "  Trying (with .php): $apiFile\n";
            if (file_exists($apiFile) && is_file($apiFile)) {
                echo "  ✓ Found\n";
                continue;
            }
        }
        
        // Try direct file
        $apiFile = $root . $uri;
        echo "  Trying (direct): $apiFile\n";
        if (file_exists($apiFile) && is_file($apiFile)) {
            echo "  ✓ Found\n";
            continue;
        }
        
        echo "  ✗ NOT found\n";
    }
    echo "\n";
}
