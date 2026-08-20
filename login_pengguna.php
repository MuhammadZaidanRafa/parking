<?php
session_start();
require_once "db.php";

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

$error = null;
$login_success = false;

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Query untuk mengambil data user berdasarkan username
    $stmt = $conn->prepare("SELECT id_user, nama_lengkap, username, password, role, status_aktif FROM tb_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Cek apakah akun aktif
        if ($user['status_aktif'] != 1) {
            $error = "Akun Anda sedang dinonaktifkan.";
        }
        // Cek role
        elseif ($user['role'] !== 'pengguna') {
            $error = "Akses ditolak. Halaman ini khusus untuk Pengguna!";
        }
        // Verifikasi password
        elseif (password_verify($password, $user['password'])) {

            $_SESSION['login']        = true;
            $_SESSION['id_user']      = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];

            // Set flag sukses untuk ditangkap JavaScript
            $login_success = true;

        } else {
            $error = "Password yang Anda masukkan salah.";
        }

    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna - Parkir System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .badge-role {
            display: inline-block;
            background-color: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header h2 {
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
            margin-top: 6px;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #0f172a;
            transition: all 0.2s ease;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #64748b;
        }

        .footer-text a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <span class="badge-role">Portal Pengguna</span>
        <h2>Selamat Datang</h2>
        <p>Silakan masuk ke akun pengguna Anda</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">
            <span>⚠️ <?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" name="login" class="btn-submit">
            Masuk
        </button>
    </form>

    <div class="footer-text">
        Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </div>
</div>

<script>
// Fungsi synthesizer sederhana untuk menghasilkan suara beep tanpa file audio
function playNotificationSound(type) {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (type === 'success') {
            // Nada tinggi berurut (chime sukses)
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, audioCtx.currentTime); // C5
            osc.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.1); // E5
            osc.frequency.setValueAtTime(783.99, audioCtx.currentTime + 0.2); // G5
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
            osc.start(audioCtx.currentTime);
            osc.stop(audioCtx.currentTime + 0.5);
        } else {
            // Nada rendah/error (buzz gagal)
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(150, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start(audioCtx.currentTime);
            osc.stop(audioCtx.currentTime + 0.3);
        }
    } catch (e) {
        console.log("Audio playback not supported or blocked by browser.");
    }
}

<?php if ($login_success): ?>
    // Jalankan suara dan notifikasi jika login berhasil
    playNotificationSound('success');
    Swal.fire({
        icon: 'success',
        title: 'Login Berhasil!',
        text: 'Selamat datang kembali, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>',
        timer: 1500,
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'dashboard.php';
    });
<?php elseif ($error): ?>
    // Jalankan suara dan notifikasi jika login gagal
    playNotificationSound('error');
    Swal.fire({
        icon: 'error',
        title: 'Login Gagal',
        text: '<?= htmlspecialchars($error); ?>',
        confirmButtonColor: '#2563eb'
    });
<?php endif; ?>
</script>

</body>
</html>