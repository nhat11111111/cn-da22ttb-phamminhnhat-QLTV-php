<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();

// Lấy thông tin người dùng từ session
$currentUser = $_SESSION['user'] ?? null;
$username = $currentUser['username'] ?? $currentUser['email'] ?? null;

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// File lưu thông báo (phản hồi từ admin)
$notificationsFile = __DIR__ . '/../../Database/notifications.json';

// Đọc thông báo
$notifications = [];
if (file_exists($notificationsFile)) {
    $json = file_get_contents($notificationsFile);
    $notifications = json_decode($json, true) ?? [];
}

// Lọc thông báo cho user hiện tại
$userNotifications = array_filter($notifications, function($notif) use ($username) {
    return isset($notif['username']) && $notif['username'] === $username;
});

// Sắp xếp theo thời gian mới nhất
usort($userNotifications, function($a, $b) {
    return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
});

echo json_encode(['success' => true, 'data' => array_values($userNotifications)]);

