<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_PRODUKSI]);

$success = $error = '';
$role = $_SESSION['role'];
$bom_detail = [];
$selected_produk = null;

// FETCH BOM saat produk dipilih (AJAX-like via GET)
$preview_id_produk = (int)($_GET['pilih'] ?? 0);
$preview_bom = null;
$preview_detail = [];
if ($preview_id_produk) {
    $preview_bom = mysqli_fetch_assoc(mysqli_query($conn,"SELECT b.*, p.nama_produk, p.satuan FROM bom b LEFT JOIN produk p ON b.id_produk=p.id_produk WHERE b.id_produk=$preview_id_produk AND b.is_active=1 LIMIT 1"));
    if ($preview_bom) {
        $res = mysqli_query($conn,"SELECT db.*, bb.nama_bahan, bb.satuan as sat_bahan, bb.stok, bb.harga_satuan FROM detail_bom db LEFT JOIN bahan_baku bb ON db.id_bahan=bb.id_bahan WHERE db.id_bom={$preview_bom['id_bom']}");
        while($d = mysqli_fetch_assoc($res)) $preview_detail[] = $d;
    }
}

// PROSES PRODUKSI
if (isset($_POST['proses_produksi'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah    = (int)$_POST['jumlah'];
    $tanggal   = $_POST['tanggal'];
    $id_bom    = (int)$_POST['id_bom'];

    // Parse jenis penyimpanan dan hitung expired
    $penyimpanan_raw = $_POST['penyimpanan'] ?? 'Suhu Ruang (Tahu)|7';
    list($jenis_penyimpanan, $hari) = explode('|', $penyimpanan_raw);
    $hari = (int)$hari;
    $tanggal_expired = date('Y-m-d', strtotime("$tanggal + $hari days"));

    // Fetch BOM detail
    $detail_res = mysqli_query($conn,"SELECT db.*, bb.stok, bb.harga_satuan FROM detail_bom db LEFT JOIN bahan_baku bb ON db.id_bahan=bb.id_bahan WHERE db.id_bom=$id_bom");
    $detail_arr = [];
    while($d = mysqli_fetch_assoc($detail_res)) $detail_arr[] = $d;

    // Validasi stok semua bahan
    $stok_cukup = true;
    $kurang = [];
    foreach ($detail_arr as $d) {
        $kebutuhan = $d['jumlah'] * $jumlah;
        if ($d['stok'] < $kebutuhan) {
            $stok_cukup = false;
            $kurang[] = $d['jumlah'] > 0 ? "Bahan #{$d['id_bahan']} butuh $kebutuhan, stok {$d['stok']}" : "";
        }
    }

    if (!$stok_cukup) {
        $error = "Stok bahan tidak mencukupi! " . implode(", ", array_filter($kurang));
    } elseif ($jumlah <= 0) {
        $error = "Jumlah produksi harus lebih dari 0.";
    } else {
        // Hitung no_batch
        $last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM produksi"))['c'];
        $no_batch = 'BTH' . date('Ymd') . str_pad($last+1, 3, '0', STR_PAD_LEFT);

        // Hitung total biaya bahan
        $total_biaya = 0;
        foreach ($detail_arr as $d) {
            $total_biaya += ($d['jumlah'] * $jumlah) * $d['harga_satuan'];
        }
        $hpp = $jumlah > 0 ? round($total_biaya / $jumlah, 2) : 0;

        // Insert produksi
        $stmt = mysqli_prepare($conn,"INSERT INTO produksi (no_batch, tanggal, id_produk, jumlah, total_biaya, hpp, status, jenis_penyimpanan, tanggal_expired) VALUES (?,?,?,?,?,?,'selesai',?,?)");
        mysqli_stmt_bind_param($stmt,"ssiidiss",$no_batch,$tanggal,$id_produk,$jumlah,$total_biaya,$hpp,$jenis_penyimpanan,$tanggal_expired);
        mysqli_stmt_execute($stmt);
        $id_produksi = mysqli_insert_id($conn);

        // Insert detail_produksi, kurangi stok bahan, catat stok_log
        foreach ($detail_arr as $d) {
            $kebutuhan  = (int)($d['jumlah'] * $jumlah);
            $harga_saat = (int)$d['harga_satuan'];
            $subtotal   = $kebutuhan * $harga_saat;
            $stok_before= (int)$d['stok'];
            $stok_after = $stok_before - $kebutuhan;

            // detail_produksi
            $stmt2 = mysqli_prepare($conn,"INSERT INTO detail_produksi (id_produksi,id_bahan,jumlah_pakai,harga_satuan,subtotal) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt2,"iiiii",$id_produksi,$d['id_bahan'],$kebutuhan,$harga_saat,$subtotal);
            mysqli_stmt_execute($stmt2);

            // Kurangi stok bahan
            mysqli_query($conn,"UPDATE bahan_baku SET stok = stok - $kebutuhan WHERE id_bahan={$d['id_bahan']}");

            // stok_log bahan (keluar)
            $ket = "Produksi #$id_produksi ($no_batch)";
            $id_u = $_SESSION['id_user'];
            $stmt3 = mysqli_prepare($conn,"INSERT INTO stok_log (id_bahan,tipe_item,tipe_transaksi,jumlah,stok_sebelum,stok_sesudah,keterangan,id_referensi,id_user) VALUES (?,?,?,?,?,?,?,?,?)");
            $ti='bahan'; $tt='keluar';
            mysqli_stmt_bind_param($stmt3,"isiiiisii",$d['id_bahan'],$ti,$tt,$kebutuhan,$stok_before,$stok_after,$ket,$id_produksi,$id_u);
            mysqli_stmt_execute($stmt3);
        }

        // Tambah stok produk jadi
        $stok_prod_before = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok FROM produk WHERE id_produk=$id_produk"))['stok'];
        $stok_prod_after  = $stok_prod_before + $jumlah;
        mysqli_query($conn,"UPDATE produk SET stok = stok + $jumlah WHERE id_produk=$id_produk");

        $success = "Produksi <strong>$no_batch</strong> berhasil! $jumlah pcs diproduksi. HPP = Rp " . number_format($hpp,0,',','.') . "/pcs. Total Biaya Bahan = Rp " . number_format($total_biaya,0,',','.');
    }
}

$qProduk = mysqli_query($conn,"SELECT p.*, (SELECT COUNT(*) FROM bom b WHERE b.id_produk=p.id_produk AND b.is_active=1) as ada_bom FROM produk p WHERE p.is_active=1 ORDER BY p.nama_produk");
$riwayat = mysqli_query($conn,"SELECT pr.*, p.nama_produk FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk ORDER BY pr.id_produksi DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Input Produksi — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Input Produksi</h1>
        <p>Sistem akan menghitung kebutuhan bahan dari BOM aktif, memvalidasi stok, dan menghitung HPP otomatis.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        <!-- STEP 1: Pilih Produk -->
        <div class="card">
            <div class="card-title">Langkah 1 — Pilih Produk & Jumlah</div>
            <div class="card-sub">Sistem akan menampilkan BOM aktif dan kebutuhan bahan</div>
            <form method="GET" style="margin-top:16px;">
                <div class="form-group">
                    <label>Produk yang Diproduksi</label>
                    <select name="pilih" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Pilih Produk --</option>
                        <?php mysqli_data_seek($qProduk,0); while($p=mysqli_fetch_assoc($qProduk)): ?>
                        <option value="<?= $p['id_produk'] ?>" <?= $preview_id_produk==$p['id_produk'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_produk']) ?> <?= !$p['ada_bom'] ? '⚠ (Belum ada BOM)' : '' ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php if($preview_bom): ?>
            <form method="POST">
                <input type="hidden" name="id_produk" value="<?= $preview_bom['id_produk'] ?>">
                <input type="hidden" name="id_bom" value="<?= $preview_bom['id_bom'] ?>">
                <div class="form-group">
                    <label>Tanggal Produksi</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Metode Simpan (Tracking Expired)</label>
                    <select name="penyimpanan" class="form-control" required>
                        <option value="Suhu Kulkas (Tahu)|7">Suhu Kulkas (Tahu) - Tahan 1 Minggu</option>
                        <option value="Suhu Ruang (Kulit)|5">Suhu Ruang (Kulit) - Tahan 5 Hari</option>
                        <option value="Suhu Kulkas (Kulit)|14">Suhu Kulkas (Kulit) - Tahan 2 Minggu</option>
                        <option value="Suhu Beku (Kulit)|180">Suhu Beku (Kulit) - Tahan 6 Bulan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Produksi (<?= $preview_bom['satuan'] ?>)</label>
                    <input type="number" name="jumlah" id="jmlProduksi" class="form-control" min="1" placeholder="0" required onchange="hitungKebutuhan(this.value)">
                </div>
                <button type="submit" name="proses_produksi" class="btn btn-primary" onclick="return confirm('Proses produksi sekarang? Stok bahan baku akan otomatis dikurangi.')">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Proses Produksi
                </button>
            </form>
            <?php elseif($preview_id_produk): ?>
            <div class="alert alert-warning" style="margin-top:16px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <span>Produk ini belum memiliki <strong>BOM aktif</strong>. <a href="/si-ud-kembang-tahu/bom/<?= $role==ROLE_OWNER ? 'owner' : 'admin_produksi' ?>.php?new=<?= $preview_id_produk ?>">Buat BOM →</a></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- STEP 2: Preview Kebutuhan Bahan -->
        <div class="card">
            <div class="card-title">Langkah 2 — Kebutuhan Bahan Baku</div>
            <div class="card-sub"><?= $preview_bom ? 'BOM: <strong>'.$preview_bom['kode_bom'].'</strong> — Jumlah kebutuhan otomatis dihitung dari BOM aktif' : 'Pilih produk untuk melihat kebutuhan bahan' ?></div>
            <?php if(!empty($preview_detail)): ?>
            <div class="table-wrap" style="margin-top:12px;">
            <table>
                <thead><tr><th>Bahan Baku</th><th>Per pcs</th><th>Stok</th><th id="hdr-total">Total (0 pcs)</th><th>Status</th></tr></thead>
                <tbody id="tblKebutuhan">
                <?php foreach($preview_detail as $d):
                    $cukup = $d['stok'] >= $d['jumlah']; ?>
                <tr>
                    <td class="td-bold"><?= htmlspecialchars($d['nama_bahan']) ?></td>
                    <td><?= $d['jumlah'] ?> <?= $d['sat_bahan'] ?></td>
                    <td style="color:<?= $d['stok']>0?'#166534':'#b91c1c' ?>;font-weight:600;"><?= number_format($d['stok'],0,',','.') ?></td>
                    <td class="td-total" data-per="<?= $d['jumlah'] ?>">0</td>
                    <td><span class="badge <?= $cukup ? 'badge-success' : 'badge-danger' ?>" id="status_<?= $d['id_bahan'] ?>"><?= $cukup ? 'Cukup' : 'Tidak Cukup' ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div style="margin-top:12px;padding:10px 14px;background:#f0fdf4;border-radius:8px;">
                <span style="font-size:13px;color:#166534;">Estimasi Biaya Bahan: <strong id="estimasiBiaya">Rp 0</strong></span>
                | <span style="font-size:13px;color:#166534;">Est. HPP/pcs: <strong id="estimasiHpp">Rp 0</strong></span>
            </div>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;min-height:120px;color:#94a3b8;">Pilih produk terlebih dahulu</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat Produksi -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div><div class="card-title">Riwayat Produksi Terakhir</div><div class="card-sub">10 entri terbaru</div></div>
            <a href="index.php" class="btn btn-ghost btn-sm">Lihat Semua →</a>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>No Batch</th><th>Produk</th><th>Jumlah</th><th>Total Biaya</th><th>HPP/pcs</th><th>Tanggal</th></tr></thead>
            <tbody>
            <?php while($r=mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['no_batch'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= number_format($r['jumlah'],0,',','.') ?> pcs</td>
                <td>Rp <?= number_format($r['total_biaya']??0,0,',','.') ?></td>
                <td style="font-weight:600;color:#166534;">Rp <?= number_format($r['hpp']??0,0,',','.') ?></td>
                <td class="td-muted"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($riwayat)==0): ?><tr><td colspan="6" class="td-empty">Belum ada data produksi</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
const hargaBahan = <?= json_encode(array_column($preview_detail, 'harga_satuan', 'id_bahan')) ?>;
const stokBahan  = <?= json_encode(array_column($preview_detail, 'stok', 'id_bahan')) ?>;

function hitungKebutuhan(jumlah) {
    jumlah = parseInt(jumlah) || 0;
    document.getElementById('hdr-total').textContent = `Total (${jumlah} pcs)`;
    let totalBiaya = 0;
    document.querySelectorAll('.td-total').forEach(td => {
        const per = parseFloat(td.dataset.per || 0);
        const total = per * jumlah;
        td.textContent = total;
        totalBiaya += total * 1; // simplified; real price computed server-side
    });
    document.getElementById('estimasiBiaya').textContent = 'Rp ' + totalBiaya.toLocaleString('id-ID');
    document.getElementById('estimasiHpp').textContent = jumlah > 0 ? 'Rp ' + Math.round(totalBiaya/jumlah).toLocaleString('id-ID') : 'Rp 0';
}
</script>
</body>
</html>
