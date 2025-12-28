<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_sach_list(): array {
    $sachFile = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
    if (!file_exists($sachFile)) {
        return [];
    }
    $json = file_get_contents($sachFile);
    $data = json_decode($json, true) ?? [];
    return is_array($data) ? $data : [];
}

$sach = get_sach_list();
echo json_encode(['success' => true, 'data' => $sach]);
