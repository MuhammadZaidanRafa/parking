<?php
session_start();
require_once "db.php";

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Ambil id_booking dari URL
$id_booking = (int) ($_GET['id_booking'] ?? $_POST['id_booking'] ?? 0);

if ($id_booking <= 0) {
    $_SESSION['pesan_error'] = "Booking tidak ditemukan.";
    header("Location: riwayat_booking.php");
    exit;
}

// Ambil data booking, pastikan milik user yang sedang login
$stmt = $conn->prepare("
    SELECT b.id_booking, b.status, b.estimasi_jam, k.plat_nomor, a.nama_area
    FROM tb_booking b
    LEFT JOIN tb_kendaraan k ON b.id_kendaraan = k.id_kendaraan
    LEFT JOIN tb_area_parkir a ON b.id_area = a.id_area
    WHERE b.id_booking = ? AND b.id_user = ?
");
$stmt->bind_param("ii", $id_booking, $id_user);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Booking tidak ada / bukan milik user ini
if (!$booking) {
    $_SESSION['pesan_error'] = "Booking tidak ditemukan atau bukan milik Anda.";
    header("Location: riwayat_booking.php");
    exit;
}

// Booking sudah bukan status 'booking' (sudah aktif/selesai/batal)
if ($booking['status'] !== 'booking') {
    $_SESSION['pesan_error'] = "Booking ini sudah tidak bisa dikonfirmasi (status: " . strtoupper($booking['status']) . ").";
    header("Location: riwayat_booking.php");
    exit;
}

$error = null;

// Proses konfirmasi saat tombol ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $now = date('Y-m-d H:i:s');
    $tanggal_sekarang = date('Y-m-d');
    $jam_sekarang     = date('H:i:s');

    $stmt = $conn->prepare("
        UPDATE tb_booking
        SET status = 'aktif',
            tanggal = ?,
            jam_masuk = ?
        WHERE id_booking = ? AND id_user = ? AND status = 'booking'
    ");
    $stmt->bind_param("ssii", $tanggal_sekarang, $jam_sekarang, $id_booking, $id_user);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $_SESSION['pesan_sukses'] = "Konfirmasi berhasil! Kendaraan tercatat masuk pukul " . date('H:i', strtotime($jam_sekarang)) . ". Timer parkir mulai berjalan.";
        header("Location: riwayat_booking.php");
        exit;
    } else {
        $stmt->close();
        $error = "Gagal mengkonfirmasi. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Kedatangan</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f4f6f9;
    padding:20px;
}

.card{
    width:100%;
    max-width:420px;
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,.1);
    text-align:center;
}

.icon{
    font-size:50px;
    margin-bottom:10px;
}

h2{
    margin-bottom:10px;
    color:#333;
}

.info{
    background:#f4f6f9;
    border-radius:8px;
    padding:15px;
    margin:20px 0;
    text-align:left;
}

.info p{
    margin:6px 0;
    font-size:14px;
    color:#444;
}

.info b{
    color:#111;
}

.error{
    background:#fee2e2;
    color:#dc2626;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    font-size:14px;
}

.btn-konfirmasi{
    display:block;
    width:100%;
    padding:14px;
    background:#28a745;
    color:white;
    border:none;
    border-radius:8px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    margin-bottom:12px;
}

.btn-konfirmasi:hover{
    background:#218838;
}

.btn-batal{
    display:block;
    padding:10px;
    color:#666;
    text-decoration:none;
    font-size:14px;
}

</style>

</head>
<body>

<div class="card">

<div class="icon">🚗📍</div>

<h2>Konfirmasi Kedatangan</h2>
<p style="color:#666; font-size:14px;">Pastikan kendaraan Anda sudah benar-benar berada di lokasi parkir sebelum konfirmasi.</p>

<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="info">
<p>Plat Nomor&nbsp;: <b><?= htmlspecialchars($booking['plat_nomor'] ?? '-') ?></b></p>
<p>Area Parkir&nbsp;&nbsp;: <b><?= htmlspecialchars($booking['nama_area'] ?? '-') ?></b></p>
<p>Estimasi&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <b><?= (int) $booking['estimasi_jam'] ?> Jam</b></p>
</div>

<form method="POST">
<input type="hidden" name="id_booking" value="<?= (int) $id_booking ?>">
<button type="submit" class="btn-konfirmasi">
✅ Ya, Saya Sudah di Tempat
</button>
</form>

<a href="kendaraan_saya.php" class="btn-batal">← Batal, kembali</a>

</div>

</body>
</html>