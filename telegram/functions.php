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

// functions.php

require_once 'config.php'; // Pastikan config.php hanya di-include sekali

// Fungsi untuk menghubungkan ke database
if (!function_exists('getDbConnection')) {
function getDbConnection() {
    $conn = new mysqli(
        DB_CONFIG['host'], 
        DB_CONFIG['user'], 
        DB_CONFIG['password'], 
        DB_CONFIG['database']
    );

    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    return $conn;
}

// Fungsi untuk notifikasi ke admin
function notifyAdmins($message, $reply_markup = null) {
    $admins = [];
    $conn = getDbConnection();
    $result = $conn->query("SELECT telegram_id FROM users WHERE is_admin = 1");

    while ($row = $result->fetch_assoc()) {
        $admins[] = $row['telegram_id'];
    }

    foreach ($admins as $admin_id) {
        sendMessage($admin_id, $message, $reply_markup);
    }

    $conn->close();
}

// Fungsi untuk mendapatkan prefix voucher berdasarkan nama plan
function getVoucherPrefix($planName) {
    // Array prefix voucher berdasarkan nama plan
    $VOUCHER_PREFIX = [
        '1Hari' => '3k',
        '5k' => '5k',
        '7Hari' => '15k',
        '30Hari' => '60k'
    ];

    // Jika planName ditemukan, return prefix-nya; jika tidak, return default prefix
    return isset($VOUCHER_PREFIX[$planName]) ? $VOUCHER_PREFIX[$planName] : 'edit_di_config/prefix';
}

// Fungsi untuk mengirim pesan ke Telegram
function sendMessage($chat_id, $text, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    curl_close($ch);
}

// Fungsi untuk mengedit pesan di Telegram
function editMessage($chat_id, $message_id, $text, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    curl_close($ch);
}

// Fungsi untuk mengambil semua admin
function getAdminIds() {
    $conn = getDbConnection();
    $admins = [];

    $result = $conn->query("SELECT telegram_id FROM users WHERE is_admin = 1");
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row['telegram_id'];
    }

    $conn->close();
    return $admins;
}
}
?>
