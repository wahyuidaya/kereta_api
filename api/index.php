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
        $filename = basename($fullPath);
        if (in_array($filename, ['login.html', 'login_admin.html', 'index.html'])) {
            // Banner dengan relative z-index agar TIDAK tertutup fixed header/navbar
            $banner = '<div style="position:relative;z-index:1001;background:#1e3a5f;color:#fff;padding:12px 20px;text-align:center;font-size:13.5px;line-height:1.7;">'
                . '<div style="margin-bottom:6px;display:flex;justify-content:center;gap:16px;flex-wrap:wrap;">'
                . '<a href="portal.html" style="color:#6ee7b7;font-weight:700;text-decoration:none;">← Kembali ke Portal Tiket Kereta</a>'
                . '<a href="https://wahyuidaya.github.io" style="color:#90cdf4;text-decoration:none;font-weight:600;">Portofolio Wahyu Idaya ↗</a>'
                . '</div>';
            if ($filename === 'login.html') {
                $banner .= '<strong style="color:#fbd38d;">🔐 Demo User:</strong> Username: <strong>Demo User</strong> | Password: <strong>user123</strong> '
                    . '<button type="button" onclick="document.getElementById(\'nama\').value=\'Demo User\';document.getElementById(\'kataSandi\').value=\'user123\';" style="margin-left:8px;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:#fff;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:12px;">Pakai</button>';
            } elseif ($filename === 'login_admin.html') {
                $banner .= '<strong style="color:#fbd38d;">🔐 Demo Admin:</strong> Username: <strong>Admin</strong> | Password: <strong>Admin123</strong> '
                    . '<button type="button" onclick="document.getElementById(\'nama\').value=\'Admin\';document.getElementById(\'kataSandi\').value=\'Admin123\';" style="margin-left:8px;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:#fff;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:12px;">Pakai</button>';
            } elseif ($filename === 'index.html') {
                $banner .= '<strong style="color:#fbd38d;">Demo App</strong> — <a href="portal.html" style="color:#6ee7b7;font-weight:700;">Buka Halaman Portal</a> | <a href="login.html" style="color:#90cdf4;">Login User</a> | <a href="login_admin.html" style="color:#90cdf4;">Login Admin</a>';
            }
            $banner .= '</div>';
            // Tambahkan script autofill dari url demo parameter
            $banner .= '<script>window.addEventListener("DOMContentLoaded", function() {
                var p = new URLSearchParams(window.location.search);
                var d = p.get("demo");
                var u = document.getElementById("nama");
                var pw = document.getElementById("kataSandi");
                if (u && pw) {
                    if (d === "penumpang") { u.value = "Demo User"; pw.value = "user123"; }
                    else if (d === "admin") { u.value = "Admin"; pw.value = "Admin123"; }
                }
            });</script>';
            // Tambahkan style agar header di bawah banner tidak menimpa banner
            $banner .= '<style>.bilah-navigasi { position: relative !important; top: 0 !important; } main { margin-top: 10px !important; }</style>';
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
