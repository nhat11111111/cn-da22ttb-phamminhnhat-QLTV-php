<?php
// Lấy danh sách tất cả tài khoản khả dụng
require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/mongo-helpers.php';

echo "=== DANH SÁCH TÀI KHOẢN CÓ THỂ ĐĂNG NHẬP ===\n\n";

$users = get_all_users();

if (empty($users)) {
    echo "❌ Không tìm thấy tài khoản nào\n";
    exit;
}

echo "Tổng số tài khoản: " . count($users) . "\n\n";
echo str_repeat("=", 100) . "\n";

foreach ($users as $i => $user) {
    echo "\n[Tài khoản " . ($i + 1) . "]\n";
    echo "  Username: " . ($user['username'] ?? 'N/A') . "\n";
    echo "  Password: 123456\n";
    echo "  Full Name: " . ($user['ho_ten'] ?? 'N/A') . "\n";
    echo "  Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "  Role: ";
    
    $role = $user['loai'] ?? 0;
    if ($role == 1) echo "Admin";
    else if ($role == 2) echo "User (Người mượn)";
    else if ($role == 3) echo "Librarian (Thư thủ)";
    else echo $role;
    
    echo "\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "\n✓ Tất cả tài khoản trên đều có password: 123456\n";
echo "✓ Bạn có thể dùng bất kỳ username nào để đăng nhập\n";
echo "\nVí dụ:\n";
echo "  - Username: nhat, Password: 123456\n";
echo "  - Username: hoanglong, Password: 123456\n";
echo "  - Username: dangkhoa, Password: 123456\n";
