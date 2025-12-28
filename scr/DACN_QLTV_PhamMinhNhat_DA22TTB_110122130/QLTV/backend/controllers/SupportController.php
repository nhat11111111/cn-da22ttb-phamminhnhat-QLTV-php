<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_support_list(): array {
    $file = __DIR__ . '/../../Database/initdb.d/QLTV.SUPPORT.json';
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true) ?? [];
    return is_array($data) ? $data : [];
}

$data = get_support_list();
echo json_encode(['success' => true, 'data' => $data]);
