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
if (!function_exists('notifyAdmins')) {
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
}

// Fungsi untuk mendapatkan prefix voucher berdasarkan nama plan
if (!function_exists('getVoucherPrefix')) {
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
}

// Fungsi untuk mengirim pesan ke Telegram
if (!function_exists('sendMessage')) {
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
        $response = curl_exec($ch);
        if ($response === false) {
            error_log("Curl error: " . curl_error($ch));
        } else {
            error_log("Response from Telegram: " . $response);
        }
        curl_close($ch);
    }
}

// Fungsi untuk mengedit pesan di Telegram
if (!function_exists('editMessage')) {
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
}

// Fungsi untuk mengambil semua admin
if (!function_exists('getAdminIds')) {
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
}
// Fungsi untuk mengirim pesan ke Admin dari web billing
if (!function_exists('sendTelegramNotification')) {
    function sendTelegramNotification($message) {
        // Ambil token bot dan ID admin dari config.php
        $botToken = BOT_TOKEN;
        $adminChatId = ADMIN_CHAT_ID;

        // URL API Telegram untuk mengirim pesan
        $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage";

        // Data yang akan dikirimkan ke API Telegram
        $data = [
            'chat_id' => $adminChatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        // Inisiasi curl untuk mengirim POST request ke API Telegram
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Eksekusi curl dan dapatkan responnya
        $response = curl_exec($ch);

        // Tutup curl
        curl_close($ch);

        // Log respon untuk debugging
        if ($response === false) {
            error_log("sendTelegramNotification: Curl error: " . curl_error($ch));
        } else {
            error_log("sendTelegramNotification: Response dari Telegram: " . $response);
        }
    }
}

?>
