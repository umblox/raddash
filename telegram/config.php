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


// Global configuration
define('BOT_TOKEN', '7530717438:AAxxx');
define('ADMIN_CHAT_ID', '2123457759');

// Database configuration array
define('DB_CONFIG', [
    'host' => '127.0.0.1',  // Ganti dengan host Anda
    'database' => 'radius', // Nama database Anda
    'user' => 'radius',     // Nama pengguna database Anda
    'password' => 'radius'  // Kata sandi pengguna database Anda
]);

//function getDbConnection() {
//    $conn = new mysqli(
//        DB_CONFIG['host'], 
//        DB_CONFIG['user'], 
//        DB_CONFIG['password'], 
//        DB_CONFIG['database']
//    );
//
//    if ($conn->connect_error) {
//        die("Koneksi database gagal: " . $conn->connect_error);
//    }
//    return $conn;
//}
