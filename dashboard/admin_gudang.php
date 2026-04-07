<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole(ROLE_GUDANG);

$total_bahan   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE is_active=1"))['c'];
$stok_min      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1"))['c'];
$beli_bulan    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(total),0) t FROM pembelian WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['t'];
$opname_bulan  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_opname WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['c'];

$bahan_kritis = mysqli_query($conn,"SELECT nama_bahan, stok, stok_minimum, satuan FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1 ORDER BY stok ASC LIMIT 10");
$pembelian_terakhir = mysqli_query($conn,"SELECT pm.*, s.nama_supplier FROM pembelian pm LEFT JOIN supplier s ON pm.id_supplier=s.id_supplier ORDER BY pm.id_pembelian DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin Gudang — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Dashboard Admin Gudang</h1>
        <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> — Monitoring persediaan & pembelian bahan baku</p>
    </div>

    <?php if($stok_min > 0): ?>
    <div class="alert alert-warning">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <div><strong><?= $stok_min ?> bahan baku</strong> mencapai batas stok minimum! <a href="/si-ud-kembang-tahu/gudang/stok_minimum.php" style="color:inherit;font-weight:600;">Lihat & Beli Sekarang →</a></div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
            <div class="stat-info"><p>Total Bahan Baku</p><h3><?= $total_bahan ?> <span>jenis</span></h3></div>
        </div>
        <div class="stat-card" style="<?= $stok_min>0 ? 'border:1px solid #fde68a;' : '' ?>">
            <div class="stat-icon <?= $stok_min>0 ? 'red' : 'amber' ?>"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div>
            <div class="stat-info"><p>Stok Minimum</p><h3 style="<?= $stok_min>0 ? 'color:#b91c1c' : '' ?>"><?= $stok_min ?> <span>bahan kritis</span></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg></div>
            <div class="stat-info"><p>Pembelian Bulan Ini</p><h3>Rp <?= number_format($beli_bulan,0,',','.') ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/></svg></div>
            <div class="stat-info"><p>Stock Opname Bulan Ini</p><h3><?= $opname_bulan ?> <span>kali</span></h3></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="card">
            <div class="card-title" style="color:#b91c1c;">⚠ Bahan Baku Kritis (Stok Minimum)</div>
            <div class="card-sub">Segera lakukan pembelian ulang</div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Nama Bahan</th><th>Stok</th><th>Minimum</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if(mysqli_num_rows($bahan_kritis) > 0): while($r = mysqli_fetch_assoc($bahan_kritis)): ?>
                <tr class="<?= $r['stok']==0 ? 'stok-habis' : 'stok-kritis' ?>">
                    <td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td>
                    <td style="color:<?= $r['stok']==0 ? '#b91c1c' : '#b45309' ?>;font-weight:600;"><?= $r['stok'] ?> <?= $r['satuan'] ?></td>
                    <td class="td-muted"><?= $r['stok_minimum'] ?></td>
                    <td><a href="/si-ud-kembang-tahu/pembelian/admin_gudang.php" class="btn btn-primary btn-sm">Beli</a></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="td-empty" style="color:#16a34a;">✓ Semua stok aman</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Pembelian Terakhir</div>
            <div class="card-sub">5 transaksi pembelian terbaru</div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Supplier</th><th>Tanggal</th><th>Total</th></tr></thead>
                <tbody>
                <?php if(mysqli_num_rows($pembelian_terakhir)>0): while($r = mysqli_fetch_assoc($pembelian_terakhir)): ?>
                <tr>
                    <td class="td-bold"><?= htmlspecialchars($r['nama_supplier'] ?? '-') ?></td>
                    <td class="td-muted"><?= $r['tanggal'] ?></td>
                    <td>Rp <?= number_format($r['total'],0,',','.') ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3" class="td-empty">Belum ada pembelian</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>