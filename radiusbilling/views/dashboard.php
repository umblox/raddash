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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/www/radiusbilling/config/database.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Koneksi ke database
$db = getDbConnection();

// Cek apakah admin mencoba mengakses halaman ini
if ($role === 'admin') {
    header('Location: admin.php');
    exit();
}

// Ambil saldo pengguna
$query = 'SELECT balance FROM users WHERE username = ?';
$stmt = $db->prepare($query);
if ($stmt === false) {
    die('Error prepare statement: ' . $db->error);
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($balance);
$stmt->fetch();
$stmt->close();

// Mengambil status permintaan top-up terbaru
$query = 'SELECT * FROM topup_requests WHERE username = ? ORDER BY created_at DESC LIMIT 1';
$stmt = $db->prepare($query);
if ($stmt === false) {
    die('Error prepare statement: ' . $db->error);
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$topup_request = $result->fetch_assoc();
$stmt->close();

// Mengatur status notifikasi
$show_bell = false; // Default: tidak tampil
$notification_message_encoded = ''; // Default: tidak ada pesan
$notification_count = 0; // Default: jumlah pesan baru

if ($topup_request) {
    $status = $topup_request['status'];
    $notification_viewed = $topup_request['notification_viewed'];
    $amount = $topup_request['amount']; // Mengambil amount

    if ($status === 'rejected' || $status === 'confirmed') {
        $notification_message = '';
        if ($status === 'rejected') {
            $notification_message = "Permintaan Anda sebesar Rp. " . number_format($amount, 2) . " telah ditolak. Silakan coba lagi.";
        } elseif ($status === 'confirmed') {
            $notification_message = "Permintaan Anda sebesar Rp. " . number_format($amount, 2) . " telah dikonfirmasi.";
        }

        // Menampilkan lonceng hanya jika statusnya bukan pending dan notification_viewed adalah 0
        if ($notification_viewed == 0) {
            $show_bell = true;
            $notification_count = 1; // Ada 1 pesan baru
        }
        $notification_message_encoded = htmlspecialchars($notification_message);
    }
}

// Tutup koneksi database
$db->close();

// Check if the request is to mark the notification as viewed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notification_viewed') {
    // Reopen database connection
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE topup_requests SET notification_viewed = 1 WHERE username = ? AND notification_viewed = 0");
    if ($stmt === false) {
        die('Error prepare statement: ' . $db->error);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->close();
    $db->close();
    exit(); // Stop further execution after updating
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/radiusbilling/assets/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 56px; /* Mengatur padding atas untuk menghindari header fixed */
        }
        .notification {
            display: none;
            background-color: #f1f1f1;
            border: 1px solid #ccc;
            padding: 10px;
            position: absolute;
            top: 60px;
            right: 10px;
            width: 300px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .notification.show {
            display: block;
        }
        .bell-icon {
            font-size: 24px;
            cursor: pointer;
            margin-left: 10px;
            color: #888;
        }
        .bell-icon.active {
            color: red;
        }
        .container {
            margin-top: 20px;
        }
        .btn-custom {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include '/www/radiusbilling/views/header.php'; ?>

    <div class="container">
        <h1>Selamat Datang</h1>
        <p>Hello, <?php echo htmlspecialchars($username); ?>!</p>
        
        <?php if ($show_bell): ?>
            <span id="bell" class="bell-icon active">🔔</span>
            <span id="notification_count"><?php echo $notification_count; ?> pesan baru</span>
        <?php endif; ?>
        
        <?php if ($notification_message_encoded): ?>
            <div id="notification" class="notification">
                <p><?php echo $notification_message_encoded; ?></p>
                <button class="btn btn-primary" onclick="hideNotification()">Tutup</button>
            </div>
        <?php endif; ?>

        <p>Your current balance is: Rp. <?php echo htmlspecialchars(number_format($balance, 2)); ?></p>

        <?php if ($role !== 'admin'): ?>
            <a href="/radiusbilling/transactions/topup.php" class="btn btn-primary">Top Up</a>
            <a href="/radiusbilling/transactions/purchase.php" class="btn btn-secondary">Purchase Plan</a>
        <?php endif; ?>

        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <script>
        // Show notification on bell click
        document.getElementById('bell')?.addEventListener('click', function() {
            var notification = document.getElementById('notification');
            notification.classList.toggle('show');
        });

        function hideNotification() {
            var notification = document.getElementById('notification');
            notification.classList.remove('show');
            var bell = document.getElementById('bell');
            var notificationCount = document.getElementById('notification_count');
            if (bell) {
                bell.classList.remove('active');
            }
            if (notificationCount) {
                notificationCount.style.display = 'none'; // Hapus teks jumlah pesan baru
            }

            // Send a request to the server to mark the notification as seen
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'action': 'mark_notification_viewed'
                })
            }).then(response => response.text()).then(() => {
                // Optionally, handle the response here if needed
                // Remove the bell icon from the page
                if (bell) {
                    bell.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
