<?php
session_start();
require_once 'db.php';

// Memastikan variabel koneksi valid
if (!isset($koneksi)) {
    if (isset($conn)) {
        $koneksi = $conn;
    } elseif (isset($mysqli)) {
        $koneksi = $mysqli;
    }
}

if (empty($koneksi)) {
    die('Variabel koneksi tidak ditemukan di db.php.');
}

// -------------------------------------------------------------
// Auto-Create Tabel Ulasan Jika Belum Ada
// -------------------------------------------------------------
$create_table_sql = "CREATE TABLE IF NOT EXISTS tb_ulasan (
    id_ulasan INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_area INT(11) NOT NULL,
    id_user INT(11) NULL,
    nama_pengulas VARCHAR(50) NOT NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 5.0,
    komentar TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_area) REFERENCES tb_area_parkir(id_area) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_table_sql);

// -------------------------------------------------------------
// PROSES TAMBAH ULASAN/KOMENTAR (POST)
// -------------------------------------------------------------
$pesan_sukses = '';
$pesan_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ulasan'])) {
    $id_area       = (int)($_POST['id_area'] ?? 0);
    $rating_input  = (float)($_POST['rating'] ?? 5.0);
    $komentar_input= trim($_POST['komentar'] ?? '');
    
    // Tentukan nama pengulas
    $id_user = null;
    $nama_pengulas = 'Tamu';
    if (isset($_SESSION['nama_lengkap'])) {
        $nama_pengulas = $_SESSION['nama_lengkap'];
        $id_user = $_SESSION['id_user'] ?? ($_SESSION['user_id'] ?? null);
    } elseif (!empty($_POST['nama_pengulas'])) {
        $nama_pengulas = trim($_POST['nama_pengulas']);
    }

    if ($id_area > 0 && !empty($komentar_input)) {
        // Simpan komentar ke tb_ulasan
        $stmt = $koneksi->prepare("INSERT INTO tb_ulasan (id_area, id_user, nama_pengulas, rating, komentar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $id_area, $id_user, $nama_pengulas, $rating_input, $komentar_input);
        
        if ($stmt->execute()) {
            // Hitung ulang rata-rata rating untuk tb_area_parkir
            $stmt_avg = $koneksi->prepare("SELECT AVG(rating) as avg_rating FROM tb_ulasan WHERE id_area = ?");
            $stmt_avg->bind_param("i", $id_area);
            $stmt_avg->execute();
            $res_avg = $stmt_avg->get_result()->fetch_assoc();
            $new_avg = round($res_avg['avg_rating'] ?? $rating_input, 1);

            // Update rating di tb_area_parkir
            $stmt_upd = $koneksi->prepare("UPDATE tb_area_parkir SET rating = ? WHERE id_area = ?");
            $stmt_upd->bind_param("di", $new_avg, $id_area);
            $stmt_upd->execute();

            $pesan_sukses = "Terima kasih! Ulasan dan rating Anda telah ditambahkan.";
        } else {
            $pesan_error = "Gagal menyimpan ulasan: " . $koneksi->error;
        }
    } else {
        $pesan_error = "Mohon isi komentar ulasan Anda.";
    }
}

// -------------------------------------------------------------
// AMBIL DATA AREA PARKIR
// -------------------------------------------------------------
$query  = "SELECT id_area, nama_area, kapasitas, terisi, IFNULL(rating, 0.0) as rating FROM tb_area_parkir ORDER BY nama_area ASC";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    die('Query gagal: ' . mysqli_error($koneksi));
}

$areas = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Ambil 3 komentar terbaru untuk setiap area
    $id_a = $row['id_area'];
    $q_komentar = mysqli_query($koneksi, "SELECT nama_pengulas, rating, komentar, created_at FROM tb_ulasan WHERE id_area = '$id_a' ORDER BY created_at DESC LIMIT 3");
    $list_komentar = [];
    if ($q_komentar) {
        while ($k = mysqli_fetch_assoc($q_komentar)) {
            $list_komentar[] = $k;
        }
    }
    $row['ulasan'] = $list_komentar;
    $areas[] = $row;
}

$totalKapasitas = array_sum(array_column($areas, 'kapasitas'));
$totalTerisi    = array_sum(array_column($areas, 'terisi'));
$totalTersedia  = $totalKapasitas - $totalTerisi;

const MAKS_SLOT_VISUAL = 40;

function statusArea(int $kapasitas, int $terisi): array
{
    $sisa   = max(0, $kapasitas - $terisi);
    $persen = $kapasitas > 0 ? round(($terisi / $kapasitas) * 100) : 0;

    if ($sisa <= 0) {
        return ['key' => 'full', 'label' => 'PENUH', 'persen' => $persen, 'sisa' => $sisa];
    }
    if ($persen >= 80) {
        return ['key' => 'warn', 'label' => 'HAMPIR PENUH', 'persen' => $persen, 'sisa' => $sisa];
    }
    return ['key' => 'ok', 'label' => 'TERSEDIA', 'persen' => $persen, 'sisa' => $sisa];
}

// Helper cetak ikon bintang FontAwesome
function renderStars($rating) {
    $html = '';
    $rating = (float)$rating;
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star text-warning"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star text-muted"></i>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Area Parkir & Ulasan</title>

<!-- Fonts & Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
  :root {
    --bg: #e9ebee;
    --panel: #ffffff;
    --ink: #1e2a38;
    --ink-soft: #5b6673;
    --line: #d8dce1;
    --ok: #2f9e44;
    --ok-bg: #e6f6ea;
    --warn: #c97400;
    --warn-bg: #fef1de;
    --full: #d3352c;
    --full-bg: #fbe5e3;
    --stripe: #ffc107;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    background:
      repeating-linear-gradient(90deg, transparent 0 78px, var(--line) 78px 80px),
      var(--bg);
    font-family: 'Inter', sans-serif;
    color: var(--ink);
    padding: 28px 20px 60px;
  }

  .wrap { max-width: 1080px; margin: 0 auto; }

  header.page {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 4px solid var(--ink);
    padding-bottom: 16px;
    margin-bottom: 24px;
  }

  .eyebrow {
    font-family: 'Oswald', sans-serif;
    font-size: 13px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin: 0 0 4px;
  }

  h1 {
    font-family: 'Oswald', sans-serif;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    font-size: clamp(28px, 4vw, 40px);
    margin: 0;
  }

  .updated {
    font-size: 13px;
    color: var(--ink-soft);
    text-align: right;
  }

  .updated a {
    display: inline-block;
    margin-top: 6px;
    font-family: 'Oswald', sans-serif;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ink);
    background: var(--stripe);
    padding: 6px 12px;
    border-radius: 3px;
    text-decoration: none;
  }

  .btn-kembali {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'Oswald', sans-serif;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ink-soft);
    text-decoration: none;
    border: 1px solid var(--line);
    background: var(--panel);
    padding: 6px 14px;
    border-radius: 3px;
    margin-bottom: 16px;
    transition: color 0.15s ease, border-color 0.15s ease;
  }

  .btn-kembali:hover {
    color: var(--ink);
    border-color: var(--ink);
  }

  .ringkasan {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 28px;
  }

  .ringkasan .kotak {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 14px 16px;
  }

  .ringkasan .angka {
    font-family: 'Oswald', sans-serif;
    font-size: 30px;
    font-weight: 600;
    line-height: 1;
  }

  .ringkasan .label {
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin-top: 4px;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
  }

  .kartu {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 18px;
    border-top: 5px solid var(--ink);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    animation: masuk 0.35s ease backwards;
    display: flex;
    flex-direction: column;
  }

  .kartu:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(30, 42, 56, 0.08);
  }

  .kartu.ok    { border-top-color: var(--ok); }
  .kartu.warn  { border-top-color: var(--warn); }
  .kartu.full  { border-top-color: var(--full); }

  .kartu .judul {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 6px;
  }

  .kartu h2 {
    font-family: 'Oswald', sans-serif;
    font-size: 19px;
    font-weight: 600;
    margin: 0;
    text-transform: uppercase;
  }

  .badge {
    font-family: 'Oswald', sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    padding: 3px 9px;
    border-radius: 3px;
    white-space: nowrap;
  }

  .badge.ok   { color: var(--ok);   background: var(--ok-bg); }
  .badge.warn { color: var(--warn); background: var(--warn-bg); }
  .badge.full { color: var(--full); background: var(--full-bg); }

  .rating-box {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ink-soft);
    margin-bottom: 12px;
  }

  .rating-box .score {
    font-weight: 700;
    color: var(--ink);
  }

  .slot-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 3px;
    margin: 12px 0;
  }

  .slot {
    aspect-ratio: 2 / 3;
    border-radius: 2px;
    border: 1.5px solid var(--line);
  }

  .slot.terisi.ok   { background: var(--ok);   border-color: var(--ok); }
  .slot.terisi.warn { background: var(--warn); border-color: var(--warn); }
  .slot.terisi.full { background: var(--full); border-color: var(--full); }

  .bar-track {
    height: 14px;
    border-radius: 7px;
    background: var(--bg);
    border: 1px solid var(--line);
    overflow: hidden;
    margin: 14px 0;
  }

  .bar-isi {
    height: 100%;
    border-radius: 7px 0 0 7px;
  }

  .bar-isi.ok   { background: var(--ok); }
  .bar-isi.warn { background: var(--warn); }
  .bar-isi.full { background: var(--full); }

  .statistik {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--ink-soft);
    border-top: 1px solid var(--line);
    padding-top: 10px;
    margin-bottom: 12px;
  }

  .statistik strong {
    display: block;
    font-family: 'Oswald', sans-serif;
    font-size: 17px;
    color: var(--ink);
    font-weight: 600;
  }

  .section-komentar {
    margin-top: auto;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px;
    border: 1px solid #e9ecef;
  }

  .komentar-item {
    font-size: 12px;
    border-bottom: 1px dashed #dee2e6;
    padding-bottom: 6px;
    margin-bottom: 6px;
  }
  .komentar-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
  }

  .kosong {
    background: var(--panel);
    border: 1px dashed var(--line);
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    color: var(--ink-soft);
  }

  @keyframes masuk {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  @media (prefers-reduced-motion: reduce) {
    .kartu { animation: none; transition: none; }
  }

  @media (max-width: 480px) {
    .ringkasan { grid-template-columns: 1fr; }
    .updated { text-align: left; }
  }
</style>
</head>
<body>
<div class="wrap">

  <a href="index.php" class="btn-kembali">&larr; Kembali ke Beranda</a>

  <header class="page">
    <div>
      <p class="eyebrow">Sistem Parkir</p>
      <h1>Status & Rating Area Parkir</h1>
    </div>
    <div class="updated">
      Terakhir diperbarui: <?= date('d M Y, H:i:s') ?>
      <br>
      <a href="cek_area.php">Refresh</a>
    </div>
  </header>

  <?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fa-solid fa-circle-check me-1"></i> <?= $pesan_sukses ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($pesan_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fa-solid fa-circle-exclamation me-1"></i> <?= $pesan_error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($areas)): ?>

    <div class="kosong">Belum ada data area parkir. Tambahkan data pada tabel <code>tb_area_parkir</code> untuk mulai memantau status.</div>

  <?php else: ?>

    <div class="ringkasan">
      <div class="kotak">
        <div class="angka"><?= $totalKapasitas ?></div>
        <div class="label">Total Kapasitas</div>
      </div>
      <div class="kotak">
        <div class="angka"><?= $totalTerisi ?></div>
        <div class="label">Total Terisi</div>
      </div>
      <div class="kotak">
        <div class="angka"><?= max(0, $totalTersedia) ?></div>
        <div class="label">Total Tersedia</div>
      </div>
    </div>

    <div class="grid">
      <?php foreach ($areas as $i => $area):
          $kap    = (int) $area['kapasitas'];
          $isi    = (int) $area['terisi'];
          $st     = statusArea($kap, $isi);
          $delay  = $i * 0.04;
          $rating = (float) $area['rating'];
      ?>
      <div class="kartu <?= $st['key'] ?>" style="animation-delay: <?= $delay ?>s">
        <div class="judul">
          <h2><?= htmlspecialchars($area['nama_area']) ?></h2>
          <span class="badge <?= $st['key'] ?>"><?= $st['label'] ?></span>
        </div>

        <!-- Rating & Bintang -->
        <div class="rating-box">
          <div class="stars"><?= renderStars($rating) ?></div>
          <span class="score"><?= number_format($rating, 1) ?></span>
          <span class="text-muted">/ 5.0</span>
        </div>

        <?php if ($kap > 0 && $kap <= MAKS_SLOT_VISUAL): ?>
          <div class="slot-grid">
            <?php for ($s = 1; $s <= $kap; $s++): ?>
              <div class="slot <?= $s <= $isi ? 'terisi ' . $st['key'] : '' ?>"></div>
            <?php endfor; ?>
          </div>
        <?php else: ?>
          <div class="bar-track">
            <div class="bar-isi <?= $st['key'] ?>" style="width: <?= min(100, $st['persen']) ?>%"></div>
          </div>
        <?php endif; ?>

        <div class="statistik">
          <div><strong><?= $kap ?></strong>Kapasitas</div>
          <div><strong><?= $isi ?></strong>Terisi</div>
          <div><strong><?= max(0, $kap - $isi) ?></strong>Tersedia</div>
        </div>

        <!-- Section Komentar & Ulasan Terbaru -->
        <div class="section-komentar">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-secondary" style="font-size: 12px;"><i class="fa-solid fa-comments me-1"></i> Ulasan Terbaru</span>
            <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#modalUlasan<?= $area['id_area'] ?>">
              + Beri Ulasan
            </button>
          </div>

          <?php if (!empty($area['ulasan'])): ?>
            <?php foreach ($area['ulasan'] as $u): ?>
              <div class="komentar-item">
                <div class="d-flex justify-content-between">
                  <strong class="text-dark"><?= htmlspecialchars($u['nama_pengulas']) ?></strong>
                  <span class="text-warning"><?= renderStars($u['rating']) ?></span>
                </div>
                <p class="mb-0 text-muted" style="line-height: 1.3;"><?= htmlspecialchars($u['komentar']) ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-2" style="font-size: 11px;">Belum ada ulasan untuk area ini.</div>
          <?php endif; ?>
        </div>

      </div>

      <!-- Modal Tambah Ulasan -->
      <div class="modal fade" id="modalUlasan<?= $area['id_area'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form method="POST" action="">
              <div class="modal-header">
                <h5 class="modal-title font-sans fw-bold">Ulasan untuk <?= htmlspecialchars($area['nama_area']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id_area" value="<?= $area['id_area'] ?>">
                
                <?php if (!isset($_SESSION['nama_lengkap'])): ?>
                  <div class="mb-3">
                    <label class="form-label small font-semibold">Nama Anda</label>
                    <input type="text" name="nama_pengulas" class="form-control form-control-sm" placeholder="Masukkan nama..." required>
                  </div>
                <?php else: ?>
                  <p class="small text-muted mb-3">Mengulas sebagai: <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></p>
                <?php endif; ?>

                <div class="mb-3">
                  <label class="form-label small font-semibold">Rating Bintang</label>
                  <select name="rating" class="form-select form-select-sm">
                    <option value="5.0">⭐⭐⭐⭐⭐ (5 - Sangat Bagus)</option>
                    <option value="4.0">⭐⭐⭐⭐ (4 - Bagus)</option>
                    <option value="3.0">⭐⭐⭐ (3 - Cukup)</option>
                    <option value="2.0">⭐⭐ (2 - Kurang)</option>
                    <option value="1.0">⭐ (1 - Buruk)</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label small font-semibold">Komentar / Pengalaman Parkir</label>
                  <textarea name="komentar" class="form-control form-control-sm" rows="3" placeholder="Tuliskan ulasan Anda..." required></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="submit_ulasan" class="btn btn-sm btn-primary">Kirim Ulasan</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>