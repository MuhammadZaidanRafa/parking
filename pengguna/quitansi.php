<?php
session_start();
require_once "../db.php";

// Harus login
if (!isset($_SESSION['login'])) {
    header("Location: login_pengguna.php");
    exit;
}

// Hanya role pengguna
if ($_SESSION['role'] != "pengguna") {
    die("Akses ditolak.");
}

$id_user    = $_SESSION['id_user'];
$id_booking = isset($_GET['id_booking']) ? (int) $_GET['id_booking'] : 0;

if ($id_booking <= 0) {
    die("ID booking tidak valid.");
}

// Ambil data booking + kendaraan + area, dan pastikan booking ini
// benar-benar milik user yang sedang login (cek lewat k.id_user)
$stmt = $conn->prepare("
    SELECT b.id_booking, b.tanggal, b.jam_masuk, b.estimasi_jam, b.status, b.created_at,
           k.plat_nomor, k.jenis_kendaraan, k.merk, k.warna,
           a.nama_area
    FROM tb_booking b
    JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan
    JOIN tb_area_parkir a ON a.id_area = b.id_area
    WHERE b.id_booking = ? AND k.id_user = ?
");
$stmt->bind_param("ii", $id_booking, $id_user);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Data booking tidak ditemukan, atau bukan milik Anda.");
}

// Label & kelas badge untuk tiap status booking
$statusInfo = [
    'booking' => ['label' => 'Menunggu',   'kelas' => 'booking'],
    'aktif'   => ['label' => 'Aktif',      'kelas' => 'aktif'],
    'selesai' => ['label' => 'Selesai',    'kelas' => 'selesai'],
    'batal'   => ['label' => 'Dibatalkan', 'kelas' => 'batal'],
];
$status = $booking['status'];
$info   = $statusInfo[$status] ?? ['label' => ucfirst($status), 'kelas' => 'booking'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kuitansi Booking #<?= $booking['id_booking']; ?></title>

<style>
body{
    font-family:Arial;
    background:#f4f6f9;
    margin:0;
}

.header{
    background:#007bff;
    color:#fff;
    padding:20px;
}

.container{
    width:90%;
    max-width:700px;
    margin:30px auto;
}

.kuitansi{
    background:#fff;
    border-radius:8px;
    padding:30px;
    box-shadow:0 1px 4px rgba(0,0,0,0.1);
}

.kuitansi-head{
    text-align:center;
    border-bottom:2px dashed #ddd;
    padding-bottom:16px;
    margin-bottom:20px;
}

.kuitansi-head h1{
    margin:0 0 6px;
    font-size:22px;
    letter-spacing:1px;
    color:#007bff;
}

.no-kuitansi{
    margin:0;
    color:#666;
    font-size:14px;
}

table.rincian{
    width:100%;
    border-collapse:collapse;
}

table.rincian th,
table.rincian td{
    text-align:left;
    padding:10px 6px;
    border-bottom:1px solid #eee;
    font-size:14px;
}

table.rincian th{
    width:40%;
    color:#666;
    font-weight:normal;
}

table.rincian td{
    font-weight:bold;
}

.badge{
    display:inline-block;
    padding:4px 12px;
    border-radius:12px;
    font-size:12px;
    color:#fff;
}

.badge-booking{background:#ffc107; color:#333;}
.badge-aktif{background:#28a745;}
.badge-selesai{background:#007bff;}
.badge-batal{background:#dc3545;}

.catatan{
    margin-top:20px;
    font-size:12px;
    color:#888;
    text-align:center;
}

.aksi{
    text-align:center;
    margin-top:24px;
}

.btn{
    display:inline-block;
    padding:10px 20px;
    border-radius:5px;
    text-decoration:none;
    color:#fff;
    border:none;
    font-size:14px;
    cursor:pointer;
    margin:0 5px;
}

.btn-cetak{ background:#28a745; }
.btn-kembali{ background:#6c757d; }

@media print{
    .header, .aksi{ display:none; }
    body{ background:#fff; }
    .container{ width:100%; max-width:none; margin:0; }
    .kuitansi{ box-shadow:none; }
}
</style>

</head>
<body>

<div class="header">
    <h2>Kuitansi Booking</h2>
    <p><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
</div>

<div class="container">

    <div class="kuitansi">
        <div class="kuitansi-head">
            <h1>KUITANSI PARKIR</h1>
            <p class="no-kuitansi">No. Booking: #<?= str_pad($booking['id_booking'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>

        <table class="rincian">
            <tr>
                <th>Nama Pengguna</th>
                <td><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></td>
            </tr>
            <tr>
                <th>Plat Nomor</th>
                <td><?= htmlspecialchars($booking['plat_nomor']); ?></td>
            </tr>
            <tr>
                <th>Jenis / Merk</th>
                <td><?= htmlspecialchars($booking['jenis_kendaraan']); ?> - <?= htmlspecialchars($booking['merk']); ?></td>
            </tr>
            <tr>
                <th>Warna</th>
                <td><?= htmlspecialchars($booking['warna']); ?></td>
            </tr>
            <tr>
                <th>Area Parkir</th>
                <td><?= htmlspecialchars($booking['nama_area']); ?></td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td><?= date('d-m-Y', strtotime($booking['tanggal'])); ?></td>
            </tr>
            <tr>
                <th>Jam Masuk</th>
                <td><?= substr($booking['jam_masuk'], 0, 5); ?></td>
            </tr>
            <tr>
                <th>Estimasi Durasi</th>
                <td><?= (int) $booking['estimasi_jam']; ?> jam</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="badge badge-<?= $info['kelas']; ?>"><?= $info['label']; ?></span></td>
            </tr>
        </table>

        <p class="catatan">Kuitansi ini adalah bukti booking area parkir. Simpan sebagai referensi Anda.</p>
    </div>

    <div class="aksi">
        <button onclick="window.print()" class="btn btn-cetak">Cetak Kuitansi</button>
        <a href="riwayat.php" class="btn btn-kembali">← Kembali</a>
    </div>

</div>

</body>
</html>