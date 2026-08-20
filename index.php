<?php
session_start();
require_once 'db.php';

// Ambil data statistik dari database secara aman
function get_total_count($conn, $query) {
    $res = $conn->query($query);
    if ($res && $row = $res->fetch_assoc()) {
        return $row['total'] ?? 0;
    }
    return 0;
}

$total_area      = get_total_count($conn, "SELECT COUNT(*) as total FROM tb_area_parkir");
$total_transaksi = get_total_count($conn, "SELECT COUNT(*) as total FROM tb_transaksi");
$total_kendaraan = get_total_count($conn, "SELECT COUNT(*) as total FROM tb_kendaraan");

// Ambil daftar area parkir untuk ditampilkan
$query_area = $conn->query("SELECT * FROM tb_area_parkir LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Parkir Modern</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-dark: #101d33;
            --brand-blue: #1e3c72;
            --brand-blue-light: #2a5298;
            --brand-gold: #f7b733;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        .navbar {
            background-color: rgba(16, 29, 51, 0.95) !important;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }
        .navbar-brand { letter-spacing: 0.5px; }
        .nav-link { font-weight: 500; }
        .hero-section {
            position: relative;
            background-image:
                linear-gradient(135deg, rgba(15, 25, 45, 0.88) 0%, rgba(30, 60, 114, 0.80) 55%, rgba(42, 82, 152, 0.72) 100%),
                url('rsjbg.JPEG');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: white;
            padding: 120px 0 100px;
        }
        @media (max-width: 991.98px) {
            .hero-section { background-attachment: scroll; padding: 80px 0 60px; }
        }
        .hero-section h1 { text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35); }
        .hero-section .lead { text-shadow: 0 1px 6px rgba(0, 0, 0, 0.3); }
        .btn-warning { background-color: var(--brand-gold); border-color: var(--brand-gold); color: #1a1a1a; }
        .btn-warning:hover { background-color: #e0a52a; border-color: #e0a52a; }
        .btn-lg, .btn-outline-light { border-radius: 10px; }
        .stat-box {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 14px;
            padding: 22px;
            transition: transform 0.25s ease, background 0.25s ease;
        }
        .stat-box:hover { transform: translateY(-4px); background: rgba(255, 255, 255, 0.18); }
        .stat-box h2 { font-weight: 800; }

        /* ===== Video Section ===== */
        #video {
            scroll-margin-top: 80px;
            background-color: #fff;
        }
        .video-wrapper {
            position: relative;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            aspect-ratio: 16 / 9;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(16, 29, 51, 0.18);
            background: #000;
        }
        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        @media (max-width: 576px) {
            .video-wrapper {
                border-radius: 12px;
            }
        }

        #fitur, #area { scroll-margin-top: 80px; }
        .section-title { font-weight: 700; color: var(--brand-dark); }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 14px 28px rgba(30, 60, 114, 0.15); }
        .feature-card .display-5 {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.1), rgba(42, 82, 152, 0.1));
        }
        .card.border-0.shadow-sm { border-radius: 14px; transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .card.border-0.shadow-sm:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1) !important; }
        .badge.bg-success { border-radius: 20px; padding: 6px 12px; font-weight: 500; }
        footer { background-color: var(--brand-dark) !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-square-p text-warning me-2"></i>E-Parkir
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#video">Video Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#area">Area Parkir</a></li>

                    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['id_user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="pengguna/kendaraan_saya.php">Kendaraan Saya</a></li>
                        <li class="nav-item"><a class="nav-link" href="pengguna/pesan_tempat.php">Booking</a></li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-light btn-sm" href="logout.php">
                                <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-light btn-sm me-2" href="login_pengguna.php">Masuk Pengguna</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-warning btn-sm fw-semibold" href="register.php">Daftar</a>
                        </li>
                        <li class="nav-item ms-lg-3 border-start ps-lg-3">
                            <a class="btn btn-sm btn-secondary" href="login.php" title="Login Admin/Petugas">
                                <i class="fa-solid fa-user-shield"></i> Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center text-lg-start">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-3">Solusi Parkir Cerdas, Cepat & Aman</h1>
                    <p class="lead mb-4 text-white-50">Cari tempat parkir, pesan slot secara online, dan lacak riwayat transaksi Anda dengan mudah dalam satu platform.</p>
                    <div class="d-sm-flex justify-content-center justify-content-lg-start gap-3">
                        <a href="pengguna/pesan_tempat.php" class="btn btn-warning btn-lg fw-bold mb-2 mb-sm-0">
                            <i class="fa-solid fa-bookmark me-2"></i>Pesan Tempat Sekarang
                        </a>
                        <a href="#area" class="btn btn-outline-light btn-lg">Lihat Area Parkir</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-box text-center">
                                <h2 class="fw-bold text-warning"><?= $total_area; ?></h2>
                                <p class="mb-0 small">Area Parkir Available</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box text-center">
                                <h2 class="fw-bold text-warning"><?= $total_kendaraan; ?></h2>
                                <p class="mb-0 small">Kendaraan Terdaftar</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-box text-center">
                                <h2 class="fw-bold text-warning"><?= number_format($total_transaksi); ?>+</h2>
                                <p class="mb-0 small">Total Transaksi Sukses</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Profil -->
    <section id="video" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-4">
                <h2 class="section-title">Kenali E-Parkir Lebih Dekat</h2>
                <p class="text-muted">Tonton video singkat tentang layanan kami</p>
            </div>
            <div class="video-wrapper">
                <video controls preload="metadata" poster="rsjbg.JPEG">
                    <source src="grhasia.mp4" type="video/mp4">
                    Browser Anda tidak mendukung pemutaran video.
                </video>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-5 bg-light">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="section-title">Layanan Utama Kami</h2>
                <p class="text-muted">Kemudahan akses untuk kebutuhan perparkiran Anda</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="display-5 text-primary mb-3"><i class="fa-solid fa-calendar-check"></i></div>
                        <h5 class="fw-bold">Booking Slot Online</h5>
                        <p class="text-muted small">Amankan tempat parkir Anda sebelum tiba di lokasi melalui menu pemesanan.</p>
                        <a href="pengguna/pesan_tempat.php" class="mt-auto text-decoration-none text-primary fw-semibold">Pesan Slot &rarr;</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="display-5 text-primary mb-3"><i class="fa-solid fa-car"></i></div>
                        <h5 class="fw-bold">Kelola Kendaraan</h5>
                        <p class="text-muted small">Daftarkan pelat nomor kendaraan Anda untuk mempercepat proses identifikasi.</p>
                        <a href="pengguna/kendaraan_saya.php" class="mt-auto text-decoration-none text-primary fw-semibold">Kelola Vehicle &rarr;</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="display-5 text-primary mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <h5 class="fw-bold">Riwayat & Struk</h5>
                        <p class="text-muted small">Cek riwayat parkir dan cetak/unduh struk bukti transaksi kapan saja.</p>
                        <a href="pengguna/riwayat.php" class="mt-auto text-decoration-none text-primary fw-semibold">Cek Riwayat &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="area" class="py-5">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="section-title mb-1">Daftar Area Parkir</h2>
                    <p class="text-muted mb-0">Lokasi area parkir yang tersedia saat ini</p>
                </div>
                <a href="cek_area.php" class="btn btn-outline-primary btn-sm">Lihat Semua Area</a>
            </div>

            <div class="row g-4">
                <?php if ($query_area && $query_area->num_rows > 0): ?>
                    <?php while ($area = $query_area->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($area['nama_area'] ?? 'Area Parkir'); ?></h5>
                                        <span class="badge bg-success">Tersedia</span>
                                    </div>
                                    <p class="card-text text-muted small">
                                        <i class="fa-solid fa-location-dot me-1"></i> Kapasitas: <?= htmlspecialchars($area['kapasitas'] ?? '-'); ?> Slot
                                    </p>
                                    <a href="pengguna/pesan_tempat.php?area_id=<?= $area['id_area'] ?? ''; ?>" class="btn btn-sm btn-primary w-100">Pesan di Area Ini</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">Belum ada data area parkir yang ditambahkan.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white pt-4 pb-3">
        <div class="container text-center">
            <p class="small text-white-50 mb-0">&copy; <?= date('Y'); ?> Sistem Manajemen Parkir. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>