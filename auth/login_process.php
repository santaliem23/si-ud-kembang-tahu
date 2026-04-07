<?php
session_start();
include '../config/database.php';

$login    = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    header("Location: login.php?error=empty"); exit;
}

$stmt = mysqli_prepare($conn, "SELECT u.*, r.nama_role FROM user u LEFT JOIN role r ON u.id_role = r.id_role WHERE (u.username = ? OR u.email = ?) LIMIT 1");
mysqli_stmt_bind_param($stmt, "ss", $login, $login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login.php?error=wrong"); exit;
}

if (isset($user['is_active']) && $user['is_active'] == 0) {
    header("Location: login.php?error=inactive"); exit;
}

// Set session
$_SESSION['id_user']   = $user['id_user'];
$_SESSION['username']  = $user['username'];
$_SESSION['role']      = $user['id_role'];
$_SESSION['role_name'] = $user['nama_role'];

// Update last login
$upd = mysqli_prepare($conn, "UPDATE user SET last_login = NOW() WHERE id_user = ?");
mysqli_stmt_bind_param($upd, "i", $user['id_user']);
mysqli_stmt_execute($upd);

// Catat login history
$ip  = $_SERVER['REMOTE_ADDR'];
$ins = mysqli_prepare($conn, "INSERT INTO login_history (id_user, waktu_login, ip_address) VALUES (?, NOW(), ?)");
mysqli_stmt_bind_param($ins, "is", $user['id_user'], $ip);
mysqli_stmt_execute($ins);

// Redirect sesuai role
switch ($user['id_role']) {
    case 1:  header("Location: /si-ud-kembang-tahu/dashboard/owner.php");        break;
    case 2:  header("Location: /si-ud-kembang-tahu/dashboard/admin_produksi.php"); break;
    case 3:  header("Location: /si-ud-kembang-tahu/dashboard/admin_gudang.php");  break;
    default: header("Location: login.php?error=wrong");
}
exit;