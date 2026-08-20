<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

/* ===============================
   CHECK-IN DARI BOOKING PELANGGAN
   Ini titik penghubung utama: booking (tb_booking, status='booking')
   dikonfirmasi datang oleh admin -> dibuatkan baris tb_transaksi baru
   yang terhubung lewat kolom id_booking.
=================================*/
if (isset($_POST['checkin_booking'])) {

    $id_booking = (int) $_POST['id_booking'];

    $stmt = $conn->prepare("
        SELECT b.id_booking, b.id_kendaraan, b.id_area, b.id_user, t.id_tarif
        FROM tb_booking b
        JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan
        JOIN tb_tarif t ON t.jenis_kendaraan = k.jenis_kendaraan
        WHERE b.id_booking = ? AND b.status = 'booking'
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_booking);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking) {
        $stmt = $conn->prepare("
            INSERT INTO tb_transaksi
                (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, id_booking)
            VALUES (?, NOW(), ?, 'masuk', ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiiii",
            $booking['id_kendaraan'],
            $booking['id_tarif'],
            $booking['id_user'],
            $booking['id_area'],
            $id_booking
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE tb_booking SET status = 'aktif' WHERE id_booking = ?");
        $stmt->bind_param("i", $id_booking);
        $stmt->execute();
        $stmt->close();

        header("Location: transaksi.php?sukses=checkin");
        exit;
    }

    header("Location: transaksi.php?error=1");
    exit;
}

/* ===============================
   BATALKAN BOOKING (dari sisi admin)
=================================*/
if (isset($_POST['batalkan_booking'])) {

    $id_booking = (int) $_POST['id_booking'];

    $stmt = $conn->prepare("UPDATE tb_booking SET status = 'batal' WHERE id_booking = ? AND status = 'booking'");
    $stmt->bind_param("i", $id_booking);
    $stmt->execute();
    $stmt->close();

    header("Location: transaksi.php?sukses=batal_booking");
    exit;
}

/* ===============================
   PARKIR MASUK (langsung / tanpa booking, misalnya tamu walk-in)
=================================*/
if (isset($_POST['parkir_masuk'])) {

    $id_kendaraan = (int) $_POST['id_kendaraan'];
    $id_area      = (int) $_POST['id_area'];

    $stmt = $conn->prepare("
        SELECT k.id_kendaraan, t.id_tarif
        FROM tb_kendaraan k
        JOIN tb_tarif t ON k.jenis_kendaraan = t.jenis_kendaraan
        WHERE k.id_kendaraan = ?
    ");
    $stmt->bind_param("i", $id_kendaraan);
    $stmt->execute();
    $kendaraan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($kendaraan) {
        $stmt = $conn->prepare("
            INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area)
            VALUES (?, NOW(), ?, 'masuk', ?, ?)
        ");
        $stmt->bind_param("iiii", $id_kendaraan, $kendaraan['id_tarif'], $id_user, $id_area);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: transaksi.php?sukses=masuk");
    exit;
}

/* ===============================
   PARKIR KELUAR
   Kalau transaksi ini berasal dari booking (id_booking terisi),
   status booking terkait ikut diperbarui jadi 'selesai'.
=================================*/
if (isset($_GET['keluar'])) {

    $id = (int) $_GET['keluar'];

    $stmt = $conn->prepare("
        SELECT tb_transaksi.*, tb_tarif.tarif_per_jam
        FROM tb_transaksi
        JOIN tb_tarif ON tb_tarif.id_tarif = tb_transaksi.id_tarif
        WHERE id_parkir = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $masuk  = strtotime($data['waktu_masuk']);
        $keluar = time();
        $jam    = max(1, (int) ceil(($keluar - $masuk) / 3600));
        $total  = $jam * $data['tarif_per_jam'];

        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET waktu_keluar = NOW(), durasi_jam = ?, biaya_total = ?, status = 'keluar'
            WHERE id_parkir = ?
        ");
        $stmt->bind_param("idi", $jam, $total, $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($data['id_booking'])) {
            $stmt = $conn->prepare("UPDATE tb_booking SET status = 'selesai' WHERE id_booking = ?");
            $stmt->bind_param("i", $data['id_booking']);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: transaksi.php?sukses=keluar");
    exit;
}

/* ===============================
   EDIT DURASI PARKIR
=================================*/
if (isset($_POST['update_durasi'])) {
    $id_parkir   = (int) $_POST['id_parkir'];
    $durasi_baru = (int) $_POST['durasi_jam'];

    if ($durasi_baru < 1) {
        $durasi_baru = 1; // Minimal durasi 1 jam
    }

    $stmt = $conn->prepare("
        SELECT tb_tarif.tarif_per_jam
        FROM tb_transaksi
        JOIN tb_tarif ON tb_tarif.id_tarif = tb_transaksi.id_tarif
        WHERE id_parkir = ?
    ");
    $stmt->bind_param("i", $id_parkir);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $total_baru = $durasi_baru * $data['tarif_per_jam'];

        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET durasi_jam = ?, biaya_total = ?
            WHERE id_parkir = ?
        ");
        $stmt->bind_param("idi", $durasi_baru, $total_baru, $id_parkir);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: transaksi.php?sukses=edit_durasi");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaksi Parkir</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: #0056b3;
        }

        .btn {
            padding: 8px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .btn:hover {
            background: #1d7a32;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #b02a37;
        }

        /* Styling Modal Edit */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 350px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Transaksi Parkir</h2>

    <?php
    if (isset($_GET['sukses'])) {
        $pesan_sukses_map = [
            'masuk'         => 'Parkir masuk (langsung) berhasil ditambahkan.',
            'keluar'        => 'Parkir keluar berhasil diproses.',
            'edit_durasi'   => 'Durasi parkir dan biaya berhasil diperbarui.',
            'checkin'       => 'Booking berhasil dikonfirmasi masuk (check-in).',
            'batal_booking' => 'Booking berhasil dibatalkan.',
        ];
        if (isset($pesan_sukses_map[$_GET['sukses']])) {
            echo "<p style='color:green;'>" . htmlspecialchars($pesan_sukses_map[$_GET['sukses']]) . "</p>";
        }
    }
    if (isset($_GET['error'])) {
        echo "<p style='color:red;'>Booking tidak ditemukan atau sudah diproses sebelumnya.</p>";
    }
    ?>

    <h3>Booking Menunggu Check-in</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Plat</th>
            <th>Pemilik</th>
            <th>Area</th>
            <th>Tanggal</th>
            <th>Jam Booking</th>
            <th>Estimasi</th>
            <th>Aksi</th>
        </tr>
        <?php
        $booking_pending = $conn->query("
            SELECT b.id_booking, b.tanggal, b.jam_masuk, b.estimasi_jam,
                   k.plat_nomor, k.pemilik, a.nama_area
            FROM tb_booking b
            JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan
            JOIN tb_area_parkir a ON a.id_area = b.id_area
            WHERE b.status = 'booking'
            ORDER BY b.tanggal ASC, b.jam_masuk ASC
        ");

        if ($booking_pending->num_rows === 0) {
            echo "<tr><td colspan='8'>Tidak ada booking yang menunggu check-in.</td></tr>";
        } else {
            $no_b = 1;
            while ($b = $booking_pending->fetch_assoc()) {
        ?>
        <tr>
            <td><?= $no_b++ ?></td>
            <td><?= htmlspecialchars($b['plat_nomor']) ?></td>
            <td><?= htmlspecialchars($b['pemilik']) ?></td>
            <td><?= htmlspecialchars($b['nama_area']) ?></td>
            <td><?= date('d-m-Y', strtotime($b['tanggal'])) ?></td>
            <td><?= date('H:i', strtotime($b['jam_masuk'])) ?></td>
            <td><?= (int) $b['estimasi_jam'] ?> Jam</td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_booking" value="<?= (int) $b['id_booking'] ?>">
                    <button type="submit" name="checkin_booking" class="btn">Check-in</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?');">
                    <input type="hidden" name="id_booking" value="<?= (int) $b['id_booking'] ?>">
                    <button type="submit" name="batalkan_booking" class="btn btn-danger">Batalkan</button>
                </form>
            </td>
        </tr>
        <?php
            }
        }
        ?>
    </table>

    <hr>

    <h3>Parkir Masuk (Langsung / Tanpa Booking)</h3>
    <form method="POST">
        <label>Kendaraan</label>
        <select name="id_kendaraan" required>
            <option value="">Pilih Kendaraan</option>
            <?php
            $k = $conn->query("SELECT * FROM tb_kendaraan");
            while ($r = $k->fetch_assoc()) {
                echo "<option value='" . (int) $r['id_kendaraan'] . "'>" .
                     htmlspecialchars($r['plat_nomor']) . " - " . htmlspecialchars($r['pemilik']) .
                     "</option>";
            }
            ?>
        </select>

        <label>Area Parkir</label>
        <select name="id_area" required>
            <option value="">Pilih Area</option>
            <?php
            $a = $conn->query("SELECT * FROM tb_area_parkir");
            while ($r = $a->fetch_assoc()) {
                echo "<option value='" . (int) $r['id_area'] . "'>" . htmlspecialchars($r['nama_area']) . "</option>";
            }
            ?>
        </select>

        <button name="parkir_masuk">Simpan</button>
    </form>

    <hr>

    <h3>Data Kendaraan Masih Parkir</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Plat</th>
            <th>Pemilik</th>
            <th>Masuk</th>
            <th>Area</th>
            <th>Asal</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = 1;
        $data = $conn->query("
            SELECT tb_transaksi.*, tb_kendaraan.plat_nomor, tb_kendaraan.pemilik, tb_area_parkir.nama_area
            FROM tb_transaksi
            JOIN tb_kendaraan ON tb_transaksi.id_kendaraan = tb_kendaraan.id_kendaraan
            JOIN tb_area_parkir ON tb_area_parkir.id_area = tb_transaksi.id_area
            WHERE tb_transaksi.status = 'masuk'
            ORDER BY tb_transaksi.waktu_masuk DESC
        ");

        while ($d = $data->fetch_assoc()) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($d['plat_nomor']) ?></td>
            <td><?= htmlspecialchars($d['pemilik']) ?></td>
            <td><?= $d['waktu_masuk'] ?></td>
            <td><?= htmlspecialchars($d['nama_area']) ?></td>
            <td><?= $d['id_booking'] ? 'Booking #' . (int) $d['id_booking'] : 'Langsung' ?></td>
            <td>
                <a class="btn" href="transaksi.php?keluar=<?= (int) $d['id_parkir'] ?>">Parkir Keluar</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <hr>

    <h3>Riwayat Kendaraan Keluar (Edit Durasi)</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Plat</th>
            <th>Masuk</th>
            <th>Keluar</th>
            <th>Durasi</th>
            <th>Total Biaya</th>
            <th>Asal</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no_keluar = 1;
        $data_keluar = $conn->query("
            SELECT tb_transaksi.*, tb_kendaraan.plat_nomor
            FROM tb_transaksi
            JOIN tb_kendaraan ON tb_transaksi.id_kendaraan = tb_kendaraan.id_kendaraan
            WHERE tb_transaksi.status = 'keluar'
            ORDER BY tb_transaksi.waktu_keluar DESC
        ");

        while ($dk = $data_keluar->fetch_assoc()) {
        ?>
        <tr>
            <td><?= $no_keluar++ ?></td>
            <td><?= htmlspecialchars($dk['plat_nomor']) ?></td>
            <td><?= $dk['waktu_masuk'] ?></td>
            <td><?= $dk['waktu_keluar'] ?></td>
            <td><?= (int) $dk['durasi_jam'] ?> Jam</td>
            <td>Rp <?= number_format($dk['biaya_total'], 0, ',', '.') ?></td>
            <td><?= $dk['id_booking'] ? 'Booking #' . (int) $dk['id_booking'] : 'Langsung' ?></td>
            <td>
                <a href="struk.php?id=<?= (int) $dk['id_parkir'] ?>" class="btn" target="_blank" rel="noopener">🧾 Struk</a>
                <button type="button" class="btn btn-warning" onclick="openEditModal(<?= (int) $dk['id_parkir'] ?>, <?= (int) $dk['durasi_jam'] ?>)">
                    Edit Durasi
                </button>
            </td>
        </tr>
        <?php } ?>
    </table>

    <br>
    <a href="../dashboard.php" class="btn" style="background:#6c757d;">← Kembali Dashboard</a>
</div>

<!-- Modal Edit Durasi Parkir -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Durasi Parkir</h3>
        <form method="POST">
            <input type="hidden" name="id_parkir" id="edit_id_parkir">

            <label>Durasi (Jam):</label>
            <input type="number" name="durasi_jam" id="edit_durasi_jam" min="1" required>

            <button type="submit" name="update_durasi">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(idParkir, durasiJam) {
        document.getElementById('edit_id_parkir').value = idParkir;
        document.getElementById('edit_durasi_jam').value = durasiJam;
        document.getElementById('modalEdit').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('modalEdit').style.display = 'none';
    }

    window.onclick = function(event) {
        var modal = document.getElementById('modalEdit');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>