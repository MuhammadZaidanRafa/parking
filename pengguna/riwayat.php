<?php
session_start();
require '../db.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Ambil pesan sukses/error dari proses booking (kalau ada), lalu hapus dari session
$pesan_sukses = $_SESSION['pesan_sukses'] ?? null;
$pesan_error  = $_SESSION['pesan_error'] ?? null;
unset($_SESSION['pesan_sukses'], $_SESSION['pesan_error']);

// Ambil riwayat booking milik user ini, sekaligus tarif per jam
// dan data transaksi aktual (kalau booking sudah di-check-in oleh admin
// lewat transaksi.php, terhubung lewat kolom tb_transaksi.id_booking).
// Catatan: MIN(t.tarif_per_jam) dipakai untuk menghindari duplikasi baris
// akibat ada 2 data tarif "motor" di tb_tarif. Sebaiknya data tarif
// dibersihkan (hapus duplikat) agar tidak perlu trik ini.
$stmt = $conn->prepare("
    SELECT
        b.id_booking,
        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        b.status,
        b.created_at,
        k.plat_nomor,
        k.jenis_kendaraan,
        a.nama_area,
        MIN(t.tarif_per_jam) AS tarif_per_jam,
        MAX(tr.waktu_masuk)  AS aktual_waktu_masuk,
        MAX(tr.waktu_keluar) AS aktual_waktu_keluar,
        MAX(tr.durasi_jam)   AS aktual_durasi_jam,
        MAX(tr.biaya_total)  AS aktual_biaya_total
    FROM tb_booking b
    LEFT JOIN tb_kendaraan k ON b.id_kendaraan = k.id_kendaraan
    LEFT JOIN tb_area_parkir a ON b.id_area = a.id_area
    LEFT JOIN tb_tarif t ON t.jenis_kendaraan = k.jenis_kendaraan
    LEFT JOIN tb_transaksi tr ON tr.id_booking = b.id_booking
    WHERE b.id_user = ?
    GROUP BY b.id_booking
    ORDER BY b.id_booking DESC
");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<title>Riwayat Booking Parkir</title>

<style>

body{
    font-family:Arial;
    background:#f4f6f9;
    margin:0;
}

.header{
    background:#007bff;
    color:white;
    padding:20px;
}

.container{
    width:95%;
    margin:20px auto;
}

.alert{
    padding:12px 16px;
    border-radius:5px;
    margin-bottom:15px;
    color:white;
}

.alert-sukses{
    background:#28a745;
}

.alert-error{
    background:#dc3545;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

table th,
table td{
    padding:12px;
    border:1px solid #ddd;
    text-align:center;
}

table th{
    background:#007bff;
    color:white;
}

.btn{
    display:inline-block;
    margin-top:20px;
    padding:10px 20px;
    background:#007bff;
    color:white;
    text-decoration:none;
    border-radius:5px;
}

.status{
    padding:5px 10px;
    border-radius:5px;
    color:white;
    font-size:13px;
    font-weight:bold;
}

.booking{
    background:#6c757d;
}

.aktif{
    background:orange;
}

.selesai{
    background:green;
}

.batal{
    background:#dc3545;
}

.harga{
    font-weight:bold;
    color:#007bff;
}

.harga-final{
    font-size:11px;
    font-weight:normal;
    color:#28a745;
    display:block;
}

.btn-kecil{
    display:inline-block;
    padding:6px 12px;
    background:#28a745;
    color:white;
    text-decoration:none;
    border-radius:5px;
    font-size:13px;
}

.btn-kecil:hover{
    background:#218838;
}

.timer{
    font-weight:bold;
    font-size:13px;
}

.timer-normal{
    color:#28a745;
}

.timer-telat{
    color:#dc3545;
}

.timer-off{
    color:#999;
    font-weight:normal;
}

</style>

</head>

<body>

<div class="header">
<h2>Riwayat Booking Parkir Saya</h2>
</div>

<div class="container">

<?php if ($pesan_sukses): ?>
<div class="alert alert-sukses"><?= htmlspecialchars($pesan_sukses) ?></div>
<?php endif; ?>

<?php if ($pesan_error): ?>
<div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
<?php endif; ?>

<table>

<tr>
<th>No</th>
<th>No Polisi</th>
<th>Jenis</th>
<th>Area</th>
<th>Tanggal</th>
<th>Jam Masuk</th>
<th>Estimasi</th>
<th>Tarif/Jam</th>
<th>Total Harga</th>
<th>Status</th>
<th>Sisa Waktu / Denda</th>
<th>Dibuat</th>
<th>Aksi</th>
</tr>

<?php

$no = 1;

if ($result->num_rows === 0) {
?>
<tr>
<td colspan="13">Belum ada riwayat booking.</td>
</tr>
<?php
} else {
    while ($data = $result->fetch_assoc()) {

        $tarif_per_jam = (int) ($data['tarif_per_jam'] ?? 0);
        $estimasi_jam  = (int) $data['estimasi_jam'];
        $status        = $data['status'];

        // Total estimasi awal (tarif x estimasi jam saat booking dibuat)
        $total_estimasi = $tarif_per_jam * $estimasi_jam;

        // Kalau booking ini sudah selesai dan sudah pernah di-check-in/keluar
        // lewat transaksi.php, tampilkan biaya AKTUAL (bisa beda dari estimasi
        // kalau kendaraan keluar lebih lambat/cepat dari estimasi).
        $total_final = ($status === 'selesai' && $data['aktual_biaya_total'] !== null)
            ? (int) $data['aktual_biaya_total']
            : null;

        // Batas waktu (deadline) untuk hitung mundur/denda.
        // Kalau sudah ada waktu check-in aktual dari admin (tb_transaksi),
        // pakai itu supaya lebih akurat. Kalau belum, pakai estimasi jam booking.
        if (!empty($data['aktual_waktu_masuk'])) {
            $waktu_masuk_dasar = strtotime($data['aktual_waktu_masuk']);
        } else {
            $waktu_masuk_dasar = strtotime($data['tanggal'] . ' ' . $data['jam_masuk']);
        }
        $deadline_unix = $waktu_masuk_dasar + ($estimasi_jam * 3600);
?>

<tr>

<td><?= $no++; ?></td>

<td><?= htmlspecialchars($data['plat_nomor'] ?? '-'); ?></td>

<td><?= htmlspecialchars($data['jenis_kendaraan'] ?? '-'); ?></td>

<td><?= htmlspecialchars($data['nama_area'] ?? '-'); ?></td>

<td><?= date('d-m-Y', strtotime($data['tanggal'])); ?></td>

<td><?= date('H:i', strtotime($data['jam_masuk'])); ?></td>

<td><?= $estimasi_jam; ?> Jam</td>

<td>Rp <?= number_format($tarif_per_jam, 0, ',', '.'); ?></td>

<td class="harga">
Rp <?= number_format($total_final ?? $total_estimasi, 0, ',', '.'); ?>
<?php if ($total_final !== null && $total_final !== $total_estimasi): ?>
<span class="harga-final">(final, estimasi awal Rp <?= number_format($total_estimasi, 0, ',', '.'); ?>)</span>
<?php endif; ?>
</td>

<td>
<?php
    echo "<span class='status {$status}'>" . strtoupper($status) . "</span>";
?>
</td>

<td>
<?php if ($status === 'aktif'): ?>
<span class="timer"
    data-timer
    data-deadline="<?= $deadline_unix; ?>"
    data-tarif="<?= $tarif_per_jam; ?>">
    Menghitung...
</span>
<?php else: ?>
<span class="timer timer-off">-</span>
<?php endif; ?>
</td>

<td><?= date('d-m-Y H:i', strtotime($data['created_at'])); ?></td>

<td>
<a href="quitansi.php?id_booking=<?= (int) $data['id_booking']; ?>" class="btn-kecil">
🧾 Kuitansi
</a>
</td>

</tr>

<?php
    }
}

$stmt->close();
?>

</table>

<a href="../dashboard_pengguna.php" class="btn">
← Kembali ke Dashboard
</a>

</div>

<script>
function formatDurasi(detik) {
    const jam = Math.floor(detik / 3600);
    const menit = Math.floor((detik % 3600) / 60);
    const sisaDetik = detik % 60;
    let hasil = '';
    if (jam > 0) hasil += jam + 'j ';
    hasil += menit + 'm ' + sisaDetik + 'd';
    return hasil;
}

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

function updateTimers() {
    const timers = document.querySelectorAll('[data-timer]');

    timers.forEach(function (el) {
        const deadline = parseInt(el.dataset.deadline, 10) * 1000; // ke milidetik
        const tarifPerJam = parseInt(el.dataset.tarif, 10) || 0;
        const now = Date.now();
        const selisihDetik = Math.floor((deadline - now) / 1000);

        if (selisihDetik > 0) {
            // Masih dalam batas waktu
            el.textContent = 'Sisa: ' + formatDurasi(selisihDetik);
            el.classList.remove('timer-telat');
            el.classList.add('timer-normal');
        } else {
            // Sudah lewat waktu -> hitung denda
            const detikTelat = Math.abs(selisihDetik);
            const jamTelat = Math.ceil(detikTelat / 3600); // dibulatkan ke atas
            const denda = jamTelat * tarifPerJam;

            el.innerHTML = 'Telat: ' + formatDurasi(detikTelat) +
                '<br>Denda: ' + formatRupiah(denda);
            el.classList.remove('timer-normal');
            el.classList.add('timer-telat');
        }
    });
}

// Jalankan sekali di awal, lalu update tiap detik
updateTimers();
setInterval(updateTimers, 1000);
</script>

</body>
</html>