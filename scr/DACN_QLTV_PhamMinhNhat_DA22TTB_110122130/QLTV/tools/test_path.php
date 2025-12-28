<?php
/**
 * Test with correct frontend root
 */
$root = dirname(__FILE__) . '/../frontend';
echo "Correct root: $root\n";
echo "API file should be at: $root/api/auth.php\n";
echo "File exists: " . (file_exists($root . '/api/auth.php') ? 'YES ✓' : 'NO ✗') . "\n";
