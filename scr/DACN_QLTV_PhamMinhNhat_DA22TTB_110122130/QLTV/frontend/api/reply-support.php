<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();

// Chỉ admin và thủ thư mới được phản hồi
$currentUser = $_SESSION['user'] ?? null;
$userRole = $currentUser['role'] ?? $currentUser['loai'] ?? '';

if ($userRole !== '1' && $userRole !== '2') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$supportId = $input['support_id'] ?? '';
$reply = $input['reply'] ?? '';
$username = $input['username'] ?? '';

if (!$supportId || !$reply || !$username) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Lưu thông báo cho người dùng
$notificationsFile = __DIR__ . '/../../Database/notifications.json';
$notifications = [];
if (file_exists($notificationsFile)) {
    $json = file_get_contents($notificationsFile);
    $notifications = json_decode($json, true) ?? [];
}

$notifications[] = [
    'username' => $username,
    'support_id' => $supportId,
    'reply' => $reply,
    'created_at' => date('Y-m-d H:i:s'),
    'replied_by' => $currentUser['username'] ?? $currentUser['email'] ?? 'Admin'
];

file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true, 'message' => 'Đã gửi phản hồi']);

