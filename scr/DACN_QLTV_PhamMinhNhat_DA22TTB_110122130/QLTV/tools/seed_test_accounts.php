<?php
declare(strict_types=1);

// Seed three test accounts into NGUOIDUNG (MySQL). Run with: php tools/seed_test_accounts.php

require_once __DIR__ . '/../backend/models/Database.php';

$conn = null;
try {
    $conn = get_db_connection();
} catch (Throwable $e) {
    echo "Error obtaining DB connection: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

if (!($conn instanceof mysqli)) {
    echo "MySQL connection not available. Aborting seeding.\n";
    exit(1);
}

$accounts = [
    ['username' => 'nhat', 'password' => '123456', 'loai' => 2, 'full_name' => 'Nhat User', 'email' => 'nhat@example.test'],
    ['username' => 'nhatthuthu', 'password' => '123456', 'loai' => 3, 'full_name' => 'Nhat Thu Thu', 'email' => 'nhatthuthu@example.test'],
    ['username' => 'admin130', 'password' => '123456', 'loai' => 1, 'full_name' => 'Admin 130', 'email' => 'admin130@example.test'],
];

foreach ($accounts as $a) {
    $username = $a['username'];
    $email = $a['email'];

    // Check exists
    $checkSql = 'SELECT 1 FROM `NGUOIDUNG` WHERE `username` = ? OR `email` = ? LIMIT 1';
    $stmt = $conn->prepare($checkSql);
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error . PHP_EOL;
        continue;
    }
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Account exists, skipping: {$username}\n";
        $stmt->close();
        continue;
    }
    $stmt->close();

    // Insert
    $id = 'U' . strtoupper(bin2hex(random_bytes(3)));
    $hashed = password_hash($a['password'], PASSWORD_DEFAULT);
    $ho_ten = $a['full_name'];
    $dia_chi = '';
    $loai = (int)$a['loai'];

    $insertSql = 'INSERT INTO `NGUOIDUNG` (`ID`, `username`, `password`, `ho_ten`, `email`, `dia_chi`, `loai`) VALUES (?, ?, ?, ?, ?, ?, ?)';
    $ins = $conn->prepare($insertSql);
    if (!$ins) {
        echo "Prepare insert failed: " . $conn->error . PHP_EOL;
        continue;
    }
    $ins->bind_param('ssssssi', $id, $username, $hashed, $ho_ten, $email, $dia_chi, $loai);
    if ($ins->execute()) {
        echo "Inserted account: {$username} (role={$loai})\n";
    } else {
        echo "Insert failed for {$username}: " . $ins->error . PHP_EOL;
    }
    $ins->close();
}

echo "Seeding finished.\n";
