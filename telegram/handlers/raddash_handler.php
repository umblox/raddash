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

// raddash_handler.php

require_once '../telegram/config.php'; 
require_once '../telegram/functions.php'; 

// Fungsi untuk menangani konfirmasi top-up
function confirmTopup($transactionId) {
    $conn = getDbConnection();
    
    // Update status menjadi 'confirmed'
    $query = "UPDATE topup_requests SET status = 'confirmed' WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("confirmTopup: Prepare failed: " . $conn->error);
        return;
    }
    if (!$stmt->bind_param("i", $transactionId)) {
        error_log("confirmTopup: Bind failed: " . $stmt->error);
        return;
    }
    if (!$stmt->execute()) {
        error_log("confirmTopup: Execute failed: " . $stmt->error);
        return;
    }
    $stmt->close();

    // Ambil data pengguna
    $query = "SELECT user_id, username, amount FROM topup_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("confirmTopup: Prepare failed (select): " . $conn->error);
        return;
    }
    if (!$stmt->bind_param("i", $transactionId)) {
        error_log("confirmTopup: Bind failed (select): " . $stmt->error);
        return;
    }
    if (!$stmt->execute()) {
        error_log("confirmTopup: Execute failed (select): " . $stmt->error);
        return;
    }
    $result = $stmt->get_result();
    $topupRequest = $result->fetch_assoc();
    $stmt->close();

    if ($topupRequest) {
        // Update saldo pengguna
        $query = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("confirmTopup: Prepare failed (update balance): " . $conn->error);
            return;
        }
        if (!$stmt->bind_param("di", $topupRequest['amount'], $topupRequest['user_id'])) {
            error_log("confirmTopup: Bind failed (update balance): " . $stmt->error);
            return;
        }
        if (!$stmt->execute()) {
            error_log("confirmTopup: Execute failed (update balance): " . $stmt->error);
            return;
        }
        $stmt->close();

        // Kirim notifikasi kepada pengguna
        sendMessage($topupRequest['user_id'], "Permintaan top-up Anda telah dikonfirmasi. Jumlah: " . $topupRequest['amount']);
        error_log("confirmTopup: Notifikasi dikirim ke pengguna ID: " . $topupRequest['user_id']);
    } else {
        error_log("confirmTopup: Transaksi tidak ditemukan dengan ID: $transactionId");
    }

    $conn->close();
}

// Fungsi untuk menangani penolakan top-up
function denyTopup($transactionId) {
    $conn = getDbConnection();

    // Update status menjadi 'rejected'
    $query = "UPDATE topup_requests SET status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("denyTopup: Prepare failed: " . $conn->error);
        return;
    }
    if (!$stmt->bind_param("i", $transactionId)) {
        error_log("denyTopup: Bind failed: " . $stmt->error);
        return;
    }
    if (!$stmt->execute()) {
        error_log("denyTopup: Execute failed: " . $stmt->error);
        return;
    }
    $stmt->close();

    // Ambil data pengguna
    $query = "SELECT user_id FROM topup_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("denyTopup: Prepare failed (select): " . $conn->error);
        return;
    }
    if (!$stmt->bind_param("i", $transactionId)) {
        error_log("denyTopup: Bind failed (select): " . $stmt->error);
        return;
    }
    if (!$stmt->execute()) {
        error_log("denyTopup: Execute failed (select): " . $stmt->error);
        return;
    }
    $result = $stmt->get_result();
    $topupRequest = $result->fetch_assoc();
    $stmt->close();

    if ($topupRequest) {
        // Kirim notifikasi kepada pengguna
        sendMessage($topupRequest['user_id'], "Permintaan top-up Anda telah ditolak.");
        error_log("denyTopup: Notifikasi dikirim ke pengguna ID: " . $topupRequest['user_id']);
    } else {
        error_log("denyTopup: Transaksi tidak ditemukan dengan ID: $transactionId");
    }

    $conn->close();
}
?>