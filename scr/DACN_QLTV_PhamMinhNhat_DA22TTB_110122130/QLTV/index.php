<?php
// Root router for Docker/Apache setup
// Routes requests to frontend or backend based on URI

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// Route to frontend by default
if ($uri === '/' || $uri === '') {
    $uri = '/frontend/index.php';
}

// Handle backend API requests
if (strpos($uri, '/backend/') === 0) {
    $file = $root . $uri;
    if (file_exists($file) && is_file($file)) {
        require $file;
        exit;
    }
}

// Handle frontend requests
if (strpos($uri, '/frontend/') === 0 || strpos($uri, '/api/') === 0) {
    // If /api/, prepend /frontend
    if (strpos($uri, '/api/') === 0) {
        $uri = '/frontend' . $uri;
    }
    
    $file = $root . $uri;
    
    // If it's a static file, serve it
    if (file_exists($file) && is_file($file)) {
        return false; // Let Apache serve static files
    }
    
    // Try with .php extension for API endpoints
    if (strpos($uri, '/api/') !== false && !preg_match('/\.php$/', $uri)) {
        $phpFile = $root . $uri . '.php';
        if (file_exists($phpFile) && is_file($phpFile)) {
            require $phpFile;
            exit;
        }
    }
}

// Handle static files (images, css, js)
if (preg_match('/\.(jpg|jpeg|png|gif|css|js|ico|svg|woff|woff2|ttf)$/', $uri)) {
    $file = $root . $uri;
    if (file_exists($file) && is_file($file)) {
        return false; // Let Apache serve it
    }
}

// Default: route to frontend
$frontendRouter = $root . '/frontend/index.php';
if (file_exists($frontendRouter)) {
    require $frontendRouter;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
