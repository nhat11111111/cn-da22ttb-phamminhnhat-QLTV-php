<?php
/**
 * Frontend Auth API - Proxy to Backend Auth API
 * This file handles login/register/logout requests
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get action and forward to backend
$action = $_POST['action'] ?? '';

if ($action === 'logout') {
    // Handle logout locally
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
    exit;
}

// Forward login/register to backend API
require_once __DIR__ . '/../../backend/includes/db.php';
require_once __DIR__ . '/../../backend/includes/mongo-helpers.php';

// Handle login
if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        exit;
    }

    // Tìm user
    $user = find_user_by_identifier($identifier);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng']);
        exit;
    }

    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'] ?? '') && 
        !hash_equals($user['password'] ?? '', $password)) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng']);
        exit;
    }

    // Thiết lập session
    $_SESSION['user'] = [
        'id' => $user['ID'] ?? null,
        'username' => $user['username'] ?? '',
        'full_name' => $user['ho_ten'] ?? '',
        'email' => $user['email'] ?? '',
        'address' => $user['dia_chi'] ?? '',
        'role' => (string)($user['loai'] ?? ''),
    ];

    echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công']);
    exit;
}

// Handle register
if ($action === 'register') {
    $fullName = trim($_POST['full_name'] ?? '');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate
    if ($fullName === '' || $identifier === '' || $password === '' || 
        $passwordConfirm === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        exit;
    }

    if ($password !== $passwordConfirm) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không trùng khớp']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng email không hợp lệ']);
        exit;
    }

    // Kiểm tra email/username tồn tại
    if (find_user_by_identifier($email) !== null) {
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        exit;
    }

    if (find_user_by_identifier($identifier) !== null) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
        exit;
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
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Không thể tạo tài khoản. Vui lòng thử lại']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);

