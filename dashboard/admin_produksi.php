<?php
session_start();

if($_SESSION['role'] != "admin_produksi"){
    header("Location: ../auth/login.php");
}
?>

<h1>Dashboard Admin Produksi</h1>

<a href="../auth/logout.php">Logout</a>