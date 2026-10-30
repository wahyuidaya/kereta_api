<?php
include 'koneksi_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama'] ?? '');
    $kataSandi = trim($_POST['kataSandi'] ?? '');

    // 1. Dukungan instan untuk Demo User
    if (strtolower($nama) === 'demo user' && $kataSandi === 'user123') {
        session_start();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['nama_pemesan'] = 'Demo User';
        header("Location: index2.html");
        exit;
    }

    // 2. Cek ke database
    $stmt = $conn->prepare("SELECT * FROM Login_Pengguna WHERE Nama_Pemesan = ?");
    if ($stmt) {
        $stmt->bind_param("s", $nama);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($kataSandi, $row['Kata_Sandi']) || $kataSandi === $row['Kata_Sandi']) {
                session_start();
                $_SESSION['user_logged_in'] = true;
                $_SESSION['nama_pemesan'] = $row['Nama_Pemesan'];
                header("Location: index2.html");
                exit;
            }
        }
        $stmt->close();
    }
    
    // Fallback jika tidak cocok
    header("Location: login.html?error=1");
    exit;
}
