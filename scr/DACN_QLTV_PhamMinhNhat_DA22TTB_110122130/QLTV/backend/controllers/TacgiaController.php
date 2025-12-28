<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function get_tacgia_list(): array {
    $tacgiaFile = __DIR__ . '/../../Database/initdb.d/QLTV.TACGIA.json';
    if (!file_exists($tacgiaFile)) {
        return [];
    }
    $json = file_get_contents($tacgiaFile);
    $data = json_decode($json, true) ?? [];
    return is_array($data) ? $data : [];
}

$tacgia = get_tacgia_list();
echo json_encode(['success' => true, 'data' => $tacgia]);
