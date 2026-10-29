<?php
include 'koneksi_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $kataSandi = $_POST['kataSandi'];

    $sql = "SELECT * FROM Login_Pengguna WHERE Nama_Pemesan = '$nama'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($kataSandi, $row['Kata_Sandi'])) {
            header("Location: index2.html");
        } else {
            header("Location: login.html");
        }
    } else {
        header("Location: login.html");
    }
    
    mysqli_close($conn);
}