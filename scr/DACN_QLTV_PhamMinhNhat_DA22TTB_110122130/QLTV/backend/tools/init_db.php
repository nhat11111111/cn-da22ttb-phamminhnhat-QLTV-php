<?php
declare(strict_types=1);

// CLI/one-off script to initialize DB schema and seed data
// Usage (Windows PowerShell): php QLTV/tools/init_db.php

require_once __DIR__ . '/../includes/db.php';

function run_sql_file(mysqli $conn, string $filePath): void {
    if (!file_exists($filePath)) {
        fwrite(STDERR, "SQL file not found: {$filePath}\n");
        exit(1);
    }
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        fwrite(STDERR, "Failed to read SQL file: {$filePath}\n");
        exit(1);
    }
    // Split by semicolon while being simple (assumes no complex delimiters)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement === '') continue;
        $ok = $conn->query($statement);
        if ($ok !== true) {
            fwrite(STDERR, "SQL error: " . $conn->error . "\nStatement: {$statement}\n");
            exit(1);
        }
    }
}

function ensure_default_accounts(mysqli $conn): void {
    $check = $conn->prepare('SELECT COUNT(*) FROM users');
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ((int)$count > 0) {
        echo "Users already exist, skipping seeding users.\n";
        return;
    }

    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)');
    if (!$stmt) {
        fwrite(STDERR, 'Prepare failed: ' . $conn->error . "\n");
        exit(1);
    }

    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $userPass  = password_hash('user123', PASSWORD_DEFAULT);

    $fullName = 'Quản trị viên'; $email = 'admin@example.com'; $hash = $adminPass; $role = 'admin';
    $stmt->bind_param('ssss', $fullName, $email, $hash, $role);
    $stmt->execute();

    $fullName = 'Độc giả mẫu'; $email = 'user@example.com'; $hash = $userPass; $role = 'user';
    $stmt->bind_param('ssss', $fullName, $email, $hash, $role);
    $stmt->execute();

    $stmt->close();
    echo "Seeded default accounts: admin@example.com / admin123, user@example.com / user123\n";
}

$conn = get_db_connection();
run_sql_file($conn, __DIR__ . '/../database.sql');
ensure_default_accounts($conn);
echo "Database initialized successfully.\n";


