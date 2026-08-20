<?php
session_start();
require '../db.php';

// Harus login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

// Hanya role pengguna
if ($_SESSION['role'] != "pengguna") {
    die("Akses ditolak.");
}

$id_user = $_SESSION['id_user'];

// Ambil kendaraan milik user, dilengkapi info booking terbaru yang masih
// berstatus 'booking' atau 'aktif' (belum selesai/batal) beserta area parkirnya
$stmt = $conn->prepare("
    SELECT k.*,
           b.id_booking,
           b.tanggal,
           b.jam_masuk,
           b.estimasi_jam,
           b.status AS status_booking,
           a.nama_area
    FROM tb_kendaraan k
    LEFT JOIN tb_booking b
        ON b.id_kendaraan = k.id_kendaraan
       AND b.id_booking = (
            SELECT b2.id_booking
            FROM tb_booking b2
            WHERE b2.id_kendaraan = k.id_kendaraan
              AND b2.status IN ('booking','aktif')
            ORDER BY b2.created_at DESC
            LIMIT 1
       )
    LEFT JOIN tb_area_parkir a ON a.id_area = b.id_area
    WHERE k.id_user = ?
    ORDER BY k.id_kendaraan DESC
");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$data = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kendaraan Saya</title>

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
    margin:30px auto;
}

.btn{
    display:inline-block;
    padding:10px 20px;
    background:#28a745;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

table th,table td{
    border:1px solid #ddd;
    padding:12px;
    text-align:center;
}

table th{
    background:#007bff;
    color:white;
}

tr:nth-child(even){
    background:#f8f8f8;
}

.kembali{
    display:inline-block;
    margin-top:20px;
    text-decoration:none;
}

/* Info booking di dalam sel tabel */
.booking-area{
    font-weight:bold;
}

.booking-waktu{
    display:block;
    color:#666;
    font-size:12px;
    margin:4px 0;
}

.badge{
    display:inline-block;
    padding:3px 10px;
    border-radius:12px;
    font-size:11px;
    color:#fff;
    white-space:nowrap;
}

.badge-booking{background:#ffc107; color:#333;}
.badge-aktif{background:#28a745;}
.badge-none{background:#6c757d;}

.link-booking{
    display:inline-block;
    margin-top:6px;
    font-size:12px;
    text-decoration:none;
    color:#007bff;
}

.link-kuitansi{
    display:inline-block;
    margin-top:6px;
    font-size:12px;
    text-decoration:none;
    color:#28a745;
}

.btn-konfirmasi-tempat{
    display:inline-block;
    margin-top:6px;
    padding:5px 10px;
    background:#ff9800;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    font-size:12px;
    font-weight:bold;
}

.btn-konfirmasi-tempat:hover{
    background:#e68900;
}

.btn-batal-booking{
    display:inline-block;
    margin-top:4px;
    padding:5px 10px;
    background:#dc3545;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    font-size:12px;
    font-weight:bold;
}

.btn-batal-booking:hover{
    background:#bd2130;
}
</style>

</head>
<body>

<div class="header">
    <h2>Kendaraan Saya</h2>
    <p><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
</div>

<div class="container">

<a href="daftarkan_kendaraan.php" class="btn">
+ Daftarkan Kendaraan
</a>

<table>

<tr>
    <th>No</th>
    <th>Plat Nomor</th>
    <th>Jenis</th>
    <th>Merk</th>
    <th>Warna</th>
    <th>Tempat Booking</th>
    <th>Aksi</th>
</tr>

<?php
$no=1;

while($row=$data->fetch_assoc()){
?>

<tr>

<td><?= $no++; ?></td>

<td><?= htmlspecialchars($row['plat_nomor']); ?></td>

<td><?= htmlspecialchars($row['jenis_kendaraan']); ?></td>

<td><?= htmlspecialchars($row['merk']); ?></td>

<td><?= htmlspecialchars($row['warna']); ?></td>

<td>
<?php if ($row['id_booking']): ?>
    <span class="booking-area"><?= htmlspecialchars($row['nama_area']); ?></span>
    <span class="booking-waktu">
        <?= date('d-m-Y', strtotime($row['tanggal'])); ?> • <?= substr($row['jam_masuk'], 0, 5); ?>
        (<?= (int)$row['estimasi_jam']; ?> jam)
    </span>
    <?php if ($row['status_booking'] == 'aktif'): ?>
        <span class="badge badge-aktif">Aktif</span>
    <?php else: ?>
        <span class="badge badge-booking">Menunggu</span>
    <?php endif; ?>
    <br>
    <?php if ($row['status_booking'] == 'booking'): ?>
        <a href="konfirmasi_sudah_ditempat.php?id_booking=<?= $row['id_booking']; ?>" class="btn-konfirmasi-tempat">
            📍 Sudah di Tempat
        </a>
        <br>
        <a href="batal_booking.php?id_booking=<?= $row['id_booking']; ?>" class="btn-batal-booking" onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan booking ini?')">
            ❌ Batal Pesanan
        </a>
        <br>
    <?php endif; ?>
    <a href="quitansi.php?id_booking=<?= $row['id_booking']; ?>" class="link-kuitansi">
        Lihat Kuitansi
    </a>
<?php else: ?>
    <span class="badge badge-none">Belum Booking</span><br>
    <a href="booking_parkir.php?id_kendaraan=<?= $row['id_kendaraan']; ?>" class="link-booking">
        + Booking Tempat
    </a>
<?php endif; ?>
</td>

<td>
<a href="edit_kendaraan.php?id=<?= $row['id_kendaraan']; ?>">Edit</a> |
<a href="hapus_kendaraan.php?id=<?= $row['id_kendaraan']; ?>"
onclick="return confirm('Hapus kendaraan?')">
Hapus
</a>
</td>

</tr>

<?php
}
?>

</table>

<br>

<a href="../dashboard_pengguna.php" class="btn">
← Kembali ke Dashboard
</a>

</div>

</body>
</html>