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
        static $conn; // Menggunakan static untuk menghindari koneksi berulang

        if (!isset($conn)) {
            $conn = new mysqli(
                DB_CONFIG['host'], 
                DB_CONFIG['user'], 
                DB_CONFIG['password'], 
                DB_CONFIG['database']
            );

            if ($conn->connect_error) {
                die("Koneksi database gagal: " . $conn->connect_error);
            }
        }

        return $conn;
    }
}


// Fungsi untuk notifikasi ke admin
function notifyAdmins($message, $reply_markup = null) {
    $admins = getAdminIds();
    error_log("notifyAdmins: Found " . count($admins) . " admin(s).");
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
    if ($response === false) {
        error_log("Curl error: " . curl_error($ch));
    } else {
        error_log("Response from Telegram: " . $response);
    }
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
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row['telegram_id'];
        }
    } else {
        error_log("getAdminIds: Query gagal: " . $conn->error);
    }

    $conn->close();
    error_log("getAdminIds: Admin IDs: " . implode(", ", $admins));
    return $admins;
}
?>
