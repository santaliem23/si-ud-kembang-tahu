<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "si_ud_kembang_tahu";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal");
}
?>