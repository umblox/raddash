<?php
require_once '/www/raddash/config/database.php';

// Koneksi ke database
$db = getDbConnection();

// Cek jumlah permintaan top-up yang pending
$query = 'SELECT COUNT(*) FROM topup_requests WHERE status = "pending"';
$stmt = $db->prepare($query);
if ($stmt === false) {
    echo json_encode(['error' => 'Error prepare statement: ' . $db->error]);
    exit();
}
$stmt->execute();
$stmt->bind_result($pendingCount);
$stmt->fetch();
$stmt->close();
$db->close();

// Mengirim hasilnya dalam format JSON
echo json_encode(['pendingCount' => $pendingCount]);
?>
