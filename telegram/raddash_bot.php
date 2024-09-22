<?php
// raddash_bot.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';
echo "functions.php loaded.\n";
require_once 'handlers/raddash_handler.php'; // Pastikan path-nya benar
echo "handlers/raddash_handler.php loaded.\n";

$offset = 0;

while (true) {
    echo "Loop berjalan...\n";

    // Membuat URL untuk mendapatkan update dari Telegram dengan timeout dan offset
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?timeout=100&offset=" . ($offset + 1);
    echo "Mengambil update dengan URL: $url\n";
    error_log("Mengambil update dengan URL: $url");

    // Menggunakan cURL sebagai alternatif file_get_contents
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // timeout lebih panjang
    $response = curl_exec($ch);
    if ($response === false) {
        echo "Curl error: " . curl_error($ch) . "\n";
        error_log("Curl error: " . curl_error($ch));
        curl_close($ch);
        sleep(5);
        continue;
    }
    curl_close($ch);



    $updates = json_decode($response, true);
    if ($updates === null) {
        echo "Gagal mendecode JSON response.\n";
        error_log("Gagal mendecode JSON response.");
        // Tunggu sebelum mencoba lagi
        sleep(5);
        continue;
    }

    echo "Menunggu update dari Telegram...\n";
    if ($updates && $updates["ok"]) {
        echo "Update diterima. Memproses...\n";
        error_log("Update diterima. Memproses...");
        foreach ($updates["result"] as $update) {
            // Set offset untuk update berikutnya
            $offset = $update["update_id"];

            // Memeriksa apakah ada message
            if (isset($update["message"])) {
                $message = $update["message"];
                $chatId = $message["chat"]["id"];
                $text = $message["text"];

                echo "Processing update ID: " . $update["update_id"] . "\n";
                error_log("Processing update ID: " . $update["update_id"]);

                // Cek apakah pesan adalah perintah /confirm_<id> atau /deny_<id>
                if (preg_match('/^\/confirm_(\d+)$/', $text, $matches)) {
                    $transactionId = $matches[1];
                    confirmTopup($transactionId);
                    sendMessage($chatId, "Top-up telah dikonfirmasi.");
                } elseif (preg_match('/^\/deny_(\d+)$/', $text, $matches)) {
                    $transactionId = $matches[1];
                    denyTopup($transactionId);
                    sendMessage($chatId, "Top-up telah ditolak.");
                } else {
                    sendMessage($chatId, "Perintah tidak dikenali. Gunakan /confirm_<id> atau /deny_<id>.");
                }
            } else {
                echo "Tidak ada message pada update ID: " . $update["update_id"] . "\n";
                error_log("Tidak ada message pada update ID: " . $update["update_id"]);
            }
        }
    } else {
        echo "Tidak ada update yang diterima.\n";
        error_log("Tidak ada update yang diterima.");
    }

    // Delay sebelum mengambil update berikutnya
    sleep(1);
}
?>
