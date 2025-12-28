<?php
header('Content-Type: application/json; charset=utf-8');

// Tắt hiển thị lỗi
error_reporting(0);
ini_set('display_errors', 0);

// Hàm chuẩn hóa chuỗi để tìm kiếm (chuyển về chữ thường, bỏ dấu)
function normalizeString($str) {
    if (empty($str)) return '';
    
    // Chuyển về chữ thường
    $str = mb_strtolower(trim($str), 'UTF-8');
    
    // Bỏ dấu tiếng Việt
    $str = str_replace(
        ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ'],
        'a', $str
    );
    $str = str_replace(
        ['è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ'],
        'e', $str
    );
    $str = str_replace(['ì', 'í', 'ị', 'ỉ', 'ĩ'], 'i', $str);
    $str = str_replace(
        ['ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ'],
        'o', $str
    );
    $str = str_replace(
        ['ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ'],
        'u', $str
    );
    $str = str_replace(['ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ'], 'y', $str);
    $str = str_replace(['đ'], 'd', $str);
    
    return $str;
}

// Đọc danh sách sách từ file JSON
function loadBooks() {
    $filePath = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
    
    if (!file_exists($filePath)) {
        return [];
    }
    
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return [];
    }
    
    $data = @json_decode($content, true);
    if (!is_array($data)) {
        return [];
    }
    
    return $data;
}

try {
    // Lấy query từ GET parameter
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    
    // Load tất cả sách
    $allBooks = loadBooks();
    
    // Lọc theo thể loại nếu có
    if (!empty($category) && $category !== 'all') {
        $allBooks = array_filter($allBooks, function($book) use ($category) {
            return isset($book['danh_muc']) && $book['danh_muc'] === $category;
        });
    }
    
    // Tìm kiếm nếu có query
    if (!empty($query)) {
        $queryNormalized = normalizeString($query);
        $results = [];
        
        foreach ($allBooks as $book) {
            $tenSach = normalizeString($book['ten_sach'] ?? '');
            $tacGia = normalizeString($book['tac_gia'] ?? '');
            
            // Tìm trong tên sách hoặc tác giả
            if (strpos($tenSach, $queryNormalized) !== false || 
                strpos($tacGia, $queryNormalized) !== false) {
                $results[] = $book;
            }
        }
        
        $allBooks = $results;
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'data' => array_values($allBooks)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
