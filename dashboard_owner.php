<?php
session_start();
require_once "db.php";

// Cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Inisialisasi variabel nama dan role secara aman
$nama_lengkap = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Owner';
$nama = $nama_lengkap; 
$role = $_SESSION['role'] ?? '';

// Proteksi: hanya role owner yang boleh akses halaman ini
if ($role !== 'owner') {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Owner - Parkir</title>

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

<h3>Dashboard Owner</h3>

<p>Silakan pilih menu di bawah ini.</p>

</div>

<div class="menu">

<a href="owner/laporan.php" class="box">
<h3>📊 Rekap Transaksi</h3>
Laporan Parkir
</a>

</div>

<br>

<a href="logout.php" class="logout">
Logout
</a>

</div>

</body>
</html>