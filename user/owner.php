<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole(ROLE_OWNER);

$success = $error = '';

// TOGGLE STATUS (aktif/nonaktif — tidak delete jika punya histori)
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id == $_SESSION['id_user']) { $error = "Tidak bisa menonaktifkan akun sendiri."; }
    else {
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_active FROM user WHERE id_user=$id"));
        $new = $row['is_active'] ? 0 : 1;
        mysqli_query($conn,"UPDATE user SET is_active=$new WHERE id_user=$id");
        $success = $new ? "Akun pengguna diaktifkan." : "Akun pengguna dinonaktifkan.";
    }
}

// HAPUS — hanya jika tidak punya histori transaksi
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id == $_SESSION['id_user']) { $error = "Tidak bisa menghapus akun sendiri."; }
    else {
        $cek_hist = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM login_history WHERE id_user=$id"))['c'];
        if ($cek_hist > 0) {
            $error = "Tidak bisa menghapus akun ini karena memiliki histori login. Gunakan Nonaktifkan.";
        } else {
            mysqli_query($conn,"DELETE FROM user WHERE id_user=$id");
            $success = "Akun berhasil dihapus.";
        }
    }
}

// SIMPAN (TAMBAH / EDIT)
if (isset($_POST['simpan'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role_id  = (int)$_POST['role'];
    $id_edit  = (int)($_POST['id_edit'] ?? 0);

    // Validasi duplikasi username
    $cek_u = "SELECT id_user FROM user WHERE username='$username'" . ($id_edit ? " AND id_user!=$id_edit" : "");
    $cek_e = "SELECT id_user FROM user WHERE email='$email'" . ($id_edit ? " AND id_user!=$id_edit" : "");
    if (mysqli_num_rows(mysqli_query($conn,$cek_u)) > 0) {
        $error = "Username <strong>$username</strong> sudah digunakan.";
    } elseif (mysqli_num_rows(mysqli_query($conn,$cek_e)) > 0) {
        $error = "Email <strong>$email</strong> sudah digunakan.";
    } elseif (!$id_edit && empty($_POST['password'])) {
        $error = "Password wajib diisi untuk akun baru.";
    } else {
        if ($id_edit) {
            if (!empty($_POST['password'])) {
                $pwd = password_hash($_POST['password'], PASSWORD_BCRYPT);
                mysqli_query($conn,"UPDATE user SET username='$username',email='$email',password='$pwd',id_role=$role_id WHERE id_user=$id_edit");
            } else {
                mysqli_query($conn,"UPDATE user SET username='$username',email='$email',id_role=$role_id WHERE id_user=$id_edit");
            }
            $success = "Data pengguna berhasil diperbarui.";
        } else {
            $pwd = password_hash($_POST['password'], PASSWORD_BCRYPT);
            mysqli_query($conn,"INSERT INTO user (username,email,password,id_role,is_active) VALUES ('$username','$email','$pwd',$role_id,1)");
            $success = "Akun pengguna <strong>$username</strong> berhasil dibuat.";
        }
    }
}

$edit_mode = false; $data_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $data_edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM user WHERE id_user=$id"));
}

$qRole = mysqli_query($conn,"SELECT * FROM role ORDER BY id_role");
$data  = mysqli_query($conn,"SELECT u.*, r.nama_role FROM user u LEFT JOIN role r ON u.id_role=r.id_role ORDER BY u.username ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manajemen Pengguna — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php include '../includes/sidebar_owner.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Manajemen Pengguna</h1>
        <p>Kelola akun, hak akses, dan status pengguna sistem</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div class="card">
        <div class="card-title"><?= $edit_mode ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></div>
        <div class="card-sub">Password yang sudah di-set tidak bisa dilihat kembali. Reset password jika lupa.</div>
        <form method="POST">
            <?php if($edit_mode): ?><input type="hidden" name="id_edit" value="<?= $data_edit['id_user'] ?>"><?php endif; ?>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Username <span style="color:#ef4444">*</span></label>
                    <input type="text" name="username" class="form-control" placeholder="username unik" value="<?= htmlspecialchars($data_edit['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@domain.com" value="<?= htmlspecialchars($data_edit['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Hak Akses (Role)</label>
                    <select name="role" class="form-control" required>
                        <option value="">-- Pilih Role --</option>
                        <?php mysqli_data_seek($qRole,0); while($r=mysqli_fetch_assoc($qRole)): ?>
                        <option value="<?= $r['id_role'] ?>" <?= ($data_edit['id_role'] ?? 0)==$r['id_role'] ? 'selected' : '' ?>><?= $r['nama_role'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Password <?= $edit_mode ? '<span style="color:#94a3b8;font-size:11px;">(Kosongkan jika tidak direset)</span>' : '<span style="color:#ef4444">*</span>' ?></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 karakter" <?= !$edit_mode ? 'required minlength="6"' : 'minlength="6"' ?>>
                </div>
            </div>
            <div class="btn-actions">
                <button type="submit" name="simpan" class="btn btn-primary"><?= $edit_mode ? 'Simpan Perubahan' : 'Buat Akun Pengguna' ?></button>
                <?php if($edit_mode): ?><a href="owner.php" class="btn btn-ghost">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Daftar Pengguna</div>
        <div class="alert alert-info" style="margin-bottom:16px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            <span>Pengguna yang memiliki histori login tidak dapat dihapus permanen. Gunakan <strong>Nonaktifkan</strong> untuk mencabut akses.</span>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($data)>0): while($r=mysqli_fetch_assoc($data)):
                $is_self = $r['id_user'] == $_SESSION['id_user'];
                $badge_color = match($r['id_role']) { 1=>'badge-success', 2=>'badge-blue', default=>'badge-warning' };
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:13px;">
                            <?= strtoupper(substr($r['username'],0,1)) ?>
                        </div>
                        <span class="td-bold"><?= htmlspecialchars($r['username']) ?> <?= $is_self ? '<span style="color:#94a3b8;font-size:11px;">(Anda)</span>' : '' ?></span>
                    </div>
                </td>
                <td class="td-muted"><?= htmlspecialchars($r['email']) ?></td>
                <td><span class="badge <?= $badge_color ?>"><?= $r['nama_role'] ?></span></td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
                <td class="td-muted"><?= $r['last_login'] ? date('d M Y H:i', strtotime($r['last_login'])) : 'Belum pernah' ?></td>
                <td>
                    <div class="td-actions">
                        <?php if(!$is_self): ?>
                        <a href="?edit=<?= $r['id_user'] ?>" class="action-edit">Edit</a>
                        <a href="?toggle=<?= $r['id_user'] ?>" class="<?= $r['is_active'] ? 'action-toggle-off' : 'action-toggle-on' ?>" onclick="return confirm('Ubah status akun ini?')">
                            <?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>
                        <a href="?hapus=<?= $r['id_user'] ?>" class="action-delete" onclick="return confirm('Hapus akun ini? Akun dengan histori login tidak dapat dihapus.')">Hapus</a>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:12px;">Akun Anda</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?><tr><td colspan="6" class="td-empty">Belum ada pengguna</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>