<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Handle POST request for submitting support tickets
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        session_start();
        
        // Get user info from session
        $user = $_SESSION['user'] ?? null;
        $userId = $user['id'] ?? 'anonymous';
        $username = $user['username'] ?? 'Anonymous User';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $details = isset($_POST['details']) ? trim($_POST['details']) : '';
        
        if (empty($title) || empty($details)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            exit;
        }
        
        // Handle screenshot upload
        $screenshotPath = null;
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../images/support/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
            $filename = 'support_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetPath)) {
                $screenshotPath = '/images/support/' . $filename;
            }
        }
        
        // Load existing support data
        $file = __DIR__ . '/../../Database/initdb.d/QLTV.SUPPORT.json';
        $supportData = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $supportData = json_decode($json, true) ?? [];
        }
        
        // Add new support request
        $newRequest = [
            'ID' => 'SUP' . strtoupper(uniqid()),
            'user_id' => $userId,
            'username' => $username,
            'title' => $title,
            'details' => $details,
            'screenshot' => $screenshotPath,
            'status' => 'pending',
            'created_at' => date('H:i:s d/m/Y'),
            'reply' => null
        ];
        
        $supportData[] = $newRequest;
        
        // Save to file
        $jsonOutput = json_encode($supportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($file, $jsonOutput) === false) {
            echo json_encode(['success' => false, 'message' => 'Không thể lưu yêu cầu']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã gửi yêu cầu hỗ trợ thành công',
            'data' => $newRequest
        ]);
        exit;
    }
    
    // Handle GET request for retrieving support tickets
    $file = __DIR__ . '/../../Database/initdb.d/QLTV.SUPPORT.json';
    if (!file_exists($file)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    $json = file_get_contents($file);
    $data = json_decode($json, true) ?? [];
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

