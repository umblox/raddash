<?php session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect jika pengguna belum login
if (!isset($_SESSION['username'])) {
    header("Location: /radiusbilling/views/login.php");
    exit();
}

include '/www/radiusbilling/views/header.php';
require '/www/radiusbilling/config/database.php';  // Menghubungkan dengan konfigurasi database
require '/www/radiusbilling/config/prefix.php';    // Menghubungkan dengan konfigurasi prefix voucher

// Mengaktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mengambil username dari session jika sudah ada
$telegram_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Fungsi untuk membuat kode voucher dengan prefix yang sesuai
function generate_voucher_code($planName, $connection) {
    do {
        $prefix = getVoucherPrefix($planName); // Mengambil prefix dari file konfigurasi
        $random_part = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 5);
        $voucher_code = $prefix . $random_part;

        // Cek apakah kode voucher sudah ada di tabel radcheck
        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM radcheck WHERE username = ?");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('s', $voucher_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    } while ($row['count'] > 0); // Ulangi jika kode sudah ada

    return $voucher_code;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : '';

$connection = getDbConnection();

if ($action == 'confirm' && !empty($plan_id)) {
    // Ambil informasi paket
    $stmt = $connection->prepare("SELECT id, planName, planCost FROM billing_plans WHERE id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $plan = $result->fetch_assoc();

        echo '<h1>Konfirmasi Pembelian</h1>';
        echo 'Paket yang Anda pilih: ' . htmlspecialchars($plan['planName']) . '<br>';
        echo 'Harga: ' . htmlspecialchars($plan['planCost']) . '<br>';
        echo '<a href="purchase.php?action=purchase&plan_id=' . urlencode($plan_id) . '">Konfirmasi</a> | ';
        echo '<a href="purchase.php">Batal</a><br><br><br><br><br><br><br><br><br><br><br><br>';
        // Tambahkan tombol kembali ke dashboard di sini
        echo '<a href="/radiusbilling/views/dashboard.php">Kembali ke Dashboard</a>';
    } else {
        echo 'Paket tidak ditemukan.';
    }

    $stmt->close();
    $connection->close();
} elseif ($action == 'purchase' && !empty($plan_id) && !empty($telegram_username)) {
    $connection->autocommit(FALSE); // Mulai transaksi

    // Ambil informasi paket
    $stmt = $connection->prepare("SELECT planName, planCost FROM billing_plans WHERE id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();

    if (!$plan) {
        echo 'Paket tidak ditemukan.<br>';
        $connection->rollback(); // Batalkan transaksi
        $stmt->close();
        $connection->close();
        exit();
    }

    // Periksa saldo pengguna berdasarkan username
    $stmt = $connection->prepare("SELECT balance FROM users WHERE username = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('s', $telegram_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo 'Pengguna tidak ditemukan.<br>';
        $connection->rollback(); // Batalkan transaksi
        $stmt->close();
        $connection->close();
        exit();
    } elseif ($user['balance'] < $plan['planCost']) {
        echo 'Saldo Anda tidak mencukupi.<br>';
        echo 'Saldo saat ini: ' . htmlspecialchars($user['balance']) . '<br>';
        $connection->rollback(); // Batalkan transaksi
        $stmt->close();
        $connection->close();
        exit();
    } else {
        $new_balance = $user['balance'] - $plan['planCost'];

        // Update saldo pengguna
        $stmt = $connection->prepare("UPDATE users SET balance = ? WHERE username = ?");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('ds', $new_balance, $telegram_username);
        $stmt->execute();

        // Generate voucher code tanpa duplikasi
        $voucher_code = generate_voucher_code($plan['planName'], $connection);

        // Insert voucher data ke radcheck
        $stmt = $connection->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Auth-Type', ':=', 'Accept')");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('s', $voucher_code);
        $stmt->execute();

        // Insert ke radusergroup
        $stmt = $connection->prepare("INSERT INTO radusergroup (username, groupname, priority) VALUES (?, ?, 1)");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('ss', $voucher_code, $plan['planName']);
        $stmt->execute();

        // Insert ke userinfo
        $creation_date = date('Y-m-d H:i:s');
        $creationby_value = $telegram_username . '@RadiusBilling';
        $stmt = $connection->prepare("INSERT INTO userinfo (username, creationdate, creationby) VALUES (?, ?, ?)");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('sss', $voucher_code, $creation_date, $creationby_value);
        $stmt->execute();

        // Insert ke userbillinfo
        $purchase_date = date('Y-m-d H:i:s');
        $stmt = $connection->prepare("INSERT INTO userbillinfo (username, planName, paymentmethod, cash, creationdate, creationby) VALUES (?, ?, 'cash', ?, ?, ?)");
        if (!$stmt) {
            die('Prepare failed: ' . $connection->error);
        }
        $stmt->bind_param('sssss', $voucher_code, $plan['planName'], $plan['planCost'], $purchase_date, $creationby_value);
        $stmt->execute();

        $connection->commit(); // Selesaikan transaksi

        // URL Login Voucher
        $login_url = "http://10.10.10.1:3990/login?username=" . urlencode($voucher_code) . "&password=Accept";

        // Tampilkan pesan sukses dan tombol kembali
        echo '<h2>Voucher Anda telah dibuat!</h2>';
        echo 'Menggunakan username: ' . htmlspecialchars($telegram_username) . '<br>';
        echo 'Voucher Anda: ' . htmlspecialchars($voucher_code) . '<br>';
        echo 'Sisa saldo Anda sekarang: ' . htmlspecialchars($new_balance) . '<br>';
        echo '<a href="' . htmlspecialchars($login_url) . '">Login</a><br><br>';
        echo '<a href="/radiusbilling/views/dashboard.php">Kembali ke Dashboard</a>';
    }

    $stmt->close();
    $connection->close();
} else {
    // Periksa saldo pengguna berdasarkan username
    $stmt = $connection->prepare("SELECT balance FROM users WHERE username = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('s', $telegram_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $balance = $user ? $user['balance'] : 0;

    // Tampilkan sisa saldo di bagian atas daftar paket
    echo '<div style="font-size: 24px; font-weight: bold; color: #4CAF50; margin-bottom: 20px;">';
    echo 'Sisa Saldo Anda: ' . htmlspecialchars($balance) . ' Kredit';
    echo '</div>';

    // Menampilkan paket yang tersedia
    $query = "SELECT id, planName, planCost FROM billing_plans WHERE planCost > 0";
    $result = $connection->query($query);

    if ($result->num_rows > 0) {
        echo '<h2>Pilih Paket yang Ingin Dibeli</h2>';
        while ($row = $result->fetch_assoc()) {
            echo '<div>';
            echo htmlspecialchars($row['planName']) . ' - ' . htmlspecialchars($row['planCost']) . ' Kredit<br>';
            echo '<a href="purchase.php?action=confirm&plan_id=' . urlencode($row['id']) . '">Beli</a>';
            echo '</div>';
        }
    } else {
        echo 'Tidak ada paket yang tersedia.';
    }

    echo '<h2>Daftar Voucher Terakhir</h2>';

    // Tampilkan daftar 3 voucher terakhir yang dibeli berdasarkan creationby
    $stmt = $connection->prepare("SELECT username, creationdate FROM userinfo WHERE creationby = ? ORDER BY creationdate DESC LIMIT 3");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $createdby_value = $telegram_username . '@RadiusBilling';
    $stmt->bind_param('s', $createdby_value);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<ul>';
        while ($row = $result->fetch_assoc()) {
            echo '<li>Voucher: ' . htmlspecialchars($row['username']) . ' - Tanggal: ' . htmlspecialchars($row['creationdate']) . '</li>';
        }
        echo '</ul>';
    } else {
        echo 'Tidak ada voucher terakhir.';
    }

    $stmt->close();
    $connection->close();
    // Tambahkan tombol kembali ke dashboard di sini
    echo '<a href="/radiusbilling/views/dashboard.php">Kembali ke Dashboard</a>';
}
?>