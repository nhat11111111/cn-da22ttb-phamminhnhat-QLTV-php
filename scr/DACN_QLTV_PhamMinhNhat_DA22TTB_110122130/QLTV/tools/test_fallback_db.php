<?php
// Test FallbackDatabase
require_once __DIR__ . '/../backend/includes/db.php';

echo "=== Testing FallbackDatabase ===\n\n";

$conn = get_db_connection();
echo "Database Type: " . get_db_type() . "\n";
echo "Connection Object: " . get_class($conn) . "\n\n";

// Test SQL query để tìm user
$sql = 'SELECT * FROM NGUOIDUNG WHERE username = ? OR email = ?';
$stmt = $conn->prepare($sql);

if ($stmt) {
    echo "✓ Prepared statement created\n";
    
    $identifier = 'nhat';
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    
    $result = $stmt->get_result();
    echo "✓ Query executed\n";
    
    $user = $result->fetch_assoc();
    
    if ($user) {
        echo "✓ User found!\n";
        echo json_encode($user, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ User not found\n";
    }
    
    $stmt->close();
} else {
    echo "❌ Prepare failed\n";
}
