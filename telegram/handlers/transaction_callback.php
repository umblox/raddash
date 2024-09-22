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

require_once '../functions.php'; 

// Fungsi untuk menyimpan transaksi top-up ke database
function saveTopup($userId, $username, $amount) {
    $conn = getDbConnection();
    $query = "INSERT INTO topup_requests (user_id, username, amount, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("prepare failed: " . $conn->error);
        return false;
    }
    if (!$stmt->bind_param("isd", $userId, $username, $amount)) {
        error_log("bind_param failed: " . $stmt->error);
        return false;
    }
    if (!$stmt->execute()) {
        error_log("execute failed: " . $stmt->error);
        return false;
    }
    $transactionId = $stmt->insert_id; // Dapatkan ID transaksi terbaru
    $stmt->close();
    $conn->close();
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

    // Panggil fungsi notifyAdmins() dari functions.php
    notifyAdmins($message);
}

// Fungsi untuk mengirim notifikasi kepada user tentang top-up
function notifyUserTopup($userId) {
    sendMessage($userId, "Permintaan top-up Anda telah diajukan dan menunggu konfirmasi admin.");
}

// Misalnya transaksi dilakukan di web
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Transaksi POST diterima");

    // Pastikan semua data yang diperlukan ada
    if (!isset($_POST['user_id'], $_POST['username'], $_POST['amount'], $_POST['type'])) {
        error_log("Data POST tidak lengkap.");
        echo json_encode(["status" => "error", "message" => "Data POST tidak lengkap."]);
        exit;
    }

    $userId = $_POST['user_id'];
    $username = $_POST['username']; // Ambil username dari form
    $amount = $_POST['amount'];
    $type = $_POST['type']; // 'topup' atau 'voucher'

    if ($type === 'topup') {
        // Simpan transaksi top-up ke database
        $transactionId = saveTopup($userId, $username, $amount);
        if ($transactionId) {
            error_log("Top-up disimpan dengan ID: $transactionId");

            // Kirim notifikasi ke admin dan user
            notifyAdminTopup($transactionId, $userId, $username, $amount);
            error_log("Notifikasi dikirim ke admin");

            notifyUserTopup($userId);
            error_log("Notifikasi dikirim ke user");
        } else {
            error_log("Gagal menyimpan transaksi top-up.");
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan transaksi."]);
            exit;
        }
    }

    // Jika belum menangan voucher, skip
    echo json_encode(["status" => "success", "message" => "Transaksi diproses."]);
}
?>
