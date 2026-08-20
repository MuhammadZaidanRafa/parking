<?php

session_start();

require '../db.php';

/* =====================================================
   CEK LOGIN
===================================================== */

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

/* =====================================================
   CEK ROLE
===================================================== */

if (($_SESSION['role'] ?? '') !== 'pengguna') {
    die("Akses ditolak.");
}

/* =====================================================
   DATA USER
===================================================== */

$id_user = (int) $_SESSION['id_user'];

$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'pengguna';

$nama = trim($nama);

$inisial = !empty($nama)
    ? strtoupper(substr($nama, 0, 1))
    : 'P';


/* =====================================================
   AMBIL KENDARAAN MILIK USER
===================================================== */

$kendaraan = mysqli_query(
    $conn,
    "SELECT *
     FROM tb_kendaraan
     WHERE id_user = $id_user
     ORDER BY id_kendaraan DESC"
);

if ($kendaraan === false) {
    die("Query kendaraan error: " . mysqli_error($conn));
}

/* Jumlah kendaraan user */

$jumlah_kendaraan = mysqli_num_rows($kendaraan);


/* =====================================================
   AMBIL SEMUA AREA PARKIR
===================================================== */

$area = mysqli_query(
    $conn,
    "SELECT *
     FROM tb_area_parkir
     ORDER BY nama_area ASC"
);

if ($area === false) {
    die("Query area parkir error: " . mysqli_error($conn));
}


/* =====================================================
   CEK KETERSEDIAAN AREA
===================================================== */

$daftar_area = [];

$ada_area_tersedia = false;

while ($a = mysqli_fetch_assoc($area)) {

    $terisi = (int) ($a['terisi'] ?? 0);
    $kapasitas = (int) ($a['kapasitas'] ?? 0);

    /*
     * Area dianggap penuh jika:
     * terisi >= kapasitas
     */

    $a['penuh'] = ($terisi >= $kapasitas);

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
/* =====================================================
   SIDEBAR
===================================================== */

* {
    box-sizing: border-box;
}

.sidebar {
    width: 260px;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;

    display: flex;
    flex-direction: column;

    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;

    z-index: 1000;

    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);

    transition: transform 0.3s ease;
}

/* HEADER SIDEBAR */
.sidebar-header {
    background: #007bff;
    color: #ffffff;
    padding: 22px 20px;
}

.sidebar-header h2 {
    font-size: 18px;
    margin: 0 0 16px;
}

/* USER INFO */
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
    font-size: 18px;
}

.user-name {
    margin: 0;
    font-weight: bold;
    font-size: 15px;
    line-height: 1.3;
}

.user-role {
    font-size: 12px;
    opacity: 0.85;
    letter-spacing: 0.5px;
}

/* MENU */
.sidebar-menu {
    flex: 1;
    padding: 14px 0;
    overflow-y: auto;
}

.nav-link {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 12px 20px;

    color: #333;
    text-decoration: none;

    font-size: 15px;

    border-left: 3px solid transparent;

    transition: all 0.2s ease;
}

.nav-link .icon {
    width: 22px;
    text-align: center;
    font-size: 18px;
}

.nav-link:hover,
.nav-link.active {
    background: #eaf2ff;
    color: #007bff;
    border-left-color: #007bff;
}

/* FOOTER */
.sidebar-footer {
    padding: 12px 0;
    border-top: 1px solid #eeeeee;
}

/* LOGOUT */
.logout-link:hover {
    background: #fdeaea;
    color: #dc3545;
    border-left-color: #dc3545;
}

/* =====================================================
   MAIN CONTENT
===================================================== */

.main-content {
    margin-left: 260px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    background: #ffffff;

    padding: 15px 25px;

    display: flex;
    align-items: center;

    gap: 15px;

    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);

    position: sticky;
    top: 0;

    z-index: 500;
}

/* HAMBURGER */
.hamburger {
    display: none;

    flex-direction: column;
    justify-content: center;

    gap: 5px;

    width: 36px;
    height: 36px;

    background: none;
    border: none;

    cursor: pointer;
    padding: 6px;
}

.hamburger span {
    width: 100%;
    height: 3px;

    background: #333;

    border-radius: 2px;
}

/* =====================================================
   OVERLAY MOBILE
===================================================== */

.overlay {
    display: none;

    position: fixed;
    inset: 0;

    background: rgba(0, 0, 0, 0.4);

    z-index: 900;
}

.overlay.active {
    display: block;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 992px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .hamburger {
        display: flex;
    }
}

@media (max-width: 576px) {

    .sidebar {
        width: 85%;
        max-width: 300px;
    }

    .topbar {
        padding: 12px 15px;
    }

    .topbar h2 {
        font-size: 16px;
    }
}
</style>

</head>

<body>

    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar" id="sidebar">


        <!-- SIDEBAR HEADER -->

        <div class="sidebar-header">

            <h2>🅿️ Aplikasi Parkir</h2>


            <div class="user-info">


                <div class="avatar">

                    <?= htmlspecialchars($inisial); ?>

                </div>


                <div>

                    <p class="user-name">

                        <?= htmlspecialchars($nama); ?>

                    </p>


                    <span class="user-role">

                        <?= strtoupper(
                            htmlspecialchars($role)
                        ); ?>

                    </span>

                </div>


            </div>

        </div>



        <!-- =================================================
             MENU
        ================================================== -->

        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="../dashboard_pengguna.php"
                class="nav-link"
            >

                <span class="icon">
                    📊
                </span>

                Dashboard

            </a>



            <!-- RIWAYAT -->

            <a
                href="riwayat.php"
                class="nav-link"
            >

                <span class="icon">
                    🕒
                </span>

                Riwayat Parkir

            </a>



            <!-- KENDARAAN AKTIF -->

            <a
                href="kendaraan_saya.php"
                class="nav-link active"
            >

                <span class="icon">
                    🚗
                </span>

                Kendaraan Saya

            </a>



            <!-- PESAN TEMPAT -->

            <a
                href="pesan_tempat.php"
                class="nav-link"
            >

                <span class="icon">
                    🅿️
                </span>

                Pesan Tempat

            </a>



            <!-- BANTUAN -->

            <a
                href="help.php"
                class="nav-link"
            >

                <span class="icon">
                    ❓
                </span>

                Bantuan

            </a>



            <!-- PROFIL -->

            <a
                href="profil.php"
                class="nav-link"
            >

                <span class="icon">
                    👤
                </span>

                Profil

            </a>


        </nav>



        <!-- =================================================
             FOOTER SIDEBAR
        ================================================== -->

        <div class="sidebar-footer">


            <!-- LANDING PAGE -->

            <a
                href="../index.php"
                class="nav-link"
            >

                <span class="icon">
                    🏠
                </span>

                Landing Page

            </a>



            <!-- LOGOUT -->

            <a
                href="../logout.php"
                class="nav-link logout-link"
                onclick="
                    return confirm(
                        'Apakah Anda yakin ingin keluar?'
                    );
                "
            >

                <span class="icon">
                    🚪
                </span>

                Logout

            </a>


        </div>


    </aside>

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