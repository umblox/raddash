<?php 
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "header.php"; 
require_once "../config/database.php";

// Cek jika ada request untuk menghapus user
if (isset($_GET['delete_id'])) {
    $id = htmlspecialchars($_GET["delete_id"]);
    $sql = "DELETE FROM users WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: users.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'> Data Gagal dihapus. " . mysqli_error($conn) . "</div>";
    }
}

// Cek jika ada request POST untuk edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = htmlspecialchars($_POST['id']);
    $username = htmlspecialchars($_POST['username']);
    $telegram_id = htmlspecialchars($_POST['telegram_id']);
    $whatsapp_number = htmlspecialchars($_POST['whatsapp_number']);
    $balance = htmlspecialchars($_POST['balance']) ?: 0;

    // Mengubah format nomor WhatsApp
    if (preg_match('/^08/', $whatsapp_number)) {
        $whatsapp_number = '62' . substr($whatsapp_number, 1);
    } elseif (preg_match('/^\+628/', $whatsapp_number)) {
        $whatsapp_number = '62' . substr($whatsapp_number, 3);
    }

    $sql = "UPDATE users SET username='$username', telegram_id=" . ($telegram_id ? "'$telegram_id'" : "NULL") . ", whatsapp_number=" . ($whatsapp_number ? "'$whatsapp_number'" : "NULL") . ", balance='$balance' WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: users.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'> Gagal memperbarui data. " . mysqli_error($conn) . "</div>";
    }
}

// Cek jika ada request POST untuk menambah user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = htmlspecialchars($_POST['username']);
    $telegram_id = htmlspecialchars($_POST['telegram_id']);
    $whatsapp_number = htmlspecialchars($_POST['whatsapp_number']);
    $balance = htmlspecialchars($_POST['balance']) ?: 0;
    $password = $_POST['password'];

    // Mengubah format nomor WhatsApp
    if (preg_match('/^08/', $whatsapp_number)) {
        $whatsapp_number = '62' . substr($whatsapp_number, 1);
    } elseif (preg_match('/^\+628/', $whatsapp_number)) {
        $whatsapp_number = '62' . substr($whatsapp_number, 3);
    }

    if (strlen($password) < 6) {
        echo "<div class='alert alert-danger'> Password harus minimal 6 karakter.</div>";
    } else {
        $sql = "INSERT INTO users (username, telegram_id, whatsapp_number, balance, password) VALUES ('$username', " . ($telegram_id ? "'$telegram_id'" : "NULL") . ", " . ($whatsapp_number ? "'$whatsapp_number'" : "NULL") . ", '$balance', '$password')";
        if (mysqli_query($conn, $sql)) {
            header("Location: users.php");
            exit;
        } else {
            echo "<div class='alert alert-danger'> Gagal menambah user. " . mysqli_error($conn) . "</div>";
        }
    }
}

// Pagination
$limit = 15; // Jumlah user per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit; // Hitung offset

// Ambil total jumlah user
$total_sql = "SELECT COUNT(*) AS total FROM users";
$total_result = mysqli_query($conn, $total_sql);
$total_data = mysqli_fetch_assoc($total_result);
$total_users = $total_data['total'];
$total_pages = ceil($total_users / $limit); // Hitung total halaman

// Ambil data pengguna dengan pagination
$sql = "SELECT * FROM users ORDER BY username ASC LIMIT $start, $limit";
$hasil = mysqli_query($conn, $sql);
?>

<body>
    <div class="container">
        <br>
        <h4><center>DAFTAR USER</center></h4>

        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addUserModal">Tambah User</button>

        <table class="my-3 table table-bordered table-hover table-striped">
            <thead>
                <tr class="table-primary">
                    <th>No</th>
                    <th>Username</th>
                    <th>Telegram</th>
                    <th>No Whatsapp</th>
                    <th>Saldo</th>
                    <th colspan='2'>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $start + 1; // Menentukan nomor urut
                while ($data = mysqli_fetch_array($hasil)) {
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo htmlspecialchars($data["username"]); ?></td>
                    <td><?php echo htmlspecialchars($data["telegram_id"]); ?></td>
                    <td><?php echo htmlspecialchars($data["whatsapp_number"]); ?></td>
                    <td><?php echo htmlspecialchars($data["balance"]); ?></td>
                    <td>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#editModal<?php echo $data['id']; ?>">Edit</button>
                        <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $data['id']; ?>">Delete</button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="telegram_id">Telegram ID (opsional)</label>
                                        <input type="text" class="form-control" name="telegram_id" value="<?php echo htmlspecialchars($data['telegram_id']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="whatsapp_number">No Whatsapp (opsional)</label>
                                        <input type="text" class="form-control" name="whatsapp_number" value="<?php echo htmlspecialchars($data['whatsapp_number']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="balance">Saldo (default 0 jika dikosongkan)</label>
                                        <input type="number" class="form-control" name="balance" value="<?php echo htmlspecialchars($data['balance']); ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="edit_user" class="btn btn-primary">Edit User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?php echo $data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus User</h5>
                            </div>
                            <div class="modal-body">
                                Apakah Anda yakin ingin menghapus user <strong><?php echo htmlspecialchars($data["username"]); ?></strong>?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <a href="?delete_id=<?php echo $data['id']; ?>" class="btn btn-danger">Hapus</a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $no++;
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Selanjutnya</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Tambah User</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="telegram_id">Telegram ID (opsional)</label>
                            <input type="text" class="form-control" name="telegram_id">
                        </div>
                        <div class="form-group">
                            <label for="whatsapp_number">No Whatsapp (opsional)</label>
                            <input type="text" class="form-control" name="whatsapp_number">
                        </div>
                        <div class="form-group">
                            <label for="balance">Saldo (default 0 jika dikosongkan)</label>
                            <input type="number" class="form-control" name="balance">
                        </div>
                        <div class="form-group">
                            <label for="password">Password (minimal 6 karakter)</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Tambah User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
ob_end_flush();
include "admin_footer.php"; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>
