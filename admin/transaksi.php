<?php
session_start();

require_once "../db.php";

/* =========================================================
   CEK LOGIN
   ========================================================= */
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama    = $_SESSION['nama_lengkap'] ?? 'User';
$role    = $_SESSION['role'] ?? '';

/* Hanya admin */
if ($role !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

/* =========================================================
   CHECK-IN DARI BOOKING
   ========================================================= */
if (isset($_POST['checkin_booking'])) {

    $id_booking = (int) $_POST['id_booking'];

    $stmt = $conn->prepare("
        SELECT
            b.id_booking,
            b.id_kendaraan,
            b.id_area,
            b.id_user,
            t.id_tarif
        FROM tb_booking b
        JOIN tb_kendaraan k
            ON k.id_kendaraan = b.id_kendaraan
        JOIN tb_tarif t
            ON t.jenis_kendaraan = k.jenis_kendaraan
        WHERE b.id_booking = ?
          AND b.status = 'booking'
        LIMIT 1
    ");

    $stmt->bind_param("i", $id_booking);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking) {

        $stmt = $conn->prepare("
            INSERT INTO tb_transaksi
            (
                id_kendaraan,
                waktu_masuk,
                id_tarif,
                status,
                id_user,
                id_area,
                id_booking
            )
            VALUES (?, NOW(), ?, 'masuk', ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiiii",
            $booking['id_kendaraan'],
            $booking['id_tarif'],
            $booking['id_user'],
            $booking['id_area'],
            $id_booking
        );

        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE tb_booking
            SET status = 'aktif'
            WHERE id_booking = ?
        ");

        $stmt->bind_param("i", $id_booking);
        $stmt->execute();
        $stmt->close();

        header("Location: transaksi.php?sukses=checkin");
        exit;
    }

    header("Location: transaksi.php?error=1");
    exit;
}

/* =========================================================
   BATALKAN BOOKING
   ========================================================= */
if (isset($_POST['batalkan_booking'])) {

    $id_booking = (int) $_POST['id_booking'];

    $stmt = $conn->prepare("
        UPDATE tb_booking
        SET status = 'batal'
        WHERE id_booking = ?
          AND status = 'booking'
    ");

    $stmt->bind_param("i", $id_booking);
    $stmt->execute();
    $stmt->close();

    header("Location: transaksi.php?sukses=batal_booking");
    exit;
}

/* =========================================================
   PARKIR MASUK LANGSUNG
   ========================================================= */
if (isset($_POST['parkir_masuk'])) {

    $id_kendaraan = (int) $_POST['id_kendaraan'];
    $id_area      = (int) $_POST['id_area'];

    $stmt = $conn->prepare("
        SELECT
            k.id_kendaraan,
            t.id_tarif
        FROM tb_kendaraan k
        JOIN tb_tarif t
            ON k.jenis_kendaraan = t.jenis_kendaraan
        WHERE k.id_kendaraan = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id_kendaraan);
    $stmt->execute();
    $kendaraan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($kendaraan) {

        $stmt = $conn->prepare("
            INSERT INTO tb_transaksi
            (
                id_kendaraan,
                waktu_masuk,
                id_tarif,
                status,
                id_user,
                id_area
            )
            VALUES (?, NOW(), ?, 'masuk', ?, ?)
        ");

        $stmt->bind_param(
            "iiii",
            $id_kendaraan,
            $kendaraan['id_tarif'],
            $id_user,
            $id_area
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: transaksi.php?sukses=masuk");
    exit;
}

/* =========================================================
   PARKIR KELUAR
   ========================================================= */
if (isset($_GET['keluar'])) {

    $id = (int) $_GET['keluar'];

    $stmt = $conn->prepare("
        SELECT
            tb_transaksi.*,
            tb_tarif.tarif_per_jam
        FROM tb_transaksi
        JOIN tb_tarif
            ON tb_tarif.id_tarif = tb_transaksi.id_tarif
        WHERE tb_transaksi.id_parkir = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {

        $masuk  = strtotime($data['waktu_masuk']);
        $keluar = time();

        $jam = max(
            1,
            (int) ceil(($keluar - $masuk) / 3600)
        );

        $total = $jam * $data['tarif_per_jam'];

        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET
                waktu_keluar = NOW(),
                durasi_jam = ?,
                biaya_total = ?,
                status = 'keluar'
            WHERE id_parkir = ?
        ");

        $stmt->bind_param("idi", $jam, $total, $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($data['id_booking'])) {

            $stmt = $conn->prepare("
                UPDATE tb_booking
                SET status = 'selesai'
                WHERE id_booking = ?
            ");

            $stmt->bind_param("i", $data['id_booking']);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: transaksi.php?sukses=keluar");
    exit;
}

/* =========================================================
   EDIT DURASI PARKIR
   ========================================================= */
if (isset($_POST['update_durasi'])) {

    $id_parkir   = (int) $_POST['id_parkir'];
    $durasi_baru = (int) $_POST['durasi_jam'];

    if ($durasi_baru < 1) {
        $durasi_baru = 1;
    }

    $stmt = $conn->prepare("
        SELECT tb_tarif.tarif_per_jam
        FROM tb_transaksi
        JOIN tb_tarif
            ON tb_tarif.id_tarif = tb_transaksi.id_tarif
        WHERE tb_transaksi.id_parkir = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id_parkir);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {

        $total_baru =
            $durasi_baru * $data['tarif_per_jam'];

        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET
                durasi_jam = ?,
                biaya_total = ?
            WHERE id_parkir = ?
        ");

        $stmt->bind_param(
            "idi",
            $durasi_baru,
            $total_baru,
            $id_parkir
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: transaksi.php?sukses=edit_durasi");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Transaksi Parkir - E-Parkir Admin</title>

<style>

/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #212529;
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

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

    z-index: 2000;

    box-shadow: 3px 0 15px rgba(0,0,0,.15);
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

    transition: .2s;
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

    transition: .2s;
}

.sidebar .logout-btn:hover {
    background: #bd2130;
}


/* =========================================================
   MOBILE BUTTON
   ========================================================= */

.mobile-menu-btn {
    display: none;

    position: fixed;

    top: 15px;
    left: 15px;

    z-index: 3000;

    width: 45px;
    height: 45px;

    border: none;

    border-radius: 10px;

    background: #007bff;

    color: white;

    font-size: 22px;

    cursor: pointer;

    box-shadow: 0 4px 12px rgba(0,0,0,.2);
}

.overlay {
    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.45);

    z-index: 1500;
}


/* =========================================================
   MAIN CONTENT
   ========================================================= */

.main-content {
    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}

.container {
    max-width: 1200px;

    margin: auto;

    background: white;

    padding: 25px;

    border-radius: 15px;

    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

.page-title {
    margin-bottom: 25px;

    color: #212529;
}

h3 {
    margin-top: 25px;

    margin-bottom: 15px;

    color: #343a40;
}

hr {
    border: none;

    border-top: 1px solid #eee;

    margin: 30px 0;
}


/* =========================================================
   TABLE
   ========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    min-width: 800px;

    border-collapse: collapse;

    margin-top: 10px;
}

table th,
table td {
    padding: 11px;

    border: 1px solid #ddd;

    text-align: left;

    white-space: nowrap;
}

th {
    background: #007bff;

    color: white;
}

tr:nth-child(even) {
    background: #f8f9fa;
}

tr:hover {
    background: #eef5ff;
}


/* =========================================================
   FORM
   ========================================================= */

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;

    font-weight: bold;

    margin-bottom: 6px;
}

input,
select {
    width: 100%;

    padding: 11px;

    border: 1px solid #ced4da;

    border-radius: 7px;

    outline: none;

    font-size: 14px;
}

input:focus,
select:focus {
    border-color: #007bff;

    box-shadow:
        0 0 0 3px rgba(0,123,255,.1);
}

button {
    background: #007bff;

    color: white;

    padding: 10px 18px;

    border: none;

    cursor: pointer;

    border-radius: 7px;

    font-size: 14px;
}

button:hover {
    background: #0056b3;
}


/* =========================================================
   BUTTON
   ========================================================= */

.btn {
    padding: 8px 13px;

    background: #198754;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    display: inline-block;

    border: none;

    cursor: pointer;

    font-size: 13px;

    margin: 2px;
}

.btn:hover {
    background: #146c43;
}

.btn-warning {
    background: #ffc107;

    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-danger {
    background: #dc3545;
}

.btn-danger:hover {
    background: #b02a37;
}

.btn-back {
    background: #6c757d;
}

.btn-back:hover {
    background: #545b62;
}


/* =========================================================
   ALERT
   ========================================================= */

.alert-success {
    padding: 12px 15px;

    background: #d1e7dd;

    color: #0f5132;

    border-radius: 7px;

    margin-bottom: 15px;
}

.alert-error {
    padding: 12px 15px;

    background: #f8d7da;

    color: #842029;

    border-radius: 7px;

    margin-bottom: 15px;
}


/* =========================================================
   MODAL
   ========================================================= */

.modal {
    display: none;

    position: fixed;

    z-index: 5000;

    inset: 0;

    background-color: rgba(0,0,0,.55);
}

.modal-content {
    background-color: white;

    margin: 10% auto;

    padding: 25px;

    border-radius: 12px;

    width: 350px;

    max-width: 90%;

    box-shadow: 0 10px 30px rgba(0,0,0,.25);
}

.modal-content h3 {
    margin-top: 0;
}

.close {
    color: #aaa;

    float: right;

    font-size: 28px;

    font-weight: bold;

    cursor: pointer;
}

.close:hover {
    color: black;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 900px) {

    .sidebar {
        transform: translateX(-100%);

        transition: .3s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .mobile-menu-btn {
        display: block;
    }

    .overlay.show {
        display: block;
    }

    .main-content {
        margin-left: 0;

        padding: 75px 15px 25px;
    }

    .container {
        padding: 18px;

        border-radius: 10px;
    }

    .page-title {
        margin-left: 55px;
    }
}

@media (max-width: 600px) {

    .main-content {
        padding: 70px 10px 20px;
    }

    .container {
        padding: 14px;
    }

    h2 {
        font-size: 21px;
    }

    h3 {
        font-size: 18px;
    }

    .modal-content {
        margin: 30% auto;
    }
}

</style>
</head>

<body>


<!-- =========================================================
     MOBILE MENU
     ========================================================= -->

<button
    type="button"
    class="mobile-menu-btn"
    onclick="toggleSidebar()"
>
    ☰
</button>

<div
    class="overlay"
    id="overlay"
    onclick="toggleSidebar()"
></div>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar" id="sidebar">

    <div class="brand">
        🅿️ E-Parkir Admin
    </div>

    <div class="user-info">
        Role:
        <b><?= htmlspecialchars(strtoupper($role)) ?></b>

        User:
        <b><?= htmlspecialchars($nama) ?></b>
    </div>

    <ul class="nav-links">

        <li>
            <a href="../dashboard.php">
                <span>🏠</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="user.php">
                <span>👤</span>
                Kelola User
            </a>
        </li>

        <li>
            <a href="tarif.php">
                <span>💰</span>
                Kelola Tarif
            </a>
        </li>

        <li>
            <a href="transaksi.php" class="active">
                <span>🎫</span>
                Transaksi Parkir
            </a>
        </li>

        <li>
            <a href="area.php">
                <span>🅿️</span>
                Area Parkir
            </a>
        </li>

        <li>
            <a href="kendaraan.php">
                <span>🚗</span>
                Kelola Kendaraan
            </a>
        </li>

        <li>
            <a href="booking.php">
                <span>📅</span>
                Booking
            </a>
        </li>

        <li>
            <a href="log.php">
                <span>📋</span>
                Log Aktivitas
            </a>
        </li>

    </ul>

    <div class="logout-container">

        <a
            href="../logout.php"
            class="logout-btn"
            onclick="return confirm('Yakin ingin logout?');"
        >
            🚪 Logout
        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<main class="main-content">

<div class="container">

<h2 class="page-title">
    🎫 Transaksi Parkir
</h2>


<?php

if (isset($_GET['sukses'])) {

    $pesan_sukses_map = [

        'masuk' =>
            'Parkir masuk (langsung) berhasil ditambahkan.',

        'keluar' =>
            'Parkir keluar berhasil diproses.',

        'edit_durasi' =>
            'Durasi parkir dan biaya berhasil diperbarui.',

        'checkin' =>
            'Booking berhasil dikonfirmasi masuk (check-in).',

        'batal_booking' =>
            'Booking berhasil dibatalkan.'
    ];

    if (isset($pesan_sukses_map[$_GET['sukses']])) {

        echo "<div class='alert-success'>" .
            htmlspecialchars(
                $pesan_sukses_map[$_GET['sukses']]
            ) .
            "</div>";
    }
}

if (isset($_GET['error'])) {

    echo "
    <div class='alert-error'>
        Booking tidak ditemukan atau sudah diproses sebelumnya.
    </div>";
}

?>


<!-- =========================================================
     BOOKING MENUNGGU CHECK-IN
     ========================================================= -->

<h3>📅 Booking Menunggu Check-in</h3>

<div class="table-wrapper">

<table>

<tr>
    <th>No</th>
    <th>Plat</th>
    <th>Pemilik</th>
    <th>Area</th>
    <th>Tanggal</th>
    <th>Jam Booking</th>
    <th>Estimasi</th>
    <th>Aksi</th>
</tr>

<?php

$booking_pending = $conn->query("
    SELECT
        b.id_booking,
        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        k.plat_nomor,
        k.pemilik,
        a.nama_area
    FROM tb_booking b
    JOIN tb_kendaraan k
        ON k.id_kendaraan = b.id_kendaraan
    JOIN tb_area_parkir a
        ON a.id_area = b.id_area
    WHERE b.status = 'booking'
    ORDER BY b.tanggal ASC, b.jam_masuk ASC
");

if ($booking_pending->num_rows === 0) {

    echo "
    <tr>
        <td colspan='8'>
            Tidak ada booking yang menunggu check-in.
        </td>
    </tr>";

} else {

    $no_b = 1;

    while ($b = $booking_pending->fetch_assoc()) {

?>

<tr>

<td><?= $no_b++ ?></td>

<td>
    <?= htmlspecialchars($b['plat_nomor']) ?>
</td>

<td>
    <?= htmlspecialchars($b['pemilik']) ?>
</td>

<td>
    <?= htmlspecialchars($b['nama_area']) ?>
</td>

<td>
    <?= date(
        'd-m-Y',
        strtotime($b['tanggal'])
    ) ?>
</td>

<td>
    <?= date(
        'H:i',
        strtotime($b['jam_masuk'])
    ) ?>
</td>

<td>
    <?= (int) $b['estimasi_jam'] ?> Jam
</td>

<td>

<form
    method="POST"
    style="display:inline;"
>

<input
    type="hidden"
    name="id_booking"
    value="<?= (int) $b['id_booking'] ?>"
>

<button
    type="submit"
    name="checkin_booking"
    class="btn"
>
    ✅ Check-in
</button>

</form>

<form
    method="POST"
    style="display:inline;"
    onsubmit="return confirm('Batalkan booking ini?');"
>

<input
    type="hidden"
    name="id_booking"
    value="<?= (int) $b['id_booking'] ?>"
>

<button
    type="submit"
    name="batalkan_booking"
    class="btn btn-danger"
>
    ❌ Batalkan
</button>

</form>

</td>

</tr>

<?php

    }

}

?>

</table>

</div>


<hr>


<!-- =========================================================
     PARKIR MASUK LANGSUNG
     ========================================================= -->

<h3>🚗 Parkir Masuk (Langsung / Tanpa Booking)</h3>

<form method="POST">

<div class="form-group">

<label>Kendaraan</label>

<select
    name="id_kendaraan"
    required
>

<option value="">
    Pilih Kendaraan
</option>

<?php

$k = $conn->query("
    SELECT *
    FROM tb_kendaraan
    ORDER BY plat_nomor ASC
");

while ($r = $k->fetch_assoc()) {

?>

<option value="<?= (int) $r['id_kendaraan'] ?>">

<?= htmlspecialchars($r['plat_nomor']) ?>

 -

<?= htmlspecialchars($r['pemilik']) ?>

</option>

<?php

}

?>

</select>

</div>


<div class="form-group">

<label>Area Parkir</label>

<select
    name="id_area"
    required
>

<option value="">
    Pilih Area
</option>

<?php

$a = $conn->query("
    SELECT *
    FROM tb_area_parkir
    ORDER BY nama_area ASC
");

while ($r = $a->fetch_assoc()) {

?>

<option value="<?= (int) $r['id_area'] ?>">

<?= htmlspecialchars($r['nama_area']) ?>

</option>

<?php

}

?>

</select>

</div>


<button
    type="submit"
    name="parkir_masuk"
>
    + Simpan Parkir Masuk
</button>

</form>


<hr>


<!-- =========================================================
     KENDARAAN MASIH PARKIR
     ========================================================= -->

<h3>🚙 Data Kendaraan Masih Parkir</h3>

<div class="table-wrapper">

<table>

<tr>
    <th>No</th>
    <th>Plat</th>
    <th>Pemilik</th>
    <th>Masuk</th>
    <th>Area</th>
    <th>Asal</th>
    <th>Aksi</th>
</tr>

<?php

$no = 1;

$data = $conn->query("
    SELECT
        tb_transaksi.*,
        tb_kendaraan.plat_nomor,
        tb_kendaraan.pemilik,
        tb_area_parkir.nama_area
    FROM tb_transaksi
    JOIN tb_kendaraan
        ON tb_transaksi.id_kendaraan =
           tb_kendaraan.id_kendaraan
    JOIN tb_area_parkir
        ON tb_area_parkir.id_area =
           tb_transaksi.id_area
    WHERE tb_transaksi.status = 'masuk'
    ORDER BY tb_transaksi.waktu_masuk DESC
");

if ($data->num_rows === 0) {

    echo "
    <tr>
        <td colspan='7'>
            Tidak ada kendaraan yang sedang parkir.
        </td>
    </tr>";

} else {

    while ($d = $data->fetch_assoc()) {

?>

<tr>

<td><?= $no++ ?></td>

<td>
    <?= htmlspecialchars($d['plat_nomor']) ?>
</td>

<td>
    <?= htmlspecialchars($d['pemilik']) ?>
</td>

<td>
    <?= htmlspecialchars($d['waktu_masuk']) ?>
</td>

<td>
    <?= htmlspecialchars($d['nama_area']) ?>
</td>

<td>

<?= !empty($d['id_booking'])
    ? 'Booking #' . (int) $d['id_booking']
    : 'Langsung'
?>

</td>

<td>

<a
    class="btn"
    href="transaksi.php?keluar=<?= (int) $d['id_parkir'] ?>"
    onclick="return confirm('Proses kendaraan ini keluar?');"
>
    🚗 Keluar
</a>

</td>

</tr>

<?php

    }

}

?>

</table>

</div>


<hr>


<!-- =========================================================
     RIWAYAT KENDARAAN KELUAR
     ========================================================= -->

<h3>📋 Riwayat Kendaraan Keluar</h3>

<div class="table-wrapper">

<table>

<tr>
    <th>No</th>
    <th>Plat</th>
    <th>Masuk</th>
    <th>Keluar</th>
    <th>Durasi</th>
    <th>Total Biaya</th>
    <th>Asal</th>
    <th>Aksi</th>
</tr>

<?php

$no_keluar = 1;

$data_keluar = $conn->query("
    SELECT
        tb_transaksi.*,
        tb_kendaraan.plat_nomor
    FROM tb_transaksi
    JOIN tb_kendaraan
        ON tb_transaksi.id_kendaraan =
           tb_kendaraan.id_kendaraan
    WHERE tb_transaksi.status = 'keluar'
    ORDER BY tb_transaksi.waktu_keluar DESC
");

if ($data_keluar->num_rows === 0) {

    echo "
    <tr>
        <td colspan='8'>
            Belum ada riwayat kendaraan keluar.
        </td>
    </tr>";

} else {

    while ($dk = $data_keluar->fetch_assoc()) {

?>

<tr>

<td>
    <?= $no_keluar++ ?>
</td>

<td>
    <?= htmlspecialchars($dk['plat_nomor']) ?>
</td>

<td>
    <?= htmlspecialchars($dk['waktu_masuk']) ?>
</td>

<td>
    <?= htmlspecialchars($dk['waktu_keluar']) ?>
</td>

<td>
    <?= (int) $dk['durasi_jam'] ?> Jam
</td>

<td>
    Rp <?= number_format(
        $dk['biaya_total'],
        0,
        ',',
        '.'
    ) ?>
</td>

<td>

<?= !empty($dk['id_booking'])
    ? 'Booking #' . (int) $dk['id_booking']
    : 'Langsung'
?>

</td>

<td>

<a
    href="struk.php?id=<?= (int) $dk['id_parkir'] ?>"
    class="btn"
    target="_blank"
    rel="noopener"
>
    🧾 Struk
</a>

<button
    type="button"
    class="btn btn-warning"
    onclick="openEditModal(
        <?= (int) $dk['id_parkir'] ?>,
        <?= (int) $dk['durasi_jam'] ?>
    )"
>
    ✏️ Edit Durasi
</button>

</td>

</tr>

<?php

    }

}

?>

</table>

</div>


<br>

<a
    href="../dashboard.php"
    class="btn btn-back"
>
    ← Kembali Dashboard
</a>

</div>

</main>


<!-- =========================================================
     MODAL EDIT DURASI
     ========================================================= -->

<div
    id="modalEdit"
    class="modal"
>

<div class="modal-content">

<span
    class="close"
    onclick="closeEditModal()"
>
    &times;
</span>

<h3>Edit Durasi Parkir</h3>

<form method="POST">

<input
    type="hidden"
    name="id_parkir"
    id="edit_id_parkir"
>

<div class="form-group">

<label>
    Durasi (Jam):
</label>

<input
    type="number"
    name="durasi_jam"
    id="edit_durasi_jam"
    min="1"
    required
>

</div>

<button
    type="submit"
    name="update_durasi"
>
    Simpan Perubahan
</button>

</form>

</div>

</div>


<script>

/* =========================================================
   SIDEBAR RESPONSIVE
   ========================================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('overlay');

    sidebar.classList.toggle('open');

    overlay.classList.toggle('show');
}


/* =========================================================
   MODAL EDIT DURASI
   ========================================================= */

function openEditModal(idParkir, durasiJam) {

    document.getElementById(
        'edit_id_parkir'
    ).value = idParkir;

    document.getElementById(
        'edit_durasi_jam'
    ).value = durasiJam;

    document.getElementById(
        'modalEdit'
    ).style.display = 'block';
}


function closeEditModal() {

    document.getElementById(
        'modalEdit'
    ).style.display = 'none';
}


/* =========================================================
   TUTUP MODAL KLIK LUAR
   ========================================================= */

window.onclick = function(event) {

    const modal =
        document.getElementById('modalEdit');

    if (event.target === modal) {

        modal.style.display = 'none';
    }
};


/* =========================================================
   TUTUP SIDEBAR DI HP
   ========================================================= */

document
    .querySelectorAll('.sidebar .nav-links a')
    .forEach(function(link) {

        link.addEventListener(
            'click',
            function() {

                if (window.innerWidth <= 900) {

                    document
                        .getElementById('sidebar')
                        .classList.remove('open');

                    document
                        .getElementById('overlay')
                        .classList.remove('show');
                }

            }
        );

    });

</script>

</body>
</html>