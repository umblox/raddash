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
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/www/radiusbilling/views/header.php';
require_once '/www/radiusbilling/config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi password
    if (strlen($password) < 6) {
        $error = 'Password harus memiliki minimal 6 karakter.';
    } else {
        // Koneksi ke database
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 0)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $success = 'Registrasi berhasil! Silakan login dalam waktu <span id="countdown">5</span> detik.';
        } else {
            $error = "Gagal mendaftar. Silakan coba lagi.";
        }
        $stmt->close();
        $conn->close();
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="/radiusbilling/css/styles.css">
    <style>
        #countdown {
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
    <h1>Register</h1>
    <?php if ($success) { echo "<p style='color: green;'>$success</p>"; } ?>
    <?php if ($error) { echo "<p style='color: red;'>$error</p>"; } ?>
    <form method="post" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>

    <script>
        // Countdown timer
        var countdownElement = document.getElementById('countdown');
        var countdownTime = 5; // Time in seconds

        function updateCountdown() {
            if (countdownTime <= 0) {
                window.location.href = 'login.php';
            } else {
                countdownElement.textContent = countdownTime;
                countdownTime--;
                setTimeout(updateCountdown, 1000); // Update every second
            }
        }

        if (countdownElement) {
            updateCountdown();
        }
    </script>
</body>
</html>
