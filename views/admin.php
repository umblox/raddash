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
ob_start(); // Buffer output

include '/www/raddash/views/header.php';
require_once '/www/raddash/config/database.php';

// Cek apakah pengguna sudah login dan memiliki akses admin
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
} elseif ($_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger text-center'>Hanya admin yang bisa mengakses halaman ini.</div>";
    echo "<a href='logout.php' class='btn btn-danger'>Logout</a>";
    echo "<a href='dashboard.php' class='btn btn-primary'>Kembali ke Dashboard</a>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <script>
function checkPendingTopups() {
    fetch('/raddash/transactions/check_pending_topups.php')
        .then(response => response.json())
        .then(data => {
            console.log(data); // Tambahkan log ini untuk melihat data yang diterima dari server
            const pendingCount = data.pendingCount;
            const notification = document.getElementById('pendingNotification');
            const viewTopupButton = document.getElementById('viewTopupButton');

            if (pendingCount > 0) {
                notification.innerHTML = `You have ${pendingCount} pending top-up request(s).`;
                notification.style.display = 'block';
                viewTopupButton.style.display = 'block'; // Menampilkan tombol jika ada request pending
            } else {
                notification.innerHTML = 'No pending top-up requests at the moment.';
                notification.style.display = 'block';
                viewTopupButton.style.display = 'none'; // Sembunyikan tombol jika tidak ada request pending
            }
        })
        .catch(error => console.error('Error:', error));
}
        // Lakukan polling setiap 10 detik
        setInterval(checkPendingTopups, 10000);

        // Panggil pertama kali saat halaman diload
        window.onload = checkPendingTopups;
    </script>
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
                        <p id="pendingNotification" style="display:none;"></p>
                        <a href="/raddash/transactions/topup.php" id="viewTopupButton" class="btn btn-primary" style="display:none;">View Top-Up Requests</a>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/raddash/views/profile.php" class="btn btn-info">Profile</a>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
ob_end_flush(); // Flush output buffer
?>
