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

// Redirect jika pengguna belum login
if (!isset($_SESSION['username'])) {
    header("Location: /raddash/views/login.php");
    exit();
}

include '/www/raddash/views/header.php';
require '/www/raddash/config/database.php';  // Menghubungkan dengan konfigurasi database
require '/www/raddash/config/prefix.php';    // Menghubungkan dengan konfigurasi prefix voucher

// Mengaktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mengambil username dari session jika sudah ada
$telegram_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Buat token transaksi dan simpan di session jika belum ada
if (!isset($_SESSION['transaction_token'])) {
    $_SESSION['transaction_token'] = bin2hex(random_bytes(32));  // Token acak
}

// Simpan token di variabel untuk digunakan
$transaction_token = $_SESSION['transaction_token'];

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

        echo '<div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">';
        echo '<h1 style="font-size: 24px; color: #333; margin-bottom: 20px;">Konfirmasi Pembelian</h1>';
        echo '<p style="font-size: 18px; color: #555;">Paket yang Anda pilih: <strong>' . htmlspecialchars($plan['planName']) . '</strong></p>';
        echo '<p style="font-size: 18px; color: #555;">Harga: <strong>' . htmlspecialchars($plan['planCost']) . '</strong></p>';
        echo '<a href="purchase.php?action=purchase&plan_id=' . urlencode($plan_id) . '&token=' . $transaction_token . '" style="display: inline-block; padding: 10px 20px; margin-right: 10px; color: #fff; background-color: #007bff; text-decoration: none; border-radius: 4px;">Konfirmasi</a>';
        echo '<a href="purchase.php" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #6c757d; text-decoration: none; border-radius: 4px;">Batal</a><br><br>';
        echo '<a href="/raddash/views/dashboard.php" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #28a745; text-decoration: none; border-radius: 4px;">Kembali ke Dashboard</a>';
        echo '</div>';
    } else {
        echo 'Paket tidak ditemukan.';
    }

    $stmt->close();
    $connection->close();
} elseif ($action == 'purchase' && !empty($plan_id) && !empty($telegram_username)) {
    // Cek token untuk mencegah double purchase
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['transaction_token']) {
    echo '<p>Token transaksi tidak valid atau telah kadaluarsa.</p>';
    echo '<p>Jika saldo Anda tidak terpotong dan voucher belum muncul, Anda bisa mengulangi proses pembelian.</p>';

    // Tombol kembali ke dashboard
    echo '<a href="/raddash/views/dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>';
        exit();
    }

    // Jika token valid, lanjutkan dengan proses transaksi
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
// Display the insufficient balance message with background
echo '<div style="background-color: #ffdddd; padding: 15px; border-radius: 8px; border: 1px solid #ff5c5c; max-width: 400px; margin: 20px auto; text-align: center;">';
echo '<p style="font-size: 16px; color: #d9534f;">Saldo Anda tidak mencukupi.</p>';
echo '<p style="font-size: 16px; color: #333;">Saldo saat ini: <strong>' . htmlspecialchars($user['balance']) . '</strong></p>';
echo '</div>';

// Rollback the transaction
$connection->rollback(); 

// Display the rollback message with consistent styling
echo '<div style="background-color: #f7f7f7; padding: 15px; border-radius: 8px; border: 1px solid #ccc; max-width: 400px; margin: 20px auto; text-align: center;">';
echo '<p style="font-size: 16px; color: #555;">Transaksi telah dibatalkan.</p>';
echo '</div>';

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
        $creationby_value = $telegram_username . '@raddash';
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
        // Set session token ke NULL untuk mencegah penggunaan ganda
        unset($_SESSION['transaction_token']);

        // URL Login Voucher
        $login_url = "http://10.10.10.1:3990/login?username=" . urlencode($voucher_code) . "&password=Accept";

// Tampilkan pesan sukses dan tombol kembali
echo '<div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; text-align: center;">';
echo '<h1 style="font-size: 24px; color: #333; margin-bottom: 20px;">Pembelian Sukses</h1>';
echo '<p style="font-size: 18px; color: #555; margin-bottom: 15px;">Klik untuk copy voucher :';
echo '<span class="voucher-text" style="font-size: 18px; color: #007bff; cursor: pointer; background-color: #e0e0e0; padding: 5px 10px; border-radius: 4px;" onclick="copyVoucherText(this)">' . htmlspecialchars($voucher_code) . '</span>';
echo '</p>';
echo '<p style="font-size: 18px; color: #555; margin-bottom: 20px;">Sisa Saldo: <strong>' . htmlspecialchars($new_balance) . '</strong></p>';
echo '<a href="' . htmlspecialchars($login_url) . '" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #007bff; text-decoration: none; border-radius: 4px; margin-right: 10px;">Login</a>';
echo '<a href="/raddash/views/dashboard.php" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #28a745; text-decoration: none; border-radius: 4px;">Kembali ke Dashboard</a>';
echo '</div>';

echo '<script>
function copyVoucherText(element) {
    var voucherText = element.innerText;
    
    // Create a temporary input element to hold the voucher text
    var tempInput = document.createElement("input");
    tempInput.style.position = "absolute";
    tempInput.style.left = "-9999px";  // Hide the input element off-screen
    tempInput.value = voucherText;
    document.body.appendChild(tempInput);
    
    // Select and copy the voucher text
    tempInput.select();
    document.execCommand("copy");
    
    // Remove the temporary input element
    document.body.removeChild(tempInput);

    // Optional: Alert or show message confirming copy action
    alert("Voucher copied: " + voucherText);
}
</script>';

    }

    $stmt->close();
    $connection->close();
} else {
    echo '<div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">';

    // Tampilkan saldo saat ini
    $stmt = $connection->prepare("SELECT balance FROM users WHERE username = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('s', $telegram_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
echo '<div style="background-color: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #cce7ff; max-width: 400px; margin: 20px auto; text-align: center;">';
echo '<p style="font-size: 18px; color: #555;">Saldo saat ini: <strong>' . htmlspecialchars($user['balance']) . '</strong></p>';
echo '</div>';

    // Tampilkan daftar paket
    $stmt = $connection->prepare("SELECT id, planName, planCost FROM billing_plans WHERE planCost > 0");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
echo '<div style="max-width: 400px; margin: 0 auto; padding: 15px;">';
echo '<h2 style="font-size: 18px; color: #333; margin-bottom: 15px; text-align: center;">Pilih Paket yang Ingin Dibeli</h2>';

while ($plan = $result->fetch_assoc()) {
    echo '<div style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 12px; background-color: #257CFD; text-align: center;">';
    echo '<p style="font-size: 14px; color: #fff; margin-bottom: 8px;">Paket: <strong>' . htmlspecialchars($plan['planName']) . '</strong></p>';
    echo '<p style="font-size: 14px; color: #fff; margin-bottom: 12px;">Harga: <strong>' . htmlspecialchars($plan['planCost']) . '</strong></p>';
    echo '<a href="purchase.php?action=confirm&plan_id=' . urlencode($plan['id']) . '" style="display: inline-block; padding: 8px 16px; color: #fff; background-color: #0ACA7D; text-decoration: none; border-radius: 4px; font-size: 14px;">Beli</a>';
    echo '</div>';
        }
    } else {
        echo 'Tidak ada paket yang tersedia.';
    }

    // Tampilkan daftar voucher terakhir
    $stmt = $connection->prepare("SELECT username, creationdate FROM userinfo WHERE creationby = ? ORDER BY creationdate DESC LIMIT 3");
    if (!$stmt) {
        die('Prepare failed: ' . $connection->error);
    }

    $createdby_value = $telegram_username . '@raddash';
    $stmt->bind_param('s', $createdby_value);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result->num_rows > 0) {
echo '<div style="max-width: 400px; margin: 0 auto; padding: 15px;">';
echo '<h2 style="font-size: 18px; color: #333; margin-bottom: 15px; text-align: center;">Tiga Voucher Terakhir Anda</h2>';
echo '<ul style="list-style-type: none; padding: 0; margin: 0;">';

while ($voucher = $result->fetch_assoc()) {
    echo '<li style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background-color: #0ACA7D; text-align: center;">';
    
    // Label for voucher
    echo '<p style="font-size: 16px; color: #ffffff; margin-bottom: 8px;">Klik untuk copy voucher </p>';
    
    // Voucher code that is clickable and copyable
    echo '<span class="voucher-text" style="font-size: 16px; color: #fff; cursor: pointer; background-color: #fff; color: #0ACA7D; padding: 5px 10px; border-radius: 5px;" onclick="copyVoucherText(this)">' . htmlspecialchars($voucher['username']) . '</span>';
    
    // Date and spacing
    echo '<p style="font-size: 14px; color: #ffffff; margin-top: 10px;">Tanggal beli : <strong>' . htmlspecialchars($voucher['creationdate']) . '</strong></p>';
    echo '</li>';
}

echo '</ul>';
echo '</div>';

echo '<script>
function copyVoucherText(element) {
    var voucherText = element.innerText;
    
    // Create a temporary input element to hold the voucher text
    var tempInput = document.createElement("input");
    tempInput.style.position = "absolute";
    tempInput.style.left = "-9999px";  // Hide the input element off-screen
    tempInput.value = voucherText;
    document.body.appendChild(tempInput);
    
    // Select and copy the voucher text
    tempInput.select();
    document.execCommand("copy");
    
    // Remove the temporary input element
    document.body.removeChild(tempInput);

    // Optional: Alert or show message confirming copy action
    alert("Voucher copied: " + voucherText);
}
</script>';

    } else {
        echo 'Tidak ada voucher terbaru.';
    }

    echo '<br>';
    echo '<a href="/raddash/views/dashboard.php" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #257CFD; text-decoration: none; border-radius: 4px;">Kembali ke Dashboard</a>';
    echo '</div>';

    $stmt->close();
    $connection->close();
}
?>
