<?php
/*
*******************************************************************************************************************
* Warning!!!, Tidak untuk diperjual belikan!, Cukup pakai sendiri atau share kepada orang lain secara gratis
*******************************************************************************************************************
* Dibuat oleh Ikromul Umam https://t.me/arnetadotid
*******************************************************************************************************************
* © 2024 Arneta.ID By https://fb.me/umblox
*******************************************************************************************************************
*/

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
