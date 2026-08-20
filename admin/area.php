<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    die("Akses ditolak!");
}

// ================= TAMBAH =================
if(isset($_POST['tambah'])){

    $nama = $_POST['nama_area'];
    $kapasitas = $_POST['kapasitas'];

    mysqli_query($conn,"
    INSERT INTO tb_area_parkir
    (nama_area,kapasitas,terisi)
    VALUES
    ('$nama','$kapasitas',0)
    ");

    header("Location: area.php");
    exit;
}

// ================= HAPUS =================
if(isset($_GET['hapus'])){

    $id=(int)$_GET['hapus'];

    mysqli_query($conn,"
    DELETE FROM tb_area_parkir
    WHERE id_area='$id'
    ");

    header("Location: area.php");
    exit;
}

// ================= UPDATE =================
if(isset($_POST['update'])){

    $id=$_POST['id'];
    $nama=$_POST['nama_area'];
    $kapasitas=$_POST['kapasitas'];
    $terisi=$_POST['terisi'];

    mysqli_query($conn,"
    UPDATE tb_area_parkir SET

    nama_area='$nama',
    kapasitas='$kapasitas',
    terisi='$terisi'

    WHERE id_area='$id'
    ");

    header("Location: area.php");
    exit;

}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Area Parkir</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,Arial;
}

body{
background:#f4f6f9;
padding:20px;
}

.container{
max-width:1100px;
margin:auto;
}

.card{

background:white;

padding:20px;

border-radius:10px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

margin-bottom:20px;

}

h2{

margin-bottom:20px;

color:#2563eb;

}

input{

width:100%;

padding:10px;

margin-top:5px;

margin-bottom:15px;

border:1px solid #ccc;

border-radius:5px;

}

button{

padding:10px 20px;

background:#2563eb;

color:white;

border:none;

border-radius:5px;

cursor:pointer;

}

button:hover{

background:#1d4ed8;

}

table{

width:100%;

border-collapse:collapse;

}

table th{

background:#2563eb;

color:white;

padding:10px;

}

table td{

padding:10px;

border-bottom:1px solid #ddd;

}

.edit{

background:orange;

color:white;

padding:6px 10px;

text-decoration:none;

border-radius:4px;

}

.hapus{

background:red;

color:white;

padding:6px 10px;

text-decoration:none;

border-radius:4px;

}

.back{

display:inline-block;

margin-bottom:20px;

background:#333;

color:white;

padding:10px 15px;

text-decoration:none;

border-radius:5px;

}

@media(max-width:768px){

table{

display:block;

overflow:auto;

white-space:nowrap;

}

}

</style>

</head>

<body>

<div class="container">

<a href="../dashboard.php" class="back">
← Dashboard
</a>

<div class="card">

<h2>Tambah Area Parkir</h2>

<form method="POST">

<input
type="text"
name="nama_area"
placeholder="Nama Area"
required>

<input
type="number"
name="kapasitas"
placeholder="Kapasitas"
required>

<button
name="tambah">

Simpan

</button>

</form>

</div>

<div class="card">

<h2>Data Area Parkir</h2>

<table>

<tr>

<th>No</th>

<th>Nama Area</th>

<th>Kapasitas</th>

<th>Terisi</th>

<th>Sisa</th>

<th>Aksi</th>

</tr>

<?php

$no=1;

$data=mysqli_query($conn,"
SELECT * FROM tb_area_parkir
ORDER BY id_area DESC
");

while($d=mysqli_fetch_assoc($data)){

$sisa=$d['kapasitas']-$d['terisi'];

?>

<tr>

<td><?= $no++; ?></td>

<td><?= htmlspecialchars($d['nama_area']); ?></td>

<td><?= $d['kapasitas']; ?></td>

<td><?= $d['terisi']; ?></td>

<td><?= $sisa; ?></td>

<td>

<a
class="edit"
href="?edit=<?= $d['id_area']; ?>">

Edit

</a>

<a
class="hapus"
href="?hapus=<?= $d['id_area']; ?>"
onclick="return confirm('Hapus area ini?')">

Hapus

</a>

</td>

</tr>

<?php } ?>

</table>

</div>

<?php

if(isset($_GET['edit'])){

$id=$_GET['edit'];

$e=mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM tb_area_parkir
WHERE id_area='$id'
"));

?>

<div class="card">

<h2>Edit Area</h2>

<form method="POST">

<input
type="hidden"
name="id"
value="<?= $e['id_area']; ?>">

<input
type="text"
name="nama_area"
value="<?= htmlspecialchars($e['nama_area']); ?>"
required>

<input
type="number"
name="kapasitas"
value="<?= $e['kapasitas']; ?>"
required>

<input
type="number"
name="terisi"
value="<?= $e['terisi']; ?>"
required>

<button
name="update">

Update

</button>

</form>

</div>

<?php } ?>

</div>

</body>
</html>