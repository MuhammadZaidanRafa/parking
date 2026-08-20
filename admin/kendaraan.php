<?php
session_start();
require_once "../db.php";

$error = null;

// --- 1. PROSES TAMBAH DATA ---
if (isset($_POST['tambah'])) {
    $plat_nomor      = mysqli_real_escape_string($conn, $_POST['plat_nomor']);
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $warna           = mysqli_real_escape_string($conn, $_POST['warna']);
    $pemilik         = mysqli_real_escape_string($conn, $_POST['pemilik']);
    $id_user         = !empty($_POST['id_user']) ? "'".mysqli_real_escape_string($conn, $_POST['id_user'])."'" : "NULL";

    $query = "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) 
              VALUES ('$plat_nomor', '$jenis_kendaraan', '$warna', '$pemilik', $id_user)";
    
    if (mysqli_query($conn, $query)) {
        header("Location: kendaraan.php?status=success_add");
        exit();
    } else {
        $error = "Gagal menambah data: " . mysqli_error($conn);
    }
}

// --- 2. PROSES EDIT DATA ---
if (isset($_POST['edit'])) {
    $id_kendaraan    = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
    $plat_nomor      = mysqli_real_escape_string($conn, $_POST['plat_nomor']);
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $warna           = mysqli_real_escape_string($conn, $_POST['warna']);
    $pemilik         = mysqli_real_escape_string($conn, $_POST['pemilik']);
    $id_user         = !empty($_POST['id_user']) ? "'".mysqli_real_escape_string($conn, $_POST['id_user'])."'" : "NULL";

    $query = "UPDATE tb_kendaraan SET 
                plat_nomor='$plat_nomor', 
                jenis_kendaraan='$jenis_kendaraan', 
                warna='$warna', 
                pemilik='$pemilik', 
                id_user=$id_user 
              WHERE id_kendaraan='$id_kendaraan'";
              
    if (mysqli_query($conn, $query)) {
        header("Location: kendaraan.php?status=success_update");
        exit();
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($conn);
    }
}

// --- 3. PROSES HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_kendaraan = mysqli_real_escape_string($conn, $_GET['hapus']);
    $query        = "DELETE FROM tb_kendaraan WHERE id_kendaraan='$id_kendaraan'";
    if (mysqli_query($conn, $query)) {
        header("Location: kendaraan.php?status=success_delete");
        exit();
    } else {
        $error = "Gagal menghapus data: " . mysqli_error($conn);
    }
}

// --- AMBIL DATA UNTUK EDIT (Hanya jika TIDAK sedang submit form POST) ---
$edit_data = null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($conn, $_GET['edit']);
    $res_edit = mysqli_query($conn, "SELECT * FROM tb_kendaraan WHERE id_kendaraan='$id_edit'");
    if ($res_edit && mysqli_num_rows($res_edit) > 0) {
        $edit_data = mysqli_fetch_assoc($res_edit);
    }
}

// --- AMBIL DATA KENDARAAN (LEFT JOIN DENGAN TB_USER) ---
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
LEFT JOIN tb_user u ON k.id_user = u.id_user
LEFT JOIN tb_transaksi t ON k.id_kendaraan = t.id_kendaraan AND t.status = 'masuk'
ORDER BY k.id_kendaraan DESC";

$data_kendaraan = mysqli_query($conn, $query_kendaraan);

// --- AMBIL DATA USER UNTUK DROPDOWN ---
$data_user = mysqli_query($conn, "SELECT id_user, nama_lengkap FROM tb_user ORDER BY nama_lengkap ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Kendaraan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f6f9; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .alert { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        form { margin-bottom: 30px; background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .form-row { display: flex; gap: 15px; }
        .form-group { flex: 1; margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background-color: #007bff; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-danger { background-color: #dc3545; }
        .btn-secondary { 
            background-color: #6c757d; 
            color: #ffffff; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-weight: 500; 
            transition: all 0.2s ease-in-out; 
        }
        .btn-secondary:hover { background-color: #5a6268; transform: translateX(-3px); }
        .btn-back-wrapper { margin-top: 25px; padding-top: 15px; border-top: 1px solid #e9ecef; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        th { background-color: #343a40; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>

<div class="container">
    <h2>Manajemen Data Kendaraan</h2>

    <!-- Notifikasi Sukses / Error -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'success_add'): ?>
            <div class="alert alert-success">Data kendaraan berhasil ditambahkan!</div>
        <?php elseif ($_GET['status'] == 'success_update'): ?>
            <div class="alert alert-success">Data kendaraan berhasil diperbarui!</div>
        <?php elseif ($_GET['status'] == 'success_delete'): ?>
            <div class="alert alert-success">Data kendaraan berhasil dihapus!</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <!-- Form Tambah / Edit Data -->
    <form action="kendaraan.php" method="POST">
        <h3><?= $edit_data ? 'Edit Data Kendaraan' : 'Tambah Kendaraan Baru'; ?></h3>
        
        <?php if ($edit_data): ?>
            <input type="hidden" name="id_kendaraan" value="<?= $edit_data['id_kendaraan']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="plat_nomor">Plat Nomor</label>
                <input type="text" name="plat_nomor" id="plat_nomor" value="<?= $edit_data ? $edit_data['plat_nomor'] : ''; ?>" required placeholder="Contoh: AB 1234 CD">
            </div>

            <div class="form-group">
                <label for="jenis_kendaraan">Jenis Kendaraan</label>
                <select name="jenis_kendaraan" id="jenis_kendaraan" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="motor" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'motor') ? 'selected' : ''; ?>>Motor</option>
                    <option value="mobil" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'mobil') ? 'selected' : ''; ?>>Mobil</option>
                    <option value="lainnya" <?= ($edit_data && $edit_data['jenis_kendaraan'] == 'lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="warna">Warna Kendaraan</label>
                <input type="text" name="warna" id="warna" value="<?= $edit_data ? $edit_data['warna'] : ''; ?>" placeholder="Contoh: Hitam">
            </div>

            <div class="form-group">
                <label for="pemilik">Nama Pemilik</label>
                <input type="text" name="pemilik" id="pemilik" value="<?= $edit_data ? $edit_data['pemilik'] : ''; ?>" placeholder="Contoh: Budi Santoso">
            </div>

            <div class="form-group">
                <label for="id_user">Akun User (Opsional)</label>
                <select name="id_user" id="id_user">
                    <option value="">-- Tanpa Akun User --</option>
                    <?php 
                    mysqli_data_seek($data_user, 0); // reset pointer query
                    while ($user = mysqli_fetch_assoc($data_user)): 
                    ?>
                        <option value="<?= $user['id_user']; ?>" <?= ($edit_data && $edit_data['id_user'] == $user['id_user']) ? 'selected' : ''; ?>>
                            <?= $user['nama_lengkap']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div>
            <?php if ($edit_data): ?>
                <button type="submit" name="edit" class="btn btn-warning">Simpan Perubahan</button>
                <a href="kendaraan.php" class="btn btn-secondary">Batal</a>
            <?php else: ?>
                <button type="submit" name="tambah" class="btn btn-primary">Tambah Kendaraan</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabel Data Kendaraan -->
    <h3>Daftar Kendaraan</h3>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Plat Nomor</th>
                <th>Jenis</th>
                <th>Warna</th>
                <th>Pemilik</th>
                <th>Akun Terhubung</th>
                <th>Status</th>
                <th>Lama Parkir</th>
                <th width="15%">Aksi</th>
            </tr>
        </thead>
        <tbody>
<?php
$no = 1;

if (mysqli_num_rows($data_kendaraan) > 0):

while($row = mysqli_fetch_assoc($data_kendaraan)):
?>
<tr>
    <td><?= $no++; ?></td>

    <td>
        <strong><?= htmlspecialchars(strtoupper($row['plat_nomor'])); ?></strong>
    </td>

    <td><?= ucfirst(htmlspecialchars($row['jenis_kendaraan'])); ?></td>

    <td>
        <?= !empty($row['warna']) ? htmlspecialchars($row['warna']) : '-'; ?>
    </td>

    <td>
        <?= !empty($row['pemilik']) ? htmlspecialchars($row['pemilik']) : '-'; ?>
    </td>

    <td>
        <?= !empty($row['nama_lengkap']) ? htmlspecialchars($row['nama_lengkap']) : '<em>Tidak Ada</em>'; ?>
    </td>

    <!-- STATUS -->
    <td>
        <?php
        if ($row['status'] == "masuk") {
            echo "<span style='color:green;font-weight:bold;'>Sedang Parkir</span>";
        } elseif ($row['status'] == "keluar") {
            echo "<span style='color:red;font-weight:bold;'>Keluar</span>";
        } else {
            echo "-";
        }
        ?>
    </td>

    <!-- LAMA PARKIR -->
    <td>
        <?php
        if ($row['status'] == "masuk" && $row['lama_parkir'] !== null) {
            echo $row['lama_parkir'] . " Jam";
        } else {
            echo "-";
        }
        ?>
    </td>

    <!-- AKSI -->
    <td>
        <a href="kendaraan.php?edit=<?= $row['id_kendaraan']; ?>"
           class="btn btn-warning"
           style="padding:4px 8px;font-size:12px;">
            Edit
        </a>

        <a href="kendaraan.php?hapus=<?= $row['id_kendaraan']; ?>"
           class="btn btn-danger"
           style="padding:4px 8px;font-size:12px;"
           onclick="return confirm('Yakin ingin menghapus kendaraan ini?')">
            Hapus
        </a>
    </td>

</tr>

<?php
endwhile;
else:
?>

<tr>
    <td colspan="9" style="text-align:center;">
        Belum ada data kendaraan.
    </td>
</tr>

<?php endif; ?>
</tbody>
    </table>

    <!-- Tombol Kembali ke dashboard -->
    <div class="btn-back-wrapper">
        <a href="../dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
    </div>
</div>

</body>
</html>