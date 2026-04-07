<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_PRODUKSI]);

$success = $error = '';
$role = $_SESSION['role'];

// TOGGLE BOM STATUS
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $bom = mysqli_fetch_assoc(mysqli_query($conn,"SELECT *, (SELECT id_produk FROM bom WHERE id_bom=$id) as id_p FROM bom WHERE id_bom=$id"));
    if (!$bom['is_active']) {
        // Sebelum aktifkan, nonaktifkan BOM lain untuk produk yang sama
        $id_produk = $bom['id_produk'];
        mysqli_query($conn,"UPDATE bom SET is_active=0 WHERE id_produk=$id_produk");
    }
    $new = $bom['is_active'] ? 0 : 1;
    mysqli_query($conn,"UPDATE bom SET is_active=$new WHERE id_bom=$id");
    $success = $new ? "BOM diaktifkan." : "BOM dinonaktifkan.";
}

// HAPUS BOM
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah BOM sudah pernah dipakai di produksi
    $cek = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM produksi WHERE id_produk=(SELECT id_produk FROM bom WHERE id_bom=$id)"))['c'];
    if ($cek > 0) { $error = "BOM tidak dapat dihapus karena sudah digunakan dalam proses produksi."; }
    else {
        mysqli_query($conn,"DELETE FROM detail_bom WHERE id_bom=$id");
        mysqli_query($conn,"DELETE FROM bom WHERE id_bom=$id");
        $success = "BOM berhasil dihapus.";
    }
}

// HAPUS DETAIL BOM
if (isset($_GET['hapus_detail'])) {
    $id = (int)$_GET['hapus_detail'];
    mysqli_query($conn,"DELETE FROM detail_bom WHERE id_detail_bom=$id");
    $success = "Item BOM berhasil dihapus.";
    header("Location: " . $_SERVER['PHP_SELF'] . "?view=" . ($_GET['bom'] ?? ''));
    exit;
}

// SIMPAN BOM BARU
if (isset($_POST['simpan_bom'])) {
    $id_produk = (int)$_POST['id_produk'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    $id_edit   = (int)($_POST['id_edit'] ?? 0);

    if ($id_edit) {
        mysqli_query($conn,"UPDATE bom SET id_produk=$id_produk, tanggal_mulai='$tgl_mulai' WHERE id_bom=$id_edit");
        $success = "BOM berhasil diperbarui.";
    } else {
        // Auto kode
        $last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT MAX(id_bom) mx FROM bom"));
        $kode = 'BOM' . str_pad(($last['mx']+1), 4, '0', STR_PAD_LEFT);
        mysqli_query($conn,"INSERT INTO bom (kode_bom, id_produk, tanggal_mulai, is_active) VALUES ('$kode',$id_produk,'$tgl_mulai',0)");
        $success = "BOM $kode berhasil dibuat. Tambahkan bahan baku di bawah.";
    }
}

// TAMBAH DETAIL BOM (bahan ke dalam BOM)
if (isset($_POST['tambah_detail'])) {
    $id_bom   = (int)$_POST['id_bom'];
    $id_bahan = (int)$_POST['id_bahan'];
    $jumlah   = (float)$_POST['jumlah'];

    // Cek sudah ada?
    $cek = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM detail_bom WHERE id_bom=$id_bom AND id_bahan=$id_bahan"))['c'];
    if ($cek > 0) {
        mysqli_query($conn,"UPDATE detail_bom SET jumlah=$jumlah WHERE id_bom=$id_bom AND id_bahan=$id_bahan");
        $success = "Jumlah bahan diperbarui.";
    } else {
        mysqli_query($conn,"INSERT INTO detail_bom (id_bom,id_bahan,jumlah) VALUES ($id_bom,$id_bahan,$jumlah)");
        $success = "Bahan berhasil ditambahkan ke BOM.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?view=$id_bom");
    exit;
}

// VIEW MODE (lihat detail BOM)
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$view_bom = $view_id ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT b.*, p.nama_produk FROM bom b LEFT JOIN produk p ON b.id_produk=p.id_produk WHERE b.id_bom=$view_id")) : null;
$detail_bom = $view_id ? mysqli_query($conn,"SELECT db.*, bb.nama_bahan, bb.satuan FROM detail_bom db LEFT JOIN bahan_baku bb ON db.id_bahan=bb.id_bahan WHERE db.id_bom=$view_id ORDER BY bb.nama_bahan") : null;

// EDIT MODE
$edit_mode = false; $data_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $data_edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM bom WHERE id_bom=$id"));
}

$qProduk = mysqli_query($conn,"SELECT * FROM produk WHERE is_active=1 ORDER BY nama_produk");
$qBahan  = mysqli_query($conn,"SELECT * FROM bahan_baku WHERE is_active=1 ORDER BY nama_bahan");
$daftar_bom = mysqli_query($conn,"SELECT b.*, p.nama_produk FROM bom b LEFT JOIN produk p ON b.id_produk=p.id_produk ORDER BY b.id_bom DESC");

// Pre-select produk jika dari link + BOM
$new_for = isset($_GET['new']) ? (int)$_GET['new'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bill of Material (BOM) — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Bill of Material (BOM)</h1>
        <p>Resep komposisi bahan baku yang dibutuhkan untuk memproduksi satu satuan produk</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- Form Tambah/Edit BOM -->
        <div class="card">
            <div class="card-title"><?= $edit_mode ? 'Edit BOM' : 'Buat BOM Baru' ?></div>
            <div class="card-sub">Setelah BOM dibuat, tambahkan bahan baku yang dibutuhkan, lalu aktifkan BOM tersebut.</div>
            <form method="POST" style="margin-top:16px;">
                <?php if($edit_mode): ?><input type="hidden" name="id_edit" value="<?= $data_edit['id_bom'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label>Produk</label>
                    <select name="id_produk" class="form-control" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php mysqli_data_seek($qProduk,0); while($p=mysqli_fetch_assoc($qProduk)): ?>
                        <option value="<?= $p['id_produk'] ?>" <?= (($data_edit['id_produk'] ?? $new_for)==$p['id_produk']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_produk']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Berlaku</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?= $data_edit['tanggal_mulai'] ?? date('Y-m-d') ?>" required>
                    <small>BOM ini berlaku sejak tanggal ini. Hanya 1 BOM aktif per produk.</small>
                </div>
                <div class="btn-actions">
                    <button type="submit" name="simpan_bom" class="btn btn-primary"><?= $edit_mode ? 'Simpan Perubahan' : 'Buat BOM' ?></button>
                    <?php if($edit_mode): ?><a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-ghost">Batal</a><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Panel Detail BOM -->
        <?php if($view_bom): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
                <div>
                    <div class="card-title">Detail BOM: <?= htmlspecialchars($view_bom['kode_bom']) ?></div>
                    <div class="card-sub">Produk: <strong><?= htmlspecialchars($view_bom['nama_produk']) ?></strong> | <?= $view_bom['is_active'] ? '<span style="color:#16a34a;font-weight:600;">Aktif</span>' : '<span style="color:#94a3b8;">Nonaktif</span>' ?></div>
                </div>
                <a href="?view=<?= $view_id ?>&toggle=<?= $view_id ?>" class="btn <?= $view_bom['is_active'] ? 'btn-ghost' : 'btn-primary' ?> btn-sm" onclick="return confirm('<?= $view_bom['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> BOM ini?')">
                    <?= $view_bom['is_active'] ? 'Nonaktifkan' : 'Aktifkan BOM' ?>
                </a>
            </div>
            <div class="table-wrap" style="max-height:180px;overflow-y:auto;">
            <table>
                <thead><tr><th>Nama Bahan</th><th>Jumlah/pcs</th><th>Satuan</th><th></th></tr></thead>
                <tbody>
                <?php if($detail_bom && mysqli_num_rows($detail_bom)>0): while($d=mysqli_fetch_assoc($detail_bom)): ?>
                <tr>
                    <td class="td-bold"><?= htmlspecialchars($d['nama_bahan']) ?></td>
                    <td><?= $d['jumlah'] ?></td>
                    <td class="td-muted"><?= $d['satuan'] ?></td>
                    <td><a href="?view=<?= $view_id ?>&hapus_detail=<?= $d['id_detail_bom'] ?>&bom=<?= $view_id ?>" class="action-delete" onclick="return confirm('Hapus bahan ini dari BOM?')" style="font-size:12px;">Hapus</a></td>
                </tr>
                <?php endwhile; else: ?><tr><td colspan="4" class="td-empty">Belum ada bahan ditambahkan</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="divider"></div>
            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:10px;">+ Tambah Bahan ke BOM</div>
            <form method="POST">
                <input type="hidden" name="id_bom" value="<?= $view_id ?>">
                <div class="form-grid-3">
                    <div class="form-group" style="grid-column:span 2;">
                        <select name="id_bahan" class="form-control" required>
                            <option value="">-- Pilih Bahan --</option>
                            <?php mysqli_data_seek($qBahan,0); while($b=mysqli_fetch_assoc($qBahan)): ?>
                            <option value="<?= $b['id_bahan'] ?>"><?= htmlspecialchars($b['nama_bahan']) ?> (<?= $b['satuan'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="jumlah" class="form-control" placeholder="Qty" step="0.01" min="0.01" required>
                    </div>
                </div>
                <button type="submit" name="tambah_detail" class="btn btn-blue btn-sm">+ Tambah Bahan</button>
            </form>
        </div>
        <?php else: ?>
        <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:200px;">
            <p style="color:#94a3b8;text-align:center;">Pilih BOM dari daftar di bawah untuk melihat & mengedit detailnya</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Daftar Semua BOM -->
    <div class="card">
        <div class="card-title">Daftar Bill of Material</div>
        <div class="card-sub">Hanya 1 BOM boleh aktif per produk pada satu waktu</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Kode BOM</th><th>Produk</th><th>Tgl Mulai</th><th>Jml Bahan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($daftar_bom)>0): while($r=mysqli_fetch_assoc($daftar_bom)):
                $jml_d = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM detail_bom WHERE id_bom={$r['id_bom']}"))['c'];
            ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['kode_bom'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk'] ?? '-') ?></td>
                <td class="td-muted"><?= $r['tanggal_mulai'] ?? '-' ?></td>
                <td><?= $jml_d ?> bahan</td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
                <td>
                    <div class="td-actions">
                        <a href="?view=<?= $r['id_bom'] ?>" class="action-edit">Detail</a>
                        <a href="?toggle=<?= $r['id_bom'] ?>" onclick="return confirm('Ubah status BOM ini? BOM lain untuk produk yang sama akan dinonaktifkan.')">
                            <?= $r['is_active'] ? '<span class="action-toggle-off">Nonaktifkan</span>' : '<span class="action-toggle-on">Aktifkan</span>' ?>
                        </a>
                        <a href="?hapus=<?= $r['id_bom'] ?>" class="action-delete" onclick="return confirm('Hapus BOM ini?')">Hapus</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?><tr><td colspan="6" class="td-empty">Belum ada BOM dibuat</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
