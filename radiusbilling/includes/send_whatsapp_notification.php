<?php
function sendWhatsAppNotification($message, $number) {
    // Encoding URL dengan parameter message dan number
    $url = "https://wa.arneta.my.id/send-message?message=" . urlencode($message) . "&number=" . urlencode($number);

    // Mengambil konten dari URL dengan penanganan kesalahan
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        error_log("Failed to send WhatsApp notification. URL: $url");
    } else {
        error_log("WhatsApp notification sent successfully. Response: $response");
    }
}

// Cek apakah permintaan POST dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['number'])) {
    $message = $_POST['message'];
    $number = $_POST['number'];
    sendWhatsAppNotification($message, $number);
} else {
    error_log('Invalid request method or missing parameters in send_whatsapp_notification.php');
}
?>
