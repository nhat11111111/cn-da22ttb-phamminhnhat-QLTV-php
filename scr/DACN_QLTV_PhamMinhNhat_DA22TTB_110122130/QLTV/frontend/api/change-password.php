<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user']['id'] ?? '';
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập lại']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$oldPassword = trim($payload['old_password'] ?? '');
$newPassword = $payload['new_password'] ?? '';
$confirmPassword = $payload['confirm_password'] ?? '';

if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới không khớp']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới tối thiểu 6 ký tự']);
    exit;
}

require_once __DIR__ . '/../../backend/includes/db.php';

try {
    $conn = get_db_connection();
    $dbType = get_db_type();
    $hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);

    // MySQL / MariaDB
    if ($dbType === 'mysql' && $conn instanceof mysqli) {
        $stmt = $conn->prepare('SELECT password FROM NGUOIDUNG WHERE ID = ? LIMIT 1');
        if (!$stmt) {
            throw new Exception('Không thể đọc tài khoản');
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userRow = $result->fetch_assoc();
        $stmt->close();

        if (!$userRow) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
            exit;
        }

        $stored = $userRow['password'] ?? '';
        if (!password_verify($oldPassword, $stored) && !hash_equals((string)$stored, $oldPassword)) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không đúng']);
            exit;
        }

        $update = $conn->prepare('UPDATE NGUOIDUNG SET password = ? WHERE ID = ?');
        if (!$update) {
            throw new Exception('Không thể cập nhật mật khẩu');
        }
        $update->bind_param('ss', $hashedNew, $userId);
        $update->execute();
        $affected = $update->affected_rows;
        $update->close();

        echo json_encode([
            'success' => $affected >= 0,
            'message' => $affected > 0 ? 'Đổi mật khẩu thành công' : 'Mật khẩu đã được giữ nguyên'
        ]);
        exit;
    }

    // MongoDB
    if ($dbType === 'mongodb' && class_exists('MongoDB\\Driver\\Manager')) {
        global $MONGO_DB_NAME;
        /** @var MongoDB\\Driver\\Manager $conn */
        $database = $conn->selectDatabase($MONGO_DB_NAME);
        $collection = $database->selectCollection('NGUOIDUNG');
        $current = $collection->findOne(['ID' => $userId]);

        if (!$current) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
            exit;
        }

        $stored = $current['password'] ?? '';
        if (!password_verify($oldPassword, (string)$stored) && !hash_equals((string)$stored, $oldPassword)) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không đúng']);
            exit;
        }

        $collection->updateOne(['ID' => $userId], ['$set' => ['password' => $hashedNew]]);
        echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
        exit;
    }

    // Fallback (JSON) chưa hỗ trợ ghi dữ liệu an toàn
    echo json_encode(['success' => false, 'message' => 'Hiện chưa hỗ trợ đổi mật khẩu ở chế độ offline']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

