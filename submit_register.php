<?php
include 'koneksi_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $kataSandi = password_hash($_POST['kataSandi'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO Login_Pengguna (Nama_Pemesan, Email, Kata_Sandi) VALUES ('$nama', '$email', '$kataSandi')";
    
    if (mysqli_query($conn, $sql)) {
        // Redirect ke login.html dengan pesan sukses
        header("Location: login.html?status=sukses");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
    
    mysqli_close($conn);
}
