<?php
/**
 * Account Management API
 * Returns list of saved/test accounts
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle saving new account locally (in browser storage, not server-side)
    // This is just for suggestion purposes
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Tài khoản đã được lưu']);
    exit;
}

// GET: Return list of test accounts for login suggestions
$testAccounts = [
    ['username' => 'hoanglong', 'email' => 'hoanglong@example.com', 'full_name' => 'Hoang Long'],
    ['username' => 'nhat', 'email' => '1@gmail.com', 'full_name' => 'Nhat User'],
    ['username' => 'nhatthuthu', 'email' => 'nhatthuthu@example.com', 'full_name' => 'Nhat Thu Thu'],
];

echo json_encode([
    'success' => true,
    'accounts' => $testAccounts
]);

