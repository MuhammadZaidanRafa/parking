<?php
session_start();
require_once "db.php";

// Cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Parkir</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f4f6f9;
}

header{
    background:#007bff;
    color:white;
    padding:20px;
}

.container{
    width:90%;
    margin:30px auto;
}

.card{
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0px 0px 10px rgba(0,0,0,.1);
    margin-bottom:20px;
}

.menu{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.box{
    background:white;
    border-radius:10px;
    padding:25px;
    text-align:center;
    text-decoration:none;
    color:black;
    box-shadow:0px 3px 8px rgba(0,0,0,.1);
    transition:.3s;
}

.box:hover{
    background:#007bff;
    color:white;
    transform:scale(1.05);
}

.logout{
    display:inline-block;
    padding:10px 20px;
    background:red;
    color:white;
    text-decoration:none;
    border-radius:5px;
}

</style>

</head>
<body>

<header>

<h2>Aplikasi Parkir</h2>

<p>Selamat datang,
<b><?php echo htmlspecialchars($nama); ?></b>

(
<?php echo strtoupper($role); ?>

)

</p>

</header>

<div class="container">

<div class="card">

<h3>Dashboard</h3>

<p>Silakan pilih menu di bawah ini.</p>

</div>

<div class="menu">

<?php

// ======================= PENGGUNA =======================
if($role=="pengguna"){
?>
<a href="riwayat.php" class="box">
<h3>🕒 Riwayat Parkir</h3>
Lihat Riwayat Parkir Anda
</a>
<a href="kendaraan_saya.php" class="box">
<h3>🚗 Kendaraan Saya</h3>
Kelola Data Kendaraan Anda
</a>
<a href="profil.php" class="box">
<h3>👤 Profil</h3>
Kelola Profil Akun
</a>
<?php
}

// ======================= ADMIN =======================
if($role=="admin"){
?>

<a href="user.php" class="box">
<h3>👤 User</h3>
Kelola User
</a>

<a href="tarif.php" class="box">
<h3>💰 Tarif</h3>
Kelola Tarif
</a>

<a href="transaksi.php" class="box">
<h3>🎫 Transaksi</h3>
Parkir Masuk / Keluar
</a>

<a href="area.php" class="box">
<h3>🅿 Area Parkir</h3>
Kelola Area
</a>

<a href="kendaraan.php" class="box">
<h3>🚗 Kendaraan</h3>
Kelola Kendaraan
</a>

<a href="log.php" class="box">
<h3>📋 Log Aktivitas</h3>
Lihat Log
</a>

<?php
}

// ======================= PETUGAS =======================
if($role=="petugas"){
?>

<a href="transaksi.php" class="box">
<h3>🎫 Transaksi</h3>
Parkir Masuk / Keluar
</a>

<a href="struk.php" class="box">
<h3>🖨 Cetak Struk</h3>
Cetak Bukti Parkir
</a>

<?php
}

// ======================= OWNER =======================
if($role=="owner"){
?>

<a href="laporan.php" class="box">
<h3>📊 Rekap Transaksi</h3>
Laporan Parkir
</a>

<?php
}

?>

</div>

<br>

<a href="logout.php" class="logout">
Logout
</a>

</div>

</body>
</html>