<?php
session_start();

// Simpan role sebelum session dihancurkan
$role = $_SESSION['role'] ?? '';

// Hapus semua session
session_unset();
session_destroy();

// Arahkan sesuai role
if ($role == "pengguna") {
    header("Location: login_pengguna.php");
} else {
    header("Location: login.php");
}

exit;
?>