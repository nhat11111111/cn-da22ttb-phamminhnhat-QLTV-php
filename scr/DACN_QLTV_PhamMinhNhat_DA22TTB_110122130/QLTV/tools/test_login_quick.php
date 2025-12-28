<?php
// Simple inline test
session_start();
$_POST = ['action' => 'login', 'identifier' => 'nhat', 'password' => '123456'];

ob_start();
require __DIR__ . '/../frontend/api/auth.php';
$output = ob_get_clean();

// Extract JSON
$lines = explode("\n", $output);
foreach ($lines as $line) {
    if (strpos($line, '{') === 0) {
        echo "Login Response: " . trim($line) . "\n";
    }
}

// Check session
if (isset($_SESSION['user'])) {
    echo "Session User: " . json_encode($_SESSION['user']) . "\n";
    echo "Auth Flow: SUCCESS - Ready for homepage redirect\n";
} else {
    echo "Session User: NOT SET\n";
}
