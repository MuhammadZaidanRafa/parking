<?php
require_once "db.php";

$error = "";
$success = "";

if(isset($_POST['register'])){

    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "pengguna";

    $cek = $conn->prepare("SELECT id_user FROM tb_user WHERE username=?");
    $cek->bind_param("s",$username);
    $cek->execute();
    $hasil = $cek->get_result();

    if($hasil->num_rows > 0){
        $error = "Username sudah digunakan.";
    }else{
        $sql = "INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES (?, ?, ?, ?, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nama, $username, $password, $role);

        if($stmt->execute()){
            $success = "Registrasi berhasil! Anda akan dialihkan ke halaman login...";
        }else{
            $error = "Registrasi gagal, silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Parkir System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            max-width: 420px;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
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

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 18px;
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
            margin-top: 10px;
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
        <h2>Register User</h2>
        <p>Buat akun baru untuk sistem parkir</p>
    </div>

    <!-- Alert Notifikasi Gagal -->
    <?php if(!empty($error)): ?>
        <div class="alert alert-error">
            <span>⚠️ <?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Alert Notifikasi Sukses -->
    <?php if(!empty($success)): ?>
        <div class="alert alert-success">
            <span>✅ <?= htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required autocomplete="off" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required autocomplete="off" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" name="register" class="btn-submit">
            Daftar Akun
        </button>
    </form>

    <div class="footer-text">
        Sudah punya akun? <a href="login_pengguna.php">Masuk sekarang</a>
    </div>
</div>

<script>
// Fungsi synthesizer Web Audio API untuk menghasilkan efek suara tanpa mp3 external
function playSound(type) {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    
    if (type === 'success') {
        // Nada Beep Sukses (Tinggi & Cepat)
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } else if (type === 'error') {
        // Nada Beep Gagal (Rendah & Bergetar)
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, ctx.currentTime);
        osc.frequency.setValueAtTime(110, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.25);
    }
}

// Menjalankan audio dan pengalihan halaman berdasarkan variabel PHP
<?php if(!empty($success)): ?>
    playSound('success');
    setTimeout(function() {
        window.location.href = "login_pengguna.php";
    }, 2000); // Redirect setelah 2 detik
<?php elseif(!empty($error)): ?>
    playSound('error');
<?php endif; ?>
</script>

</body>
</html>