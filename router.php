<?php

// Autoload server classes
spl_autoload_register(function ($class) {
    $prefix = 'Sakuci\\';
    $baseDir = __DIR__ . '/server/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API Endpoints
if (str_starts_with($uri, '/api/')) {
    \Sakuci\ApiHandler::handle($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    exit;
}

// Serve static files from public directory
$publicDir = __DIR__ . '/public';
$filePath = $publicDir . $uri;

if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    // Custom mime types for modern web assets
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'ttf' => 'font/ttf'
    ];

    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        readfile($filePath);
        exit;
    }

    return false; // Let PHP built-in server handle other static files
}

// Fallback to index.html for Single Page App
$indexFile = $publicDir . '/index.html';
if (file_exists($indexFile)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($indexFile);
    exit;
}

http_response_code(404);
echo "404 Not Found";
