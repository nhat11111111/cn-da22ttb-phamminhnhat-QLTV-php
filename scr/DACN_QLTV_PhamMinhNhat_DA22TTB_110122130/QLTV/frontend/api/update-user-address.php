<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['user_id']) || !isset($data['address'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
        exit;
    }
    
    $userId = trim($data['user_id']);
    $newAddress = trim($data['address']);
    
    if (empty($userId) || empty($newAddress)) {
        echo json_encode(['success' => false, 'message' => 'ID người dùng và địa chỉ không được để trống']);
        exit;
    }
    
    // Read current users
    $file = __DIR__ . '/../../Database/initdb.d/QLTV.NGUOIDUNG.json';
    if (!file_exists($file)) {
        echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
        exit;
    }
    
    $json = file_get_contents($file);
    $users = json_decode($json, true);
    
    if (!is_array($users)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }
    
    // Find and update user
    $found = false;
    foreach ($users as &$user) {
        if ((isset($user['ID']) && $user['ID'] === $userId) || 
            (isset($user['id']) && $user['id'] === $userId)) {
            $user['dia_chi'] = $newAddress;
            $found = true;
            break;
        }
    }
    unset($user);
    
    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
        exit;
    }
    
    // Save back to file
    $jsonOutput = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($file, $jsonOutput) === false) {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu dữ liệu']);
        exit;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật địa chỉ thành công',
        'data' => ['user_id' => $userId, 'address' => $newAddress]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
