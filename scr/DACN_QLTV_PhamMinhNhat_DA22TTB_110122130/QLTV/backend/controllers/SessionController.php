<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;

if ($user) {
    echo json_encode([
        'loggedIn' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'loggedIn' => false,
        'user' => null
    ]);
}
