<?php
session_start();

// Nama pengguna dan kata sandi default untuk admin
$admin_username = "Admin";
$admin_password = "Admin123";

// Mendapatkan data dari form login
$nama = $_POST['nama'];
$kataSandi = $_POST['kataSandi'];

// Memeriksa kredensial
if ($nama === $admin_username && $kataSandi === $admin_password) {
    // Kredensial benar, login berhasil
    $_SESSION['admin_logged_in'] = true;
    header("Location: index2.html");
    exit;
} else {
    // Kredensial salah, login gagal
    $_SESSION['error_message'] = "Nama pengguna atau kata sandi salah";
    header("Location: login_admin.html");
    exit;
}
