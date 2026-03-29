<?php
session_start();
include "../config/database.php";

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    header("Location: login.php?error=empty");
    exit;
}

// Ambil user
$query = "SELECT * FROM user WHERE username = ? OR email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $login, $login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {

    // ✅ VERIFY PASSWORD (HASH)
    if (password_verify($password, $user['password'])) {

        // ✅ SESSION
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['id_role'];

        // ✅ UPDATE LAST LOGIN
        $update = mysqli_prepare($conn, "UPDATE user SET last_login = NOW() WHERE id_user = ?");
        mysqli_stmt_bind_param($update, "i", $user['id_user']);
        mysqli_stmt_execute($update);

        // ✅ LOGIN HISTORY
        $ip = $_SERVER['REMOTE_ADDR'];
        $insert = mysqli_prepare($conn, "
            INSERT INTO login_history (id_user, waktu_login, ip_address)
            VALUES (?, NOW(), ?)
        ");
        mysqli_stmt_bind_param($insert, "is", $user['id_user'], $ip);
        mysqli_stmt_execute($insert);

        // ✅ ROLE REDIRECT
        if ($user['id_role'] == 1) {
            header("Location: ../dashboard/owner.php");
        } elseif ($user['id_role'] == 2) {
            header("Location: ../dashboard/produksi.php");
        } elseif ($user['id_role'] == 3) {
            header("Location: ../dashboard/gudang.php");
        }

        exit;

    } else {
        header("Location: login.php?error=wrong");
        exit;
    }

} else {
    header("Location: login.php?error=wrong");
    exit;
}