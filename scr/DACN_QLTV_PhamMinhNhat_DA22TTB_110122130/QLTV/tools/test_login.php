<?php
// Test login
$_POST = [
    'action' => 'login',
    'identifier' => 'nhat',
    'password' => '123456'
];

require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/mongo-helpers.php';

echo "Testing Login...\n";
echo "===============\n\n";

// Simulate the auth API logic
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Hành động không hợp lệ'];

try {
    if ($action === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $response = ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        } else {
            // Tìm user
            $user = find_user_by_identifier($identifier);
            
            if (!$user) {
                echo "❌ User not found\n";
                $response = ['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng'];
            } else {
                echo "✓ User found: " . $user['username'] . "\n";
                echo "  Full Name: " . $user['ho_ten'] . "\n";
                echo "  Email: " . $user['email'] . "\n";
                
                // Kiểm tra mật khẩu
                $storedPassword = $user['password'] ?? '';
                $passwordMatch = password_verify($password, $storedPassword) || 
                                hash_equals($storedPassword, $password);
                
                if (!$passwordMatch) {
                    echo "❌ Password incorrect\n";
                    $response = ['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng'];
                } else {
                    echo "✓ Password correct!\n";
                    
                    // Thiết lập session
                    $sessionUser = [
                        'id' => $user['ID'] ?? null,
                        'username' => $user['username'] ?? '',
                        'full_name' => $user['ho_ten'] ?? '',
                        'email' => $user['email'] ?? '',
                        'address' => $user['dia_chi'] ?? '',
                        'role' => (string)($user['loai'] ?? ''),
                    ];
                    
                    echo "✓ Session user set\n";
                    echo "\nFinal Response:\n";
                    $response = ['success' => true, 'message' => 'Đăng nhập thành công'];
                }
            }
        }
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()];
}

echo "\n" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
