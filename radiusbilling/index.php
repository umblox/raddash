<?php
session_start();

// Jika pengguna belum login, arahkan ke halaman login
if (!isset($_SESSION['username'])) {
    header('Location: views/login.php');
    exit();
}

// Ambil peran pengguna dari sesi
$role = $_SESSION['role'] ?? '';

// Menentukan URI yang diminta
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Arahkan pengguna ke halaman yang sesuai berdasarkan perannya
if ($role === 'admin') {
    if ($uri === '/radiusbilling/') {
        header('Location: views/admin.php'); // Arahkan ke halaman admin
        exit();
    } elseif ($uri === '/radiusbilling/transactions/topup.php') {
        include 'views/topup_form.php';
    } elseif ($uri === '/radiusbilling/transactions/purchase.php') {
        include 'views/purchase_form.php';
    } else {
        echo "404 Not Found";
    }
} elseif ($role === 'customer') {
    if ($uri === '/radiusbilling/') {
        header('Location: views/dashboard.php'); // Arahkan ke halaman pelanggan
        exit();
    } elseif ($uri === '/radiusbilling/transactions/topup.php') {
        include 'views/topup_form.php';
    } elseif ($uri === '/radiusbilling/transactions/purchase.php') {
        include 'views/purchase_form.php';
    } else {
        echo "404 Not Found";
    }
} else {
    echo "404 Not Found";
}
?>
