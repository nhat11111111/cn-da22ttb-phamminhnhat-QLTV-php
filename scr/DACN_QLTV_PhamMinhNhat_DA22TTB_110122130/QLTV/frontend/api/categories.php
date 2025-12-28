<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_categories_list(): array {
    $file = __DIR__ . '/../../Database/initdb.d/QLTV.DANHMUC.json';
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true) ?? [];
    return is_array($data) ? $data : [];
}

$categories = get_categories_list();

// Đảm bảo "Phát triển cá nhân" được hiển thị đúng
// (Danh mục đã được thêm vào database)

echo json_encode(['success' => true, 'data' => $categories]);

