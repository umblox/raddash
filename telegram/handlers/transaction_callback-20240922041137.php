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

require_once '../config.php';
require_once '../functions.php'; 

// Fungsi untuk menyimpan transaksi top-up ke database
function saveTopup($userId, $username, $amount) {
    $conn = getDbConnection();
    $query = "INSERT INTO topup_requests (user_id, username, amount, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $userId, $username, $amount); // Sesuaikan dengan tipe data
    $stmt->execute();
    $transactionId = $conn->insert_id; // Dapatkan ID transaksi terbaru
    $conn->close(); // Tutup koneksi
    return $transactionId;
}
// Fungsi untuk mengirim notifikasi ke admin Telegram
function notifyAdminTopup($transactionId, $userId, $username, $amount) {
    $message = "Permintaan top-up baru: \n";
    $message .= "ID Transaksi: $transactionId\n";
    $message .= "User ID: $userId\n";
    $message .= "Username: $username\n";
    $message .= "Jumlah: $amount\n";
    $message .= "Klik untuk konfirmasi: \n";
    $message .= "/confirm_$transactionId atau /deny_$transactionId";

    notifyAdmins($message); // Panggil fungsi untuk mengirim notifikasi ke semua admin

    sendTelegramRequest('sendMessage', [
        'chat_id' => ADMIN_CHAT_ID, // Ganti dengan chat ID admin
        'text' => $message,
    ]);
}

// Fungsi untuk mengirim notifikasi kepada user tentang top-up
function notifyUserTopup($transactionId, $userId) {
    sendTelegramRequest('sendMessage', [
        'chat_id' => $userId,
        'text' => "Permintaan top-up Anda telah diajukan dan menunggu konfirmasi admin.",
    ]);
}

// Fungsi untuk mengirim pesan ke Telegram
function sendTelegramRequest($method, $data) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/" . $method;
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        // Log error atau lakukan penanganan kesalahan
        error_log("Error sending message: " . print_r($data, true));
    }
    return $result;
}

// Misalnya transaksi dilakukan di web
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $username = $_POST['username']; // Ambil username dari form
    $amount = $_POST['amount'];
    $type = $_POST['type']; // 'topup' atau 'voucher'

    if ($type === 'topup') {
        // Simpan transaksi top-up ke database
        $transactionId = saveTopup($userId, $username, $amount);
        // Kirim notifikasi ke admin dan user
        notifyAdminTopup($transactionId, $userId, $username, $amount);
        notifyUserTopup($transactionId, $userId);
    } elseif ($type === 'voucher') {
        // Simpan transaksi pembelian voucher
        saveVoucherPurchase($userId, $username, $amount, $type);
        // Kirim notifikasi ke admin dan user
        notifyVoucherPurchase($userId, $username, $amount, $type);
    }

    echo json_encode(["status" => "success", "message" => "Transaksi diproses."]);
}

// Fungsi untuk menyimpan pembelian voucher
function saveVoucherPurchase($userId, $username, $amount, $type) {
    global $db; // Pastikan untuk menggunakan koneksi database
    $query = "INSERT INTO voucher_purchases (user_id, username, amount, type) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $username, $amount, $type]);
}

// Fungsi untuk mengirim notifikasi ke admin dan user tentang pembelian voucher
function notifyVoucherPurchase($userId, $username, $amount, $type) {
    $message = "Pembelian voucher baru: \n";
    $message .= "User ID: $userId\n";
    $message .= "Username: $username\n";
    $message .= "Jumlah: $amount\n";
    $message .= "Tipe: " . ucfirst($type) . "\n";

    // Notifikasi ke admin
    sendTelegramRequest('sendMessage', [
        'chat_id' => ADMIN_CHAT_ID,
        'text' => $message,
    ]);

    // Notifikasi ke user
    sendTelegramRequest('sendMessage', [
        'chat_id' => $userId,
        'text' => "Pembelian voucher Anda berhasil. Jumlah: $amount, Tipe: " . ucfirst($type),
    ]);
}

// Misalnya transaksi dilakukan di web
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $username = $_POST['username']; // Ambil username dari form
    $amount = $_POST['amount'];
    $type = $_POST['type']; // 'topup' atau 'voucher'

    if ($type === 'topup') {
        // Simpan transaksi top-up ke database
        $transactionId = saveTopup($userId, $username, $amount);
        // Kirim notifikasi ke admin dan user
        notifyAdminTopup($transactionId, $userId, $username, $amount);
        notifyUserTopup($transactionId, $userId);
    } elseif ($type === 'voucher') {
        // Simpan transaksi pembelian voucher
        saveVoucherPurchase($userId, $username, $amount, $type);
        // Kirim notifikasi ke admin dan user
        notifyVoucherPurchase($userId, $username, $amount, $type);
    }

    echo json_encode(["status" => "success", "message" => "Transaksi diproses."]);
}
?>
