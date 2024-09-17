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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = getDbConnection();

if (!$db) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: /radiusbilling/views/login.php');
    exit();
}

// Ambil informasi pengguna dari session
$username = $_SESSION['username'];

// Periksa apakah pengguna adalah admin
$isAdmin = false;
$query = 'SELECT is_admin FROM users WHERE username = ?';
$stmt = $db->prepare($query);
if ($stmt === false) {
    die('Error prepare statement: ' . $db->error);
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();
$isAdmin = $is_admin == 1;

// Fungsi untuk mendapatkan saldo pengguna
function getUserBalance($username) {
    global $db;

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

    return $balance;
}

// Fungsi untuk mengirim notifikasi (implementasikan sesuai dengan kebutuhan Anda)
function sendNotification($username, $message) {
    // Implementasikan pengiriman notifikasi sesuai dengan sistem yang Anda gunakan
}

// Tangani permintaan top-up jika POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdmin && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);

    // Daftar jumlah top-up default
    $defaultAmounts = [3000, 5000, 10000, 20000, 50000, 100000];

    if (!in_array($amount, $defaultAmounts)) {
        $_SESSION['status_message'] = "Jumlah top-up tidak valid. Pilih jumlah yang sesuai.";
        header('Location: /radiusbilling/transactions/topup.php');
        exit();
    }

    // Cek apakah ada permintaan top-up yang belum dikonfirmasi dalam 1 hari terakhir
    $query = 'SELECT COUNT(*) FROM topup_requests WHERE username = ? AND amount = ? AND status = "pending" AND created_at >= NOW() - INTERVAL 1 DAY';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        die('Error prepare statement: ' . $db->error);
    }
    $stmt->bind_param('sd', $username, $amount);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['status_message'] = "Anda sudah memiliki permintaan top-up yang menunggu konfirmasi untuk jumlah ini.";
        header('Location: /radiusbilling/transactions/topup.php');
        exit();
    }

    // Ambil user_id dari username
    $query = 'SELECT id FROM users WHERE username = ?';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        die('Error prepare statement: ' . $db->error);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Masukkan permintaan top-up baru
    $query = 'INSERT INTO topup_requests (user_id, username, amount, status) VALUES (?, ?, ?, "pending")';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        die('Error prepare statement: ' . $db->error);
    }
    $stmt->bind_param('isd', $user_id, $username, $amount);
    $stmt->execute();
    $stmt->close();

    $_SESSION['status_message'] = "Permintaan top-up sebesar $amount kredit sedang menunggu konfirmasi admin.";
    header('Location: /radiusbilling/transactions/topup.php');
    exit();
}

// Jika admin, tangani konfirmasi atau penolakan top-up
if ($isAdmin && isset($_GET['action']) && isset($_GET['username']) && isset($_GET['amount'])) {
    $action = $_GET['action'];
    $username = $_GET['username'];
    $amount = floatval($_GET['amount']);

    if ($action === 'confirm') {
        // Cek apakah permintaan top-up ada
        $query = 'SELECT user_id FROM topup_requests WHERE username = ? AND amount = ? AND status = "pending"';
        $stmt = $db->prepare($query);
        if ($stmt === false) {
            die('Error prepare statement: ' . $db->error);
        }
        $stmt->bind_param('sd', $username, $amount);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        if ($user_id) {
            // Tambahkan saldo pengguna
            $query = 'UPDATE users SET balance = balance + ? WHERE username = ?';
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                die('Error prepare statement: ' . $db->error);
            }
            $stmt->bind_param('ds', $amount, $username);
            $stmt->execute();
            $stmt->close();

            // Ubah status permintaan top-up
            $query = 'UPDATE topup_requests SET status = "confirmed" WHERE username = ? AND amount = ? AND status = "pending"';
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                die('Error prepare statement: ' . $db->error);
            }
            $stmt->bind_param('sd', $username, $amount);
            $stmt->execute();
            $stmt->close();

            // Kirim notifikasi ke pelanggan
            $message = "Permintaan top-up Anda sebesar $amount telah dikonfirmasi. Saldo Anda saat ini adalah " . getUserBalance($username);
            sendNotification($username, $message);

            $_SESSION['status_message'] = "Top-up untuk pengguna @$username sebesar $amount telah dikonfirmasi.";
        } else {
            $_SESSION['status_message'] = "Data top-up tidak ditemukan atau sudah diproses.";
        }
        header('Location: /radiusbilling/views/admin.php');
        exit();
    } elseif ($action === 'reject') {
        // Cek apakah permintaan top-up ada
        $query = 'SELECT amount FROM topup_requests WHERE username = ? AND amount = ? AND status = "pending"';
        $stmt = $db->prepare($query);
        if ($stmt === false) {
            die('Error prepare statement: ' . $db->error);
        }
        $stmt->bind_param('sd', $username, $amount);
        $stmt->execute();
        $stmt->bind_result($amount_found);
        $stmt->fetch();
        $stmt->close();

        if ($amount_found) {
            // Ubah status permintaan top-up
            $query = 'UPDATE topup_requests SET status = "rejected" WHERE username = ? AND amount = ? AND status = "pending"';
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                die('Error prepare statement: ' . $db->error);
            }
            $stmt->bind_param('sd', $username, $amount);
            $stmt->execute();
            $stmt->close();

            // Kirim notifikasi ke pelanggan
            $message = "Permintaan top-up Anda sebesar $amount telah ditolak. Saldo Anda tetap " . getUserBalance($username);
            sendNotification($username, $message);

            $_SESSION['status_message'] = "Top-up untuk pengguna @$username sebesar $amount telah ditolak.";
        } else {
            $_SESSION['status_message'] = "Data top-up tidak ditemukan atau sudah diproses.";
        }
        header('Location: /radiusbilling/views/admin.php');
        exit();
    } else {
        $_SESSION['status_message'] = "Aksi tidak dikenal atau Anda tidak memiliki izin.";
        header('Location: /radiusbilling/views/admin.php');
        exit();
    }
}

// Ambil saldo pengguna jika bukan admin
$pendingRequest = false;
$statusMessage = '';
if (!$isAdmin) {
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

    // Cek status permintaan top-up
    $query = 'SELECT amount, status FROM topup_requests WHERE username = ? ORDER BY created_at DESC LIMIT 1';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        die('Error prepare statement: ' . $db->error);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($amount, $status);
    if ($stmt->fetch()) {
        if ($status === 'pending') {
            $pendingRequest = true;
            $statusMessage = "Permintaan top-up sebesar $amount sedang menunggu konfirmasi.";
        } elseif ($status === 'confirmed') {
            $statusMessage = "Permintaan top-up sebesar $amount telah dikonfirmasi. Saldo Anda saat ini adalah $balance.";
        } elseif ($status === 'rejected') {
            $statusMessage = "Permintaan top-up sebesar $amount telah ditolak. Saldo Anda tetap $balance.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top-Up Saldo Pelanggan Arneta.ID</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/radiusbilling/assets/css/bootstrap.min.css">
    <style>
        .topup-form-container {
            max-width: 400px; /* Membatasi lebar form */
            margin: 0 auto; /* Menempatkan form di tengah */
            padding: 20px;
            background-color: #d0efff; /* Latar belakang biru muda */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .topup-form-container label {
            font-weight: bold;
            color: blue;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <!-- Menggunakan Bootstrap class untuk styling header -->
        <div class="bg-primary text-white text-center py-4">
            <?php if ($isAdmin): ?>
                <h1 class="display-4">Permintaan Top-Up</h1>
                <?php
                $query = 'SELECT username, amount, created_at, status FROM topup_requests WHERE status = "pending" ORDER BY created_at DESC';
                $result = $db->query($query);
                if ($result->num_rows > 0): ?>
                    <table class="table table-striped table-bordered mt-4">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Jumlah</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>Rp <?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <a href="?action=confirm&username=<?php echo urlencode($row['username']); ?>&amount=<?php echo urlencode($row['amount']); ?>" class="btn btn-success btn-sm">Konfirmasi</a>
                                        <a href="?action=reject&username=<?php echo urlencode($row['username']); ?>&amount=<?php echo urlencode($row['amount']); ?>" class="btn btn-danger btn-sm">Tolak</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="mt-4">Belum ada permintaan top-up.</p>
                <?php endif; ?>
            <?php else: ?>
                <h1 class="display-4">Top Up Saldo Arneta.ID</h1>
                <p class="lead">Saldo Anda saat ini: Rp <?php echo number_format($balance, 0, ',', '.'); ?></p>
                <?php if ($pendingRequest): ?>
                    <div class="alert alert-info mt-4" role="alert">
                        <?php echo htmlspecialchars($statusMessage); ?>
                    </div>
                    <form action="/radiusbilling/views/dashboard.php" method="GET" class="mt-4">
                        <button type="submit" class="btn btn-primary">Kembali ke Dashboard</button>
                    </form>
                <?php else: ?>
    <div class="container mt-5">
        <div class="topup-form-container">
            <form action="topup.php" method="POST">
                <div class="form-group text-center">
                    <label for="amount">Jumlah Top-Up:</label>
                    <select id="amount" name="amount" class="form-control" required>
                        <option value="3000">Rp 3,000</option>
                        <option value="5000">Rp 5,000</option>
                        <option value="10000">Rp 10,000</option>
                        <option value="20000">Rp 20,000</option>
                        <option value="50000">Rp 50,000</option>
                        <option value="100000">Rp 100,000</option>
                    </select>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-custom">Kirim Permintaan</button>
                </div>
            </form>
        </div>
    </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>

    <!-- Bootstrap JS (opsional jika diperlukan interaksi JS Bootstrap) -->
    <script src="/radiusbilling/assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>
