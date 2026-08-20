<?php
session_start();
require_once "db.php";

// Cek apakah pengguna sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];

// Proteksi: hanya role pengguna yang boleh mengakses halaman ini
if ($role !== 'pengguna') {
    header("Location: dashboard.php");
    exit;
}

$inisial = strtoupper(substr($nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - Parkir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f6f9;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
        }

        .sidebar-header {
            background: #007bff;
            color: white;
            padding: 22px 20px;
        }

        .sidebar-header h2 {
            font-size: 1.15rem;
            margin-bottom: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .user-name {
            font-weight: bold;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.85;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            flex: 1;
            padding: 14px 0;
            overflow-y: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-link .icon {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #eaf2ff;
            color: #007bff;
            border-left-color: #007bff;
        }

        .sidebar-footer {
            padding: 12px 0;
            border-top: 1px solid #eee;
        }

        .logout-link:hover {
            background: #fdeaea;
            color: #dc3545;
            border-left-color: #dc3545;
        }

        /* Overlay untuk mobile */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 900;
        }

        .overlay.active {
            display: block;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        .topbar {
            background: white;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .topbar h2 {
            font-size: 1.1rem;
            color: #333;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 32px;
            height: 32px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
        }

        .hamburger span {
            width: 100%;
            height: 3px;
            background: #333;
            border-radius: 2px;
        }

        .container {
            width: 92%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card h3 {
            margin-bottom: 5px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .box h3 {
            margin-bottom: 10px;
        }

        .box:hover {
            background: #007bff;
            color: white;
            transform: translateY(-5px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .hamburger {
                display: flex;
            }
        }

        @media (max-width: 576px) {
            .container {
                width: 94%;
                margin: 15px auto;
            }

            .card,
            .box {
                padding: 16px;
            }

            .topbar {
                padding: 12px 15px;
            }

            .topbar h2 {
                font-size: 1rem;
            }

            .sidebar {
                width: 85%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>

    <div class="wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🅿️ Aplikasi Parkir</h2>
                <div class="user-info">
                    <div class="avatar"><?php echo htmlspecialchars($inisial); ?></div>
                    <div>
                        <p class="user-name"><?php echo htmlspecialchars($nama); ?></p>
                        <span class="user-role"><?php echo strtoupper(htmlspecialchars($role)); ?></span>
                    </div>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="nav-link active">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="pengguna/riwayat.php" class="nav-link">
                    <span class="icon">🕒</span> Riwayat Parkir
                </a>
                <a href="pengguna/kendaraan_saya.php" class="nav-link">
                    <span class="icon">🚗</span> Kendaraan Saya
                </a>
                <a href="pengguna/pesan_tempat.php" class="nav-link">
                    <span class="icon">🅿️</span> Pesan Tempat
                </a>
                <a href="pengguna/help.php" class="nav-link">
                    <span class="icon">❓</span> Bantuan
                </a>
                <a href="pengguna/profil.php" class="nav-link">
                    <span class="icon">👤</span> Profil
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php" class="nav-link">
                    <span class="icon">🏠</span> Landing Page
                </a>
                <a href="logout.php" class="nav-link logout-link">
                    <span class="icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <div class="overlay" id="overlay"></div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <header class="topbar">
                <button class="hamburger" id="hamburgerBtn" aria-label="Buka menu">
                    <span></span><span></span><span></span>
                </button>
                <h2>Dashboard Pengguna</h2>
            </header>

            <div class="container">
                <div class="card">
                    <h3>Selamat datang, <?php echo htmlspecialchars($nama); ?> 👋</h3>
                    <p>Silakan pilih menu melalui sidebar atau kartu di bawah ini.</p>
                </div>

                <div class="menu">
                    <a href="pengguna/riwayat.php" class="box">
                        <h3>🕒 Riwayat Parkir</h3>
                        <p>Lihat Riwayat Parkir Anda</p>
                    </a>

                    <a href="pengguna/kendaraan_saya.php" class="box">
                        <h3>🚗 Kendaraan Saya</h3>
                        <p>Kelola Data Kendaraan Anda</p>
                    </a>

                    <a href="pengguna/pesan_tempat.php" class="box">
                        <h3>🅿️ Pesan Tempat</h3>
                        <p>Pesan Tempat Parkir</p>
                    </a>

                    <a href="pengguna/help.php" class="box">
                        <h3>❓ Bantuan</h3>
                        <p>Dapatkan Bantuan</p>
                    </a>

                    <a href="pengguna/profil.php" class="box">
                        <h3>👤 Profil</h3>
                        <p>Kelola Profil Akun</p>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const hamburgerBtn = document.getElementById('hamburgerBtn');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburgerBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Tutup sidebar otomatis saat menu diklik di layar kecil
        document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    </script>

</body>
</html>