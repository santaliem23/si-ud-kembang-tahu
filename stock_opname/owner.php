<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);

$success = $error = '';
$role = $_SESSION['role'];

// PROSES SIMPAN STOCK OPNAME
if (isset($_POST['simpan_opname']) && $role == ROLE_GUDANG) {
    if (empty($_POST['item_opname'])) {
        $error = "Silakan pilih item yang ingin di-opname.";
    } else {
        $item_raw    = $_POST['item_opname']; // format: bahan_1 atau produk_2
        $stok_fisik  = (int)$_POST['stok_fisik'];
        $alasan      = mysqli_real_escape_string($conn, trim($_POST['alasan']));
        
        list($tipe, $id_item) = explode('_', $item_raw);
        $id_item = (int)$id_item;

        // Ambil stok sistem saat ini
        if ($tipe == 'bahan') {
            $item = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok, nama_bahan as nama FROM bahan_baku WHERE id_bahan=$id_item"));
        } else {
            $item = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok, nama_produk as nama FROM produk WHERE id_produk=$id_item"));
        }
        
        $stok_sistem = (int)$item['stok'];
        $selisih     = $stok_fisik - $stok_sistem;

        if ($selisih != 0 && empty($alasan)) {
            $error = "Ada selisih stok (<strong>$selisih</strong>). Wajib isi alasan penyesuaian!";
        } else {
            $id_user = $_SESSION['id_user'];
            
            // Insert ke stock_opname
            $id_bahan_val = ($tipe == 'bahan') ? $id_item : "NULL";
            $id_produk_val = ($tipe == 'produk') ? $id_item : "NULL";
            
            mysqli_query($conn,"INSERT INTO stock_opname (tipe_item, id_bahan, id_produk, stok_sistem, stok_fisik, selisih, alasan, tanggal, id_user) 
                                VALUES ('$tipe', $id_bahan_val, $id_produk_val, $stok_sistem, $stok_fisik, $selisih, '$alasan', NOW(), $id_user)");
            $id_opname = mysqli_insert_id($conn);

            // Update stok dan catat log
            if ($tipe == 'bahan') {
                mysqli_query($conn,"UPDATE bahan_baku SET stok=$stok_fisik WHERE id_bahan=$id_item");
                $ket = "Stock Opname Bahan #$id_opname" . ($alasan ? " — $alasan" : "");
                mysqli_query($conn,"INSERT INTO stok_log (id_bahan,tipe_item,tipe_transaksi,jumlah,stok_sebelum,stok_sesudah,keterangan,id_referensi,id_user) 
                                    VALUES ($id_item, 'bahan', 'opname', ABS($selisih), $stok_sistem, $stok_fisik, '$ket', $id_opname, $id_user)");
            } else {
                mysqli_query($conn,"UPDATE produk SET stok=$stok_fisik WHERE id_produk=$id_item");
                $ket = "Stock Opname Produk #$id_opname" . ($alasan ? " — $alasan" : "");
                mysqli_query($conn,"INSERT INTO stok_log (id_produk,tipe_item,tipe_transaksi,jumlah,stok_sebelum,stok_sesudah,keterangan,id_referensi,id_user) 
                                    VALUES ($id_item, 'produk', 'opname', ABS($selisih), $stok_sistem, $stok_fisik, '$ket', $id_opname, $id_user)");
            }

            $tanda = $selisih > 0 ? "+$selisih" : ($selisih < 0 ? "$selisih" : "0 (tidak ada selisih)");
            $success = "Stock opname <strong>{$item['nama']}</strong> berhasil dicatat. Selisih: $tanda. Stok diperbarui ke $stok_fisik.";
        }
    }
}

$qBahan  = mysqli_query($conn,"SELECT id_bahan, nama_bahan, satuan, stok FROM bahan_baku WHERE is_active=1 ORDER BY nama_bahan");
$qProduk = mysqli_query($conn,"SELECT id_produk, nama_produk, satuan, stok FROM produk WHERE is_active=1 ORDER BY nama_produk");

// Filter riwayat
$filter_dari   = $_GET['dari'] ?? date('Y-m-01');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');
$riwayat = mysqli_query($conn,
    "SELECT so.*, 
            COALESCE(bb.nama_bahan, p.nama_produk) as nama_item, 
            COALESCE(bb.satuan, p.satuan) as satuan, 
            u.username
     FROM stock_opname so
     LEFT JOIN bahan_baku bb ON so.id_bahan=bb.id_bahan
     LEFT JOIN produk p ON so.id_produk=p.id_produk
     LEFT JOIN user u ON so.id_user=u.id_user
     WHERE DATE(so.tanggal) BETWEEN '$filter_dari' AND '$filter_sampai'
     ORDER BY so.id_opname DESC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Stock Opname — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Stock Opname & Audit Persediaan</h1>
        <p>Penghitungan stok fisik vs sistem untuk Bahan Baku maupun Produk Jadi.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        <!-- Form Opname -->
        <?php if($role == ROLE_GUDANG): ?>
        <div class="card">
            <div class="card-title">Input Stock Opname</div>
            <div class="card-sub">Masukkan stok fisik hasil penghitungan di lapangan</div>
            <form method="POST" style="margin-top:16px;" id="formOpname">
                <div class="form-group">
                    <label>Item yang Diperiksa</label>
                    <select name="item_opname" id="selBahan" class="form-control" required onchange="isiStokSistem()">
                        <option value="">-- Pilih Bahan / Produk --</option>
                        <optgroup label="Bahan Baku">
                            <?php while($b=mysqli_fetch_assoc($qBahan)): ?>
                            <option value="bahan_<?= $b['id_bahan'] ?>" data-stok="<?= $b['stok'] ?>">
                                [BAHAN] <?= htmlspecialchars($b['nama_bahan']) ?> — Stok: <?= $b['stok'] ?> <?= $b['satuan'] ?>
                            </option>
                            <?php endwhile; ?>
                        </optgroup>
                        <optgroup label="Produk Jadi">
                            <?php while($p=mysqli_fetch_assoc($qProduk)): ?>
                            <option value="produk_<?= $p['id_produk'] ?>" data-stok="<?= $p['stok'] ?>">
                                [PRODUK] <?= htmlspecialchars($p['nama_produk']) ?> — Stok: <?= $p['stok'] ?> <?= $p['satuan'] ?>
                            </option>
                            <?php endwhile; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Stok Sistem (otomatis)</label>
                        <input type="text" id="stok_sistem_disp" class="form-control" readonly style="background:#f8fafc;color:#64748b;" placeholder="Pilih item dulu">
                    </div>
                    <div class="form-group">
                        <label>Stok Fisik (hasil hitung) <span style="color:#ef4444">*</span></label>
                        <input type="number" name="stok_fisik" id="stokFisik" class="form-control" min="0" placeholder="0" required onchange="hitungSelisih()">
                    </div>
                </div>
                <div class="form-group">
                    <label>Selisih</label>
                    <div id="selisihDisp" style="padding:10px 12px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;color:#64748b;font-size:14px;">—</div>
                </div>
                <div class="form-group">
                    <label>Alasan Penyesuaian <span id="alasan-label" style="color:#ef4444;font-size:11px;">(wajib jika ada selisih)</span></label>
                    <textarea name="alasan" class="form-control" id="txtAlasan" rows="3" placeholder="Contoh: Stok rusak/kadaluarsa, kehilangan, kesalahan hitung..."></textarea>
                </div>
                <button type="submit" name="simpan_opname" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/></svg>
                    Simpan & Perbarui Stok
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="card alert alert-info" style="align-items:flex-start;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            <span>Sebagai Owner, Anda hanya dapat melihat riwayat stock opname. Untuk melakukan opname, login sebagai <strong>Admin Gudang</strong>.</span>
        </div>
        <?php endif; ?>

        <!-- Statistik Mini -->
        <div class="card">
            <div class="card-title">Ringkasan Opname Bulan Ini</div>
            <?php
            $bulan_ini = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c, SUM(ABS(selisih)) ts FROM stock_opname WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"));
            $selisih_pos = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_opname WHERE selisih>0 AND MONTH(tanggal)=MONTH(CURDATE())"))['c'];
            $selisih_neg = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_opname WHERE selisih<0 AND MONTH(tanggal)=MONTH(CURDATE())"))['c'];
            ?>
            <div class="stats-grid" style="margin-top:16px;grid-template-columns:1fr 1fr;">
                <div class="stat-card"><div class="stat-icon blue"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/></svg></div><div class="stat-info"><p>Total Opname</p><h3><?= $bulan_ini['c'] ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon amber"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div><div class="stat-info"><p>Total Selisih</p><h3><?= $bulan_ini['ts'] ?? 0 ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon green"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="17 11 12 6 7 11"/></svg></div><div class="stat-info"><p>Stok Berlebih</p><h3><?= $selisih_pos ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon red"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="17 13 12 18 7 13"/></svg></div><div class="stat-info"><p>Stok Kurang</p><h3><?= $selisih_neg ?></h3></div></div>
            </div>
        </div>
    </div>

    <!-- Riwayat Stock Opname -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="card-title">Histori Stock Opname (Audit Trail Permanen)</div>
                <div class="card-sub">Data tidak dapat dihapus. Tersimpan selamanya untuk keperluan audit.</div>
            </div>
            <form method="GET" class="filter-bar" style="margin:0;">
                <div class="form-group"><label style="font-size:12px;">Dari</label><input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>"></div>
                <div class="form-group"><label style="font-size:12px;">Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>"></div>
                <div class="form-group" style="align-self:flex-end;"><button type="submit" class="btn btn-ghost">Filter</button></div>
            </form>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Tgl & Waktu</th><th>Item (Bahan/Produk)</th><th>Tipe</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th><th>Alasan</th><th>Admin</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($riwayat)>0): while($r=mysqli_fetch_assoc($riwayat)):
                $sl = (int)$r['selisih'];
                $sl_color = $sl > 0 ? '#166534' : ($sl < 0 ? '#b91c1c' : '#64748b');
                $sl_text  = $sl > 0 ? "+$sl" : $sl;
                $badge_tipe = $r['tipe_item'] == 'produk' ? 'badge-blue' : 'badge-warning';
            ?>
            <tr>
                <td class="td-muted"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_item'] ?? '-') ?></td>
                <td><span class="badge <?= $badge_tipe ?>"><?= strtoupper($r['tipe_item']) ?></span></td>
                <td><?= number_format($r['stok_sistem'],0,',','.') ?> <?= $r['satuan'] ?></td>
                <td><?= number_format($r['stok_fisik'],0,',','.') ?> <?= $r['satuan'] ?></td>
                <td style="font-weight:700;color:<?= $sl_color ?>"><?= $sl_text ?></td>
                <td style="max-width:200px;font-size:12.5px;color:#475569;"><?= $r['alasan'] ? htmlspecialchars($r['alasan']) : '<span style="color:#94a3b8;">—</span>' ?></td>
                <td class="td-muted"><?= htmlspecialchars($r['username'] ?? '-') ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="td-empty">Tidak ada data opname pada periode ini</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
function isiStokSistem() {
    const sel = document.getElementById('selBahan');
    const opt = sel.options[sel.selectedIndex];
    const stok = opt.getAttribute('data-stok') || '';
    document.getElementById('stok_sistem_disp').value = stok !== '' ? stok + ' unit' : '';
    hitungSelisih();
}

function hitungSelisih() {
    const sel = document.getElementById('selBahan');
    const opt = sel.options[sel.selectedIndex];
    const stokSistem = parseInt(opt.getAttribute('data-stok') || 0);
    const stokFisik  = parseInt(document.getElementById('stokFisik').value || 0);
    const selisih    = stokFisik - stokSistem;
    const el = document.getElementById('selisihDisp');

    if (!opt.value) { el.textContent = '—'; el.style.color = '#64748b'; return; }
    if (selisih > 0)  { el.textContent = '+' + selisih + ' (stok berlebih)'; el.style.color = '#166534'; el.style.background = '#f0fdf4'; }
    else if (selisih < 0) { el.textContent = selisih + ' (stok kurang)'; el.style.color = '#b91c1c'; el.style.background = '#fef2f2'; }
    else { el.textContent = '0 (sesuai)'; el.style.color = '#64748b'; el.style.background = '#f8fafc'; }
}
</script>
</body>
</html>
