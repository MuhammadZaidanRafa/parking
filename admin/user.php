<?php
session_start();
require_once "../db.php";

// Cek login dan role admin
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    die("Akses ditolak!");
}

// ==================== TAMBAH ====================
if (isset($_POST['simpan'])) {

    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    mysqli_query($conn,"INSERT INTO tb_user
    (nama_lengkap,username,password,role,status_aktif)
    VALUES
    ('$nama','$username','$password','$role',1)");

    header("Location: user.php");
    exit;
}

// ==================== HAPUS ====================
if(isset($_GET['hapus'])){

    $id = intval($_GET['hapus']);

    mysqli_query($conn,"DELETE FROM tb_user WHERE id_user='$id'");

    header("Location:user.php");
    exit;
}

// ==================== UPDATE ====================
if(isset($_POST['update'])){

    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    if($_POST['password']!=""){

        $password=password_hash($_POST['password'],PASSWORD_DEFAULT);

        mysqli_query($conn,"
        UPDATE tb_user SET

        nama_lengkap='$nama',
        username='$username',
        password='$password',
        role='$role'

        WHERE id_user='$id'
        ");

    }else{

        mysqli_query($conn,"
        UPDATE tb_user SET

        nama_lengkap='$nama',
        username='$username',
        role='$role'

        WHERE id_user='$id'
        ");

    }

    header("Location:user.php");
    exit;

}
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Data User</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,Arial;
}

body{
background:#f5f5f5;
padding:20px;
}

.container{
max-width:1100px;
margin:auto;
}

.card{
background:#fff;
padding:20px;
border-radius:10px;
box-shadow:0 5px 15px rgba(0,0,0,.1);
margin-bottom:20px;
}

h2{
margin-bottom:20px;
color:#2563eb;
}

input,select{

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
padding:6px 10px;
text-decoration:none;
color:white;
border-radius:4px;

}

.hapus{

background:red;
padding:6px 10px;
text-decoration:none;
color:white;
border-radius:4px;

}

.back{

display:inline-block;
margin-bottom:20px;
text-decoration:none;
background:#333;
color:white;
padding:10px 15px;
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

<h2>Tambah User</h2>

<form method="POST">

<input
type="text"
name="nama"
placeholder="Nama Lengkap"
required>

<input
type="text"
name="username"
placeholder="Username"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<select name="role">

<option value="admin">Admin</option>

<option value="petugas">Petugas</option>

<option value="owner">Owner</option>

</select>

<button name="simpan">
Simpan
</button>

</form>

</div>

<div class="card">

<h2>Data User</h2>

<table>

<tr>

<th>No</th>

<th>Nama</th>

<th>Username</th>

<th>Role</th>

<th>Status</th>

<th>Aksi</th>

</tr>

<?php

$no=1;

$data=mysqli_query($conn,"SELECT * FROM tb_user ORDER BY id_user DESC");

while($d=mysqli_fetch_array($data)){

?>

<tr>

<td><?= $no++ ?></td>

<td><?= htmlspecialchars($d['nama_lengkap']) ?></td>

<td><?= htmlspecialchars($d['username']) ?></td>

<td><?= strtoupper($d['role']) ?></td>

<td>

<?= $d['status_aktif']==1 ? "Aktif":"Nonaktif"; ?>

</td>

<td>

<a class="edit"
href="?edit=<?= $d['id_user']; ?>">
Edit
</a>

<a class="hapus"
onclick="return confirm('Hapus user ini?')"
href="?hapus=<?= $d['id_user']; ?>">
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

$e=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tb_user WHERE id_user='$id'"));

?>

<div class="card">

<h2>Edit User</h2>

<form method="POST">

<input
type="hidden"
name="id"
value="<?= $e['id_user']; ?>">

<input
type="text"
name="nama"
value="<?= htmlspecialchars($e['nama_lengkap']); ?>"
required>

<input
type="text"
name="username"
value="<?= htmlspecialchars($e['username']); ?>"
required>

<input
type="password"
name="password"
placeholder="Kosongkan jika tidak diubah">

<select name="role">

<option value="admin" <?= $e['role']=="admin"?"selected":"" ?>>Admin</option>

<option value="petugas" <?= $e['role']=="petugas"?"selected":"" ?>>Petugas</option>

<option value="owner" <?= $e['role']=="owner"?"selected":"" ?>>Owner</option>

</select>

<button name="update">

Update

</button>

</form>

</div>

<?php } ?>

</div>

</body>
</html>