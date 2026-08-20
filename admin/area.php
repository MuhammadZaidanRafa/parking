<?php

session_start();
require_once "../db.php";

// ================= CEK LOGIN =================
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

// ================= CEK ROLE =================
if ($_SESSION['role'] !== "admin") {
    die("Akses ditolak!");
}

$nama = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

// ================= TAMBAH =================
if (isset($_POST['tambah'])) {

    $nama_area = trim($_POST['nama_area']);
    $kapasitas = (int) $_POST['kapasitas'];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi)
         VALUES (?, ?, 0)"
    );

    mysqli_stmt_bind_param($stmt, "si", $nama_area, $kapasitas);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: area.php");
    exit;
}

// ================= HAPUS =================
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM tb_area_parkir WHERE id_area = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: area.php");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update'])) {

    $id       = (int) $_POST['id'];
    $nama_area = trim($_POST['nama_area']);
    $kapasitas = (int) $_POST['kapasitas'];
    $terisi    = (int) $_POST['terisi'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE tb_area_parkir
         SET nama_area = ?, kapasitas = ?, terisi = ?
         WHERE id_area = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "siii",
        $nama_area,
        $kapasitas,
        $terisi,
        $id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: area.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Area Parkir - E-Parkir Admin</title>

<style>

/* ================= RESET ================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f6f9;
    min-height: 100vh;
}

/* ================= SIDEBAR ================= */

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

    z-index: 1000;
}

/* Brand */

.sidebar .brand {
    padding: 20px;

    font-size: 20px;
    font-weight: bold;

    background: #0f172a;

    border-bottom: 1px solid #334155;

    text-align: center;
}

/* User info */

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

/* Navigation */

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

    transition: 0.2s;
}

.sidebar .nav-links li a:hover,
.sidebar .nav-links li a.active {
    background: #007bff;

    color: #fff;
}

/* Logout */

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

    transition: 0.2s;
}

.sidebar .logout-btn:hover {
    background: #bd2130;
}

/* ================= MAIN CONTENT ================= */

.main-content {
    margin-left: 250px;

    min-height: 100vh;

    display: flex;
    flex-direction: column;
}

/* Header */

header {
    background: #007bff;

    color: white;

    padding: 20px 30px;

    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

header h2 {
    margin-bottom: 5px;
}

/* Container */

.container {
    padding: 30px;

    width: 100%;

    max-width: 1200px;

    margin: auto;
}

/* ================= CARD ================= */

.card {
    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow: 0 5px 15px rgba(0,0,0,.08);

    margin-bottom: 25px;
}

.card h2 {
    margin-bottom: 20px;

    color: #2563eb;

    font-size: 22px;
}

/* ================= FORM ================= */

input {
    width: 100%;

    padding: 11px;

    margin-top: 5px;

    margin-bottom: 15px;

    border: 1px solid #ccc;

    border-radius: 5px;

    font-size: 14px;

    outline: none;
}

input:focus {
    border-color: #2563eb;

    box-shadow: 0 0 0 2px rgba(37,99,235,.1);
}

button {
    padding: 10px 20px;

    background: #2563eb;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    font-weight: bold;

    transition: .2s;
}

button:hover {
    background: #1d4ed8;
}

/* ================= TABLE ================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 700px;
}

table th {
    background: #2563eb;

    color: white;

    padding: 12px;

    text-align: left;
}

table td {
    padding: 12px;

    border-bottom: 1px solid #ddd;
}

table tr:hover td {
    background: #f8fafc;
}

/* ================= BUTTON AKSI ================= */

.edit {
    display: inline-block;

    background: #f59e0b;

    color: white;

    padding: 6px 10px;

    text-decoration: none;

    border-radius: 4px;

    margin-right: 5px;

    font-size: 13px;
}

.edit:hover {
    background: #d97706;
}

.hapus {
    display: inline-block;

    background: #dc2626;

    color: white;

    padding: 6px 10px;

    text-decoration: none;

    border-radius: 4px;

    font-size: 13px;
}

.hapus:hover {
    background: #b91c1c;
}

/* ================= STATUS ================= */

.status-aman {
    color: #16a34a;

    font-weight: bold;
}

.status-penuh {
    color: #dc2626;

    font-weight: bold;
}

/* ================= RESPONSIVE ================= */

@media (max-width: 768px) {

    .sidebar {
        width: 100%;

        position: relative;

        height: auto;
    }

    .sidebar .nav-links {
        max-height: 350px;
    }

    .main-content {
        margin-left: 0;
    }

    header {
        padding: 18px 20px;
    }

    .container {
        padding: 20px 15px;
    }

    .card {
        padding: 15px;
    }

    .card h2 {
        font-size: 19px;
    }

    .sidebar .nav-links li a {
        padding: 11px 20px;
    }

}

</style>

</head>

<body>

<!-- ================= SIDEBAR ================= -->

<aside class="sidebar">

    <div class="brand">
        🅿️ E-Parkir Admin
    </div>

    <div class="user-info">

        Role:
        <b><?= strtoupper(htmlspecialchars($role)); ?></b>

        User:
        <b><?= htmlspecialchars($nama); ?></b>

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
            <a href="area.php" class="active">
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
            <a href="log.php">
                <span>📋</span>
                Log Aktivitas
            </a>
        </li>

    </ul>

    <div class="logout-container">

        <a href="../logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</aside>


<!-- ================= KONTEN UTAMA ================= -->

<div class="main-content">

    <header>

        <h2>🅿️ Area Parkir</h2>

        <p>
            Kelola area dan kapasitas parkir
        </p>

    </header>


    <div class="container">

        <!-- ================= TAMBAH AREA ================= -->

        <div class="card">

            <h2>Tambah Area Parkir</h2>

            <form method="POST">

                <input
                    type="text"
                    name="nama_area"
                    placeholder="Nama Area"
                    required
                >

                <input
                    type="number"
                    name="kapasitas"
                    placeholder="Kapasitas"
                    min="1"
                    required
                >

                <button type="submit" name="tambah">
                    + Simpan Area
                </button>

            </form>

        </div>


        <!-- ================= DATA AREA ================= -->

        <div class="card">

            <h2>Data Area Parkir</h2>

            <div class="table-wrapper">

                <table>

                    <tr>

                        <th>No</th>

                        <th>Nama Area</th>

                        <th>Kapasitas</th>

                        <th>Terisi</th>

                        <th>Sisa</th>

                        <th>Status</th>

                        <th>Aksi</th>

                    </tr>

                    <?php

                    $no = 1;

                    $data = mysqli_query(
                        $conn,
                        "SELECT * FROM tb_area_parkir
                         ORDER BY id_area DESC"
                    );

                    while ($d = mysqli_fetch_assoc($data)):

                        $kapasitas = (int) $d['kapasitas'];
                        $terisi = (int) $d['terisi'];

                        $sisa = $kapasitas - $terisi;

                    ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['nama_area']); ?>
                        </td>

                        <td>
                            <?= $kapasitas; ?>
                        </td>

                        <td>
                            <?= $terisi; ?>
                        </td>

                        <td>
                            <?= $sisa; ?>
                        </td>

                        <td>

                            <?php if ($sisa <= 0): ?>

                                <span class="status-penuh">
                                    PENUH
                                </span>

                            <?php else: ?>

                                <span class="status-aman">
                                    TERSEDIA
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <a
                                class="edit"
                                href="?edit=<?= $d['id_area']; ?>"
                            >
                                Edit
                            </a>

                            <a
                                class="hapus"
                                href="?hapus=<?= $d['id_area']; ?>"
                                onclick="return confirm('Hapus area ini?')"
                            >
                                Hapus
                            </a>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                </table>

            </div>

        </div>


        <!-- ================= EDIT AREA ================= -->

        <?php

        if (isset($_GET['edit'])):

            $id = (int) $_GET['edit'];

            $result = mysqli_query(
                $conn,
                "SELECT * FROM tb_area_parkir
                 WHERE id_area = $id"
            );

            $e = mysqli_fetch_assoc($result);

            if ($e):

        ?>

        <div class="card">

            <h2>✏️ Edit Area Parkir</h2>

            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= $e['id_area']; ?>"
                >

                <input
                    type="text"
                    name="nama_area"
                    value="<?= htmlspecialchars($e['nama_area']); ?>"
                    required
                >

                <input
                    type="number"
                    name="kapasitas"
                    value="<?= $e['kapasitas']; ?>"
                    min="1"
                    required
                >

                <input
                    type="number"
                    name="terisi"
                    value="<?= $e['terisi']; ?>"
                    min="0"
                    required
                >

                <button
                    type="submit"
                    name="update"
                >
                    Update Area
                </button>

                <a
                    href="area.php"
                    style="
                        display:inline-block;
                        margin-left:8px;
                        padding:10px 20px;
                        background:#64748b;
                        color:white;
                        text-decoration:none;
                        border-radius:5px;
                    "
                >
                    Batal
                </a>

            </form>

        </div>

        <?php

            endif;

        endif;

        ?>

    </div>

</div>

</body>

</html>