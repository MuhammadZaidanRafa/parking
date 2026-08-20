<?php
session_start();
require '../db.php';

// Cek login (dukungan untuk id_user maupun user_id)
$id_user = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;

if (!$id_user) {
    header("Location: login_pengguna.php");
    exit;
}

// Ambil data user di awal (dipakai untuk cek role & status sebelum proses apapun)
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE id_user = ?");
if ($stmt === false) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param("i", $id_user);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// User tidak ditemukan (misal akun sudah dihapus tapi sesi masih aktif)
if (!$data) {
    session_destroy();
    header("Location: login_pengguna.php");
    exit;
}

// Halaman ini khusus role pengguna
if ($data['role'] !== 'pengguna') {
    header("Location: dashboard.php");
    exit;
}

// Akun nonaktif tidak boleh mengakses halaman ini
if (!$data['status_aktif']) {
    session_destroy();
    header("Location: login_pengguna.php?msg=nonaktif");
    exit;
}

$pesan = "";
$tipe_pesan = "";

// Simpan perubahan
if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama === '') {
        $pesan = "Nama lengkap tidak boleh kosong.";
        $tipe_pesan = "danger";
    } elseif ($password !== '' && strlen($password) < 6) {
        $pesan = "Password baru minimal 6 karakter.";
        $tipe_pesan = "danger";
    } else {
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tb_user SET nama_lengkap=?, password=? WHERE id_user=?");
            if ($stmt === false) {
                die("Query error: " . $conn->error);
            }
            $stmt->bind_param("ssi", $nama, $password_hash, $id_user);
        } else {
            $stmt = $conn->prepare("UPDATE tb_user SET nama_lengkap=? WHERE id_user=?");
            if ($stmt === false) {
                die("Query error: " . $conn->error);
            }
            $stmt->bind_param("si", $nama, $id_user);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['nama_lengkap'] = $nama;
            $data['nama_lengkap'] = $nama; // update tampilan tanpa perlu query ulang
            $pesan = "Profil berhasil diperbarui!";
            $tipe_pesan = "success";
        } else {
            $pesan = "Gagal memperbarui profil.";
            $tipe_pesan = "danger";
        }
    }
}

// Definisikan halaman kembali (karena halaman ini khusus pengguna, langsung set saja)
$dashboard_url = "../dashboard_pengguna.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - E-Parkir</title>
    <!-- Bootstrap 5 CSS & FontAwesome Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .profile-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .avatar-circle {
            width: 80px;
            height: 80px;
            background-color: #1e3c72;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 15px auto;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= $dashboard_url; ?>">
                <i class="fa-solid fa-square-p text-warning me-2"></i>E-Parkir
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="<?= $dashboard_url; ?>" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-house me-1"></i> Beranda
                </a>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <div class="card profile-card p-4">
                    <div class="text-center mb-3">
                        <div class="avatar-circle">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($data['nama_lengkap']); ?></h4>
                        <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($data['role']); ?></span>
                    </div>

                    <?php if (!empty($pesan)): ?>
                        <div class="alert alert-<?= $tipe_pesan; ?> alert-dismissible fade show" role="alert">
                            <i class="fa-solid <?= $tipe_pesan == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?> me-2"></i>
                            <?= $pesan; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" name="nama_lengkap" class="form-control"
                                       value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-at"></i></span>
                                <input type="text" class="form-control bg-light"
                                       value="<?= htmlspecialchars($data['username'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" class="form-control bg-light text-uppercase fw-semibold"
                                       value="<?= htmlspecialchars($data['role']); ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Status</label>
                                <input type="text" class="form-control bg-light fw-semibold text-success"
                                       value="Aktif" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                <input type="password" name="password" class="form-control" minlength="6"
                                       placeholder="Kosongkan jika tidak diganti">
                            </div>
                            <div class="form-text">Biarkan kosong jika Anda tidak ingin mengubah password. Minimal 6 karakter jika diisi.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="simpan" class="btn btn-primary fw-semibold">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                            <a href="<?= $dashboard_url; ?>" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container text-center">
            <p class="small text-white-50 mb-0">&copy; <?= date('Y'); ?> Sistem Parkir. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>