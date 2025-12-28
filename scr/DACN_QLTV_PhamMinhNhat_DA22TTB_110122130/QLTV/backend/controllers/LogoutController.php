<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Đăng xuất thành công'
]);
