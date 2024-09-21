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

// Mulai session untuk pelanggan
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: /raddash/views/login.php');
    exit();
}

// Ambil username dari session
$username = $_SESSION['username'];

// Koneksi ke database
require_once '/www/raddash/config/database.php'; // Pastikan path ini benar

// Inisialisasi variabel
$telegram_id = '';
$password = '';
$whatsapp_number = '';
$error_message = '';
$success_message = '';

// Dapatkan koneksi database
$conn = getDbConnection();

// Ambil data pengguna dari database berdasarkan username
$query = "SELECT telegram_id, password, whatsapp_number FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error prepare statement: ' . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $telegram_id = $user['telegram_id'];
    $password = $user['password'];
    $whatsapp_number = $user['whatsapp_number'];
}

// Proses jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['field']) && isset($_POST['value'])) {
    $field = $_POST['field'];
    $new_value = trim($_POST['value']);

    // Validasi dan update berdasarkan field
    switch ($field) {
        case 'username':
            if (empty($new_value)) {
                $error_message = "Username wajib diisi.";
            } else {
                // Update username
                $update_query = "UPDATE users SET username = ? WHERE username = ?";
                $stmt = $conn->prepare($update_query);
                if ($stmt === false) {
                    die('Error prepare statement: ' . $conn->error);
                }
                $stmt->bind_param("ss", $new_value, $username);
                if ($stmt->execute()) {
                    $success_message = "Username berhasil diperbarui!";
                    $_SESSION['username'] = $new_value; // Update session username
                    $username = $new_value;
                } else {
                    $error_message = "Terjadi kesalahan saat memperbarui username. Silakan coba lagi. Error: " . $stmt->error;
                }
                $stmt->close();
            }
            break;
        case 'password':
            if (!empty($new_value)) {
                // Simpan password sebagai plaintext (tidak direkomendasikan)
                $update_query = "UPDATE users SET password = ? WHERE username = ?";
                $stmt = $conn->prepare($update_query);
                if ($stmt === false) {
                    die('Error prepare statement: ' . $conn->error);
                }
                $stmt->bind_param("ss", $new_value, $username);
                if ($stmt->execute()) {
                    $success_message = "Password berhasil diperbarui!";
                    $password = $new_value;
                } else {
                    $error_message = "Terjadi kesalahan saat memperbarui password. Silakan coba lagi. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Password baru tidak boleh kosong.";
            }
            break;
        case 'telegram_id':
            // Telegram ID bisa kosong (opsional)
            $update_query = "UPDATE users SET telegram_id = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);
            if ($stmt === false) {
                die('Error prepare statement: ' . $conn->error);
            }
            $stmt->bind_param("ss", $new_value, $username);
            if ($stmt->execute()) {
                $success_message = "Telegram ID berhasil diperbarui!";
                $telegram_id = $new_value;
            } else {
                $error_message = "Terjadi kesalahan saat memperbarui Telegram ID. Silakan coba lagi. Error: " . $stmt->error;
            }
            $stmt->close();
            break;
        case 'whatsapp_number':
            // Validasi nomor WhatsApp dan tambah awalan '62' jika perlu
            $new_whatsapp_number = preg_replace('/^08/', '628', $new_value); // Ganti '08' jadi '628'
            if (strpos($new_whatsapp_number, '62') !== 0 && !empty($new_whatsapp_number)) {
                $new_whatsapp_number = '62' . $new_whatsapp_number; // Tambah '62' jika belum ada
            }
            // Update nomor WhatsApp
            $update_query = "UPDATE users SET whatsapp_number = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);
            if ($stmt === false) {
                die('Error prepare statement: ' . $conn->error);
            }
            $stmt->bind_param("ss", $new_whatsapp_number, $username);
            if ($stmt->execute()) {
                $success_message = "Nomor WhatsApp berhasil diperbarui!";
                $whatsapp_number = $new_whatsapp_number;
            } else {
                $error_message = "Terjadi kesalahan saat memperbarui nomor WhatsApp. Silakan coba lagi. Error: " . $stmt->error;
            }
            $stmt->close();
            break;
        default:
            $error_message = "Field yang dimaksud tidak dikenali.";
            break;
    }
}

// Tutup koneksi database
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profil</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/raddash/assets/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 50px; /* Memberi ruang di atas */
            background-color: #f8f9fa; /* Warna latar belakang yang lembut */
        }
        .container {
            max-width: 800px; /* Membatasi lebar kontainer */
        }
        .form-group {
            margin-bottom: 1.5rem; /* Menambah jarak antar form group */
        }
        .btn-custom {
            margin-right: 10px;
        }
        .edit-btn {
            margin-left: 10px;
        }
        .input-display {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include '/www/raddash/views/header.php'; ?>

    <div class="container">
        <h2 class="mb-4 text-center">Update Profil</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Username Telegram -->
        <div class="form-group">
            <label for="username_display">Username Telegram <span class="text-danger">*</span>:</label>
            <div class="input-group">
                <input type="text" class="form-control input-display" id="username_display" value="<?= htmlspecialchars($username); ?>" <?= $username ? 'readonly' : 'readonly placeholder="Username Telegram belum diatur"'; ?>>
                <button type="button" class="btn btn-outline-secondary edit-btn" onclick="enableEdit('username')">Edit</button>
            </div>
            <form action="profile.php" method="POST" id="form_username" style="display: none;">
                <input type="hidden" name="field" value="username">
                <div class="input-group mt-2">
                    <input type="text" class="form-control" name="value" value="<?= htmlspecialchars($username); ?>" placeholder="Masukkan Username Telegram" required>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit('username')">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Password Baru -->
        <div class="form-group">
            <label for="password_display">Password Baru:</label>
            <div class="input-group">
                <input type="password" class="form-control input-display" id="password_display" placeholder="<?= $password ? 'Password telah diatur' : 'Password belum diatur'; ?>" readonly>
                <button type="button" class="btn btn-outline-secondary edit-btn" onclick="enableEdit('password')">Edit</button>
            </div>
            <form action="profile.php" method="POST" id="form_password" style="display: none;">
                <input type="hidden" name="field" value="password">
                <div class="input-group mt-2">
                    <input type="password" class="form-control" name="value" placeholder="Masukkan Password Baru" required>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit('password')">Cancel</button>
                </div>
                <small class="form-text text-muted">Jika tidak ingin mengubah password, biarkan kosong.</small>
            </form>
        </div>

        <!-- Telegram ID -->
        <div class="form-group">
            <label for="telegram_id_display">Telegram ID (Opsional):</label>
            <div class="input-group">
                <input type="text" class="form-control input-display" id="telegram_id_display" value="<?= htmlspecialchars($telegram_id); ?>" <?= $telegram_id ? 'readonly' : 'readonly placeholder="Telegram ID belum diatur"'; ?>>
                <button type="button" class="btn btn-outline-secondary edit-btn" onclick="enableEdit('telegram_id')">Edit</button>
            </div>
            <form action="profile.php" method="POST" id="form_telegram_id" style="display: none;">
                <input type="hidden" name="field" value="telegram_id">
                <div class="input-group mt-2">
                    <input type="text" class="form-control" name="value" value="<?= htmlspecialchars($telegram_id); ?>" placeholder="Masukkan Telegram ID">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit('telegram_id')">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Nomor WhatsApp -->
        <div class="form-group">
            <label for="whatsapp_number_display">Nomor WhatsApp (Opsional):</label>
            <div class="input-group">
                <span class="input-group-text">62</span>
                <input type="text" class="form-control input-display" id="whatsapp_number_display" value="<?= htmlspecialchars(str_replace('62', '', $whatsapp_number)); ?>" <?= $whatsapp_number ? 'readonly' : 'readonly placeholder="Nomor WhatsApp belum diatur"'; ?>>
                <button type="button" class="btn btn-outline-secondary edit-btn" onclick="enableEdit('whatsapp_number')">Edit</button>
            </div>
            <form action="profile.php" method="POST" id="form_whatsapp_number" style="display: none;">
                <input type="hidden" name="field" value="whatsapp_number">
                <div class="input-group mt-2">
                    <span class="input-group-text">62</span>
                    <input type="text" class="form-control" name="value" value="<?= htmlspecialchars(str_replace('62', '', $whatsapp_number)); ?>" placeholder="81234567890">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit('whatsapp_number')">Cancel</button>
                </div>
                <small class="form-text text-muted">Masukkan nomor tanpa awalan 0, contoh: 81234567890. Kosongkan jika tidak ingin mengubah.</small>
            </form>
        </div>

        <!-- Tombol Logout dihapus sesuai permintaan -->

    </div>

    <?php include '/www/raddash/views/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="/raddash/assets/js/bootstrap.min.js"></script>
    <script>
        function enableEdit(field) {
            // Sembunyikan input display dan tombol edit
            document.getElementById(field + '_display').style.display = 'none';
            var form = document.getElementById('form_' + field);
            form.style.display = 'block';
        }

        function cancelEdit(field) {
            // Sembunyikan form edit dan tampilkan kembali input display dan tombol edit
            var form = document.getElementById('form_' + field);
            form.style.display = 'none';
            document.getElementById(field + '_display').style.display = 'block';
        }
    </script>
</body>
</html>
