<?php
session_start();
require_once '/www/radiusbilling/config/database.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Koneksi ke database
$db = getDbConnection();

// Cek jumlah permintaan top-up yang pending
$query = 'SELECT COUNT(*) FROM topup_requests WHERE status = "pending"';
$stmt = $db->prepare($query);
if ($stmt === false) {
    die('Error prepare statement: ' . $db->error);
}
$stmt->execute();
$stmt->bind_result($pendingCount);
$stmt->fetch();
$stmt->close();
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Welcome to Admin Panel</h1>
    <?php if ($pendingCount > 0): ?>
        <p>You have <?php echo htmlspecialchars($pendingCount); ?> pending top-up request(s).</p>
        <a href="/radiusbilling/transactions/topup.php">View Top-Up Requests</a>
    <?php else: ?>
        <p>No pending top-up requests at the moment.</p>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</body>
</html>
