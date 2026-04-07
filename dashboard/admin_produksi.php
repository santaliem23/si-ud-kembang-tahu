<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole(ROLE_PRODUKSI);

$prod_bulan  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(jumlah),0) t FROM produksi WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['t'];
$hpp_rata    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(AVG(hpp),0) t FROM produksi WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE()) AND hpp>0"))['t'];
$total_produk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM produk WHERE is_active=1"))['c'];
$total_bom   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bom WHERE is_active=1"))['c'];

$riwayat = mysqli_query($conn,"SELECT pr.*, p.nama_produk FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk ORDER BY pr.id_produksi DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin Produksi — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Dashboard Admin Produksi</h1>
        <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> — Monitoring produksi & BOM</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="stat-info"><p>Produksi Bulan Ini</p><h3><?= number_format($prod_bulan,0,',','.') ?> <span>pcs</span></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
            <div class="stat-info"><p>Rata-rata HPP Bulan Ini</p><h3>Rp <?= number_format($hpp_rata,0,',','.') ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg></div>
            <div class="stat-info"><p>Jenis Produk Aktif</p><h3><?= $total_produk ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg></div>
            <div class="stat-info"><p>BOM Aktif</p><h3><?= $total_bom ?></h3></div>
        </div>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <div class="card-title">Riwayat Produksi</div>
                <div class="card-sub">8 entri produksi terakhir</div>
            </div>
            <a href="/si-ud-kembang-tahu/produksi/input.php" class="btn btn-primary">+ Input Produksi</a>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>No Batch</th><th>Produk</th><th>Jumlah</th><th>Total Biaya Bahan</th><th>HPP/pcs</th><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r = mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['no_batch'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= number_format($r['jumlah'],0,',','.') ?> pcs</td>
                <td>Rp <?= number_format($r['total_biaya'] ?? 0,0,',','.') ?></td>
                <td>Rp <?= number_format($r['hpp'] ?? 0,0,',','.') ?></td>
                <td class="td-muted"><?= $r['tanggal'] ?></td>
                <td><span class="badge badge-success"><?= $r['status'] ?? 'Selesai' ?></span></td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($riwayat)==0): ?><tr><td colspan="7" class="td-empty">Belum ada data produksi</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>