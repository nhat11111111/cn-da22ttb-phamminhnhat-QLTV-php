<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
try {
    $muonFile = __DIR__ . '/../../Database/initdb.d/QLTV.MUON_TRA.json';
    $booksFile = __DIR__ . '/../../Database/initdb.d/QLTV.SACH.json';
    $muon = [];
    if (file_exists($muonFile)) {
        $muon = json_decode(file_get_contents($muonFile), true) ?? [];
    }
    $books = [];
    if (file_exists($booksFile)) {
        $books = json_decode(file_get_contents($booksFile), true) ?? [];
    }

    $totalBorrowed = 0;
    $totalReturned = 0;
    $borrowedToday = 0;
    $returnedToday = 0;
    $now = date('Y-m-d');

    foreach ($muon as $m) {
        $status = isset($m['trang_thai']) ? mb_strtolower($m['trang_thai']) : '';
        if (stripos($status, 'đang mượn') !== false || stripos($status, 'dang muon') !== false) {
            $totalBorrowed++;
        }
        if (stripos($status, 'đã trả') !== false || stripos($status, 'da tra') !== false) {
            $totalReturned++;
        }
        if (isset($m['ngay_muon']) && strpos($m['ngay_muon'], $now) === 0) $borrowedToday++;
        if (isset($m['ngay_tra']) && strpos($m['ngay_tra'], $now) === 0) $returnedToday++;
    }

    // quick inventory counts
    $totalBooks = 0; $totalAvailable = 0;
    foreach ($books as $b) {
        $totalBooks++;
        if (isset($b['so_luong']) && intval($b['so_luong']) > 0) $totalAvailable++;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'totalBorrowed' => $totalBorrowed,
            'totalReturned' => $totalReturned,
            'borrowedToday' => $borrowedToday,
            'returnedToday' => $returnedToday,
            'totalBooks' => $totalBooks,
            'totalAvailable' => $totalAvailable
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
