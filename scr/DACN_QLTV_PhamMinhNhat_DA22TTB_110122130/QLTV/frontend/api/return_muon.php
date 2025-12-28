<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

if (!isset($data['id_sach'])) {
    echo json_encode(['success' => false, 'message' => 'Missing id_sach']);
    exit;
}

$id_sach = (string)$data['id_sach'];
$dbFile = __DIR__ . '/../../Database/initdb.d/QLTV.MUON_TRA.json';

if (!file_exists($dbFile)) {
    echo json_encode(['success' => false, 'message' => 'Database file not found']);
    exit;
}

$json = file_get_contents($dbFile);
$arr = json_decode($json, true);
if (!is_array($arr)) $arr = [];

$updated = false;
$now = date('Y-m-d');

// Find first matching entry for this book that is not already returned
foreach ($arr as &$entry) {
    if ((isset($entry['ID_sach']) && (string)$entry['ID_sach'] === $id_sach)
        || (isset($entry['ID_sach']) && (string)$entry['ID_sach'] === $id_sach)
        || (isset($entry['ID_sach']) && (string)$entry['ID_sach'] === $id_sach)) {

        // if already returned skip
        if (isset($entry['trang_thai']) && stripos($entry['trang_thai'], 'trả') !== false) {
            continue;
        }

        $entry['trang_thai'] = 'Đã trả';
        $entry['ngay_tra'] = $now;
        $updated = true;
        // Also increment book quantity in SACH.json
        $sachFile = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
        if (file_exists($sachFile)) {
            $sjson = file_get_contents($sachFile);
            $sarr = json_decode($sjson, true);
            if (is_array($sarr)) {
                foreach ($sarr as &$sb) {
                    if (isset($sb['ID']) && (string)$sb['ID'] === $id_sach) {
                        $sb['so_luong'] = (isset($sb['so_luong']) ? intval($sb['so_luong']) : 0) + 1;
                        $sb['trang_thai'] = $sb['so_luong'] > 0 ? 'Có sẵn' : 'Hết sách';
                        break;
                    }
                }
                unset($sb);
                @file_put_contents($sachFile, json_encode($sarr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        break;
    }
}

if ($updated) {
    $ok = file_put_contents($dbFile, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($ok === false) {
        echo json_encode(['success' => false, 'message' => 'Unable to write database']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Đã ghi nhận trả sách']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bản ghi mượn phù hợp']);
    exit;
}

?>
