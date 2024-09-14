<?php
function getDbConnection() {
    $host = '127.0.0.1';  // Ganti dengan host Anda
    $db = 'radius'; // Nama database Anda
    $user = 'radius';        // Nama pengguna database Anda
    $pass = 'radius';        // Kata sandi pengguna database Anda

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    return $conn;
}
?>
