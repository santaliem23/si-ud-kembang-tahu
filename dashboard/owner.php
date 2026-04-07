<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole(ROLE_OWNER);

// Stats
$total_bahan   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE is_active=1"))['c'];
$total_produk  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM produk WHERE is_active=1"))['c'];
$total_supplier= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM supplier WHERE is_active=1"))['c'];

// Stok minimum
$stok_min = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1"))['c'];

// Produksi bulan ini
$prod_bulan = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(jumlah),0) t FROM produksi WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['t'];

// Pembelian bulan ini
$beli_bulan = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(total),0) t FROM pembelian WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['t'];

// Chart produksi 6 bulan terakhir
$chart_labels = []; $chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M Y', strtotime("-$i months"));
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(jumlah),0) t FROM produksi WHERE DATE_FORMAT(tanggal,'%Y-%m')='$bln'"));
    $chart_data[] = $r['t'];
}

// 5 produksi terakhir
$riwayat = mysqli_query($conn,"SELECT pr.*, p.nama_produk FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk ORDER BY pr.id_produksi DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Owner — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include '../includes/sidebar_owner.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Dashboard Owner</h1>
        <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> — Ringkasan operasional UD Kembang Tahu</p>
    </div>

    <?php if($stok_min > 0): ?>
    <div class="alert alert-warning" style="margin-bottom:20px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <div><strong><?= $stok_min ?> bahan baku</strong> mencapai batas minimum stok. <a href="/si-ud-kembang-tahu/gudang/stok_minimum.php" style="color:inherit;font-weight:600;">Lihat Detail →</a></div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
            <div class="stat-info"><p>Total Bahan Baku</p><h3><?= $total_bahan ?> <span>jenis</span></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg></div>
            <div class="stat-info"><p>Jenis Produk</p><h3><?= $total_produk ?> <span>produk</span></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg></div>
            <div class="stat-info"><p>Pembelian Bulan Ini</p><h3>Rp <?= number_format($beli_bulan,0,',','.') ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="stat-info"><p>Produksi Bulan Ini</p><h3><?= number_format($prod_bulan,0,',','.') ?> <span>pcs</span></h3></div>
        </div>
        <?php if($stok_min > 0): ?>
        <div class="stat-card" style="border:1px solid #fde68a;">
            <div class="stat-icon red"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div>
            <div class="stat-info"><p>Stok Minimum</p><h3 style="color:#b91c1c;"><?= $stok_min ?> <span>bahan kritis</span></h3></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Grafik Produksi 6 Bulan Terakhir</div>
        <div class="card-sub">Tren jumlah produksi per bulan</div>
        <canvas id="chartProd" height="90"></canvas>
    </div>

    <div class="card">
        <div class="card-title">Produksi Terakhir</div>
        <div class="card-sub">5 transaksi produksi terbaru</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>No Batch</th><th>Produk</th><th>Jumlah</th><th>Tanggal</th><th>HPP/pcs</th></tr></thead>
            <tbody>
            <?php while($r = mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['no_batch'] ?? '-') ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= number_format($r['jumlah'],0,',','.') ?> pcs</td>
                <td class="td-muted"><?= $r['tanggal'] ?></td>
                <td>Rp <?= number_format(($r['hpp'] ?? 0),0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($riwayat) == 0): ?><tr><td colspan="5" class="td-empty">Belum ada data produksi</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById('chartProd'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Produksi (pcs)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(22,163,74,0.15)',
            borderColor: '#16a34a',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
    }
});
</script>
</body>
</html>