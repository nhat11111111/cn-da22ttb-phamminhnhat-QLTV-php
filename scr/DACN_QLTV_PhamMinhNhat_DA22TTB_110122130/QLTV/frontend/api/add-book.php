<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Chỉ chấp nhận POST');
    }
    
    // Xử lý FormData upload
    $id = trim($_POST['ID'] ?? '');
    $tenSach = trim($_POST['ten_sach'] ?? '');
    $tacGia = trim($_POST['tac_gia'] ?? '');
    $namXuatBan = isset($_POST['nam_xuat_ban']) && $_POST['nam_xuat_ban'] !== '' ? intval($_POST['nam_xuat_ban']) : null;
    $ngonNgu = trim($_POST['ngon_ngu'] ?? '');
    $danhMuc = trim($_POST['danh_muc'] ?? '');
    $giaSach = trim($_POST['gia_sach'] ?? '');
    $soLuong = isset($_POST['so_luong']) ? intval($_POST['so_luong']) : 0;
    
    if (empty($id) || empty($tenSach) || empty($tacGia)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }
    
    // Xử lý upload ảnh
    $imagePath = '';
    if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['book_image']['name']);
        $extension = strtolower($fileInfo['extension'] ?? 'jpg');
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!in_array($extension, $allowedExts)) {
            throw new Exception('Định dạng ảnh không được hỗ trợ');
        }
        
        // Tạo tên file từ tên sách
        $sanitizedName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($tenSach));
        $sanitizedName = trim($sanitizedName, '-');
        $newFileName = $sanitizedName . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['book_image']['tmp_name'], $targetPath)) {
            $imagePath = '/images/' . $newFileName;
        }
    }
    
    // Đọc file JSON hiện tại
    $filePath = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
    if (!file_exists($filePath)) {
        throw new Exception('Không tìm thấy file dữ liệu');
    }
    
    $content = file_get_contents($filePath);
    $books = json_decode($content, true);
    if (!is_array($books)) {
        $books = [];
    }
    
    // Kiểm tra mã sách trùng
    foreach ($books as $book) {
        if (isset($book['ID']) && $book['ID'] === $id) {
            throw new Exception('Mã sách đã tồn tại');
        }
    }
    
    // Tạo đối tượng sách mới
    $newBook = [
        'ID' => $id,
        'ten_sach' => $tenSach,
        'tac_gia' => $tacGia,
        'nam_xuat_ban' => $namXuatBan,
        'ngon_ngu' => $ngonNgu ?: 'Tiếng Việt',
        'trang_thai' => $soLuong > 0 ? 'Có sẵn' : 'Hết sách',
        'vi_tri' => $giaSach ? ('Giá: ' . $giaSach) : '',
        'id_nguoi_them' => 'ID00123',
        'ngay_them' => date('Y-m-d'),
        'anh_bia' => $imagePath,
        'danh_muc' => $danhMuc ?: 'Chưa phân loại',
        'ID_danh_muc' => 'DM000',
        'so_luong' => $soLuong
    ];
    
    // Thêm sách mới vào mảng
    $books[] = $newBook;
    
    // Lưu lại file
    $json = json_encode($books, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (file_put_contents($filePath, $json) === false) {
        throw new Exception('Không thể lưu dữ liệu');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Thêm sách thành công',
        'data' => $newBook
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
