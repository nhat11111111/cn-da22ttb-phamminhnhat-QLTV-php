<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Chỉ chấp nhận POST');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception('Dữ liệu không hợp lệ');

    $id = trim($input['ID'] ?? '');
    if (!$id) throw new Exception('ID sách không hợp lệ');

    $filePath = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
    if (!file_exists($filePath)) throw new Exception('Không tìm thấy file dữ liệu');

    $content = file_get_contents($filePath);
    $books = json_decode($content, true);
    if (!is_array($books)) $books = [];

    $found = false;
    foreach ($books as &$book) {
        if (isset($book['ID']) && $book['ID'] === $id) {
            // Update fields if provided
            if (isset($input['ten_sach'])) $book['ten_sach'] = $input['ten_sach'];
            if (isset($input['tac_gia'])) $book['tac_gia'] = $input['tac_gia'];
            if (isset($input['nam_xuat_ban'])) $book['nam_xuat_ban'] = $input['nam_xuat_ban'];
            if (isset($input['ngon_ngu'])) $book['ngon_ngu'] = $input['ngon_ngu'];
            if (isset($input['danh_muc'])) $book['danh_muc'] = $input['danh_muc'];
            if (isset($input['gia_sach'])) $book['vi_tri'] = $input['gia_sach'] ? ('Giá: ' . $input['gia_sach']) : '';
            if (isset($input['so_luong'])) {
                $book['so_luong'] = intval($input['so_luong']);
                $book['trang_thai'] = $book['so_luong'] > 0 ? 'Có sẵn' : 'Hết sách';
            }
            $found = true;
            break;
        }
    }
    unset($book);

    if (!$found) throw new Exception('Không tìm thấy sách với ID này');

    $json = json_encode($books, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (file_put_contents($filePath, $json) === false) throw new Exception('Không thể lưu dữ liệu');

    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
