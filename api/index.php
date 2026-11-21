<?php
/**
 * Vercel PHP Serverless Entrypoint - Kereta Api
 * Handles both .html and .php file serving
 */

$uri  = $_SERVER['REQUEST_URI'];
$path = ltrim(parse_url($uri, PHP_URL_PATH), '/');
$root = dirname(__DIR__);

// Default to index.html
if (empty($path) || $path === '/') {
    $path = 'index.html';
}

$fullPath = $root . '/' . $path;

// If directory, look for index.html then index.php
if (is_dir($fullPath)) {
    if (file_exists($fullPath . '/index.html')) {
        $fullPath = rtrim($fullPath, '/') . '/index.html';
        $path     = rtrim($path, '/')     . '/index.html';
    } elseif (file_exists($fullPath . '/index.php')) {
        $fullPath = rtrim($fullPath, '/') . '/index.php';
        $path     = rtrim($path, '/')     . '/index.php';
    }
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

if (file_exists($fullPath)) {
    if ($ext === 'php') {
        chdir(dirname($fullPath));
        require $fullPath;
    } elseif ($ext === 'html' || $ext === 'htm') {
        $html = file_get_contents($fullPath);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    } else {
        $mime = [
            'css'  => 'text/css', 'js' => 'application/javascript',
            'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
        ];
        if (isset($mime[$ext])) header('Content-Type: ' . $mime[$ext]);
        readfile($fullPath);
    }
} else {
    // Fallback to index.html
    chdir($root);
    $html = file_get_contents($root . '/index.html');
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}
