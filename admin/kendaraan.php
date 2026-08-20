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

$error = null;

/* =========================================================
   PROSES TAMBAH DATA
   ========================================================= */
if (isset($_POST['tambah'])) {

    $plat_nomor      = trim($_POST['plat_nomor'] ?? '');
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $warna           = trim($_POST['warna'] ?? '');
    $pemilik         = trim($_POST['pemilik'] ?? '');
    $id_user_form    = !empty($_POST['id_user'])
        ? (int) $_POST['id_user']
        : null;

    if ($plat_nomor === '' || $jenis_kendaraan === '') {
        $error = "Plat nomor dan jenis kendaraan wajib diisi.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO tb_kendaraan
            (
                plat_nomor,
                jenis_kendaraan,
                warna,
                pemilik,
                id_user
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssi",
            $plat_nomor,
            $jenis_kendaraan,
            $warna,
            $pemilik,
            $id_user_form
        );

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: kendaraan.php?status=success_add");
            exit;
        }

        $error = "Gagal menambah data: " . $stmt->error;
        $stmt->close();
    }
}

/* =========================================================
   PROSES EDIT DATA
   ========================================================= */
if (isset($_POST['edit'])) {

    $id_kendaraan    = (int) ($_POST['id_kendaraan'] ?? 0);
    $plat_nomor      = trim($_POST['plat_nomor'] ?? '');
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $warna           = trim($_POST['warna'] ?? '');
    $pemilik         = trim($_POST['pemilik'] ?? '');
    $id_user_form    = !empty($_POST['id_user'])
        ? (int) $_POST['id_user']
        : null;

    if ($id_kendaraan <= 0 || $plat_nomor === '' || $jenis_kendaraan === '') {

        $error = "Data kendaraan tidak lengkap.";

    } else {

        $stmt = $conn->prepare("
            UPDATE tb_kendaraan
            SET
                plat_nomor = ?,
                jenis_kendaraan = ?,
                warna = ?,
                pemilik = ?,
                id_user = ?
            WHERE id_kendaraan = ?
        ");

        $stmt->bind_param(
            "ssssii",
            $plat_nomor,
            $jenis_kendaraan,
            $warna,
            $pemilik,
            $id_user_form,
            $id_kendaraan
        );

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: kendaraan.php?status=success_update");
            exit;
        }

        $error = "Gagal memperbarui data: " . $stmt->error;
        $stmt->close();
    }
}

/* =========================================================
   PROSES HAPUS DATA
   ========================================================= */
if (isset($_GET['hapus'])) {

    $id_kendaraan = (int) $_GET['hapus'];

    if ($id_kendaraan > 0) {

        $stmt = $conn->prepare("
            DELETE FROM tb_kendaraan
            WHERE id_kendaraan = ?
        ");

        $stmt->bind_param("i", $id_kendaraan);

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: kendaraan.php?status=success_delete");
            exit;
        }

        $error = "Gagal menghapus data: " . $stmt->error;
        $stmt->close();
    }
}

/* =========================================================
   AMBIL DATA UNTUK EDIT
   ========================================================= */
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {

    $id_edit = (int) $_GET['edit'];

    if ($id_edit > 0) {

        $stmt = $conn->prepare("
            SELECT *
            FROM tb_kendaraan
            WHERE id_kendaraan = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id_edit);
        $stmt->execute();

        $result_edit = $stmt->get_result();

        if ($result_edit->num_rows > 0) {
            $edit_data = $result_edit->fetch_assoc();
        }

        $stmt->close();
    }
}

/* =========================================================
   AMBIL DATA KENDARAAN
   ========================================================= */
$query_kendaraan = "
    SELECT
        k.*,
        u.nama_lengkap,
        t.id_parkir,
        t.waktu_masuk,
        t.waktu_keluar,
        t.status,
        TIMESTAMPDIFF(HOUR, t.waktu_masuk, NOW()) AS lama_parkir
    FROM tb_kendaraan k
    LEFT JOIN tb_user u
        ON k.id_user = u.id_user
    LEFT JOIN tb_transaksi t
        ON k.id_kendaraan = t.id_kendaraan
        AND t.status = 'masuk'
    ORDER BY k.id_kendaraan DESC
";

$data_kendaraan = mysqli_query($conn, $query_kendaraan);

/* =========================================================
   AMBIL DATA USER
   ========================================================= */
$data_user = mysqli_query(
    $conn,
    "
    SELECT
        id_user,
        nama_lengkap
    FROM tb_user
    ORDER BY nama_lengkap ASC
    "
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Kendaraan - E-Parkir Admin</title>

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
   FORM
   ========================================================= */

.form-card {
    background: #f8f9fa;

    border: 1px solid #e9ecef;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 25px;
}

.form-card h3 {
    margin-top: 0;

    margin-bottom: 20px;
}

.form-row {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 15px;
}

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

    background: white;
}

input:focus,
select:focus {
    border-color: #007bff;

    box-shadow:
        0 0 0 3px rgba(0,123,255,.1);
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

.btn-primary {
    background: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
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
   TABLE
   ========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    min-width: 1000px;

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
   STATUS
   ========================================================= */

.status {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.status-parkir {
    background: #d1e7dd;

    color: #0f5132;
}

.status-keluar {
    background: #f8d7da;

    color: #842029;
}

.status-kosong {
    background: #e9ecef;

    color: #495057;
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
   EMPTY DATA
   ========================================================= */

.empty-data {
    text-align: center;

    padding: 25px;

    color: #6c757d;
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

    .form-row {
        grid-template-columns: 1fr;
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

    .form-card {
        padding: 14px;
    }

    .btn {
        font-size: 12px;

        padding: 7px 10px;
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
            <a href="kendaraan.php" class="active">
                <span>🚗</span>
                Kelola Kendaraan
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
    🚗 Kelola Data Kendaraan
</h2>


<!-- =========================================================
     NOTIFIKASI
     ========================================================= -->

<?php if (isset($_GET['status'])): ?>

    <?php if ($_GET['status'] === 'success_add'): ?>

        <div class="alert-success">
            ✅ Data kendaraan berhasil ditambahkan.
        </div>

    <?php elseif ($_GET['status'] === 'success_update'): ?>

        <div class="alert-success">
            ✅ Data kendaraan berhasil diperbarui.
        </div>

    <?php elseif ($_GET['status'] === 'success_delete'): ?>

        <div class="alert-success">
            ✅ Data kendaraan berhasil dihapus.
        </div>

    <?php endif; ?>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert-error">
        ❌ <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<!-- =========================================================
     FORM TAMBAH / EDIT
     ========================================================= -->

<div class="form-card">

<h3>
    <?= $edit_data
        ? '✏️ Edit Data Kendaraan'
        : '➕ Tambah Kendaraan Baru'
    ?>
</h3>

<form
    action="kendaraan.php<?= $edit_data ? '?edit=' . (int)$edit_data['id_kendaraan'] : '' ?>"
    method="POST"
>

<?php if ($edit_data): ?>

    <input
        type="hidden"
        name="id_kendaraan"
        value="<?= (int)$edit_data['id_kendaraan'] ?>"
    >

<?php endif; ?>


<div class="form-row">

    <div class="form-group">

        <label for="plat_nomor">
            Plat Nomor
        </label>

        <input
            type="text"
            name="plat_nomor"
            id="plat_nomor"
            value="<?= $edit_data
                ? htmlspecialchars($edit_data['plat_nomor'])
                : ''
            ?>"
            placeholder="Contoh: AB 1234 CD"
            required
        >

    </div>


    <div class="form-group">

        <label for="jenis_kendaraan">
            Jenis Kendaraan
        </label>

        <select
            name="jenis_kendaraan"
            id="jenis_kendaraan"
            required
        >

            <option value="">
                -- Pilih Jenis --
            </option>

            <option
                value="motor"
                <?= (
                    $edit_data &&
                    $edit_data['jenis_kendaraan'] === 'motor'
                ) ? 'selected' : '' ?>
            >
                Motor
            </option>

            <option
                value="mobil"
                <?= (
                    $edit_data &&
                    $edit_data['jenis_kendaraan'] === 'mobil'
                ) ? 'selected' : '' ?>
            >
                Mobil
            </option>

            <option
                value="lainnya"
                <?= (
                    $edit_data &&
                    $edit_data['jenis_kendaraan'] === 'lainnya'
                ) ? 'selected' : '' ?>
            >
                Lainnya
            </option>

        </select>

    </div>

</div>


<div class="form-row">

    <div class="form-group">

        <label for="warna">
            Warna Kendaraan
        </label>

        <input
            type="text"
            name="warna"
            id="warna"
            value="<?= $edit_data
                ? htmlspecialchars($edit_data['warna'])
                : ''
            ?>"
            placeholder="Contoh: Hitam"
        >

    </div>


    <div class="form-group">

        <label for="pemilik">
            Nama Pemilik
        </label>

        <input
            type="text"
            name="pemilik"
            id="pemilik"
            value="<?= $edit_data
                ? htmlspecialchars($edit_data['pemilik'])
                : ''
            ?>"
            placeholder="Contoh: Budi Santoso"
        >

    </div>

</div>


<div class="form-group">

    <label for="id_user">
        👤 Akun User
    </label>

    <select
        name="id_user"
        id="id_user"
    >

        <option value="">
            -- Tanpa Akun User --
        </option>

        <?php

        if ($data_user && $data_user->num_rows > 0):

            while ($user = $data_user->fetch_assoc()):

        ?>

            <option
                value="<?= (int)$user['id_user'] ?>"
                <?= (
                    $edit_data &&
                    (int)$edit_data['id_user'] ===
                    (int)$user['id_user']
                ) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($user['nama_lengkap']) ?>
            </option>

        <?php

            endwhile;

        endif;

        ?>

    </select>

</div>


<div>

<?php if ($edit_data): ?>

    <button
        type="submit"
        name="edit"
        class="btn btn-warning"
    >
        💾 Simpan Perubahan
    </button>

    <a
        href="kendaraan.php"
        class="btn btn-back"
    >
        ✖ Batal
    </a>

<?php else: ?>

    <button
        type="submit"
        name="tambah"
        class="btn btn-primary"
    >
        ➕ Tambah Kendaraan
    </button>

<?php endif; ?>

</div>

</form>

</div>


<hr>


<!-- =========================================================
     DAFTAR KENDARAAN
     ========================================================= -->

<h3>
    🚗 Daftar Kendaraan
</h3>

<div class="table-wrapper">

<table>

<thead>

<tr>

    <th width="5%">
        No
    </th>

    <th>
        Plat Nomor
    </th>

    <th>
        Jenis
    </th>

    <th>
        Warna
    </th>

    <th>
        Pemilik
    </th>

    <th>
        Akun Terhubung
    </th>

    <th>
        Status
    </th>

    <th>
        Lama Parkir
    </th>

    <th>
        Aksi
    </th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

if (
    $data_kendaraan &&
    mysqli_num_rows($data_kendaraan) > 0
):

    while (
        $row = mysqli_fetch_assoc($data_kendaraan)
    ):

?>

<tr>

    <td>
        <?= $no++ ?>
    </td>


    <td>

        <strong>
            <?= htmlspecialchars(
                strtoupper($row['plat_nomor'])
            ) ?>
        </strong>

    </td>


    <td>

        <?= htmlspecialchars(
            ucfirst($row['jenis_kendaraan'])
        ) ?>

    </td>


    <td>

        <?= !empty($row['warna'])
            ? htmlspecialchars($row['warna'])
            : '-'
        ?>

    </td>


    <td>

        <?= !empty($row['pemilik'])
            ? htmlspecialchars($row['pemilik'])
            : '-'
        ?>

    </td>


    <td>

        <?php if (!empty($row['nama_lengkap'])): ?>

            👤 <?= htmlspecialchars(
                $row['nama_lengkap']
            ) ?>

        <?php else: ?>

            <span style="color:#6c757d;">
                Tidak Ada
            </span>

        <?php endif; ?>

    </td>


    <!-- STATUS -->

    <td>

        <?php if ($row['status'] === 'masuk'): ?>

            <span class="status status-parkir">
                🟢 Sedang Parkir
            </span>

        <?php elseif ($row['status'] === 'keluar'): ?>

            <span class="status status-keluar">
                🔴 Keluar
            </span>

        <?php else: ?>

            <span class="status status-kosong">
                ⚪ Tidak Parkir
            </span>

        <?php endif; ?>

    </td>


    <!-- LAMA PARKIR -->

    <td>

        <?php

        if (
            $row['status'] === 'masuk' &&
            $row['lama_parkir'] !== null
        ) {

            echo (int)$row['lama_parkir'] . " Jam";

        } else {

            echo "-";

        }

        ?>

    </td>


    <!-- AKSI -->

    <td>

        <a
            href="kendaraan.php?edit=<?= (int)$row['id_kendaraan'] ?>"
            class="btn btn-warning"
        >
            ✏️ Edit
        </a>

        <a
            href="kendaraan.php?hapus=<?= (int)$row['id_kendaraan'] ?>"
            class="btn btn-danger"
            onclick="return confirm(
                'Yakin ingin menghapus kendaraan ini?'
            );"
        >
            🗑️ Hapus
        </a>

    </td>

</tr>

<?php

    endwhile;

else:

?>

<tr>

    <td
        colspan="9"
        class="empty-data"
    >
        🚗 Belum ada data kendaraan.
    </td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>


<br>


<!-- =========================================================
     KEMBALI
     ========================================================= -->

<a
    href="../dashboard.php"
    class="btn btn-back"
>
    ← Kembali Dashboard
</a>


</div>

</main>


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