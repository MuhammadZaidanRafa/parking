<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Halaman ini hanya boleh diakses lewat submit form booking
if (!isset($_POST['booking'])) {
    header("Location: pesan_tempat.php");
    exit;
}

$id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);
$id_area = (int) ($_POST['id_area'] ?? 0);
$tanggal = trim($_POST['tanggal'] ?? '');
$jam_masuk = trim($_POST['jam_masuk'] ?? '');
$estimasi_jam = (int) ($_POST['estimasi_jam'] ?? 0);

// ============ VALIDASI DASAR ============
$errors = [];

if ($id_kendaraan <= 0) {
    $errors[] = "Kendaraan wajib dipilih.";
}
if ($id_area <= 0) {
    $errors[] = "Area parkir wajib dipilih.";
}

// Format tanggal harus YYYY-MM-DD dan tidak boleh sebelum hari ini
$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$tanggal_obj || $tanggal_obj->format('Y-m-d') !== $tanggal) {
    $errors[] = "Format tanggal tidak valid.";
} elseif ($tanggal < date('Y-m-d')) {
    $errors[] = "Tanggal booking tidak boleh sebelum hari ini.";
}

// Format jam HH:MM (atau HH:MM:SS, tergantung browser)
if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $jam_masuk)) {
    $errors[] = "Format jam masuk tidak valid.";
}

if ($estimasi_jam < 1 || $estimasi_jam > 24) {
    $errors[] = "Estimasi jam parkir harus antara 1 - 24 jam.";
}

if (!empty($errors)) {
    $_SESSION['pesan_error'] = implode(" ", $errors);
    header("Location: pesan_tempat.php");
    exit;
}

// ============ VERIFIKASI KENDARAAN MILIK USER INI ============
$stmt = $conn->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE id_kendaraan = ? AND id_user = ?");
if ($stmt === false) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param("ii", $id_kendaraan, $id_user);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    $_SESSION['pesan_error'] = "Kendaraan tidak valid atau bukan milik Anda.";
    header("Location: pesan_tempat.php");
    exit;
}
$stmt->close();

// ============ PROSES BOOKING (TRANSAKSI + LOCK ANTI RACE CONDITION) ============
mysqli_begin_transaction($conn);

try {
    // Kunci baris area parkir supaya aman kalau ada booking lain masuk di waktu bersamaan
    $stmt = $conn->prepare("SELECT kapasitas, terisi FROM tb_area_parkir WHERE id_area = ? FOR UPDATE");
    if ($stmt === false) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $id_area);
    $stmt->execute();
    $area = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$area) {
        throw new Exception("Area parkir tidak ditemukan.");
    }

    if ((int) $area['terisi'] >= (int) $area['kapasitas']) {
        throw new Exception("Mohon maaf, area parkir ini baru saja penuh. Silakan pilih area lain.");
    }

    // Simpan data booking
    $stmt = $conn->prepare("INSERT INTO tb_booking (id_user, id_kendaraan, id_area, tanggal, jam_masuk, estimasi_jam, status) VALUES (?, ?, ?, ?, ?, ?, 'booking')");
    if ($stmt === false) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("iiissi", $id_user, $id_kendaraan, $id_area, $tanggal, $jam_masuk, $estimasi_jam);
    $stmt->execute();
    $stmt->close();

    // Tambah jumlah slot terisi di area yang dipilih
    $stmt = $conn->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?");
    if ($stmt === false) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $id_area);
    $stmt->execute();
    $stmt->close();

    mysqli_commit($conn);

    $_SESSION['pesan_sukses'] = "Booking berhasil dibuat! Silakan datang sesuai jadwal yang Anda pilih.";
    header("Location: riwayat.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['pesan_error'] = $e->getMessage();
    header("Location: pesan_tempat.php");
    exit;
}