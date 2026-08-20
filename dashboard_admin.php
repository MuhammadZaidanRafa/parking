<?php
session_start();
require_once "db.php";

// Cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'] ?? 'User';
$role = $_SESSION['role'] ?? '';

// Proteksi: hanya role admin yang boleh akses halaman ini
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Parkir</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f6f9;
    display: flex;
    min-height: 100vh;
}

/* Sidebar Navigation */
.sidebar {
    width: 250px;
    background: #1e293b;
    color: #fff;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
}

.sidebar .brand {
    padding: 20px;
    font-size: 20px;
    font-weight: bold;
    background: #0f172a;
    border-bottom: 1px solid #334155;
    text-align: center;
}

.sidebar .user-info {
    padding: 15px 20px;
    background: #1e293b;
    border-bottom: 1px solid #334155;
    font-size: 13px;
    color: #94a3b8;
}

.sidebar .user-info b {
    color: #fff;
    display: block;
    font-size: 15px;
    margin-top: 3px;
}

.sidebar .nav-links {
    list-style: none;
    padding: 15px 0;
    flex-grow: 1;
    overflow-y: auto;
}

.sidebar .nav-links li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 14px;
    transition: 0.2s;
}

.sidebar .nav-links li a:hover,
.sidebar .nav-links li a.active {
    background: #007bff;
    color: #fff;
}

.sidebar .logout-container {
    padding: 15px 20px;
    border-top: 1px solid #334155;
}

.sidebar .logout-btn {
    display: block;
    width: 100%;
    padding: 10px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
    transition: 0.2s;
}

.sidebar .logout-btn:hover {
    background: #bd2130;
}

/* Main Content Area */
.main-content {
    margin-left: 250px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

header {
    background: #007bff;
    color: white;
    padding: 20px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.container {
    padding: 30px;
    flex: 1;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0,0,0,.08);
    margin-bottom: 25px;
}

.menu {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.box {
    background: white;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    text-decoration: none;
    color: #333;
    box-shadow: 0px 3px 8px rgba(0,0,0,.08);
    transition: .3s;
    border: 1px solid #e2e8f0;
}

.box h3 {
    margin-bottom: 8px;
}

.box:hover {
    background: #007bff;
    color: white;
    transform: translateY(-4px);
    border-color: #007bff;
}

/* Responsive Handling */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }
    .sidebar {
        width: 100%;
        position: relative;
    }
    .main-content {
        margin-left: 0;
    }
}
</style>

</head>
<body>

<!-- Sidebar Navigasi -->
<aside class="sidebar">
    <div class="brand">
        🅿️ E-Parkir Admin
    </div>
    <div class="user-info">
        Role: <b><?php echo strtoupper($role); ?></b>
        User: <b><?php echo htmlspecialchars($nama); ?></b>
    </div>
    <ul class="nav-links">
        <li><a href="index.php" class="active"><span>🏠</span> Landing Page</a></li>
        <li><a href="admin/user.php"><span>👤</span> Kelola User</a></li>
        <li><a href="admin/tarif.php"><span>💰</span> Kelola Tarif</a></li>
        <li><a href="admin/transaksi.php"><span>🎫</span> Transaksi Parkir</a></li>
        <li><a href="admin/area.php"><span>🅿️</span> Area Parkir</a></li>
        <li><a href="admin/kendaraan.php"><span>🚗</span> Kelola Kendaraan</a></li>
        <li><a href="admin/log.php"><span>📋</span> Log Aktivitas</a></li>
    </ul>
    <div class="logout-container">
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</aside>

<!-- Konten Utama -->
<div class="main-content">

    <header>
        <h2>Aplikasi Parkir</h2>
        <p>Selamat datang, <b><?php echo htmlspecialchars($nama); ?></b> (<?php echo strtoupper($role); ?>)</p>
    </header>

    <div class="container">

        <div class="card">
            <h3>Dashboard Admin</h3>
            <p>Silakan pilih menu navigasi di sebelah kiri atau melalui ringkasan di bawah ini.</p>
        </div>

        <div class="menu">

            <a href="admin/user.php" class="box">
                <h3>👤 User</h3>
                Kelola User
            </a>

            <a href="admin/tarif.php" class="box">
                <h3>💰 Tarif</h3>
                Kelola Tarif
            </a>

            <a href="admin/transaksi.php" class="box">
                <h3>🎫 Transaksi</h3>
                Parkir Masuk / Keluar
            </a>

            <a href="admin/area.php" class="box">
                <h3>🅿 Area Parkir</h3>
                Kelola Area
            </a>

            <a href="admin/kendaraan.php" class="box">
                <h3>🚗 Kendaraan</h3>
                Kelola Kendaraan
            </a>

            <a href="admin/log.php" class="box">
                <h3>📋 Log Aktivitas</h3>
                Lihat Log
            </a>

        </div>

    </div>

</div>

</body>
</html>