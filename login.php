<?php
session_start();
require_once "db.php";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM tb_user WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if ($password === $user['password']) {

            if ($user['status_aktif'] == 1) {

                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Akun tidak aktif.";
            }

        } else {
            $error = "Password salah.";
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

<title>Login Parkir</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,Arial,sans-serif;
}

body{

    min-height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    background:linear-gradient(135deg,#0f172a,#2563eb);

    padding:20px;

}

.card{

    width:100%;
    max-width:420px;

    background:white;

    padding:35px;

    border-radius:15px;

    box-shadow:0 10px 25px rgba(0,0,0,.25);

}

.card h2{

    text-align:center;

    margin-bottom:30px;

    color:#2563eb;

}

.input{

    width:100%;

    padding:14px;

    border:1px solid #ccc;

    border-radius:8px;

    margin-bottom:18px;

    font-size:15px;

}

.input:focus{

    border-color:#2563eb;

    outline:none;

}

button{

    width:100%;

    padding:14px;

    background:#2563eb;

    color:white;

    border:none;

    border-radius:8px;

    cursor:pointer;

    font-size:16px;

    font-weight:bold;

    transition:.3s;

}

button:hover{

    background:#1d4ed8;

}

.error{

    background:#fee2e2;

    color:#dc2626;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;

}

.register{

    text-align:center;

    margin-top:20px;

}

.register a{

    color:#2563eb;

    text-decoration:none;

    font-weight:bold;

}

@media(max-width:480px){

.card{

padding:25px;

}

}

</style>

</head>
<body>

<div class="card">

<h2>🚗 Login Parkir</h2>

<?php
if(isset($error)){
    echo "<div class='error'>$error</div>";
}
?>

<form method="POST">

<input
type="text"
name="username"
class="input"
placeholder="Username"
required>

<input
type="password"
name="password"
class="input"
placeholder="Password"
required>

<button name="login">
Masuk
</button>

</form>


<br><br>

</a>

</div>

</div>

</body>
</html>