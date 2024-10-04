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

// handlers/topup_handler.php

require_once '/www/raddash/telegram/functions.php'; // Pastikan hanya di-include sekali

function handleTopupCommand($chat_id, $username) {
    $conn = getDbConnection();

    // Cek apakah user sudah terdaftar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE telegram_id = ?");
    $stmt->bind_param("s", $chat_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        sendMessage($chat_id, "Anda belum terdaftar. Silakan gunakan /start untuk mendaftar terlebih dahulu.");
        $conn->close();
        return;
    }

    // Cek apakah user memiliki request topup pending
    $stmt = $conn->prepare("SELECT COUNT(*) FROM topup_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("s", $chat_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();

    if ($pending_count > 0) {
        sendMessage($chat_id, "Anda sudah memiliki permintaan top-up yang sedang diproses. Harap tunggu hingga permintaan tersebut diselesaikan ❌.");
        $conn->close();
        return;
    }

    // Jika user belum memiliki permintaan pending, tampilkan pilihan nominal
    $amounts = [3000, 5000, 10000, 20000, 50000, 100000];
    $keyboard = [];
    foreach ($amounts as $amount) {
        $keyboard[] = [['text' => "Top-up $amount", 'callback_data' => "topup,$amount"]];
    }
    $reply_markup = ['inline_keyboard' => $keyboard];

    sendMessage($chat_id, "Pilih jumlah top-up yang Anda inginkan:", $reply_markup);

    $conn->close();
}

function handleTopupCallback($chat_id, $message_id, $from_id, $username, $data_parts) {
    if (count($data_parts) < 2) {
        editMessage($chat_id, $message_id, "Data callback tidak valid.");
        return;
    }

    $selected_amount = $data_parts[1];

    $conn = getDbConnection();

    // Cek apakah user masih terdaftar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE telegram_id = ?");
    $stmt->bind_param("s", $from_id);
    $stmt->execute();
    $stmt->bind_result($user_count);
    $stmt->fetch();
    $stmt->close();

    if ($user_count == 0) {
        editMessage($chat_id, $message_id, "Anda belum terdaftar. Silakan gunakan /start untuk mendaftar terlebih dahulu ❌.");
        $conn->close();
        return;
    }

    // Cek apakah user memiliki permintaan topup pending
    $stmt = $conn->prepare("SELECT COUNT(*) FROM topup_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("s", $from_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();

    if ($pending_count > 0) {
        editMessage($chat_id, $message_id, "Anda sudah memiliki permintaan top-up yang sedang diproses ❌.");
        $conn->close();
        return;
    }

    // Tampilkan konfirmasi
    $keyboard = [
        [
            ['text' => '✅ Konfirmasi', 'callback_data' => "user_confirm_topup,$selected_amount"],
            ['text' => '❌ Batal', 'callback_data' => "user_cancel_topup"]
        ]
    ];
    $reply_markup = ['inline_keyboard' => $keyboard];

    $message = "Anda memilih top-up sebesar <b>$selected_amount</b>.\n\nApakah jumlah ini sudah benar?";
    editMessage($chat_id, $message_id, $message, $reply_markup);

    $conn->close();
}

function handleUserTopupConfirmation($chat_id, $message_id, $from_id, $username, $data_parts) {
    if (count($data_parts) < 2) {
        editMessage($chat_id, $message_id, "Data callback tidak valid.");
        return;
    }

    $selected_amount = $data_parts[1];

    $conn = getDbConnection();

    // Cek apakah user masih terdaftar
    if ($from_id != null && $from_id != '') {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE telegram_id = ?");
        $stmt->bind_param("s", $from_id);
        $stmt->execute();
        $stmt->bind_result($user_id, $user_username);
        if (!$stmt->fetch()) {
            editMessage($chat_id, $message_id, "User  tidak ditemukan. Silakan gunakan /start untuk m endaftar ❌.");
            $stmt->close();
            $conn->close();
            return;
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $user_username);
        if (!$stmt->fetch()) {
            editMessage($chat_id, $message_id, "User   tidak ditemukan. Silakan gunakan /start untuk mendaftar ❌.");
            $stmt->close();
            $conn->close();
            return;
        }
        $stmt->close();
    }

    // Cek apakah user memiliki permintaan topup pending
    $stmt = $conn->prepare("SELECT COUNT(*) FROM topup_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();

    if ($pending_count > 0) {
        editMessage($chat_id, $message_id, "Anda sudah memiliki permintaan top-up yang sedang diproses ❌.");
        $conn->close();
        return;
    }

    // Simpan permintaan topup dengan status 'pending'
    $stmt = $conn->prepare("INSERT INTO topup_requests (user_id, username, amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("isd", $user_id, $username, $selected_amount);
    if ($stmt->execute()) {
        $stmt->close();

        // Notifikasi ke admin untuk konfirmasi
        $admin_message = "Permintaan top-up baru:\n\n";
        $admin_message .= "ID: $user_id\n";
        $admin_message .= "Username: @$username\n";
        $admin_message .= "Jumlah: $selected_amount kredit\n\n";
        $admin_message .= "Silakan konfirmasi atau tolak permintaan ini.";

        // Dapatkan semua admin
        $admins = getAdminIds();

        foreach ($admins as $admin_id) {
            $keyboard = [
                [
                    ['text' => '✅ Terima', 'callback_data' => "admin_confirm_topup,$user_id,$selected_amount"],
                    ['text' => '❌ Tolak', 'callback_data' => "admin_reject_topup,$user_id"]
                ]
            ];
            $reply_markup = ['inline_keyboard' => $keyboard];

            sendMessage($admin_id, $admin_message, $reply_markup);
        }

        // Edit pesan pengguna
        $user_message = "🟢 Permintaan top-up sebesar <b>$selected_amount</b> kredit sedang menunggu konfirmasi admin.";
        editMessage($chat_id, $message_id, $user_message);

    } else {
        editMessage($chat_id, $message_id, "Gagal membuat permintaan top-up. Silakan coba lagi ❌.");
    }

    $conn->close();
}
function handleAdminTopupConfirmation($chat_id, $message_id, $data_parts) {
    if (count($data_parts) < 2) {
        editMessage($chat_id, $message_id, "Data callback tidak valid.");
        return;
    }

    $action = $data_parts[0];
    $user_id = $data_parts[1];
    $amount = isset($data_parts[2]) ? $data_parts[2] : null;

    $conn = getDbConnection();

    if ($action === 'admin_confirm_topup') {
        // Validasi data
        if (!$amount) {
            editMessage($chat_id, $message_id, "Data amount tidak ditemukan.");
            $conn->close();
            return;
        }

        // Ambil user info
        $stmt = $conn->prepare("SELECT id, username, balance FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $current_balance);
        if (!$stmt->fetch()) {
            editMessage($chat_id, $message_id, "User  tidak ditemukan.");
            $stmt->close();
            $conn->close();
            return;
        }
        $stmt->close();

        // Update saldo user
        $new_balance = $current_balance + $amount;
        $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param("ds", $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        // Update status topup_request
        $stmt = $conn->prepare("UPDATE topup_requests SET status = 'confirmed' WHERE user_id = ? AND amount = ? AND status = 'pending'");
        $stmt->bind_param("sd", $user_id, $amount);
        $stmt->execute();
        $stmt->close();

        // Notifikasi ke user
        $stmt = $conn->prepare("SELECT telegram_id FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($telegram_id);
        $stmt->fetch();
        $stmt->close();

        if ($telegram_id != null && $telegram_id != '') {
            sendMessage($telegram_id, "Top-up sebesar <b>$amount</b> kredit telah dikonfirmasi oleh admin.\nSaldo Anda sekarang adalah <b>$new_balance</b> kredit ✅.");
        }

        // Edit pesan admin
        $message = "Top-up untuk pengguna <b>$user_id</b> (@$username) sebesar <b>$amount</b> telah dikonfirmasi.\nSaldo baru: <b>$new_balance</b> kredit ✅.";
        editMessage($chat_id, $message_id, $message);

    } elseif ($action === 'admin_reject_topup') {
        // Ambil user info dan amount
        $stmt = $conn->prepare("SELECT tr.username, tr.amount FROM topup_requests tr WHERE tr.user_id = ? AND tr.status = 'pending' LIMIT 1");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($username, $amount);
        if (!$stmt->fetch()) {
            editMessage($chat_id, $message_id, "Permintaan top-up tidak ditemukan atau sudah diproses ❌.");
            $stmt->close();
            $conn->close();
            return;
        }
        $stmt->close();

        // Update status topup_request
        $stmt = $conn->prepare("UPDATE topup_requests SET status = 'rejected' WHERE user_id = ? AND amount = ? AND status = 'pending'");
        $stmt->bind_param("sd", $user_id, $amount);
        $stmt->execute();
        $stmt->close();

        // Ambil saldo user
        $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_balance);
        $stmt->fetch();
        $stmt->close();

        // Notifikasi ke user
        $stmt = $conn->prepare("SELECT telegram_id FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($telegram_id);
        $stmt->fetch();
        $stmt->close();

        if ($telegram_id != null && $telegram_id != '') {
            sendMessage($telegram_id, "Top-up sebesar <b>$amount</b> kredit telah ditolak oleh admin.\nSaldo Anda tetap: <b>$current_balance</b> kredit ❌.");
        }

        // Edit pesan admin
        $message = "Top-up untuk pengguna <b>$user_id</b> (@$username) sebesar <b>$amount</b> telah ditolak.\nSaldo saat ini tetap: <b>$current_balance</b> kredit ❌.";
        editMessage($chat_id, $message_id, $message);
    }

    $conn->close();
}
?>
