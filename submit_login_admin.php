<?php
session_start();

$admin_username = "Admin";
$admin_password = "Admin123";

$nama = trim($_POST['nama'] ?? '');
$kataSandi = trim($_POST['kataSandi'] ?? '');

if (strcasecmp($nama, $admin_username) === 0 && $kataSandi === $admin_password) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'Admin';
    header("Location: dashboard_admin.php");
    exit;
} else {
    $_SESSION['error_message'] = "Nama pengguna atau kata sandi salah";
    header("Location: login_admin.html?error=1");
    exit;
}
