<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_danhmuc_list(): array {
    $danhmucFile = __DIR__ . '/../../Database/initdb.d/QLTV.DANHMUC.json';
    if (!file_exists($danhmucFile)) {
        return [];
    }
    $json = file_get_contents($danhmucFile);
    $data = json_decode($json, true) ?? [];
    return is_array($data) ? $data : [];
}

$danhmuc = get_danhmuc_list();
echo json_encode(['success' => true, 'data' => $danhmuc]);
