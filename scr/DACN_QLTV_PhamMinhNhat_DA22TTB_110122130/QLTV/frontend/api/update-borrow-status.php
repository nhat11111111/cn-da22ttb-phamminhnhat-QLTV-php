<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['ID_muon_tra']) || empty($data['ID_muon_tra'])) {
    echo json_encode(['success' => false, 'message' => 'ID_muon_tra is required']);
    exit;
}

if (!isset($data['trang_thai']) || empty($data['trang_thai'])) {
    echo json_encode(['success' => false, 'message' => 'trang_thai is required']);
    exit;
}

$ID_muon_tra = $data['ID_muon_tra'];
$trang_thai = $data['trang_thai'];

// Validate status
$valid_statuses = ['Đang mượn', 'Chờ xác nhận trả', 'Đã trả'];
if (!in_array($trang_thai, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Load MUON_TRA.json
$file = __DIR__ . '/../../Database/initdb.d/QLTV.MUON_TRA.json';
if (!file_exists($file)) {
    echo json_encode(['success' => false, 'message' => 'Database file not found']);
    exit;
}

$json = file_get_contents($file);
$records = json_decode($json, true);

if (!is_array($records)) {
    echo json_encode(['success' => false, 'message' => 'Invalid database format']);
    exit;
}

// Find the record and update it
$found = false;
foreach ($records as &$record) {
    if (isset($record['ID_muon_tra']) && $record['ID_muon_tra'] === $ID_muon_tra) {
        $record['trang_thai'] = $trang_thai;
        
        // If status is "Đã trả", set return date
        if ($trang_thai === 'Đã trả') {
            $record['ngay_tra'] = date('Y-m-d');
        }
        
        $found = true;
        break;
    }
}
unset($record); // Break reference

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

// Save back to file
$updated = file_put_contents($file, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($updated === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save changes']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Status updated successfully',
    'data' => $records
]);
