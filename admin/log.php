<?php
session_start();
require_once "../db.php";

// Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Hanya Admin
if ($_SESSION['role'] != "admin") {
    die("Akses ditolak!");
}

// Ambil Data Log
$query = mysqli_query($conn,"
SELECT
tb_log.*,
tb_user.nama_lengkap

FROM tb_log

LEFT JOIN tb_user
ON tb_log.id_user=tb_user.id_user

ORDER BY waktu DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Log Aktivitas</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial;
}

body{
background:#f4f6f9;
}

.container{

width:95%;
max-width:1200px;
margin:30px auto;

}

.card{

background:#fff;

padding:20px;

border-radius:10px;

box-shadow:0 3px 10px rgba(0,0,0,.1);

}

h2{

margin-bottom:20px;

color:#007bff;

}

table{

width:100%;

border-collapse:collapse;

}

table th{

background:#007bff;

color:white;

padding:12px;

}

table td{

padding:12px;

border-bottom:1px solid #ddd;

}

tr:nth-child(even){

background:#f7f7f7;

}

.btn{

display:inline-block;

margin-bottom:20px;

background:#6c757d;

color:white;

text-decoration:none;

padding:10px 20px;

border-radius:6px;

}

.btn:hover{

background:#555;

}

@media(max-width:768px){

table{

display:block;

overflow-x:auto;

white-space:nowrap;

}

}

</style>

</head>

<body>

<div class="container">

<a href="../dashboard.php" class="btn">
← Kembali ke Dashboard
</a>

<div class="card">

<h2>Log Aktivitas</h2>

<table>

<tr>

<th>No</th>

<th>Nama User</th>

<th>Aktivitas</th>

<th>Tanggal & Waktu</th>

</tr>

<?php

$no=1;

while($row=mysqli_fetch_assoc($query)){

?>

<tr>

<td><?= $no++; ?></td>

<td><?= htmlspecialchars($row['nama_lengkap']); ?></td>

<td><?= htmlspecialchars($row['aktivitas']); ?></td>

<td><?= date('d-m-Y H:i:s',strtotime($row['waktu'])); ?></td>

</tr>

<?php
}
?>

</table>

</div>

</div>

</body>

</html>