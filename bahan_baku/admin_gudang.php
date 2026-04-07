<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);

$success = $error = '';

// TOGGLE STATUS
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_active FROM bahan_baku WHERE id_bahan=$id"));
    $new = $row['is_active'] ? 0 : 1;
    mysqli_query($conn,"UPDATE bahan_baku SET is_active=$new WHERE id_bahan=$id");
    $success = $new ? "Bahan baku diaktifkan." : "Bahan baku dinonaktifkan.";
}

// SIMPAN (TAMBAH / EDIT)
if (isset($_POST['simpan'])) {
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_bahan']));
    $satuan    = mysqli_real_escape_string($conn, trim($_POST['satuan']));
    $harga     = (int)$_POST['harga_satuan'];
    $stok_min  = (int)$_POST['stok_minimum'];
    $stok_awal = (int)($_POST['stok_awal'] ?? 0);
    $id_edit   = (int)($_POST['id_edit'] ?? 0);

    // Cek duplikasi
    $cek = "SELECT id_bahan FROM bahan_baku WHERE LOWER(nama_bahan)=LOWER('$nama')" . ($id_edit ? " AND id_bahan!=$id_edit" : "");
    if (mysqli_num_rows(mysqli_query($conn, $cek)) > 0) {
        $error = "Bahan baku <strong>$nama</strong> sudah terdaftar, tidak boleh duplikasi.";
    } else {
        if ($id_edit) {
            mysqli_query($conn,"UPDATE bahan_baku SET nama_bahan='$nama',satuan='$satuan',harga_satuan=$harga,stok_minimum=$stok_min WHERE id_bahan=$id_edit");
            $success = "Data bahan baku berhasil diperbarui.";
        } else {
            // Auto kode
            $last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT MAX(id_bahan) mx FROM bahan_baku"));
            $kode = 'BB' . str_pad(($last['mx']+1), 4, '0', STR_PAD_LEFT);
            mysqli_query($conn,"INSERT INTO bahan_baku (kode_bahan,nama_bahan,satuan,stok,harga_satuan,stok_minimum,is_active) VALUES ('$kode','$nama','$satuan',$stok_awal,$harga,$stok_min,1)");
            $success = "Bahan baku <strong>$nama</strong> berhasil ditambahkan dengan stok awal $stok_awal.";
        }
    }
}

// MODE EDIT
$edit_mode = false; $data_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $data_edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM bahan_baku WHERE id_bahan=$id"));
}

// Filter status
$filter_status = $_GET['status'] ?? 'semua';
$where_status = ($filter_status === 'aktif') ? "WHERE is_active=1" : (($filter_status === 'nonaktif') ? "WHERE is_active=0" : "");
$data = mysqli_query($conn,"SELECT * FROM bahan_baku $where_status ORDER BY nama_bahan ASC");
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Bahan Baku — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Master Bahan Baku</h1>
        <p>Kelola data bahan baku produksi, satuan, harga, dan batas minimum stok</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div class="card">
        <div class="card-title"><?= $edit_mode ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru' ?></div>
        <div class="card-sub">Kode bahan akan di-generate otomatis oleh sistem</div>
        <form method="POST">
            <?php if($edit_mode): ?><input type="hidden" name="id_edit" value="<?= $data_edit['id_bahan'] ?>"><?php endif; ?>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Nama Bahan Baku <span style="color:#ef4444">*</span></label>
                    <input type="text" name="nama_bahan" class="form-control" placeholder="Contoh: Kedelai Impor" value="<?= htmlspecialchars($data_edit['nama_bahan'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan" class="form-control">
                        <?php foreach(['Kg','Gram','Liter','mL','Pcs','Karung','Botol'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($data_edit['satuan'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Harga Satuan (Rp)</label>
                    <input type="number" name="harga_satuan" class="form-control" min="0" placeholder="0" value="<?= $data_edit['harga_satuan'] ?? 0 ?>">
                    <small>Harga per satuan saat pembelian</small>
                </div>
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Batas Minimum Stok</label>
                    <input type="number" name="stok_minimum" class="form-control" min="0" placeholder="0" value="<?= $data_edit['stok_minimum'] ?? 0 ?>">
                    <small>Sistem akan notifikasi jika stok ≤ nilai ini</small>
                </div>
                <?php if(!$edit_mode): ?>
                <div class="form-group">
                    <label>Stok Awal</label>
                    <input type="number" name="stok_awal" class="form-control" min="0" placeholder="0" value="0">
                    <small>Input jika ada stok pembuka saat pertama kali sistem digunakan</small>
                </div>
                <?php endif; ?>
            </div>
            <div class="btn-actions">
                <button type="submit" name="simpan" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Bahan Baku' ?>
                </button>
                <?php if($edit_mode): ?><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-ghost">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <div class="card-title">Daftar Bahan Baku</div>
                <div class="card-sub"><?= mysqli_num_rows($data) ?> bahan ditemukan</div>
            </div>
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <select name="status" class="form-control" style="width:140px;" onchange="this.form.submit()">
                    <option value="semua" <?= $filter_status=='semua' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="aktif" <?= $filter_status=='aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $filter_status=='nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </form>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th>Stok</th><th>Min Stok</th><th>Harga Satuan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($data)>0): while($r=mysqli_fetch_assoc($data)):
                $kritis = $r['stok'] <= $r['stok_minimum']; ?>
            <tr class="<?= $r['stok']==0 ? 'stok-habis' : ($kritis ? 'stok-kritis' : '') ?>">
                <td class="td-muted"><?= htmlspecialchars($r['kode_bahan']) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td>
                <td><?= $r['satuan'] ?></td>
                <td style="font-weight:600;color:<?= $r['stok']==0 ? '#b91c1c' : ($kritis ? '#b45309' : '#166534') ?>;">
                    <?= number_format($r['stok'],0,',','.') ?>
                    <?php if($kritis): ?><span class="badge badge-minimum" style="margin-left:4px;">Min</span><?php endif; ?>
                </td>
                <td class="td-muted"><?= number_format($r['stok_minimum'],0,',','.') ?></td>
                <td>Rp <?= number_format($r['harga_satuan'],0,',','.') ?></td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
                <td>
                    <div class="td-actions">
                        <a href="?edit=<?= $r['id_bahan'] ?>" class="action-edit">Edit</a>
                        <a href="?toggle=<?= $r['id_bahan'] ?>" class="<?= $r['is_active'] ? 'action-toggle-off' : 'action-toggle-on' ?>" onclick="return confirm('<?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> bahan baku ini?')">
                            <?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="td-empty">Tidak ada data bahan baku</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
