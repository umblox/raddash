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

// handlers/start_handler.php

require '/www/raddash/telegram/functions.php'; 

function handleStartCommand($chat_id, $username) {
    $conn = getDbConnection();

    // Cek apakah user sudah terdaftar
    $query = $conn->prepare("SELECT * FROM users WHERE telegram_id = ?");
    $query->bind_param("s", $chat_id);
    $query->execute();
    $result = $query->get_result();

    // Jika pengguna sudah terdaftar
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response = "Selamat datang kembali, " . htmlspecialchars($user['username']) . "!\n\n";
        $response .= "Data Akun Anda:\n";
        $response .= "Username: " . htmlspecialchars($user['username']) . "\n";
        $response .= "Saldo: " . number_format($user['balance'], 2) . "\n";
        $response .= "Anda terdaftar sebagai: " . ($user['is_admin'] ? 'Admin' : 'User') . "\n\n";
    } else {
        // Jika pengguna belum terdaftar, daftarkan mereka
        if ($username) {
            $stmt = $conn->prepare("INSERT INTO users (telegram_id, username, balance, is_admin) VALUES (?, ?, ?, ?)");
            $default_balance = 0.00;
            $is_admin = 0; // Default sebagai user

            $stmt->bind_param("ssdi", $chat_id, $username, $default_balance, $is_admin);
            if ($stmt->execute()) {
                $response = "Selamat datang, " . htmlspecialchars($username) . "! Anda telah berhasil terdaftar.\n\n";
            } else {
                $response = "Gagal mendaftar. Silakan coba lagi.";
                // Kirim pesan dan keluar dari fungsi jika gagal mendaftar
                sendMessage($chat_id, $response);
                $stmt->close();
                $conn->close();
                return;
            }
            $stmt->close();
        } else {
            $response = "Anda perlu mengatur username Telegram terlebih dahulu. Silakan buat username dan coba lagi.";
            sendMessage($chat_id, $response);
            $conn->close();
            return;
        }
    }

    // Menampilkan daftar command yang bisa digunakan
    $response .= "\nCommand yang bisa digunakan:\n";
    $response .= "/topup - Top-up saldo\n";
    $response .= "/beli - Beli voucher hotspot\n";
    $response .= "/saldo - Cek sisa saldo\n";
    $response .= "/profile - Melihat profil akun\n";

    // Kirim pesan balasan
    sendMessage($chat_id, $response);

    // Tutup koneksi
    $conn->close();
}
?>
