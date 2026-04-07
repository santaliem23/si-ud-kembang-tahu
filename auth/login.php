<?php
session_start();
if (isset($_SESSION['id_user'])) {
    $role = $_SESSION['role'];
    if ($role == 1) header("Location: /si-ud-kembang-tahu/dashboard/owner.php");
    elseif ($role == 2) header("Location: /si-ud-kembang-tahu/dashboard/admin_produksi.php");
    else header("Location: /si-ud-kembang-tahu/dashboard/admin_gudang.php");
    exit;
}
$error = $_GET['error'] ?? '';
$msgs = [
    'empty'   => 'Username dan password wajib diisi.',
    'wrong'   => 'Username atau password salah.',
    'inactive'=> 'Akun Anda dinonaktifkan. Hubungi Owner.',
];
$errorMsg = $msgs[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    background: #f1f5f9;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.login-box {
    width: 100%; max-width: 400px;
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}
.login-box h2 { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-align: center; }
.login-box p  { font-size: 13.5px; color: #64748b; margin-bottom: 30px; text-align: center; }

.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
.form-group input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px; font-size: 14px;
    font-family: inherit; color: #1e293b;
    transition: all 0.2s;
    background: #f8fafc;
}
.form-group input:focus { outline: none; border-color: #16a34a; background: white; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }

.btn-login {
    width: 100%; padding: 12px;
    background: #16a34a; color: white;
    border: none; border-radius: 8px;
    font-size: 14.5px; font-weight: 600;
    cursor: pointer; font-family: inherit;
    transition: background 0.2s;
    margin-top: 8px;
}
.btn-login:hover { background: #15803d; }
.alert-error {
    background: #fef2f2; color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 11px 14px; border-radius: 8px;
    font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
</style>
</head>
<body>
<div class="login-box">
    <h2>SI UD Kembang Tahu</h2>
    <p>Silakan masuk ke akun Anda</p>

    <?php if ($errorMsg): ?>
    <div class="alert-error">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php endif; ?>

    <form action="/si-ud-kembang-tahu/auth/login_process.php" method="POST">
        <div class="form-group">
            <label for="login">Username</label>
            <input type="text" id="login" name="login" placeholder="Masukkan username" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-login">Masuk</button>
    </form>
</div>
</body>
</html>