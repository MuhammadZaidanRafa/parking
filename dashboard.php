<?php
session_start();
require_once "db.php";

// Cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];

// Router: arahkan user ke file dashboard sesuai role masing-masing
switch ($role) {
    case 'admin':
        header("Location: dashboard_admin.php");
        break;
    case 'petugas':
        header("Location: dashboard_petugas.php");
        break;
    case 'owner':
        header("Location: dashboard_owner.php");
        break;
    case 'pengguna':
        header("Location: dashboard_pengguna.php");
        break;
    default:
        // Role tidak dikenali / tidak valid -> paksa logout demi keamanan
        session_destroy();
        header("Location: login.php");
        break;
}
exit;
