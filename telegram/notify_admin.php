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

// Batasi akses hanya melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

// Ambil data dari POST
$username = isset($_POST['username']) ? $_POST['username'] : null;
$amount = isset($_POST['amount']) ? $_POST['amount'] : null;
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

// Validasi data
if (!$username || !$amount || !$user_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit();
}

// Include functions.php menggunakan require_once untuk menghindari duplikasi
require_once '/www/raddash/telegram/functions.php';

// Buat pesan notifikasi
$message = "Permintaan top-up baru:\n\n";
$message .= "Username: @$username\n";
$message .= "Jumlah: Rp " . number_format($amount, 0, ',', '.') . "\n\n";
$message .= "Silakan konfirmasi atau tolak permintaan ini.";

// Buat reply_markup untuk tombol konfirmasi dan tolak
$reply_markup = [
    'inline_keyboard' => [
        [
            ['text' => '✅ Terima', 'callback_data' => "admin_confirm_topup,$user_id,$amount"],
            ['text' => '❌ Tolak', 'callback_data' => "admin_reject_topup,$user_id"]
        ]
    ]
];

// Panggil fungsi notifikasi
notifyAdmins($message, $reply_markup);

// Balas dengan status sukses
echo json_encode(['status' => 'success', 'message' => 'Notifikasi dikirim ke admin.']);

// Setelah mengirim notifikasi
$query = 'UPDATE topup_requests SET notified = 1 WHERE user_id = ? AND amount = ? AND status = "pending"';
$stmt = $conn->prepare($query);
$stmt->bind_param('id', $user_id, $amount);
$stmt->execute();
$stmt->close();

?>
