<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_PRODUKSI]);
$role = $_SESSION['role'];

$filter_dari   = $_GET['dari']   ?? date('Y-m-01');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');
$filter_produk = (int)($_GET['id_produk'] ?? 0);

$where = "WHERE pr.tanggal BETWEEN '$filter_dari' AND '$filter_sampai'";
if ($filter_produk) $where .= " AND pr.id_produk=$filter_produk";

$data = mysqli_query($conn,
    "SELECT pr.*, p.nama_produk, p.satuan FROM produksi pr
     LEFT JOIN produk p ON pr.id_produk=p.id_produk
     $where ORDER BY pr.id_produksi DESC"
);
$total_jml   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(jumlah),0) t FROM produksi pr $where"))['t'];
$total_biaya = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(total_biaya),0) t FROM produksi pr $where"))['t'];

$qProduk = mysqli_query($conn,"SELECT id_produk, nama_produk FROM produk WHERE is_active=1 ORDER BY nama_produk");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Riwayat Produksi — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_produksi.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Riwayat Produksi</h1>
        <p>Semua catatan produksi beserta detail HPP dan batch number</p>
    </div>

    <!-- Filter -->
    <div class="card no-print">
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label>Produk</label>
                <select name="id_produk" class="form-control" style="min-width:180px;">
                    <option value="">Semua Produk</option>
                    <?php while($p=mysqli_fetch_assoc($qProduk)): ?>
                    <option value="<?= $p['id_produk'] ?>" <?= $filter_produk==$p['id_produk']?'selected':'' ?>><?= htmlspecialchars($p['nama_produk']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label>Dari</label><input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>"></div>
            <div class="form-group"><label>Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>"></div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if($role==ROLE_PRODUKSI): ?>
                <a href="/si-ud-kembang-tahu/produksi/input.php" class="btn btn-ghost" style="margin-left:8px;">+ Input Produksi</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom:20px;">
        <div class="stat-card"><div class="stat-icon green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="stat-info"><p>Total Diproduksi</p><h3><?= number_format($total_jml,0,',','.') ?> <span>pcs</span></h3></div></div>
        <div class="stat-card"><div class="stat-icon amber"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg></div><div class="stat-info"><p>Total Biaya Bahan</p><h3>Rp <?= number_format($total_biaya,0,',','.') ?></h3></div></div>
        <div class="stat-card"><div class="stat-icon blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div><div class="stat-info"><p>Jumlah Batch</p><h3><?= mysqli_num_rows($data) ?></h3></div></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Batch</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Total Biaya Bahan</th>
                    <th>HPP/pcs</th>
                    <th>Tgl Expired</th>
                    <th>Detail Bahan</th>
                </tr>
            </thead>
            <tbody>
            <?php
                if(mysqli_num_rows($data)>0): mysqli_data_seek($data,0); while($r=mysqli_fetch_assoc($data)):
                // Ambil detail bahan yang dipakai
                $detail_p = mysqli_query($conn,"SELECT dp.jumlah_pakai, dp.harga_satuan, dp.subtotal, bb.nama_bahan, bb.satuan FROM detail_produksi dp LEFT JOIN bahan_baku bb ON dp.id_bahan=bb.id_bahan WHERE dp.id_produksi={$r['id_produksi']}");
                $detail_html = '';
                while($dp=mysqli_fetch_assoc($detail_p)) {
                    $detail_html .= htmlspecialchars($dp['nama_bahan']) . " {$dp['jumlah_pakai']}{$dp['satuan']}; ";
                }
                
                $tgl_exp = $r['tanggal_expired'];
                $is_expired = $tgl_exp && strtotime($tgl_exp) < time();
                $is_warning = $tgl_exp && strtotime($tgl_exp) < strtotime('+3 days') && !$is_expired;
            ?>
            <tr style="<?= $is_expired ? 'background:#fef2f2;' : '' ?>">
                <td><span class="badge badge-blue"><?= htmlspecialchars($r['no_batch']??'-') ?></span></td>
                <td class="td-muted"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td style="font-weight:600;"><?= number_format($r['jumlah'],0,',','.') ?> <?= $r['satuan'] ?></td>
                <td>Rp <?= number_format($r['total_biaya']??0,0,',','.') ?></td>
                <td style="font-weight:700;color:#166534;">Rp <?= number_format($r['hpp']??0,0,',','.') ?></td>
                <td>
                    <?php if($tgl_exp): ?>
                        <div style="font-size:12px; font-weight:600; color:<?= $is_expired ? '#b91c1c' : ($is_warning ? '#b45309' : '#166534') ?>">
                            <?= date('d M Y', strtotime($tgl_exp)) ?>
                        </div>
                        <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars(explode(' (', $r['jenis_penyimpanan'])[0] ?? '') ?></div>
                    <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748b;max-width:200px;"><?= $detail_html ?: '<span style="color:#94a3b8;">—</span>' ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="td-empty">Tidak ada data produksi pada periode ini</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
