<?php
session_start();

if($_SESSION['role'] != "admin_gudang"){
    header("Location: ../auth/login.php");
}
?>

<h1>Dashboard Admin Gudang</h1>

<a href="../auth/logout.php">Logout</a>