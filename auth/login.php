<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login</title>

<link rel="stylesheet" href="../assets/css/login.css">

</head>
<body>

<div class="login-container">

    <div class="title-top">
        UD. Kulit Kembang Tahu & Tahu
    </div>

    <h2>Sign in to</h2>
    <div class="subtitle">access your dashboard</div>
<?php if(isset($_GET['error'])): ?>
<script>
    <?php if($_GET['error'] == 'empty'): ?>
        alert("Semua input wajib diisi!");
    <?php elseif($_GET['error'] == 'password'): ?>
        alert("username atau Password salah!");
    <?php elseif($_GET['error'] == 'user'): ?>
        alert("username atau Password salah!");
    <?php endif; ?>
</script>
<?php endif; ?>
    <form action="login_process.php" method="POST">

        <div class="form-group">
            <label>User name</label>
            <input type="text" name="login" placeholder="Enter your user name" required>
        </div>

        <div class="form-group password-box">
            <label>Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your Password" required>
            <span onclick="togglePassword()"></span>
        </div>

        <button type="submit">Login</button>

    </form>

</div>

<script>
function togglePassword() {
    let p = document.getElementById("password");
    p.type = (p.type === "password") ? "text" : "password";
}
</script>

</body>
</html>