<?php
// Frontend router - handles requests to frontend API and views
// This router is invoked by the root index.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// Check if requesting a static file first
if (file_exists($root . $uri) && is_file($root . $uri)) {
    return false; // Let PHP built-in server serve it
}

// Handle API requests in frontend/api
if (strpos($uri, '/api/') === 0) {
    // Try with .php extension if not already present
    if (!preg_match('/\.php$/', $uri)) {
        $apiFile = $root . $uri . '.php';
        if (file_exists($apiFile) && is_file($apiFile)) {
            require $apiFile;
            exit;
        }
    }
    
    // Try direct file
    $apiFile = $root . $uri;
    if (file_exists($apiFile) && is_file($apiFile)) {
        require $apiFile;
        exit;
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found: ' . $uri]);
    exit;
}

// Handle view requests
if (strpos($uri, '/views/') === 0 || strpos($uri, '.html') !== false) {
    $viewFile = $root . $uri;
    if (file_exists($viewFile) && is_file($viewFile)) {
        readfile($viewFile);
        exit;
    }
}

// Default: serve login view
$loginFile = $root . '/views/Dangnhap.html';
if (file_exists($loginFile)) {
    readfile($loginFile);
    exit;
}

// Fallback
http_response_code(404);
echo 'Frontend: Resource not found';
