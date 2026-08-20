<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID transaksi tidak ditemukan.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
    SELECT
        tb_transaksi.*,
        tb_kendaraan.plat_nomor,
        tb_kendaraan.pemilik,
        tb_kendaraan.jenis_kendaraan,
        tb_area_parkir.nama_area,
        tb_user.nama_lengkap
    FROM tb_transaksi
    JOIN tb_kendaraan ON tb_transaksi.id_kendaraan = tb_kendaraan.id_kendaraan
    JOIN tb_area_parkir ON tb_transaksi.id_area = tb_area_parkir.id_area
    JOIN tb_user ON tb_transaksi.id_user = tb_user.id_user
    WHERE tb_transaksi.id_parkir = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<title>Struk Parkir</title>

<style>

body{
    font-family:Courier New;
    background:#eee;
}

.struk{

    width:350px;
    margin:30px auto;
    background:#fff;
    border:1px solid #000;
    padding:20px;

}

h2{

text-align:center;
margin-bottom:5px;

}

hr{

border:1px dashed #000;

}

table{

width:100%;
font-size:14px;

}

table td{

padding:4px;

}

.total{

font-size:18px;
font-weight:bold;
text-align:center;
margin-top:10px;

}

.btn{

margin-top:20px;
text-align:center;

}

button,a{

padding:10px 20px;
border:none;
background:#007bff;
color:white;
text-decoration:none;
cursor:pointer;
border-radius:5px;

}

@media print{

button,a{

display:none;

}

body{

background:white;

}

.struk{

border:none;

}

}

</style>

</head>

<body>

<div class="struk">

<h2>GRHASIA PARKIR</h2>

<center>
Jl. Contoh No.123
<br>
Telp. 08123456789
</center>

<hr>

<table>

<tr>
<td>No Transaksi</td>
<td>: <?= $data['id_parkir']; ?></td>
</tr>

<tr>
<td>Plat Nomor</td>
<td>: <?= $data['plat_nomor']; ?></td>
</tr>

<tr>
<td>Pemilik</td>
<td>: <?= $data['pemilik']; ?></td>
</tr>

<tr>
<td>Jenis</td>
<td>: <?= ucfirst($data['jenis_kendaraan']); ?></td>
</tr>

<tr>
<td>Area</td>
<td>: <?= $data['nama_area']; ?></td>
</tr>

<tr>
<td>Masuk</td>
<td>: <?= $data['waktu_masuk']; ?></td>
</tr>

<tr>
<td>Keluar</td>
<td>: <?= $data['waktu_keluar']; ?></td>
</tr>

<tr>
<td>Durasi</td>
<td>: <?= $data['durasi_jam']; ?> Jam</td>
</tr>

<tr>
<td>Petugas</td>
<td>: <?= $data['nama_lengkap']; ?></td>
</tr>

</table>

<hr>

<div class="total">

TOTAL BAYAR

<br>

Rp <?= number_format($data['biaya_total'],0,",","."); ?>

</div>

<hr>

<center>

Terima Kasih

<br>

Selamat Jalan

</center>

<div class="btn">

<button onclick="window.print()">
🖨 Cetak
</button>

<a href="transaksi.php">
Kembali
</a>

</div>

</div>

</body>
</html>