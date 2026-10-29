<?php
// Sertakan file koneksi database
require_once 'koneksi_db.php';

// Ambil data yang dikirim dari form
$nama = $_POST['nama'];
$email = $_POST['email'];
$telepon = $_POST['telepon'];
$alamat = $_POST['alamat'];
$tujuan = $_POST['tujuan'];
$kelas = $_POST['kelas'];
$tgl_keberangkatan = $_POST['tgl_keberangkatan'];
$jam_keberangkatan = $_POST['jam_keberangkatan'];
$tempat_duduk = $_POST['tempat_duduk'];
$harga = $_POST['harga'];

// Query untuk menyimpan data ke dalam tabel Login_Pengguna
$query_login = "INSERT INTO Login_Pengguna (Nama_Pemesan, Email) VALUES ('$nama', '$email')";
if ($conn->query($query_login) === FALSE) {
    echo "Error: " . $query_login . "<br>" . $conn->error;
}

// Ambil ID_Pengguna yang baru saja di-generate
$id_pengguna = $conn->insert_id;

// Query untuk menyimpan data ke dalam tabel Penumpang
$query_penumpang = "INSERT INTO Penumpang (No_Telepon, Alamat, ID_Pengguna) VALUES ('$telepon', '$alamat', '$id_pengguna')";
if ($conn->query($query_penumpang) === FALSE) {
    echo "Error: " . $query_penumpang . "<br>" . $conn->error;
}

// Ambil ID_Penumpang yang baru saja di-generate
$id_penumpang = $conn->insert_id;

// Query untuk menyimpan data ke dalam tabel Kereta
$query_kereta = "INSERT INTO Kereta (Tujuan, Kelas, Jam_Keberangkatan) VALUES ('$tujuan', '$kelas', '$jam_keberangkatan')";
if ($conn->query($query_kereta) === FALSE) {
    echo "Error: " . $query_kereta . "<br>" . $conn->error;
}

// Ambil ID_Kereta yang baru saja di-generate
$id_kereta = $conn->insert_id;

// Query untuk menyimpan data ke dalam tabel Tiket
$query_tiket = "INSERT INTO Tiket (ID_Pemesan, ID_Penumpang, ID_Kereta, Tgl_Keberangkatan, Tempat_Duduk, Harga_Tiket) VALUES ('$id_pengguna', '$id_penumpang', '$id_kereta', '$tgl_keberangkatan', '$tempat_duduk', '$harga')";
if ($conn->query($query_tiket) === TRUE) {
    // Jika berhasil menyimpan, redirect ke halaman karcis_tiket.html
    header("Location: karcis_tiket.html");
    exit();
} else {
    echo "Error: " . $query_tiket . "<br>" . $conn->error;
}

// Tutup koneksi database
$conn->close();
