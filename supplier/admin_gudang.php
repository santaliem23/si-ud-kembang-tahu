<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);

$success = $error = '';

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_active FROM supplier WHERE id_supplier=$id"));
    $new = $row['is_active'] ? 0 : 1;
    mysqli_query($conn,"UPDATE supplier SET is_active=$new WHERE id_supplier=$id");
    $success = $new ? "Supplier diaktifkan." : "Supplier dinonaktifkan.";
}

if (isset($_POST['simpan'])) {
    $nama    = mysqli_real_escape_string($conn, trim($_POST['nama_supplier']));
    $alamat  = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $telp    = mysqli_real_escape_string($conn, trim($_POST['no_telp']));
    $id_edit = (int)($_POST['id_edit'] ?? 0);

    $cek = "SELECT id_supplier FROM supplier WHERE LOWER(nama_supplier)=LOWER('$nama')" . ($id_edit ? " AND id_supplier!=$id_edit" : "");
    if (mysqli_num_rows(mysqli_query($conn,$cek)) > 0) {
        $error = "Supplier <strong>$nama</strong> sudah terdaftar.";
    } else {
        if ($id_edit) {
            mysqli_query($conn,"UPDATE supplier SET nama_supplier='$nama',alamat='$alamat',no_telp='$telp' WHERE id_supplier=$id_edit");
            $success = "Data supplier berhasil diperbarui.";
        } else {
            mysqli_query($conn,"INSERT INTO supplier (nama_supplier,alamat,no_telp,is_active) VALUES ('$nama','$alamat','$telp',1)");
            $success = "Supplier <strong>$nama</strong> berhasil ditambahkan.";
        }
    }
}

$edit_mode = false; $data_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $data_edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM supplier WHERE id_supplier=$id"));
}

$data = mysqli_query($conn,"SELECT s.*, (SELECT COUNT(*) FROM pembelian pm WHERE pm.id_supplier=s.id_supplier) as total_beli FROM supplier s ORDER BY s.nama_supplier ASC");
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Supplier — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Master Supplier</h1>
        <p>Kelola data pemasok bahan baku. Riwayat pembelian per supplier tersedia di modul Laporan.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div class="card">
        <div class="card-title"><?= $edit_mode ? 'Edit Supplier' : 'Tambah Supplier Baru' ?></div>
        <form method="POST">
            <?php if($edit_mode): ?><input type="hidden" name="id_edit" value="<?= $data_edit['id_supplier'] ?>"><?php endif; ?>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Nama Supplier <span style="color:#ef4444">*</span></label>
                    <input type="text" name="nama_supplier" class="form-control" placeholder="Nama perusahaan / perorangan" value="<?= htmlspecialchars($data_edit['nama_supplier'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($data_edit['no_telp'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. ..."><?= htmlspecialchars($data_edit['alamat'] ?? '') ?></textarea>
            </div>
            <div class="btn-actions">
                <button type="submit" name="simpan" class="btn btn-primary"><?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Supplier' ?></button>
                <?php if($edit_mode): ?><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-ghost">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Daftar Supplier</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Nama Supplier</th><th>Telepon</th><th>Alamat</th><th>Total Pembelian</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($data)>0): while($r=mysqli_fetch_assoc($data)): ?>
            <tr>
                <td class="td-bold"><?= htmlspecialchars($r['nama_supplier']) ?></td>
                <td><?= htmlspecialchars($r['no_telp'] ?: '-') ?></td>
                <td style="max-width:200px;white-space:normal;font-size:12.5px;color:#475569;"><?= htmlspecialchars($r['alamat'] ?: '-') ?></td>
                <td class="td-muted"><?= $r['total_beli'] ?> transaksi</td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
                <td>
                    <div class="td-actions">
                        <a href="?edit=<?= $r['id_supplier'] ?>" class="action-edit">Edit</a>
                        <a href="?toggle=<?= $r['id_supplier'] ?>" class="<?= $r['is_active'] ? 'action-toggle-off' : 'action-toggle-on' ?>" onclick="return confirm('Ubah status supplier ini?')">
                            <?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" class="td-empty">Belum ada supplier terdaftar</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
