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

require_once 'config.php'; // Ambil BOT_TOKEN, database credentials, dll.
require_once 'functions.php';

// Fungsi untuk mengirim notifikasi top-up
function notifyTopup($userId, $username, $amount, $transactionId, $status) {
    $statusMessage = $status === 'confirmed' ? "Permintaan top-up Anda telah dikonfirmasi." : "Permintaan top-up Anda telah ditolak.";
    
    // Notifikasi ke user
    sendTelegramRequest('sendMessage', [
        'chat_id' => $userId,
        'text' => $statusMessage,
    ]);

    // Notifikasi ke admin
    sendTelegramRequest('sendMessage', [
        'chat_id' => ADMIN_CHAT_ID,
        'text' => "Top-up: ID Transaksi $transactionId oleh $username, Status: $status",
    ]);
}

// Fungsi untuk mengirim notifikasi pembelian voucher
function notifyVoucher($userId, $username, $amount, $type) {
    // Notifikasi ke user
    sendTelegramRequest('sendMessage', [
        'chat_id' => $userId,
        'text' => "Pembelian voucher berhasil: Jumlah: $amount, Tipe: " . ucfirst($type),
    ]);

    // Notifikasi ke admin
    sendTelegramRequest('sendMessage', [
        'chat_id' => ADMIN_CHAT_ID,
        'text' => "Pembelian voucher baru oleh $username: Jumlah: $amount, Tipe: " . ucfirst($type),
    ]);
}

// Contoh pemanggilan fungsi (sesuaikan dengan kebutuhan)
// notifyTopup($userId, $username, $amount, $transactionId, 'confirmed');
// notifyVoucher($userId, $username, $amount, $type);
?>
