<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/AccountRepository.php';

// Manage accounts stored inside frontend/Taikhoan.html (script#savedAccounts JSON)
// GET: return JSON array
// POST: accept 'username' and 'password' to append (if not duplicate)

$repo = new AccountRepository();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $accounts = $repo->readAccounts();
    echo json_encode(['success' => true, 'accounts' => $accounts]);
    exit;
}

// POST: append new account
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu username hoặc password']);
    exit;
}

if ($repo->addAccount($username, $password)) {
    echo json_encode(['success' => true, 'message' => 'Lưu tài khoản thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu tài khoản (có thể trùng lặp)']);
}
exit;
