<?php
header('Content-Type: application/json; charset=utf-8');

// Parse input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

if (!isset($data['id_sach'])) {
    echo json_encode(['success' => false, 'message' => 'Missing id_sach']);
    exit;
}

$id_sach = (string)$data['id_sach'];

// Try to get user id from session if available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = 'U000';
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
} elseif (!empty($data['id_nguoi_dung'])) {
    $userId = (string)$data['id_nguoi_dung'];
}

$sachFile = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
$muonFile = __DIR__ . '/../../Database/initdb.d/QLTV.MUON_TRA.json';

if (!file_exists($sachFile)) {
    echo json_encode(['success' => false, 'message' => 'Sách database không tồn tại']);
    exit;
}

// Load books
$sachJson = file_get_contents($sachFile);
$sachArr = json_decode($sachJson, true);
if (!is_array($sachArr)) $sachArr = [];

$found = false;
for ($i = 0; $i < count($sachArr); $i++) {
    $s = $sachArr[$i];
    if ((string)($s['ID'] ?? '') === $id_sach) {
        $found = true;
        $so_luong = isset($s['so_luong']) ? (int)$s['so_luong'] : 0;
        if ($so_luong <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sách hiện đã hết, không thể mượn']);
            exit;
        }

        // Decrement
        $sachArr[$i]['so_luong'] = $so_luong - 1;
        // Update trạng thái
        $sachArr[$i]['trang_thai'] = ($sachArr[$i]['so_luong'] <= 0) ? 'Đang mượn' : 'Có sẵn';
        $bookTitle = $sachArr[$i]['ten_sach'] ?? $sachArr[$i]['ten'] ?? '';
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sách với mã đã cho']);
    exit;
}

// Save updated books
$ok = file_put_contents($sachFile, json_encode($sachArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($ok === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật file sách']);
    exit;
}

// Load muon_tra
$muonArr = [];
if (file_exists($muonFile)) {
    $mjson = file_get_contents($muonFile);
    $muonArr = json_decode($mjson, true);
    if (!is_array($muonArr)) $muonArr = [];
}

// Generate next IDs
$maxMt = 0; $maxPm = 0;
foreach ($muonArr as $m) {
    if (!empty($m['ID_muon_tra'])) {
        if (preg_match('/(\d+)/', $m['ID_muon_tra'], $mt)) {
            $num = (int)$mt[1]; if ($num > $maxMt) $maxMt = $num;
        }
    }
    if (!empty($m['ID_phieu'])) {
        if (preg_match('/(\d+)/', $m['ID_phieu'], $pm)) {
            $num2 = (int)$pm[1]; if ($num2 > $maxPm) $maxPm = $num2;
        }
    }
}

$newMt = 'MT' . str_pad((string)($maxMt + 1), 3, '0', STR_PAD_LEFT);
$newPm = 'PM' . str_pad((string)($maxPm + 1), 3, '0', STR_PAD_LEFT);

$today = date('Y-m-d');
$due = date('Y-m-d', strtotime('+14 days'));

$newEntry = [
    'ID_muon_tra' => $newMt,
    'ID_phieu' => $newPm,
    'ID_nguoi_dung' => $userId,
    'ID_sach' => $id_sach,
    'ten_sach' => $bookTitle,
    'ngay_muon' => $today,
    'ngay_tra_du_kien' => $due,
    'trang_thai' => 'Đang mượn',
    '__v' => 0
];

$muonArr[] = $newEntry;

$ok2 = file_put_contents($muonFile, json_encode($muonArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($ok2 === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật file mượn trả (muon_tra)']);
    exit;
}

// Response
echo json_encode([
    'success' => true,
    'message' => 'Mượn sách thành công',
    'data' => [
        'muon' => $newEntry,
        'so_luong_con_lai' => $sachArr[$i]['so_luong']
    ]
], JSON_UNESCAPED_UNICODE);
exit;

?>