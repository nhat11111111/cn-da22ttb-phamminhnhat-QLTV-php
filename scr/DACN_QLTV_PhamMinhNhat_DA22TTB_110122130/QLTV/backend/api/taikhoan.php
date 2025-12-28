<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Manage accounts stored inside frontend/Taikhoan.html (script#savedAccounts JSON)
// GET: return JSON array
// POST: accept 'username' and 'password' to append (if not duplicate)

$file = realpath(__DIR__ . '/../../frontend/Taikhoan.html');
if (!$file) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Taikhoan.html không tồn tại']);
    exit;
}

$html = @file_get_contents($file);
if ($html === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể đọc Taikhoan.html']);
    exit;
}

// Extract JSON inside <script id="savedAccounts" type="application/json">...
$matches = [];
if (!preg_match('#<script[^>]*id=["\']savedAccounts["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
    // No script tag found — initialize
    $accounts = [];
} else {
    $json = trim($matches[1]);
    $accounts = json_decode($json, true);
    if (!is_array($accounts)) $accounts = [];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    echo json_encode(['success' => true, 'accounts' => $accounts]);
    exit;
}

// POST: append new account
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu username hoặc password']);
    exit;
}

// avoid duplicates by username
foreach ($accounts as $a) {
    if (isset($a['username']) && $a['username'] === $username) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản đã tồn tại']);
        exit;
    }
}

$accounts[] = ['username' => $username, 'password' => $password];

// Replace JSON in the html
$newJson = json_encode($accounts, JSON_UNESCAPED_UNICODE);
if (preg_match('#(<script[^>]*id=["\']savedAccounts["\'][^>]*>)(.*?)(</script>)#is', $html)) {
    $newHtml = preg_replace('#(<script[^>]*id=["\']savedAccounts["\'][^>]*>)(.*?)(</script>)#is', '$1' . $newJson . '$3', $html, 1);
} else {
    // append script before closing body
    $newHtml = preg_replace('#</body>#i', "<script id=\"savedAccounts\" type=\"application/json\">" . $newJson . "</script>\n</body>", $html, 1);
}

if (@file_put_contents($file, $newHtml) === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu Taikhoan.html']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Lưu tài khoản thành công']);
exit;
