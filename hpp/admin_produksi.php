<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_PRODUKSI]);
$role = $_SESSION['role'];

$filter_dari  = $_GET['dari']   ?? date('Y-m-01');
$filter_sampai= $_GET['sampai'] ?? date('Y-m-d');

$data = mysqli_query($conn,
    "SELECT pr.*, p.nama_produk, p.satuan FROM produksi pr
     LEFT JOIN produk p ON pr.id_produk=p.id_produk
     WHERE pr.tanggal BETWEEN '$filter_dari' AND '$filter_sampai' AND pr.hpp>0
     ORDER BY pr.tanggal DESC"
);

$rekap_produk = mysqli_query($conn,
    "SELECT p.nama_produk, p.satuan,
        SUM(pr.jumlah) as total_jml,
        SUM(pr.total_biaya) as total_biaya,
        AVG(pr.hpp) as hpp_rata
     FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk
     WHERE pr.tanggal BETWEEN '$filter_dari' AND '$filter_sampai' AND pr.hpp>0
     GROUP BY pr.id_produk ORDER BY p.nama_produk"
);

$total_nilai = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(total_biaya),0) t FROM produksi WHERE tanggal BETWEEN '$filter_dari' AND '$filter_sampai'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Perhitungan HPP — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Perhitungan HPP</h1>
        <p>Harga Pokok Produksi = Total Biaya Bahan ÷ Jumlah Produk Jadi</p>
    </div>

    <!-- Filter -->
    <div class="card no-print">
        <form method="GET" class="filter-bar">
            <div class="form-group"><label>Dari</label><input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>"></div>
            <div class="form-group"><label>Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>"></div>
            <div class="form-group" style="align-self:flex-end;"><button type="submit" class="btn btn-primary">Filter</button>
            <a href="?dari=<?= $filter_dari ?>&sampai=<?= $filter_sampai ?>&cetak=1" target="_blank" class="btn btn-ghost" style="margin-left:8px;">🖨 Cetak</a></div>
        </form>
    </div>

    <!-- Stat -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><div class="stat-info"><p>Total Biaya Bahan</p><h3>Rp <?= number_format($total_nilai,0,',','.') ?></h3></div></div>
        <div class="stat-card"><div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="stat-info"><p>Jumlah Produksi</p><h3><?= mysqli_num_rows($data) ?> batch</h3></div></div>
    </div>

    <!-- Rekap per Produk -->
    <div class="card">
        <div class="card-title">Rekap HPP per Produk</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th>Total Diproduksi</th><th>Total Biaya Bahan</th><th>Rata-rata HPP/pcs</th></tr></thead>
            <tbody>
            <?php while($r=mysqli_fetch_assoc($rekap_produk)): ?>
            <tr>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= number_format($r['total_jml'],0,',','.') ?> <?= $r['satuan'] ?></td>
                <td>Rp <?= number_format($r['total_biaya'],0,',','.') ?></td>
                <td style="font-weight:700;color:#166534;font-size:15px;">Rp <?= number_format($r['hpp_rata'],0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Detail per Batch -->
    <div class="card">
        <div class="card-title">Detail HPP per Batch Produksi</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>No Batch</th><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Total Biaya Bahan</th><th>HPP/pcs</th></tr></thead>
            <tbody>
            <?php mysqli_data_seek($data,0); while($r=mysqli_fetch_assoc($data)): ?>
            <tr>
                <td class="td-muted"><?= htmlspecialchars($r['no_batch']??'-') ?></td>
                <td class="td-muted"><?= date('d M Y',strtotime($r['tanggal'])) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= number_format($r['jumlah'],0,',','.') ?></td>
                <td>Rp <?= number_format($r['total_biaya'],0,',','.') ?></td>
                <td style="font-weight:700;color:#166534;">Rp <?= number_format($r['hpp'],0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($data)==0): ?><tr><td colspan="6" class="td-empty">Belum ada data HPP periode ini</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>