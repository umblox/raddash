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

// beli_handler.php

require_once '/www/raddash/telegram/config.php';
require_once '/www/raddash/telegram/functions.php';

// Fungsi untuk mendapatkan ID pengguna dari chat ID
function getUserIdFromChatId($chat_id) {
    $conn = getDbConnection();
    $result = $conn->query("SELECT id FROM users WHERE telegram_id = '{$chat_id}'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['id'];
    }

    return null; // Jika tidak ditemukan
}

// Fungsi untuk menggenerate kode voucher
function generateVoucherCode($planName) {
    $prefix = getVoucherPrefix($planName);
    $random_part = substr(str_shuffle(str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", 5)), 0, 5);
    return $prefix . $random_part;
}


// Fungsi untuk mendapatkan username dari chat ID
function getUsernameFromChatId($chat_id) {
    // Logika untuk mendapatkan username berdasarkan chat ID
    // Misalnya dengan query ke database atau langsung dari data Telegram
    // Sesuaikan dengan struktur data Anda
    return ""; // Ganti dengan logika yang sesuai
}

// Fungsi untuk menangani command /beli
function handleBeliCommand($chat_id, $username) {
    $conn = getDbConnection();
    $plans = [];

    $result = $conn->query("SELECT id, planName, planCost FROM billing_plans WHERE planCost > 0");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }

    if (empty($plans)) {
        sendMessage($chat_id, 'Tidak ada paket yang tersedia untuk dibeli.');
        return;
    }

    // Mempersiapkan tombol untuk paket
    $keyboard = [];
    foreach ($plans as $plan) {
        $keyboard[] = [
            [
                'text' => "{$plan['planName']} - {$plan['planCost']}",
                'callback_data' => "confirm_beli,{$plan['id']}"
            ]
        ];
    }

    $reply_markup = [
        'inline_keyboard' => $keyboard
    ];

    sendMessage($chat_id, 'Pilih paket yang ingin Anda beli:', $reply_markup);
}

// Fungsi untuk menangani konfirmasi pembelian
function handleBeliConfirmation($chat_id, $plan_id, $message_id) {
    $conn = getDbConnection();
    $result = $conn->query("SELECT planName, planCost FROM billing_plans WHERE id = '{$plan_id}'");

    if ($result->num_rows === 0) {
        editMessage($chat_id, $message_id, "Paket tidak ditemukan.");
        return;
    }

    $plan = $result->fetch_assoc();

    // Tampilkan pesan konfirmasi
    $confirmation_message = (
        "Anda telah memilih paket: {$plan['planName']} dengan harga {$plan['planCost']}\n\n" .
        "Apakah Anda ingin melanjutkan pembelian?"
    );

    $keyboard = [
        [
            [
                'text' => "✅ Ya",
                'callback_data' => "beli,{$plan_id},{$message_id}"
            ],
            [
                'text' => "❌ Batal",
                'callback_data' => "beli_kembali"
            ]
        ]
    ];

    $reply_markup = [
        'inline_keyboard' => $keyboard
    ];

    editMessage($chat_id, $message_id, $confirmation_message, $reply_markup);
}

// Fungsi untuk menangani pembelian voucher
function handleBeliPurchase($chat_id, $plan_id, $message_id) {
    $conn = getDbConnection();
    $user_id = getUserIdFromChatId($chat_id);
    $username = getUsernameFromChatId($chat_id);

    $result = $conn->query("SELECT planName, planCost FROM billing_plans WHERE id = '{$plan_id}'");
    $plan = $result->fetch_assoc();

    $user_result = $conn->query("SELECT balance FROM users WHERE id = '{$user_id}'");
    $user = $user_result->fetch_assoc();

    if (!$plan || !$user || $user['balance'] < $plan['planCost']) {
        editMessage($chat_id, $message_id, "Saldo Anda tidak mencukupi atau paket tidak ditemukan.");
        return;
    }

    // Potong saldo
    $new_balance = $user['balance'] - $plan['planCost'];
    $conn->query("UPDATE users SET balance = '{$new_balance}' WHERE id = '{$user_id}'");

    // Generate voucher code
    $voucher_code = generateVoucherCode($plan['planName']); // Pastikan menggunakan nama plan

    // Simpan voucher ke tabel
    $conn->query("INSERT INTO radcheck (username, attribute, op, value) VALUES ('{$voucher_code}', 'Auth-Type', ':=', 'Accept')");
    $conn->query("INSERT INTO radusergroup (username, groupname, priority) VALUES ('{$voucher_code}', '{$plan['planName']}', 1)");

    $creation_date = date('Y-m-d H:i:s');
    $creation_by = "{$telegram_username}@RadDashBot";

    $conn->query("INSERT INTO userinfo (username, creationdate, creationby) VALUES ('{$voucher_code}', '{$creation_date}', '{$creation_by}')");
    $conn->query("INSERT INTO userbillinfo (username, planName, paymentmethod, cash, creationdate, creationby) VALUES ('{$voucher_code}', '{$plan['planName']}', 'cash', '{$plan['planCost']}', '{$creation_date}', '{$creation_by}')");

    // Notifikasi ke admin
    notifyAdmins("🟢 Pengguna *{$telegram_username}* telah berhasil membeli voucher *{$plan['planName']}*.\nKode Voucher: *{$voucher_code}*\nSisa Saldo: *{$new_balance}*");

    // URL login
    $login_url = "http://10.10.10.1:3990/login?username={$voucher_code}&password=Accept";

    // Tombol login
    $keyboard = [
        [
            [
                'text' => "Login dengan Voucher",
                'url' => $login_url
            ]
        ]
    ];

    // Menghilangkan tombol konfirmasi dan menampilkan pesan hasil
    $final_message = "🟢Voucher Anda telah dibuat dengan kode: *{$voucher_code}*\nSisa saldo Anda sekarang: *{$new_balance}*";
    editMessage($chat_id, $message_id, $final_message, ['inline_keyboard' => $keyboard]);
}?>
