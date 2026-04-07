<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);

$role = $_SESSION['role'];

// Filter
$filter_item = $_GET['item'] ?? 'bahan';
$filter_dari = $_GET['dari'] ?? date('Y-m-01');
$filter_sampai= $_GET['sampai'] ?? date('Y-m-d');

// Stats bahan baku
$total_bahan = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE is_active=1"))['c'];
$stok_kritis = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1"))['c'];
$stok_habis  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE stok=0 AND is_active=1"))['c'];

// Stats produk
$total_produk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM produk WHERE is_active=1"))['c'];

$bahan_list = mysqli_query($conn,"SELECT * FROM bahan_baku WHERE is_active=1 ORDER BY nama_bahan ASC");
$produk_list= mysqli_query($conn,"SELECT * FROM produk WHERE is_active=1 ORDER BY nama_produk ASC");

// Histori stok_log
$filter_type = $filter_item == 'produk' ? "AND tipe_item='produk'" : "AND tipe_item='bahan'";
$log_list = mysqli_query($conn,
    "SELECT sl.*, bb.nama_bahan, u.username
     FROM stok_log sl
     LEFT JOIN bahan_baku bb ON sl.id_bahan = bb.id_bahan
     LEFT JOIN user u ON sl.id_user = u.id_user
     WHERE DATE(sl.created_at) BETWEEN '$filter_dari' AND '$filter_sampai' $filter_type
     ORDER BY sl.id_log DESC LIMIT 50"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manajemen Persediaan — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Manajemen Persediaan</h1>
        <p>Monitoring stok bahan baku dan produk jadi secara real-time</p>
    </div>

    <?php if($stok_kritis > 0): ?>
    <div class="alert alert-warning" style="margin-bottom:20px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <div><strong><?= $stok_kritis ?> bahan</strong> stok minimum, <strong><?= $stok_habis ?></strong> habis total. <a href="stok_minimum.php" style="color:inherit;font-weight:600;">Lihat Semua →</a></div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div><div class="stat-info"><p>Total Bahan Baku</p><h3><?= $total_bahan ?></h3></div></div>
        <div class="stat-card" style="<?= $stok_kritis?'border:1px solid #fde68a;':'' ?>"><div class="stat-icon <?= $stok_kritis?'red':'amber' ?>"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div><div class="stat-info"><p>Stok Kritis</p><h3 style="<?= $stok_kritis?'color:#b91c1c':'' ?>"><?= $stok_kritis ?></h3></div></div>
        <div class="stat-card"><div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg></div><div class="stat-info"><p>Jenis Produk</p><h3><?= $total_produk ?></h3></div></div>
        <div class="stat-card"><div class="stat-icon red"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="17 13 12 18 7 13"/><line x1="12" y1="2" x2="12" y2="18"/></svg></div><div class="stat-info"><p>Stok Bahan Habis</p><h3 style="color:#b91c1c;"><?= $stok_habis ?></h3></div></div>
    </div>

    <!-- Tabel Bahan Baku -->
    <div class="card">
        <div class="card-title">Stok Bahan Baku</div>
        <div class="card-sub">Highlight <span style="background:#fff7ed;padding:2px 6px;border-radius:4px;">oranye</span> = stok minimum | <span style="background:#fef2f2;padding:2px 6px;border-radius:4px;">merah</span> = habis</div>
        <div class="table-wrap" style="margin-top:12px;">
        <table>
            <thead><tr><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th>Stok Saat Ini</th><th>Batas Minimum</th><th>Harga Satuan</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r=mysqli_fetch_assoc($bahan_list)):
                $kritis = $r['stok'] <= $r['stok_minimum'];
                $habis  = $r['stok'] == 0;
            ?>
            <tr class="<?= $habis ? 'stok-habis' : ($kritis ? 'stok-kritis' : '') ?>">
                <td class="td-muted"><?= htmlspecialchars($r['kode_bahan'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td>
                <td><?= $r['satuan'] ?></td>
                <td style="font-weight:700;color:<?= $habis ? '#b91c1c' : ($kritis ? '#b45309' : '#166534') ?>;">
                    <?= number_format($r['stok'],0,',','.') ?>
                    <?php if($habis): ?><span class="badge badge-danger" style="margin-left:6px;">HABIS</span>
                    <?php elseif($kritis): ?><span class="badge badge-minimum" style="margin-left:6px;">MIN</span><?php endif; ?>
                </td>
                <td class="td-muted"><?= number_format($r['stok_minimum'],0,',','.') ?></td>
                <td>Rp <?= number_format($r['harga_satuan'],0,',','.') ?></td>
                <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Tabel Produk Jadi -->
    <div class="card">
        <div class="card-title">Stok Produk Jadi</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Nama Produk</th><th>Satuan</th><th>Stok</th><th>Harga</th></tr></thead>
            <tbody>
            <?php while($r=mysqli_fetch_assoc($produk_list)): ?>
            <tr class="<?= $r['stok']==0 ? 'stok-habis' : '' ?>">
                <td class="td-muted"><?= htmlspecialchars($r['kode_produk'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= $r['satuan'] ?></td>
                <td style="font-weight:700;color:<?= $r['stok']==0?'#b91c1c':'#166534' ?>;"><?= number_format($r['stok'],0,',','.') ?></td>
                <td><?= $r['harga'] ? 'Rp '.number_format($r['harga'],0,',','.') : '-' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Histori Stok Log -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <div><div class="card-title">Histori Pergerakan Stok</div><div class="card-sub">Log masuk/keluar/opname (maks 50 entri terakhir)</div></div>
            <form method="GET" class="filter-bar" style="margin:0;">
                <div class="form-group"><label style="font-size:12px;">Dari</label><input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>"></div>
                <div class="form-group"><label style="font-size:12px;">Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>"></div>
                <div class="form-group" style="align-self:flex-end;"><button type="submit" class="btn btn-ghost">Filter</button></div>
            </form>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>Item</th><th>Jenis</th><th>Jumlah</th><th>Stok Sebelum</th><th>Stok Sesudah</th><th>Keterangan</th><th>Admin</th></tr></thead>
            <tbody>
            <?php if(mysqli_num_rows($log_list)>0): while($r=mysqli_fetch_assoc($log_list)):
                $badge = match($r['tipe_transaksi']) { 'masuk'=>'badge-success','keluar'=>'badge-danger',default=>'badge-blue' };
                $label = match($r['tipe_transaksi']) { 'masuk'=>'Masuk','keluar'=>'Keluar',default=>'Opname' };
            ?>
            <tr>
                <td class="td-muted"><?= date('d M H:i', strtotime($r['created_at'])) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_bahan'] ?? '-') ?></td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td style="font-weight:600;"><?= $r['tipe_transaksi']=='keluar' ? '-' : '+' ?><?= number_format($r['jumlah'],0,',','.') ?></td>
                <td class="td-muted"><?= number_format($r['stok_sebelum'],0,',','.') ?></td>
                <td class="td-muted"><?= number_format($r['stok_sesudah'],0,',','.') ?></td>
                <td style="font-size:12.5px;color:#475569;max-width:180px;"><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
                <td class="td-muted"><?= htmlspecialchars($r['username'] ?? '-') ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="td-empty">Tidak ada pergerakan stok pada periode ini</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
