<?php

session_start();
require_once "../db.php";

// ==================== CEK LOGIN ====================

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

// ==================== CEK ROLE ====================

if ($_SESSION['role'] !== "admin") {
    die("Akses ditolak!");
}

$nama = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

// ==================== TAMBAH USER ====================

if (isset($_POST['simpan'])) {

    $nama_lengkap = trim($_POST['nama']);
    $username     = trim($_POST['username']);
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_user    = $_POST['role'];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tb_user
        (nama_lengkap, username, password, role, status_aktif)
        VALUES (?, ?, ?, ?, 1)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nama_lengkap,
        $username,
        $password,
        $role_user
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: user.php");
    exit;
}

// ==================== HAPUS USER ====================

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM tb_user WHERE id_user = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: user.php");
    exit;
}

// ==================== UPDATE USER ====================

if (isset($_POST['update'])) {

    $id           = (int) $_POST['id'];
    $nama_lengkap = trim($_POST['nama']);
    $username     = trim($_POST['username']);
    $role_user    = $_POST['role'];

    // Jika password diisi, password ikut diubah
    if (!empty($_POST['password'])) {

        $password = password_hash(
            $_POST['password'],
            PASSWORD_DEFAULT
        );

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE tb_user
             SET nama_lengkap = ?,
                 username = ?,
                 password = ?,
                 role = ?
             WHERE id_user = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $nama_lengkap,
            $username,
            $password,
            $role_user,
            $id
        );

    } else {

        // Password tidak diubah
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE tb_user
             SET nama_lengkap = ?,
                 username = ?,
                 role = ?
             WHERE id_user = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $nama_lengkap,
            $username,
            $role_user,
            $id
        );
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: user.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola User - E-Parkir Admin</title>

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

/* BRAND */

.sidebar .brand {
    padding: 20px;

    font-size: 20px;
    font-weight: bold;

    background: #0f172a;

    border-bottom: 1px solid #334155;

    text-align: center;
}

/* USER INFO */

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

/* NAVIGATION */

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

/* LOGOUT */

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

/* ================= MAIN CONTENT ================= */

.main-content {
    margin-left: 250px;

    min-height: 100vh;

    display: flex;
    flex-direction: column;
}

/* HEADER */

header {
    background: #007bff;

    color: white;

    padding: 20px 30px;

    box-shadow: 0 2px 5px rgba(0,0,0,.1);
}

header h2 {
    margin-bottom: 5px;
}

/* ================= CONTAINER ================= */

.container {
    padding: 30px;

    width: 100%;

    max-width: 1200px;

    margin: auto;
}

/* ================= CARD ================= */

.card {
    background: #fff;

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

input,
select {
    width: 100%;

    padding: 11px;

    margin-top: 5px;

    margin-bottom: 15px;

    border: 1px solid #ccc;

    border-radius: 5px;

    font-size: 14px;

    outline: none;
}

input:focus,
select:focus {
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

/* ================= ROLE ================= */

.role-admin {
    display: inline-block;

    padding: 4px 9px;

    background: #dbeafe;

    color: #1d4ed8;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.role-petugas {
    display: inline-block;

    padding: 4px 9px;

    background: #fef3c7;

    color: #92400e;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.role-owner {
    display: inline-block;

    padding: 4px 9px;

    background: #dcfce7;

    color: #166534;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

/* ================= STATUS ================= */

.status-aktif {
    color: #16a34a;

    font-weight: bold;
}

.status-nonaktif {
    color: #dc2626;

    font-weight: bold;
}

/* ================= AKSI ================= */

.edit {
    display: inline-block;

    background: #f59e0b;

    padding: 6px 10px;

    text-decoration: none;

    color: white;

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

    padding: 6px 10px;

    text-decoration: none;

    color: white;

    border-radius: 4px;

    font-size: 13px;
}

.hapus:hover {
    background: #b91c1c;
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
            <a href="user.php" class="active">
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


<!-- ================= MAIN CONTENT ================= -->

<div class="main-content">

    <header>

        <h2>👤 Kelola User</h2>

        <p>
            Kelola pengguna sistem E-Parkir
        </p>

    </header>


    <div class="container">


        <!-- ================= TAMBAH USER ================= -->

        <div class="card">

            <h2>Tambah User</h2>

            <form method="POST">

                <input
                    type="text"
                    name="nama"
                    placeholder="Nama Lengkap"
                    required
                >

                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                >

                <select name="role" required>

                    <option value="admin">
                        Admin
                    </option>

                    <option value="petugas">
                        Petugas
                    </option>

                    <option value="owner">
                        Owner
                    </option>

                </select>

                <button
                    type="submit"
                    name="simpan"
                >
                    + Simpan User
                </button>

            </form>

        </div>


        <!-- ================= DATA USER ================= -->

        <div class="card">

            <h2>Data User</h2>

            <div class="table-wrapper">

                <table>

                    <tr>

                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>

                    </tr>

                    <?php

                    $no = 1;

                    $data = mysqli_query(
                        $conn,
                        "SELECT * FROM tb_user
                         ORDER BY id_user DESC"
                    );

                    while ($d = mysqli_fetch_assoc($data)):

                    ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['nama_lengkap']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['username']); ?>
                        </td>

                        <td>

                            <?php if ($d['role'] === 'admin'): ?>

                                <span class="role-admin">
                                    ADMIN
                                </span>

                            <?php elseif ($d['role'] === 'petugas'): ?>

                                <span class="role-petugas">
                                    PETUGAS
                                </span>

                            <?php else: ?>

                                <span class="role-owner">
                                    OWNER
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($d['status_aktif'] == 1): ?>

                                <span class="status-aktif">
                                    ● Aktif
                                </span>

                            <?php else: ?>

                                <span class="status-nonaktif">
                                    ● Nonaktif
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <a
                                class="edit"
                                href="?edit=<?= $d['id_user']; ?>"
                            >
                                Edit
                            </a>

                            <a
                                class="hapus"
                                onclick="return confirm('Hapus user ini?')"
                                href="?hapus=<?= $d['id_user']; ?>"
                            >
                                Hapus
                            </a>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                </table>

            </div>

        </div>


        <!-- ================= EDIT USER ================= -->

        <?php

        if (isset($_GET['edit'])):

            $id = (int) $_GET['edit'];

            $result = mysqli_query(
                $conn,
                "SELECT * FROM tb_user
                 WHERE id_user = $id"
            );

            $e = mysqli_fetch_assoc($result);

            if ($e):

        ?>

        <div class="card">

            <h2>✏️ Edit User</h2>

            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= $e['id_user']; ?>"
                >

                <input
                    type="text"
                    name="nama"
                    value="<?= htmlspecialchars($e['nama_lengkap']); ?>"
                    required
                >

                <input
                    type="text"
                    name="username"
                    value="<?= htmlspecialchars($e['username']); ?>"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Kosongkan jika password tidak diubah"
                >

                <select name="role" required>

                    <option
                        value="admin"
                        <?= $e['role'] == "admin" ? "selected" : ""; ?>
                    >
                        Admin
                    </option>

                    <option
                        value="petugas"
                        <?= $e['role'] == "petugas" ? "selected" : ""; ?>
                    >
                        Petugas
                    </option>

                    <option
                        value="owner"
                        <?= $e['role'] == "owner" ? "selected" : ""; ?>
                    >
                        Owner
                    </option>

                </select>

                <button
                    type="submit"
                    name="update"
                >
                    Update User
                </button>

                <a
                    href="user.php"
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