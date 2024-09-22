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

// bot.php
if (!defined('IN_BOT')) {
    die('Direct access not allowed.');
}

require_once 'config.php';
require_once 'functions.php';
require_once 'handlers/start_handler.php';
require_once 'handlers/topup_handler.php';
require_once 'handlers/beli_handler.php';
require_once 'handlers/saldo_handler.php';
require_once 'handlers/profile_handler.php';
// Lainnya nyusul ya kak

// Fungsi untuk mendapatkan pembaruan dari Telegram
function getUpdates($offset = 0) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?timeout=100";
    if ($offset) {
        $url .= "&offset=" . $offset;
    }
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Fungsi utama untuk menjalankan bot
function runBot() {
    $offset = 0;

    while (true) {
        $updates = getUpdates($offset);

        if ($updates && isset($updates['result'])) {
            foreach ($updates['result'] as $update) {
                // Mengupdate offset
                $offset = $update['update_id'] + 1;

                // Menangani pesan teks
                if (isset($update['message'])) {
                    $chat_id = $update['message']['chat']['id'];
                    $text = isset($update['message']['text']) ? $update['message']['text'] : '';
                    $username = isset($update['message']['chat']['username']) ? $update['message']['chat']['username'] : null;

                    // Menangani command
                    if ($text === '/start') {
                        handleStartCommand($chat_id, $username);
                    } elseif ($text === '/topup') {
                        handleTopupCommand($chat_id, $username);
                    } elseif ($text === '/beli') {
                        handleBeliCommand($chat_id, $username);
                    } elseif ($text === '/saldo') {
                        handleSaldoCommand($chat_id, $username);
                    }
                    // Tambahkan handler lain di sini untuk command lain
                }

                // Menangani callback_query
                if (isset($update['callback_query'])) {
                    $callback = $update['callback_query'];
                    $callback_data = $callback['data'];
                    $chat_id = $callback['message']['chat']['id'];
                    $message_id = $callback['message']['message_id'];
                    $from_id = $callback['from']['id'];
                    $username = isset($callback['from']['username']) ? $callback['from']['username'] : null;

                    // Memisahkan callback_data
                    $data_parts = explode(',', $callback_data);
                    $action = $data_parts[0];

                    if ($action === 'topup') {
                        // Contoh: callback_data = "topup,5000"
                        handleTopupCallback($chat_id, $message_id, $from_id, $username, $data_parts);
                    } elseif ($action === 'user_confirm_topup') {
                        // Contoh: callback_data = "user_confirm_topup,5000"
                        handleUserTopupConfirmation($chat_id, $message_id, $from_id, $username, $data_parts);
                    } elseif ($action === 'user_cancel_topup') {
                        // Contoh: callback_data = "user_cancel_topup"
                        editMessage($chat_id, $message_id, "Permintaan top-up Anda telah dibatalkan.");
                    } elseif ($action === 'admin_confirm_topup') {
                        // Contoh: callback_data = "admin_confirm_topup,USER_ID,5000"
                        handleAdminTopupConfirmation($chat_id, $message_id, $data_parts);
                    } elseif ($action === 'admin_reject_topup') {
                        // Contoh: callback_data = "admin_reject_topup,USER_ID"
                        handleAdminTopupConfirmation($chat_id, $message_id, $data_parts);
                    } elseif ($action === 'confirm_beli') {
                        handleBeliConfirmation($chat_id, $data_parts[1], $message_id);
                    } elseif ($action === 'beli') {
                        // Call the purchase function here
                        handleBeliPurchase($chat_id, $data_parts[1], $message_id);
                    } elseif ($action === 'beli_kembali') {
                        handleBeliCommand($chat_id, $username);
                    } elseif ($action === 'close_saldo') {
                        handleSaldoCallback($callback_data, $chat_id, $message_id);
                    }

                    // Tambahkan handler lain di sini untuk callback lain
                }
            }
        }

        // Tunggu beberapa detik sebelum mendapatkan update berikutnya
        sleep(1);
    }
}

// Menjalankan bot
runBot();
?>
