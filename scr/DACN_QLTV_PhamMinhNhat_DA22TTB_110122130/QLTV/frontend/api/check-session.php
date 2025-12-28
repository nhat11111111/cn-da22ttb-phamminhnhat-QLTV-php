<?php
/**
 * Check Session API
 * Returns current user session status
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    echo json_encode([
        'success' => true,
        'loggedIn' => true,
        'user' => $_SESSION['user'],
    ]);
} else {
    echo json_encode([
        'success' => false,
        'loggedIn' => false,
        'message' => 'Chưa đăng nhập',
    ]);
}

