<?php
declare(strict_types=1);

// Test script - Kiểm tra kết nối MongoDB
// Chạy: php tools/test_mongo_connection.php

echo "=== MongoDB Connection Test ===\n\n";

// Load connection helpers
require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/mongo-helpers.php';

try {
    echo "1. Connecting to database...\n";
    $conn = get_db_connection();
    $dbType = get_db_type();
    
    echo "   ✓ Database type: " . strtoupper($dbType) . "\n\n";
    
    if ($dbType === 'mongodb') {
        echo "2. Testing MongoDB connection...\n";
        
        // Get all users
        $users = get_all_users();
        
        if (empty($users)) {
            echo "   ⚠️  No users found in MongoDB.\n";
        } else {
            echo "   ✓ Found " . count($users) . " users in MongoDB\n\n";
            
            echo "3. Users:\n";
            echo str_repeat("=", 80) . "\n";
            
            foreach ($users as $i => $user) {
                echo "\n[User " . ($i + 1) . "]\n";
                echo "  ID: " . ($user['ID'] ?? 'N/A') . "\n";
                echo "  Username: " . ($user['username'] ?? 'N/A') . "\n";
                echo "  Full Name: " . ($user['ho_ten'] ?? 'N/A') . "\n";
                echo "  Email: " . ($user['email'] ?? 'N/A') . "\n";
                echo "  Role: " . ($user['loai'] ?? 'N/A') . "\n";
                echo "  Password Hash: " . substr($user['password'] ?? '', 0, 20) . "...\n";
            }
            
            echo "\n" . str_repeat("=", 80) . "\n\n";
        }
        
        echo "✓ MongoDB connection successful!\n";
        
    } elseif ($dbType === 'mysql') {
        echo "ℹ️  Connected to MySQL database.\n";
        
    } elseif ($dbType === 'fallback') {
        echo "ℹ️  Using Fallback JSON database.\n";
    }
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
