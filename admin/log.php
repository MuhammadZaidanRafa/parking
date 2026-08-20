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

/* =========================================================
   HANYA ADMIN
   ========================================================= */
if ($role !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

/* =========================================================
   AMBIL DATA LOG
   ========================================================= */
$query = mysqli_query($conn, "
    SELECT
        tb_log.*,
        tb_user.nama_lengkap
    FROM tb_log
    LEFT JOIN tb_user
        ON tb_log.id_user = tb_user.id_user
    ORDER BY tb_log.waktu DESC
");

if (!$query) {
    die("Gagal mengambil data log: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Log Aktivitas - E-Parkir Admin</title>

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

    margin-bottom: 5px;
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


/* =========================================================
   HEADER LOG
   ========================================================= */

.log-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;
}

.log-header h2 {
    margin: 0;

    color: #212529;
}

.log-description {
    color: #6c757d;

    font-size: 14px;

    margin-top: 5px;
}


/* =========================================================
   TABLE
   ========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;

    border-radius: 8px;
}

table {
    width: 100%;

    min-width: 700px;

    border-collapse: collapse;

    margin-top: 10px;
}

table th,
table td {
    padding: 12px;

    border: 1px solid #ddd;

    text-align: left;

    white-space: nowrap;
}

table th {
    background: #007bff;

    color: white;

    font-weight: bold;
}

table tr:nth-child(even) {
    background: #f8f9fa;
}

table tr:hover {
    background: #eef5ff;
}

.no-data {
    text-align: center !important;

    color: #6c757d;

    padding: 25px !important;
}


/* =========================================================
   BADGE
   ========================================================= */

.activity-badge {
    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    background: #e7f1ff;

    color: #0069d9;

    font-size: 13px;

    font-weight: 600;
}

.user-name {
    font-weight: 600;

    color: #343a40;
}

.user-system {
    color: #6c757d;

    font-style: italic;
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

    transition: .2s;
}

.btn:hover {
    background: #146c43;
}

.btn-back {
    background: #6c757d;
}

.btn-back:hover {
    background: #545b62;
}


/* =========================================================
   INFO BOX
   ========================================================= */

.info-box {
    display: flex;

    align-items: center;

    gap: 12px;

    background: #eef5ff;

    border-left: 4px solid #007bff;

    padding: 12px 15px;

    margin-bottom: 20px;

    border-radius: 6px;

    color: #495057;

    font-size: 14px;
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

    .log-header {
        display: block;
    }

    .log-header h2 {
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

    .log-header h2 {
        font-size: 20px;

        margin-left: 55px;
    }

    .log-description {
        font-size: 13px;

        line-height: 1.5;
    }

    table th,
    table td {
        padding: 10px;

        font-size: 13px;
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

        <b>
            <?= htmlspecialchars(strtoupper($role)) ?>
        </b>

        User:

        <b>
            <?= htmlspecialchars($nama) ?>
        </b>

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
            <a href="transaksi.php">
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
            <a href="log.php" class="active">
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


    <!-- HEADER -->

    <div class="log-header">

        <div>

            <h2>
                📋 Log Aktivitas
            </h2>

            <p class="log-description">
                Riwayat aktivitas pengguna dalam sistem E-Parkir.
            </p>

        </div>

    </div>


    <!-- INFO -->

    <div class="info-box">

        <span style="font-size:20px;">
            ℹ️
        </span>

        <span>
            Semua aktivitas yang tercatat pada sistem ditampilkan
            pada halaman ini.
        </span>

    </div>


    <!-- =====================================================
         TABEL LOG
         ===================================================== -->

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th width="7%">
                        No
                    </th>

                    <th>
                        Nama User
                    </th>

                    <th>
                        Aktivitas
                    </th>

                    <th>
                        Tanggal & Waktu
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            $no = 1;

            if (mysqli_num_rows($query) > 0):

                while ($row = mysqli_fetch_assoc($query)):

            ?>

                <tr>

                    <!-- NO -->

                    <td>
                        <?= $no++; ?>
                    </td>


                    <!-- USER -->

                    <td>

                        <?php if (!empty($row['nama_lengkap'])): ?>

                            <span class="user-name">
                                👤
                                <?= htmlspecialchars($row['nama_lengkap']); ?>
                            </span>

                        <?php else: ?>

                            <span class="user-system">
                                Sistem / User Tidak Diketahui
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- AKTIVITAS -->

                    <td>

                        <span class="activity-badge">

                            <?= htmlspecialchars(
                                $row['aktivitas']
                            ); ?>

                        </span>

                    </td>


                    <!-- WAKTU -->

                    <td>

                        <?= !empty($row['waktu'])
                            ? date(
                                'd-m-Y H:i:s',
                                strtotime($row['waktu'])
                            )
                            : '-';
                        ?>

                    </td>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td
                        colspan="4"
                        class="no-data"
                    >

                        📋 Belum ada aktivitas yang tercatat.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- =====================================================
         KEMBALI
         ===================================================== -->

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
     JAVASCRIPT
     ========================================================= -->

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
   TUTUP SIDEBAR SAAT MENU DIPILIH DI HP
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