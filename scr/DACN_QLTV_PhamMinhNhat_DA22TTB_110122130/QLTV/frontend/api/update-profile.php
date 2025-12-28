<?php
require_once __DIR__ . '/../../backend/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';
$address = $input['address'] ?? '';
$phone = $input['phone'] ?? '';

if (empty($id) || empty($address) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

try {
    $conn = get_db_connection();
    
    // Kiểm tra xem bảng có cột số điện thoại không
    $checkPhoneColumn = $conn->query("SHOW COLUMNS FROM NGUOIDUNG LIKE 'so_dien_thoai'");
    $hasPhoneColumn = $checkPhoneColumn && $checkPhoneColumn->num_rows > 0;
    
    if (!$hasPhoneColumn) {
        // Thêm cột số điện thoại nếu chưa có
        $conn->query("ALTER TABLE NGUOIDUNG ADD COLUMN so_dien_thoai VARCHAR(20) DEFAULT ''");
    }
    
    // Cập nhật địa chỉ và số điện thoại
    $stmt = $conn->prepare("UPDATE NGUOIDUNG SET dia_chi = ?, so_dien_thoai = ? WHERE ID = ?");
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }
    
    $stmt->bind_param('sss', $address, $phone, $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không có thay đổi nào']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

