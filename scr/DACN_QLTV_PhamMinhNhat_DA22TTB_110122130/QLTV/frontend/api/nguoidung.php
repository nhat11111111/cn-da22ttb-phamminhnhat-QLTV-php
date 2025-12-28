<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_nguoidung_list(string $loai = ''): array {
    $file = __DIR__ . '/../../Database/initdb.d/QLTV.NGUOIDUNG.json';
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true) ?? [];
    if (!is_array($data)) return [];
    
    // Filter by loai if specified
    if ($loai !== '') {
        return array_filter($data, function($user) use ($loai) {
            return isset($user['loai']) && (string)$user['loai'] === $loai;
        });
    }
    
    return $data;
}

// Get loai from query parameter
$loai = isset($_GET['loai']) ? (string)$_GET['loai'] : '';
$data = get_nguoidung_list($loai);

echo json_encode(['success' => true, 'data' => array_values($data)]);
