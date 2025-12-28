<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mongo-helpers.php';

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Hành động không hợp lệ'];

try {
    if ($action === 'login') {
        $response = handle_login();
    } elseif ($action === 'register') {
        $response = handle_register();
    } elseif ($action === 'logout') {
        session_destroy();
        $response = ['success' => true, 'message' => 'Đã đăng xuất'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()];
}

echo json_encode($response);
exit;

// ============= Hàm xử lý Login =============
function handle_login(): array {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
    }

    // Tìm user
    $user = find_user_by_identifier($identifier);
    if (!$user) {
        return ['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng'];
    }

    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'] ?? '') && 
        !hash_equals($user['password'] ?? '', $password)) {
        return ['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng'];
    }

    // Thiết lập session
    $sessionUser = [
        'id' => $user['ID'] ?? null,
        'username' => $user['username'] ?? '',
        'full_name' => $user['ho_ten'] ?? '',
        'email' => $user['email'] ?? '',
        'address' => $user['dia_chi'] ?? '',
        'role' => (string)($user['loai'] ?? ''),
    ];
    $_SESSION['user'] = $sessionUser;

    return ['success' => true, 'message' => 'Đăng nhập thành công'];
}

// ============= Hàm xử lý Register =============
function handle_register(): array {
    $fullName = trim($_POST['full_name'] ?? '');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate
    if ($fullName === '' || $identifier === '' || $password === '' || 
        $passwordConfirm === '' || $email === '') {
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'message' => 'Mật khẩu xác nhận không trùng khớp'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Định dạng email không hợp lệ'];
    }

    // Kiểm tra email/username tồn tại
    if (find_user_by_identifier($email) !== null) {
        return ['success' => false, 'message' => 'Email đã được sử dụng'];
    }

    if (find_user_by_identifier($identifier) !== null) {
        return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }

    // Tạo user mới
    $userData = [
        'username' => $identifier,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'ho_ten' => $fullName,
        'email' => $email,
        'dia_chi' => $address,
        'loai' => 2,
    ];

    if (insert_user($userData) !== null) {
        return ['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập'];
    }

    return ['success' => false, 'message' => 'Không thể tạo tài khoản. Vui lòng thử lại'];
}
