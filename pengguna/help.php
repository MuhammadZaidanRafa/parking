<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Pemesanan Parkir - E-Parkir</title>

<!-- Fonts & Framework Bootstrap 5 -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
  :root {
    --primary: #007bff;
    --primary-hover: #0056b3;
    --bg-light: #f4f6f9;
    --text-dark: #1e293b;
    --text-muted: #64748b;
  }

  body {
    background-color: var(--bg-light);
    font-family: 'Inter', sans-serif;
    color: var(--text-dark);
    padding-bottom: 60px;
  }

  .hero-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 40px 20px;
    border-radius: 0 0 20px 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
  }

  .btn-kembali {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    transition: all 0.2s ease;
    backdrop-filter: blur(5px);
  }

  .btn-kembali:hover {
    color: white;
    background: rgba(255,255,255,0.25);
  }

  .card-guide {
    background: white;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0f2fe;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
  }

  .accordion-item {
    border: none;
    margin-bottom: 10px;
    border-radius: 8px !important;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
  }

  .accordion-button:not(.collapsed) {
    background-color: #e0f2fe;
    color: var(--primary);
  }

  .contact-box {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border-left: 5px solid var(--primary);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }
</style>
</head>
<body>

<!-- Header Hero -->
<header class="hero-header text-center">
  <div class="container" style="max-width: 800px;">
    <div class="d-flex justify-content-start mb-3">
      <a href="javascript:history.back()" class="btn-kembali">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
    <h1 class="fw-bold mb-2"><i class="fa-solid fa-circle-question me-2"></i> Panduan Pemesanan Parkir</h1>
    <p class="mb-0 opacity-90">Petunjuk langkah demi langkah untuk memesan slot parkir secara daring.</p>
  </div>
</header>

<div class="container" style="max-width: 900px;">

  <!-- Alur Pemesanan -->
  <div class="card card-guide p-4 mb-4">
    <h4 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-list-check me-2"></i> Tata Cara Pemesanan Slot Parkir</h4>

    <div class="row g-4">
      <!-- Step 1 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">1</div>
          <div>
            <h6 class="fw-bold mb-1">Daftarkan Kendaraan</h6>
            <p class="text-muted small mb-0">Masuk ke akun Anda, lalu isi plat nomor, jenis, merk, dan warna kendaraan pada halaman <strong>Kendaraan Saya</strong>.</p>
          </div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">2</div>
          <div>
            <h6 class="fw-bold mb-1">Cek Ketersediaan Slot</h6>
            <p class="text-muted small mb-0">Lihat halaman <strong>Status Area Parkir</strong> untuk mengetahui lokasi mana yang masih memiliki kuota slot kosong.</p>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">3</div>
          <div>
            <h6 class="fw-bold mb-1">Lakukan Booking</h6>
            <p class="text-muted small mb-0">Klik tombol <strong>+ Booking Tempat</strong> pada kendaraan Anda, pilih lokasi area, tanggal, jam masuk, serta estimasi durasi.</p>
          </div>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">4</div>
          <div>
            <h6 class="fw-bold mb-1">Konfirmasi & Parkir</h6>
            <p class="text-muted small mb-0">Saat Anda telah sampai di lokasi, tekan tombol <span class="badge bg-warning text-dark">📍 Sudah di Tempat</span> untuk mengaktifkan status parkir Anda.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FAQ / Pertanyaan Sering Diajukan -->
  <div class="card card-guide p-4 mb-4">
    <h4 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-comments-question me-2"></i> Pertanyaan Umum (FAQ)</h4>

    <div class="accordion" id="faqAccordion">
      
      <!-- FAQ 1 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
            Bagaimana cara membatalkan pemesanan?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Jika belum mengonfirmasi kedatangan, Anda bisa menekan tombol <span class="badge bg-danger">❌ Batal Pesanan</span> pada menu <strong>Kendaraan Saya</strong>. Slot parkir akan dilepaskan kembali secara otomatis.
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            Bagaimana cara mencetak kuitansi booking?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Buka menu <strong>Kendaraan Saya</strong>, lalu klik tautan <strong>Lihat Kuitansi</strong> di bawah rincian booking kendaraan Anda.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            Berapa lama batas toleransi keterlambatan?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Batas toleransi kedatangan adalah 15–30 menit dari jam masuk yang dipilih. Jika melebihi batas waktu tersebut tanpa konfirmasi, booking dapat dibatalkan oleh petugas.
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Kontak Bantuan -->
  <div class="contact-box d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h5 class="fw-bold mb-1"><i class="fa-solid fa-headset text-primary me-2"></i> Butuh Bantuan Petugas?</h5>
      <p class="text-muted small mb-0">Jika terdapat kendala saat melakukan booking, silakan hubungi pusat layanan kami.</p>
    </div>
    <div>
      <a href="https://wa.me/081326831712" target="_blank" class="btn btn-success btn-sm px-3 fw-semibold">
        <i class="fa-brands fa-whatsapp me-1"></i> Hubungi WhatsApp
      </a>
    </div>
  </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>