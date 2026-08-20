<?php
session_start();
require '../db.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Ambil kendaraan milik user (id_user sudah di-cast ke int, aman dari SQL injection)
$kendaraan = mysqli_query($conn, "SELECT * FROM tb_kendaraan WHERE id_user = $id_user");
if ($kendaraan === false) {
    die("Query error: " . mysqli_error($conn));
}
$jumlah_kendaraan = mysqli_num_rows($kendaraan);

// Ambil semua area parkir, urut berdasarkan nama
$area = mysqli_query($conn, "SELECT * FROM tb_area_parkir ORDER BY nama_area");
if ($area === false) {
    die("Query error: " . mysqli_error($conn));
}

// Tandai area yang sudah penuh (terisi >= kapasitas) supaya tidak bisa dipilih
$daftar_area = [];
$ada_area_tersedia = false;
while ($a = mysqli_fetch_assoc($area)) {
    $a['penuh'] = ((int) $a['terisi'] >= (int) $a['kapasitas']);
    if (!$a['penuh']) {
        $ada_area_tersedia = true;
    }
    $daftar_area[] = $a;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Parkir</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body{
    background:linear-gradient(135deg,#e3f2fd,#f4f6f9);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.container{
    width:100%;
    max-width:500px;
    background:#fff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.12);
}

.container h2{
    text-align:center;
    color:#0d6efd;
    margin-bottom:25px;
    font-size:28px;
}

label{
    display:block;
    margin-top:15px;
    margin-bottom:8px;
    font-weight:600;
    color:#444;
}

input,
select{
    width:100%;
    padding:12px 15px;
    border:1px solid #ced4da;
    border-radius:8px;
    font-size:15px;
    background:#fff;
    transition:.3s;
    outline:none;
}

input:focus,
select:focus{
    border-color:#0d6efd;
    box-shadow:0 0 0 3px rgba(13,110,253,.2);
}

option:disabled{
    color:#adb5bd;
}

button{
    width:100%;
    margin-top:25px;
    padding:14px;
    border:none;
    border-radius:8px;
    background:#0d6efd;
    color:#fff;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    background:#0b5ed7;
    transform:translateY(-2px);
}

button:active{
    transform:scale(.98);
}

.notice{
    text-align:center;
    padding:20px;
    border-radius:10px;
    background:#fff3cd;
    color:#664d03;
    font-size:15px;
    line-height:1.6;
}

.notice a{
    display:inline-block;
    margin-top:12px;
    color:#0d6efd;
    font-weight:600;
    text-decoration:none;
}

.notice a:hover{
    text-decoration:underline;
}

@media (max-width:576px){

    .container{
        padding:20px;
    }

    .container h2{
        font-size:24px;
    }

}

</style>

</head>

<body>

<div class="container">

<h2>Booking Parkir</h2>

<?php if ($jumlah_kendaraan === 0): ?>

    <div class="notice">
        Anda belum memiliki kendaraan terdaftar.<br>
        Silakan tambahkan kendaraan terlebih dahulu sebelum melakukan booking.
        <br>
        <a href="kendaraan_saya.php">+ Tambah Kendaraan</a>
    </div>

<?php elseif (!$ada_area_tersedia): ?>

    <div class="notice">
        Mohon maaf, semua area parkir sedang penuh saat ini.<br>
        Silakan coba lagi beberapa saat lagi.
    </div>

<?php else: ?>

<form method="POST" action="proses_pesan.php">

<label>Kendaraan</label>

<select name="id_kendaraan" required>

<?php while($k = mysqli_fetch_assoc($kendaraan)): ?>

<option value="<?= (int) $k['id_kendaraan']; ?>">

<?= htmlspecialchars($k['plat_nomor']); ?> -
<?= htmlspecialchars($k['jenis_kendaraan']); ?>

</option>

<?php endwhile; ?>

</select>

<label>Area Parkir</label>

<select name="id_area" required>

<?php foreach ($daftar_area as $a): ?>

<option value="<?= (int) $a['id_area']; ?>" <?= $a['penuh'] ? 'disabled' : ''; ?>>

<?= htmlspecialchars($a['nama_area']); ?>
(<?= (int) $a['terisi']; ?>/<?= (int) $a['kapasitas']; ?>)
<?= $a['penuh'] ? '- PENUH' : ''; ?>

</option>

<?php endforeach; ?>

</select>

<label>Tanggal</label>

<input type="date" name="tanggal" min="<?= date('Y-m-d'); ?>" required>

<label>Jam Masuk</label>

<input type="time" name="jam_masuk" required>

<label>Estimasi (Jam)</label>

<input type="number" name="estimasi_jam" min="1" max="24" required>

<button type="submit" name="booking">
Pesan Tempat Sekarang
</button>

</form>

<?php endif; ?>

</div>

</body>
</html>