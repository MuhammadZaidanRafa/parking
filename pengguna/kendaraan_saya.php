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
$inisial = strtoupper(substr($nama, 0, 1));


/* =====================================================
   AMBIL KENDARAAN + BOOKING TERBARU
===================================================== */

$stmt = $conn->prepare("
    SELECT
        k.*,
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
              AND b2.status IN ('booking', 'aktif')
            ORDER BY b2.created_at DESC
            LIMIT 1
        )

    LEFT JOIN tb_area_parkir a
        ON a.id_area = b.id_area

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

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kendaraan Saya - Aplikasi Parkir</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f6f9;
    color: #333;
}


/* =====================================================
   LAYOUT
===================================================== */

.wrapper {
    display: flex;
    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

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

    transition: transform 0.3s ease;

    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
}


/* =====================================================
   SIDEBAR HEADER
===================================================== */

.sidebar-header {
    background: #007bff;
    color: white;
    padding: 22px 20px;
}

.sidebar-header h2 {
    font-size: 1.15rem;
    margin-bottom: 16px;
}


/* =====================================================
   USER INFO
===================================================== */

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    flex-shrink: 0;

    width: 42px;
    height: 42px;

    border-radius: 50%;

    background: rgba(255, 255, 255, 0.25);

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
    font-size: 1.1rem;
}

.user-name {
    font-weight: bold;
    font-size: 0.95rem;
    line-height: 1.3;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.85;
    letter-spacing: 0.5px;
}


/* =====================================================
   SIDEBAR MENU
===================================================== */

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

    font-size: 0.95rem;

    border-left: 3px solid transparent;

    transition: all 0.2s ease;
}

.nav-link .icon {
    font-size: 1.1rem;

    width: 22px;

    text-align: center;
}

.nav-link:hover,
.nav-link.active {
    background: #eaf2ff;

    color: #007bff;

    border-left-color: #007bff;
}


/* =====================================================
   SIDEBAR FOOTER
===================================================== */

.sidebar-footer {
    padding: 12px 0;

    border-top: 1px solid #eee;
}

.logout-link:hover {
    background: #fdeaea;

    color: #dc3545;

    border-left-color: #dc3545;
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
   MAIN CONTENT
===================================================== */

.main-content {
    flex: 1;

    min-width: 0;

    margin-left: 260px;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    background: white;

    padding: 15px 25px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);

    position: sticky;

    top: 0;

    z-index: 500;
}

.topbar h2 {
    font-size: 1.1rem;

    color: #333;
}


/* =====================================================
   HAMBURGER
===================================================== */

.hamburger {
    display: none;

    flex-direction: column;

    justify-content: center;

    gap: 5px;

    width: 32px;

    height: 32px;

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
   CONTAINER
===================================================== */

.container {
    width: 95%;

    max-width: 1500px;

    margin: 25px auto;
}


/* =====================================================
   BUTTON TAMBAH
===================================================== */

.btn {
    display: inline-block;

    margin-bottom: 20px;

    padding: 10px 20px;

    background: #28a745;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    transition: 0.2s;
}

.btn:hover {
    background: #218838;
}


/* =====================================================
   TABLE WRAPPER
===================================================== */

.table-wrapper {
    width: 100%;

    overflow-x: auto;

    background: white;

    border-radius: 10px;

    box-shadow:
        0 3px 10px rgba(0, 0, 0, 0.08);
}


/* =====================================================
   TABLE
===================================================== */

table {
    width: 100%;

    min-width: 900px;

    border-collapse: collapse;

    background: white;
}

table th,
table td {
    padding: 12px;

    border: 1px solid #ddd;

    text-align: center;
}

table th {
    background: #007bff;

    color: white;

    font-size: 13px;
}

table td {
    font-size: 13px;
}

table tr:hover td {
    background: #f8fbff;
}


/* =====================================================
   BOOKING AREA
===================================================== */

.booking-area {
    display: block;

    font-weight: bold;

    color: #222;
}

.booking-waktu {
    display: block;

    color: #666;

    font-size: 12px;

    margin: 5px 0;
}


/* =====================================================
   BADGE
===================================================== */

.badge {
    display: inline-block;

    padding: 4px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}

.badge-booking {
    background: #ffc107;

    color: #333;
}

.badge-aktif {
    background: #28a745;

    color: white;
}

.badge-none {
    background: #6c757d;

    color: white;
}


/* =====================================================
   LINK BOOKING
===================================================== */

.link-booking {
    display: inline-block;

    margin-top: 7px;

    color: #007bff;

    font-size: 12px;

    font-weight: bold;

    text-decoration: none;
}

.link-booking:hover {
    text-decoration: underline;
}


/* =====================================================
   KUITANSI
===================================================== */

.link-kuitansi {
    display: inline-block;

    margin-top: 7px;

    color: #28a745;

    font-size: 12px;

    font-weight: bold;

    text-decoration: none;
}

.link-kuitansi:hover {
    text-decoration: underline;
}


/* =====================================================
   KONFIRMASI
===================================================== */

.btn-konfirmasi-tempat {
    display: inline-block;

    margin-top: 7px;

    padding: 6px 10px;

    background: #ff9800;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 11px;

    font-weight: bold;
}

.btn-konfirmasi-tempat:hover {
    background: #e68900;
}


/* =====================================================
   BATAL BOOKING
===================================================== */

.btn-batal-booking {
    display: inline-block;

    margin-top: 5px;

    padding: 6px 10px;

    background: #dc3545;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 11px;

    font-weight: bold;
}

.btn-batal-booking:hover {
    background: #bd2130;
}


/* =====================================================
   AKSI EDIT
===================================================== */

.action-edit {
    color: #007bff;

    font-weight: bold;

    text-decoration: none;
}


/* =====================================================
   AKSI HAPUS
===================================================== */

.action-delete {
    color: #dc3545;

    font-weight: bold;

    text-decoration: none;
}


/* =====================================================
   RESPONSIVE TABLE
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


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 576px) {

    .sidebar {
        width: 85%;

        max-width: 300px;
    }

    .container {
        width: 94%;

        margin: 15px auto;
    }

    .topbar {
        padding: 12px 15px;
    }

    .topbar h2 {
        font-size: 1rem;
    }

    .btn {
        width: 100%;

        text-align: center;
    }
}

</style>

</head>


<body>


<div class="wrapper">


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



    <!-- =================================================
         OVERLAY
    ================================================== -->

    <div
        class="overlay"
        id="overlay"
    ></div>



    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <div class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <button
                class="hamburger"
                id="hamburgerBtn"
                type="button"
                aria-label="Buka menu"
            >

                <span></span>

                <span></span>

                <span></span>

            </button>


            <h2>
                Kendaraan Saya
            </h2>


        </header>



        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="container">


            <!-- TAMBAH KENDARAAN -->

            <a
                href="daftarkan_kendaraan.php"
                class="btn"
            >

                + Daftarkan Kendaraan

            </a>



            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                Plat Nomor
                            </th>

                            <th>
                                Jenis
                            </th>

                            <th>
                                Merk
                            </th>

                            <th>
                                Warna
                            </th>

                            <th>
                                Tempat Booking
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                    <?php

                    $no = 1;


                    if ($data->num_rows === 0):

                    ?>

                        <tr>

                            <td colspan="7">

                                Belum ada kendaraan
                                yang terdaftar.

                            </td>

                        </tr>


                    <?php

                    else:

                        while ($row = $data->fetch_assoc()):

                    ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?= $no++; ?>

                            </td>



                            <!-- PLAT -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['plat_nomor'] ?? '-'
                                ); ?>

                            </td>



                            <!-- JENIS -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['jenis_kendaraan'] ?? '-'
                                ); ?>

                            </td>



                            <!-- MERK -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['merk'] ?? '-'
                                ); ?>

                            </td>



                            <!-- WARNA -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['warna'] ?? '-'
                                ); ?>

                            </td>



                            <!-- BOOKING -->

                            <td>


                            <?php if (
                                !empty($row['id_booking'])
                            ): ?>


                                <!-- AREA -->

                                <span class="booking-area">

                                    <?= htmlspecialchars(
                                        $row['nama_area'] ?? '-'
                                    ); ?>

                                </span>



                                <!-- WAKTU -->

                                <span class="booking-waktu">

                                    <?= !empty($row['tanggal'])
                                        ? date(
                                            'd-m-Y',
                                            strtotime(
                                                $row['tanggal']
                                            )
                                        )
                                        : '-';
                                    ?>

                                    •

                                    <?= !empty($row['jam_masuk'])
                                        ? substr(
                                            $row['jam_masuk'],
                                            0,
                                            5
                                        )
                                        : '-';
                                    ?>

                                    (

                                    <?= (int) (
                                        $row['estimasi_jam']
                                        ?? 0
                                    ); ?>

                                    jam)

                                </span>



                                <!-- STATUS -->

                                <?php if (
                                    ($row['status_booking'] ?? '')
                                    === 'aktif'
                                ): ?>


                                    <span
                                        class="badge badge-aktif"
                                    >

                                        Aktif

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge badge-booking"
                                    >

                                        Menunggu

                                    </span>


                                <?php endif; ?>



                                <br>



                                <!-- BOOKING STATUS -->

                                <?php if (
                                    ($row['status_booking'] ?? '')
                                    === 'booking'
                                ): ?>


                                    <!-- KONFIRMASI -->

                                    <a
                                        href="konfirmasi_sudah_ditempat.php?id_booking=<?= (int) $row['id_booking']; ?>"
                                        class="btn-konfirmasi-tempat"
                                    >

                                        📍 Sudah di Tempat

                                    </a>


                                    <br>



                                    <!-- BATAL -->

                                    <a
                                        href="batal_booking.php?id_booking=<?= (int) $row['id_booking']; ?>"
                                        class="btn-batal-booking"
                                        onclick="
                                            return confirm(
                                                'Apakah Anda yakin ingin membatalkan pesanan booking ini?'
                                            );
                                        "
                                    >

                                        ❌ Batal Pesanan

                                    </a>


                                    <br>


                                <?php endif; ?>



                                <!-- KUITANSI -->

                                <a
                                    href="quitansi.php?id_booking=<?= (int) $row['id_booking']; ?>"
                                    class="link-kuitansi"
                                >

                                    🧾 Lihat Kuitansi

                                </a>


                            <?php else: ?>


                                <!-- BELUM BOOKING -->

                                <span
                                    class="badge badge-none"
                                >

                                    Belum Booking

                                </span>


                                <br>



                                <!-- BOOKING TEMPAT -->

                                <a
                                    href="booking_parkir.php?id_kendaraan=<?= (int) $row['id_kendaraan']; ?>"
                                    class="link-booking"
                                >

                                    + Booking Tempat

                                </a>


                            <?php endif; ?>


                            </td>



                            <!-- AKSI -->

                            <td>


                                <a
                                    href="edit_kendaraan.php?id=<?= (int) $row['id_kendaraan']; ?>"
                                    class="action-edit"
                                >

                                    Edit

                                </a>


                                |


                                <a
                                    href="hapus_kendaraan.php?id=<?= (int) $row['id_kendaraan']; ?>"
                                    class="action-delete"
                                    onclick="
                                        return confirm(
                                            'Hapus kendaraan ini?'
                                        );
                                    "
                                >

                                    Hapus

                                </a>


                            </td>


                        </tr>


                    <?php

                        endwhile;

                    endif;

                    ?>


                    </tbody>


                </table>


            </div>



            <!-- =================================================
                 KEMBALI
            ================================================== -->

            <a
                href="../dashboard_pengguna.php"
                class="btn"
                style="
                    background:#007bff;
                    margin-top:20px;
                "
            >

                ← Kembali ke Dashboard

            </a>


        </div>


    </div>


</div>



<!-- =====================================================
     JAVASCRIPT SIDEBAR
===================================================== -->

<script>


const sidebar =
    document.getElementById(
        'sidebar'
    );


const overlay =
    document.getElementById(
        'overlay'
    );


const hamburgerBtn =
    document.getElementById(
        'hamburgerBtn'
    );


/* =====================================================
   TOGGLE SIDEBAR
===================================================== */

function toggleSidebar() {

    sidebar.classList.toggle(
        'active'
    );

    overlay.classList.toggle(
        'active'
    );

}


/* =====================================================
   HAMBURGER
===================================================== */

hamburgerBtn.addEventListener(
    'click',
    toggleSidebar
);


/* =====================================================
   OVERLAY
===================================================== */

overlay.addEventListener(
    'click',
    toggleSidebar
);


/* =====================================================
   TUTUP SIDEBAR SAAT MENU DIKLIK
===================================================== */

document
    .querySelectorAll(
        '.sidebar .nav-link'
    )
    .forEach(function(link) {

        link.addEventListener(
            'click',
            function() {

                if (
                    window.innerWidth <= 992
                ) {

                    sidebar.classList.remove(
                        'active'
                    );

                    overlay.classList.remove(
                        'active'
                    );

                }

            }
        );

    });


/* =====================================================
   ESC UNTUK MENUTUP SIDEBAR
===================================================== */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key === 'Escape'
        ) {

            sidebar.classList.remove(
                'active'
            );

            overlay.classList.remove(
                'active'
            );

        }

    }
);

</script>


</body>

</html>

<?php

$stmt->close();

?>