<?php
session_start();
require_once "../db.php";

// Hanya admin yang sudah login yang boleh membuka halaman ini
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// --- 1. PROSES TAMBAH DATA ---
if (isset($_POST['tambah'])) {
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tarif_per_jam   = (int)$_POST['tarif_per_jam'];

    $query = "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('$jenis_kendaraan', '$tarif_per_jam')";
    if (mysqli_query($conn, $query)) {
        header("Location: tarif.php?status=success_add");
        exit;
    } else {
        $error = "Gagal menambah data: " . mysqli_error($conn);
    }
}

// --- 2. PROSES EDIT DATA ---
if (isset($_POST['edit'])) {
    $id_tarif        = (int)$_POST['id_tarif'];
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tarif_per_jam   = (int)$_POST['tarif_per_jam'];

    $query = "UPDATE tb_tarif SET jenis_kendaraan='$jenis_kendaraan', tarif_per_jam='$tarif_per_jam' WHERE id_tarif='$id_tarif'";
    if (mysqli_query($conn, $query)) {
        header("Location: tarif.php?status=success_update");
        exit;
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($conn);
    }
}

// --- 3. PROSES HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_tarif = (int)$_GET['hapus'];
    $query    = "DELETE FROM tb_tarif WHERE id_tarif='$id_tarif'";
    if (mysqli_query($conn, $query)) {
        header("Location: tarif.php?status=success_delete");
        exit;
    } else {
        $error = "Gagal menghapus data: " . mysqli_error($conn);
    }
}

// --- AMBIL DATA UNTUK EDIT (JIKA ADA PARAMETER GET edit) ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit   = (int)$_GET['edit'];
    $res_edit  = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE id_tarif='$id_edit'");
    $edit_data = mysqli_fetch_assoc($res_edit);
}

// --- AMBIL SEMUA DATA TARIF ---
$data_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif ORDER BY id_tarif DESC");

// --- DATA GRAFIK ---
$label = [];
$data  = [];
$grafik = mysqli_query($conn, "SELECT jenis_kendaraan, tarif_per_jam FROM tb_tarif");

while ($g = mysqli_fetch_assoc($grafik)) {
    $label[] = ucfirst($g['jenis_kendaraan']);
    $data[]  = (int)$g['tarif_per_jam'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tarif Parkir</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 30px; 
            background-color: #f4f6f9; 
        }
        .container { 
            max-width: 900px; 
            margin: auto; 
            background: #fff; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h2, h3 { 
            color: #333; 
        }
        h2 { 
            margin-top: 0; 
        }
        .alert { 
            padding: 10px 15px; 
            margin-bottom: 15px; 
            border-radius: 4px; 
        }
        .alert-success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .alert-danger { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        form { 
            margin-bottom: 30px; 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 6px; 
            border: 1px solid #e9ecef; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        input[type="number"], select { 
            width: 100%; 
            padding: 8px; 
            box-sizing: border-box; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .btn { 
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            color: white; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 14px; 
        }
        .btn-primary { background-color: #007bff; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-danger  { background-color: #dc3545; }
        .btn-secondary { 
            background-color: #6c757d; 
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: .3s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            border: 1px solid #dee2e6; 
            padding: 10px; 
            text-align: left; 
        }
        th { 
            background-color: #343a40; 
            color: white; 
        }
        tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        .btn-back-wrapper {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">
    <h2>Manajemen Tarif Parkir</h2>

    <!-- Notifikasi Sukses / Error -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'success_add'): ?>
            <div class="alert alert-success">Tarif berhasil ditambahkan!</div>
        <?php elseif ($_GET['status'] == 'success_update'): ?>
            <div class="alert alert-success">Tarif berhasil diperbarui!</div>
        <?php elseif ($_GET['status'] == 'success_delete'): ?>
            <div class="alert alert-success">Tarif berhasil dihapus!</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <!-- Form Tambah / Edit Data -->
    <form action="tarif.php" method="POST">
        <h3><?= $edit_data ? 'Edit Tarif' : 'Tambah Tarif Baru'; ?></h3>
        
        <?php if ($edit_data): ?>
            <input type="hidden" name="id_tarif" value="<?= $edit_data['id_tarif']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="jenis_kendaraan">Jenis Kendaraan</label>
            <select name="jenis_kendaraan" id="jenis_kendaraan" required>
                <option value="">-- Pilih Jenis --</option>
                <option value="motor" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'motor') ? 'selected' : ''; ?>>Motor</option>
                <option value="mobil" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'mobil') ? 'selected' : ''; ?>>Mobil</option>
                <option value="lainnya" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'lainnya') ? 'selected' : ''; ?>>Lainnya</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tarif_per_jam">Tarif per Jam (Rp)</label>
            <input type="number" name="tarif_per_jam" id="tarif_per_jam" value="<?= $edit_data ? $edit_data['tarif_per_jam'] : ''; ?>" required placeholder="Contoh: 3000">
        </div>

        <div>
            <?php if ($edit_data): ?>
                <button type="submit" name="edit" class="btn btn-warning">Simpan Perubahan</button>
                <a href="tarif.php" class="btn btn-secondary" style="padding: 8px 15px;">Batal</a>
            <?php else: ?>
                <button type="submit" name="tambah" class="btn btn-primary">Tambah Tarif</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabel Data Tarif -->
    <h3>Daftar Tarif</h3>
    <table>
        <thead>
            <tr>
                <th width="8%">No</th>
                <th>Jenis Kendaraan</th>
                <th>Tarif per Jam</th>
                <th width="20%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($data_tarif) > 0):
                while ($row = mysqli_fetch_assoc($data_tarif)): 
            ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= ucfirst($row['jenis_kendaraan']); ?></td>
                    <td>Rp <?= number_format($row['tarif_per_jam'], 0, ',', '.'); ?></td>
                    <td>
                        <a href="tarif.php?edit=<?= $row['id_tarif']; ?>" class="btn btn-warning" style="padding: 4px 8px; font-size: 12px;">Edit</a>
                        <a href="tarif.php?hapus=<?= $row['id_tarif']; ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('Yakin ingin menghapus tarif ini?')">Hapus</a>
                    </td>
                </tr>
            <?php 
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Belum ada data tarif.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr style="margin: 40px 0;">

    <!-- Section Grafik Tarif Parkir -->
    <h3>Grafik Tarif Parkir</h3>
    <canvas id="grafikTarif" height="100"></canvas>

    <!-- Tombol Kembali ke Dashboard -->
    <div class="btn-back-wrapper">
        <a href="../dashboard.php" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>
</div>

<script>
    const ctx = document.getElementById('grafikTarif');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($label); ?>,
            datasets: [{
                label: 'Tarif Per Jam',
                data: <?= json_encode($data); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#007bff'
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
                    beginAtZero: true
                }
            }
        }
    });
</script>

</body>
</html>