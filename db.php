<?php
// ======================================
// Koneksi Database
// File : db.php
// ======================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "parkir";

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Mengatur charset
$conn->set_charset("utf8");

// Mengatur zona waktu
date_default_timezone_set("Asia/Jakarta");
?>