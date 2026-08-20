<?php
session_start();

// ==== KONEKSI DATABASE ====
require_once "../db.php";

$errors = [];
$success = '';

// ==== PROSES FORM ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $role         = 'admin'; // dikunci sebagai admin

    // Validasi
    if ($nama_lengkap === '') $errors[] = "Nama lengkap wajib diisi.";
    if ($username === '') $errors[] = "Username wajib diisi.";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
    if ($password !== $konfirmasi) $errors[] = "Konfirmasi password tidak cocok.";

    // Cek username sudah dipakai atau belum
    if (empty($errors)) {
        $stmtCek = $conn->prepare("SELECT id_user FROM tb_user WHERE username = ?");
        $stmtCek->bind_param("s", $username);
        $stmtCek->execute();
        $resultCek = $stmtCek->get_result();
        if ($resultCek->num_rows > 0) {
            $errors[] = "Username sudah digunakan, silakan pilih username lain.";
        }
        $stmtCek->close();
    }

    // Simpan jika tidak ada error
    if (empty($errors)) {
        // Password disimpan langsung tanpa hashing
        $stmt = $conn->prepare(
            "INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssi", $nama_lengkap, $username, $password, $role, $status_aktif);

        if ($stmt->execute()) {
            $success = "Admin baru berhasil ditambahkan!";
            $nama_lengkap = $username = ''; // reset input
        } else {
            $errors[] = "Gagal menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Admin - Sistem Parkir</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .card {
        background: #fff;
        padding: 30px 35px;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 420px;
    }
    h2 {
        margin-top: 0;
        color: #2c3e50;
        text-align: center;
    }
    label {
        display: block;
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: 600;
        color: #34495e;
        font-size: 14px;
    }
    input[type=text], input[type=password] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 14px;
    }
    .checkbox-row {
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    button {
        width: 100%;
        margin-top: 22px;
        padding: 12px;
        background: #2c7be5;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        cursor: pointer;
    }
    button:hover {
        background: #1a5fc4;
    }
    .alert {
        padding: 10px 14px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
    }
    .alert-error {
        background: #fdecea;
        color: #c0392b;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #eafaf1;
        color: #1e8449;
        border: 1px solid #c3e6cb;
    }
    .alert ul {
        margin: 0;
        padding-left: 18px;
    }
</style>
</head>
<body>
<div class="card">
    <h2>Tambah Admin</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="nama_lengkap">Nama Lengkap</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap"
               value="<?= htmlspecialchars($nama_lengkap ?? '') ?>" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($username ?? '') ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="konfirmasi_password">Konfirmasi Password</label>
        <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>

        <div class="checkbox-row">
            <input type="checkbox" id="status_aktif" name="status_aktif" checked>
            <label for="status_aktif" style="margin:0;">Status Aktif</label>
        </div>

        <button type="submit">Tambah Admin</button>
    </form>
</div>
</body>
</html>