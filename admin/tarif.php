<?php

session_start();
require_once "../db.php";

// ==================== CEK LOGIN ====================

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

// ==================== CEK ROLE ADMIN ====================

if ($_SESSION['role'] !== "admin") {
    die("Akses ditolak!");
}

$nama = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';


// ==================== TAMBAH TARIF ====================

if (isset($_POST['tambah'])) {

    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $tarif_per_jam = (int) $_POST['tarif_per_jam'];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tb_tarif
        (jenis_kendaraan, tarif_per_jam)
        VALUES (?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $jenis_kendaraan,
        $tarif_per_jam
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: tarif.php?status=success_add");
        exit;

    } else {

        $error = "Gagal menambah data: " . mysqli_error($conn);

        mysqli_stmt_close($stmt);
    }
}


// ==================== UPDATE TARIF ====================

if (isset($_POST['edit'])) {

    $id_tarif = (int) $_POST['id_tarif'];
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $tarif_per_jam = (int) $_POST['tarif_per_jam'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE tb_tarif
         SET jenis_kendaraan = ?,
             tarif_per_jam = ?
         WHERE id_tarif = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sii",
        $jenis_kendaraan,
        $tarif_per_jam,
        $id_tarif
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: tarif.php?status=success_update");
        exit;

    } else {

        $error = "Gagal memperbarui data: " . mysqli_error($conn);

        mysqli_stmt_close($stmt);
    }
}


// ==================== HAPUS TARIF ====================

if (isset($_GET['hapus'])) {

    $id_tarif = (int) $_GET['hapus'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM tb_tarif
         WHERE id_tarif = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_tarif
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: tarif.php?status=success_delete");
        exit;

    } else {

        $error = "Gagal menghapus data: " . mysqli_error($conn);

        mysqli_stmt_close($stmt);
    }
}


// ==================== DATA EDIT ====================

$edit_data = null;

if (isset($_GET['edit'])) {

    $id_edit = (int) $_GET['edit'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM tb_tarif
         WHERE id_tarif = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_edit
    );

    mysqli_stmt_execute($stmt);

    $result_edit = mysqli_stmt_get_result($stmt);

    $edit_data = mysqli_fetch_assoc($result_edit);

    mysqli_stmt_close($stmt);
}


// ==================== AMBIL DATA TARIF ====================

$data_tarif = mysqli_query(
    $conn,
    "SELECT *
     FROM tb_tarif
     ORDER BY id_tarif DESC"
);


// ==================== DATA GRAFIK ====================

$label = [];
$data = [];

$grafik = mysqli_query(
    $conn,
    "SELECT jenis_kendaraan, tarif_per_jam
     FROM tb_tarif"
);

while ($g = mysqli_fetch_assoc($grafik)) {

    $label[] = ucfirst($g['jenis_kendaraan']);

    $data[] = (int) $g['tarif_per_jam'];
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Kelola Tarif - E-Parkir Admin</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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


/* ================= MAIN ================= */

.main-content {

    margin-left: 250px;

    min-height: 100vh;

    display: flex;

    flex-direction: column;
}


/* ================= HEADER ================= */

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

.card h2,
.card h3 {

    margin-bottom: 20px;

    color: #2563eb;
}


/* ================= ALERT ================= */

.alert {

    padding: 12px 15px;

    margin-bottom: 20px;

    border-radius: 6px;

    font-weight: bold;
}

.alert-success {

    background: #d4edda;

    color: #155724;

    border: 1px solid #c3e6cb;
}

.alert-danger {

    background: #f8d7da;

    color: #721c24;

    border: 1px solid #f5c6cb;
}


/* ================= FORM ================= */

form {

    background: #f8f9fa;

    padding: 20px;

    border-radius: 8px;

    border: 1px solid #e9ecef;
}

.form-group {

    margin-bottom: 15px;
}

label {

    display: block;

    margin-bottom: 7px;

    font-weight: bold;

    color: #333;
}

input[type="number"],
select {

    width: 100%;

    padding: 11px;

    border: 1px solid #ccc;

    border-radius: 5px;

    outline: none;

    font-size: 14px;
}

input[type="number"]:focus,
select:focus {

    border-color: #2563eb;

    box-shadow: 0 0 0 2px rgba(37,99,235,.1);
}


/* ================= BUTTON ================= */

.btn {

    padding: 9px 15px;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    color: white;

    text-decoration: none;

    display: inline-block;

    font-size: 14px;

    transition: .2s;
}

.btn-primary {

    background: #007bff;
}

.btn-primary:hover {

    background: #0056b3;
}

.btn-warning {

    background: #f59e0b;

    color: white;
}

.btn-warning:hover {

    background: #d97706;
}

.btn-danger {

    background: #dc3545;
}

.btn-danger:hover {

    background: #bd2130;
}

.btn-secondary {

    background: #64748b;
}

.btn-secondary:hover {

    background: #475569;
}


/* ================= TABLE ================= */

.table-wrapper {

    width: 100%;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 650px;
}

th {

    background: #2563eb;

    color: white;

    padding: 12px;

    text-align: left;
}

td {

    padding: 12px;

    border-bottom: 1px solid #ddd;
}

tr:hover td {

    background: #f8fafc;
}


/* ================= GRAFIK ================= */

.chart-wrapper {

    width: 100%;

    max-width: 1000px;

    margin: auto;
}


/* ================= BACK ================= */

.btn-back-wrapper {

    margin-top: 10px;

    padding-top: 20px;

    border-top: 1px solid #e9ecef;
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

    .card h2,
    .card h3 {

        font-size: 19px;
    }

}

</style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

    <div class="brand">

        🅿️ E-Parkir Admin

    </div>


    <div class="user-info">

        Role:

        <b>
            <?= strtoupper(htmlspecialchars($role)); ?>
        </b>

        User:

        <b>
            <?= htmlspecialchars($nama); ?>
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

            <a href="tarif.php" class="active">

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



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main-content">


    <!-- HEADER -->

    <header>

        <h2>💰 Kelola Tarif Parkir</h2>

        <p>
            Kelola tarif parkir berdasarkan jenis kendaraan
        </p>

    </header>



    <div class="container">


        <!-- ================= ALERT ================= -->

        <?php if (isset($_GET['status'])): ?>

            <?php if ($_GET['status'] === 'success_add'): ?>

                <div class="alert alert-success">

                    ✅ Tarif berhasil ditambahkan!

                </div>

            <?php elseif ($_GET['status'] === 'success_update'): ?>

                <div class="alert alert-success">

                    ✅ Tarif berhasil diperbarui!

                </div>

            <?php elseif ($_GET['status'] === 'success_delete'): ?>

                <div class="alert alert-success">

                    ✅ Tarif berhasil dihapus!

                </div>

            <?php endif; ?>

        <?php endif; ?>


        <?php if (isset($error)): ?>

            <div class="alert alert-danger">

                ❌ <?= htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <!-- ================= FORM TARIF ================= -->

        <div class="card">

            <h2>

                <?= $edit_data
                    ? '✏️ Edit Tarif'
                    : '➕ Tambah Tarif Baru';
                ?>

            </h2>


            <form
                action="tarif.php"
                method="POST"
            >


                <?php if ($edit_data): ?>

                    <input
                        type="hidden"
                        name="id_tarif"
                        value="<?= $edit_data['id_tarif']; ?>"
                    >

                <?php endif; ?>


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

                            -- Pilih Jenis Kendaraan --

                        </option>


                        <option
                            value="motor"
                            <?= (
                                $edit_data &&
                                $edit_data['jenis_kendaraan'] === 'motor'
                            )
                            ? 'selected'
                            : '';
                            ?>
                        >

                            🏍️ Motor

                        </option>


                        <option
                            value="mobil"
                            <?= (
                                $edit_data &&
                                $edit_data['jenis_kendaraan'] === 'mobil'
                            )
                            ? 'selected'
                            : '';
                            ?>
                        >

                            🚗 Mobil

                        </option>


                        <option
                            value="lainnya"
                            <?= (
                                $edit_data &&
                                $edit_data['jenis_kendaraan'] === 'lainnya'
                            )
                            ? 'selected'
                            : '';
                            ?>
                        >

                            🚐 Lainnya

                        </option>

                    </select>

                </div>



                <div class="form-group">

                    <label for="tarif_per_jam">

                        Tarif per Jam (Rp)

                    </label>


                    <input
                        type="number"
                        name="tarif_per_jam"
                        id="tarif_per_jam"

                        value="<?=
                            $edit_data
                            ? $edit_data['tarif_per_jam']
                            : '';
                        ?>"

                        min="0"

                        required

                        placeholder="Contoh: 3000"
                    >

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
                            href="tarif.php"
                            class="btn btn-secondary"
                        >

                            Batal

                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="tambah"
                            class="btn btn-primary"
                        >

                            ➕ Tambah Tarif

                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>



        <!-- ================= TABEL TARIF ================= -->

        <div class="card">

            <h2>

                📋 Daftar Tarif

            </h2>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th width="8%">
                                No
                            </th>

                            <th>
                                Jenis Kendaraan
                            </th>

                            <th>
                                Tarif per Jam
                            </th>

                            <th width="20%">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php

                        $no = 1;

                        if (mysqli_num_rows($data_tarif) > 0):

                            while (
                                $row =
                                mysqli_fetch_assoc($data_tarif)
                            ):

                        ?>

                        <tr>

                            <td>

                                <?= $no++; ?>

                            </td>


                            <td>

                                <?= ucfirst(
                                    htmlspecialchars(
                                        $row['jenis_kendaraan']
                                    )
                                ); ?>

                            </td>


                            <td>

                                <strong>

                                    Rp
                                    <?= number_format(
                                        $row['tarif_per_jam'],
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </strong>

                            </td>


                            <td>

                                <a
                                    href="tarif.php?edit=<?= $row['id_tarif']; ?>"
                                    class="btn btn-warning"
                                >

                                    ✏️ Edit

                                </a>


                                <a
                                    href="tarif.php?hapus=<?= $row['id_tarif']; ?>"
                                    class="btn btn-danger"

                                    onclick="
                                        return confirm(
                                            'Yakin ingin menghapus tarif ini?'
                                        )
                                    "
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
                                colspan="4"
                                style="text-align:center;"
                            >

                                Belum ada data tarif.

                            </td>

                        </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>



        <!-- ================= GRAFIK ================= -->

        <div class="card">

            <h2>

                📊 Grafik Tarif Parkir

            </h2>


            <div class="chart-wrapper">

                <canvas
                    id="grafikTarif"
                    height="100"
                ></canvas>

            </div>

        </div>



        <!-- ================= BACK ================= -->

        <div class="btn-back-wrapper">

            <a
                href="../dashboard.php"
                class="btn btn-secondary"
            >

                ← Kembali ke Dashboard

            </a>

        </div>


    </div>

</div>



<script>

const ctx =
    document.getElementById('grafikTarif');


new Chart(ctx, {

    type: 'line',

    data: {

        labels:
            <?= json_encode($label); ?>,

        datasets: [{

            label: 'Tarif Per Jam',

            data:
                <?= json_encode($data); ?>,

            borderColor: '#007bff',

            backgroundColor:
                'rgba(0, 123, 255, 0.2)',

            borderWidth: 3,

            fill: true,

            tension: 0.4,

            pointRadius: 5,

            pointBackgroundColor:
                '#007bff'

        }]

    },

    options: {

        responsive: true,

        plugins: {

            legend: {

                display: true

            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {

                        return 'Rp ' +
                            value.toLocaleString('id-ID');

                    }

                }

            }

        }

    }

});

</script>

</body>

</html>