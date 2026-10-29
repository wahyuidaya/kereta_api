<?php
$host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: 'localhost';
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$dbname   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'kereta_api';
$useSSL   = filter_var($_ENV['DB_SSL'] ?? getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN);

$conn = new mysqli();

if ($useSSL) {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $conn->real_connect($host, $username, $password, $dbname, 4000, NULL, MYSQLI_CLIENT_SSL);
} else {
    $conn->real_connect($host, $username, $password, $dbname);
}

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
