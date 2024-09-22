<?php
// test_notify.php

require_once '../functions.php';

// Mengirim pesan percobaan ke admin
$message = "Ini adalah pesan percobaan untuk admin.";
notifyAdmins($message);
echo "Pesan percobaan dikirim ke admin.\n";
?>
