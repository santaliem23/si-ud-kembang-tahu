<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_PRODUKSI]);

$success = $error = '';

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_active FROM produk WHERE id_produk=$id"));
    $new = $row['is_active'] ? 0 : 1;
    mysqli_query($conn,"UPDATE produk SET is_active=$new WHERE id_produk=$id");
    $success = $new ? "Produk diaktifkan." : "Produk dinonaktifkan.";
}

if (isset($_POST['simpan'])) {
    $nama   = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $satuan = mysqli_real_escape_string($conn, trim($_POST['satuan']));
    $harga  = (int)$_POST['harga'];
    $id_edit= (int)($_POST['id_edit'] ?? 0);

    $cek = "SELECT id_produk FROM produk WHERE LOWER(nama_produk)=LOWER('$nama')" . ($id_edit ? " AND id_produk!=$id_edit" : "");
    if (mysqli_num_rows(mysqli_query($conn,$cek)) > 0) {
        $error = "Produk <strong>$nama</strong> sudah terdaftar.";
    } else {
        if ($id_edit) {
            mysqli_query($conn,"UPDATE produk SET nama_produk='$nama',satuan='$satuan',harga=$harga WHERE id_produk=$id_edit");
            $success = "Data produk berhasil diperbarui.";
        } else {
            $last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT MAX(id_produk) mx FROM produk"));
            $kode = 'PRD' . str_pad(($last['mx']+1), 4, '0', STR_PAD_LEFT);
            mysqli_query($conn,"INSERT INTO produk (kode_produk,nama_produk,satuan,harga,stok,is_active) VALUES ('$kode','$nama','$satuan',$harga,0,1)");
            $success = "Produk <strong>$nama</strong> berhasil ditambahkan.";
        }
    }
}

$edit_mode = false; $data_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $data_edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM produk WHERE id_produk=$id"));
}

$filter_status = $_GET['status'] ?? 'semua';
$where = ($filter_status==='aktif') ? "WHERE is_active=1" : (($filter_status==='nonaktif') ? "WHERE is_active=0" : "");
$data = mysqli_query($conn,"SELECT * FROM produk $where ORDER BY nama_produk ASC");
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Produk — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Master Produk Jadi</h1>
        <p>Kelola data produk yang diproduksi. Stok produk dikelola otomatis oleh modul produksi.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div class="card">
        <div class="card-title"><?= $edit_mode ? 'Edit Produk' : 'Tambah Produk Baru' ?></div>
        <div class="card-sub">Kode produk akan di-generate otomatis</div>
        <form method="POST">
            <?php if($edit_mode): ?><input type="hidden" name="id_edit" value="<?= $data_edit['id_produk'] ?>"><?php endif; ?>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Nama Produk <span style="color:#ef4444">*</span></label>
                    <input type="text" name="nama_produk" class="form-control" placeholder="Contoh: Kulit Kembang Tahu" value="<?= htmlspecialchars($data_edit['nama_produk'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan" class="form-control">
                        <?php foreach(['Pcs','Kg','Gram','Liter','Bungkus','Loyang'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($data_edit['satuan'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Harga Jual (Rp) <span style="color:#94a3b8; font-size:11px;">(Opsional)</span></label>
                    <input type="number" name="harga" class="form-control" min="0" placeholder="0" value="<?= $data_edit['harga'] ?? 0 ?>">
                </div>
            </div>
            <div class="btn-actions">
                <button type="submit" name="simpan" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Produk' ?>
                </button>
                <?php if($edit_mode): ?><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-ghost">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <div class="card-title">Daftar Produk</div>
                <div class="card-sub">Klik BOM untuk melihat/kelola komposisi bahan</div>
            </div>
            <form method="GET"><select name="status" class="form-control" style="width:140px;" onchange="this.form.submit()">
                <option value="semua" <?= $filter_status=='semua'?'selected':'' ?>>Semua</option>
                <option value="aktif" <?= $filter_status=='aktif'?'selected':'' ?>>Aktif</option>
                <option value="nonaktif" <?= $filter_status=='nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select></form>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Nama Produk</th><th>Satuan</th><th>Stok</th><th>Harga</th><th>BOM</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($data)>0): while($r=mysqli_fetch_assoc($data)):
                $bom_aktif = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bom WHERE id_produk={$r['id_produk']} AND is_active=1"))['c'];
            ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['kode_produk'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= $r['satuan'] ?></td>
                <td style="font-weight:600;"><?= number_format($r['stok'],0,',','.') ?></td>
                <td><?= $r['harga'] ? 'Rp '.number_format($r['harga'],0,',','.') : '-' ?></td>
                <td>
                    <?php if($bom_aktif): ?>
                        <span class="badge badge-success">Ada BOM</span>
                    <?php else: ?>
                        <a href="/si-ud-kembang-tahu/bom/<?= $role==ROLE_OWNER ? 'owner' : 'admin_produksi' ?>.php?new=<?= $r['id_produk'] ?>" class="badge badge-warning" style="text-decoration:none;">+ Buat BOM</a>
                    <?php endif; ?>
                </td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
                <td>
                    <div class="td-actions">
                        <a href="?edit=<?= $r['id_produk'] ?>" class="action-edit">Edit</a>
                        <a href="?toggle=<?= $r['id_produk'] ?>" class="<?= $r['is_active'] ? 'action-toggle-off' : 'action-toggle-on' ?>" onclick="return confirm('Ubah status produk ini?')">
                            <?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="td-empty">Belum ada produk terdaftar</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
