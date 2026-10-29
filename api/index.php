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
        // Inject demo banner into HTML files at the <body> tag
        $html = file_get_contents($fullPath);
        $banner = '';
        // Only inject on login pages
        $filename = basename($fullPath);
        if (in_array($filename, ['login.html', 'login_admin.html', 'index.html'])) {
            $banner = '<div style="background:#1e3a5f;color:#fff;padding:12px 20px;text-align:center;font-size:13.5px;line-height:1.7;">'
                . '<a href="https://wahyuidaya.github.io" style="display:inline-block;margin-bottom:6px;color:#90cdf4;text-decoration:none;font-weight:600;font-size:13px;">← Kembali ke Portofolio Wahyu Idaya</a><br>';
            if ($filename === 'login.html') {
                $banner .= '<strong style="color:#fbd38d;">🔐 Demo User:</strong> Username: <strong>Demo User</strong> | Password: <strong>user123</strong>';
            } elseif ($filename === 'login_admin.html') {
                $banner .= '<strong style="color:#fbd38d;">🔐 Demo Admin:</strong> Username: <strong>Admin</strong> | Password: <strong>Admin123</strong>';
            } elseif ($filename === 'index.html') {
                $banner .= '<strong style="color:#fbd38d;">Demo App</strong> — <a href="login.html" style="color:#90cdf4;">Login User</a> | <a href="login_admin.html" style="color:#90cdf4;">Login Admin</a>';
            }
            $banner .= '</div>';
            $html = str_replace('<body>', '<body>' . $banner, $html);
        }
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
