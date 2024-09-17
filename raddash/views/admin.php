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

session_start();

include '/www/radiusbilling/views/header.php';
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
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Welcome to Admin Panel</h1>
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Dashboard</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($pendingCount > 0): ?>
                            <p>You have <?php echo htmlspecialchars($pendingCount); ?> pending top-up request(s).</p>
                            <a href="/radiusbilling/transactions/topup.php" class="btn btn-primary">View Top-Up Requests</a>
                        <?php else: ?>
                            <p class="text-success">No pending top-up requests at the moment.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
