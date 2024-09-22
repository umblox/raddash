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

// saldo_handler.php

require_once '/www/raddash/telegram/functions.php';

// Fungsi untuk menangani command /saldo
function handleSaldoCommand($chat_id, $username) {
    $conn = getDbConnection();
    $user_id = getUserIdFromChatId($chat_id);

    if (!$user_id) {
        sendMessage($chat_id, "Pengguna tidak ditemukan.");
        $conn->close();
        return;
    }

    // Ambil saldo pengguna dari database
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$user) {
        sendMessage($chat_id, "Pengguna tidak ditemukan.");
        return;
    }

    $balance = $user['balance'];

    // Pesan dengan saldo
    $saldo_message = "💰 *Saldo Anda saat ini:* `{$balance}`";

    // Tombol untuk menutup pesan
    $keyboard = [
        [
            [
                'text' => "❌ Tutup",
                'callback_data' => "close_saldo"
            ]
        ]
    ];

    // Kirim pesan dengan saldo
    sendMessage($chat_id, $saldo_message, [
        'inline_keyboard' => $keyboard
    ]);
}

// Fungsi untuk menangani callback dari /saldo
function handleSaldoCallback($callback_data, $chat_id, $message_id) {
    if ($callback_data === 'close_saldo') {
        // Mengedit pesan untuk menutup saldo
        editMessage($chat_id, $message_id, "🔒 *Pesan saldo telah ditutup.*");
    }
}
?>
