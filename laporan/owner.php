<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole(ROLE_OWNER);

$jenis  = $_GET['jenis']  ?? 'pembelian';
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$cetak  = isset($_GET['cetak']);

$data = null;
$title_lap = '';

switch ($jenis) {
    case 'pembelian':
        $title_lap = 'Laporan Pembelian Bahan Baku';
        $data = mysqli_query($conn,"SELECT pm.*, s.nama_supplier FROM pembelian pm LEFT JOIN supplier s ON pm.id_supplier=s.id_supplier WHERE pm.tanggal BETWEEN '$dari' AND '$sampai' ORDER BY pm.tanggal DESC");
        break;
    case 'produksi':
        $title_lap = 'Laporan Produksi';
        $data = mysqli_query($conn,"SELECT pr.*, p.nama_produk FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk WHERE pr.tanggal BETWEEN '$dari' AND '$sampai' ORDER BY pr.tanggal DESC");
        break;
    case 'persediaan_bahan':
        $title_lap = 'Laporan Persediaan Bahan Baku';
        $data = mysqli_query($conn,"SELECT * FROM bahan_baku WHERE is_active=1 ORDER BY nama_bahan");
        break;
    case 'persediaan_produk':
        $title_lap = 'Laporan Persediaan Produk Jadi';
        $data = mysqli_query($conn,"SELECT * FROM produk WHERE is_active=1 ORDER BY nama_produk");
        break;
    case 'hpp':
        $title_lap = 'Laporan Harga Pokok Produksi (HPP)';
        $data = mysqli_query($conn,"SELECT pr.*, p.nama_produk FROM produksi pr LEFT JOIN produk p ON pr.id_produk=p.id_produk WHERE pr.tanggal BETWEEN '$dari' AND '$sampai' AND pr.hpp>0 ORDER BY pr.tanggal DESC");
        break;
    case 'opname':
        $title_lap = 'Laporan Histori Stock Opname';
        $data = mysqli_query($conn,"SELECT so.*, COALESCE(bb.nama_bahan, p.nama_produk) as nama_item, COALESCE(bb.satuan, p.satuan) as satuan, u.username FROM stock_opname so LEFT JOIN bahan_baku bb ON so.id_bahan=bb.id_bahan LEFT JOIN produk p ON so.id_produk=p.id_produk LEFT JOIN user u ON so.id_user=u.id_user WHERE DATE(so.tanggal) BETWEEN '$dari' AND '$sampai' ORDER BY so.tanggal DESC");
        break;
    case 'rekap_supplier':
        $title_lap = 'Rekap Pembelian per Supplier';
        $data = mysqli_query($conn,"SELECT s.nama_supplier, COUNT(pm.id_pembelian) as total_trx, IFNULL(SUM(pm.total),0) as total_nilai FROM supplier s LEFT JOIN pembelian pm ON s.id_supplier=pm.id_supplier AND pm.tanggal BETWEEN '$dari' AND '$sampai' WHERE s.is_active=1 GROUP BY s.id_supplier ORDER BY total_nilai DESC");
        break;
    case 'rekap_bahan':
        $title_lap = 'Rekap Penggunaan Bahan Baku';
        $data = mysqli_query($conn,"SELECT bb.nama_bahan, bb.satuan, IFNULL(SUM(dp.jumlah_pakai),0) as total_pakai, IFNULL(SUM(dp.subtotal),0) as total_biaya FROM bahan_baku bb LEFT JOIN detail_produksi dp ON bb.id_bahan=dp.id_bahan LEFT JOIN produksi pr ON dp.id_produksi=pr.id_produksi AND pr.tanggal BETWEEN '$dari' AND '$sampai' WHERE bb.is_active=1 GROUP BY bb.id_bahan ORDER BY total_pakai DESC");
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if(!$cetak) include '../includes/sidebar_owner.php'; ?>
<div class="main-content" style="<?= $cetak ? 'margin-left:0;' : '' ?>">
    <?php if(!$cetak): ?>
    <div class="page-header">
        <h1>Modul Laporan</h1>
        <p>Pilih jenis laporan, tentukan periode, lalu tampilkan atau cetak</p>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card no-print">
        <form method="GET" class="filter-bar" style="flex-wrap:wrap;gap:14px;align-items:flex-end;">
            <div class="form-group">
                <label>Jenis Laporan</label>
                <select name="jenis" class="form-control" style="min-width:220px;">
                    <optgroup label="Transaksi">
                        <option value="pembelian" <?= $jenis=='pembelian'?'selected':'' ?>>Pembelian Bahan Baku</option>
                        <option value="produksi"  <?= $jenis=='produksi'?'selected':'' ?>>Produksi</option>
                    </optgroup>
                    <optgroup label="Persediaan">
                        <option value="persediaan_bahan"   <?= $jenis=='persediaan_bahan'?'selected':'' ?>>Persediaan Bahan Baku</option>
                        <option value="persediaan_produk"  <?= $jenis=='persediaan_produk'?'selected':'' ?>>Persediaan Produk Jadi</option>
                        <option value="hpp"                <?= $jenis=='hpp'?'selected':'' ?>>Perhitungan HPP</option>
                    </optgroup>
                    <optgroup label="Audit & Rekap">
                        <option value="opname"         <?= $jenis=='opname'?'selected':'' ?>>Histori Stock Opname</option>
                        <option value="rekap_supplier" <?= $jenis=='rekap_supplier'?'selected':'' ?>>Rekap per Supplier</option>
                        <option value="rekap_bahan"    <?= $jenis=='rekap_bahan'?'selected':'' ?>>Rekap Penggunaan Bahan</option>
                    </optgroup>
                </select>
            </div>
            <?php if(!in_array($jenis,['persediaan_bahan','persediaan_produk'])): ?>
            <div class="form-group"><label>Dari Tanggal</label><input type="date" name="dari" class="form-control" value="<?= $dari ?>"></div>
            <div class="form-group"><label>Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $sampai ?>"></div>
            <?php else: ?>
            <input type="hidden" name="dari" value="<?= $dari ?>">
            <input type="hidden" name="sampai" value="<?= $sampai ?>">
            <?php endif; ?>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="?jenis=<?= $jenis ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&cetak=1" target="_blank" class="btn btn-ghost" style="margin-left:8px;">🖨 Cetak</a>
            </div>
        </form>
    </div>

    <!-- Judul Laporan -->
    <div class="card" id="laporanContent">
        <div style="margin-bottom:20px;">
            <div style="font-size:11px;text-transform:uppercase;color:#94a3b8;font-weight:600;letter-spacing:0.06em;">UD. Kulit Kembang Tahu & Tahu</div>
            <div style="font-size:18px;font-weight:700;color:#0f172a;margin-top:4px;"><?= $title_lap ?></div>
            <?php if(!in_array($jenis,['persediaan_bahan','persediaan_produk'])): ?>
            <div style="font-size:13px;color:#64748b;margin-top:2px;">Periode: <?= date('d F Y',strtotime($dari)) ?> — <?= date('d F Y',strtotime($sampai)) ?></div>
            <?php else: ?>
            <div style="font-size:13px;color:#64748b;margin-top:2px;">Data per: <?= date('d F Y') ?></div>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
        <?php if($data && mysqli_num_rows($data)>0): ?>
        <table>
        <?php
        switch($jenis) {
            case 'pembelian': ?>
                <thead><tr><th>No</th><th>Tanggal</th><th>Supplier</th><th>Total Nilai</th></tr></thead>
                <tbody><?php $no=1; $grand=0; while($r=mysqli_fetch_assoc($data)): $grand+=$r['total']; ?>
                <tr><td><?= $no++ ?></td><td><?= date('d M Y',strtotime($r['tanggal'])) ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_supplier']??'-') ?></td><td>Rp <?= number_format($r['total'],0,',','.') ?></td></tr>
                <?php endwhile; ?>
                <tr style="font-weight:700;background:#f8fafc;"><td colspan="3" style="text-align:right;padding:12px 14px;">TOTAL</td><td>Rp <?= number_format($grand,0,',','.') ?></td></tr>
                </tbody><?php break;

            case 'produksi': ?>
                <thead><tr><th>No</th><th>No Batch</th><th>Produk</th><th>Jumlah</th><th>Total Biaya</th><th>Tanggal</th></tr></thead>
                <tbody><?php $no=1; $tjml=0; while($r=mysqli_fetch_assoc($data)): $tjml+=$r['jumlah']; ?>
                <tr><td><?= $no++ ?></td><td class="td-muted"><?= htmlspecialchars($r['no_batch']??'-') ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td><td><?= number_format($r['jumlah'],0,',','.') ?></td><td>Rp <?= number_format($r['total_biaya']??0,0,',','.') ?></td><td class="td-muted"><?= date('d M Y',strtotime($r['tanggal'])) ?></td></tr>
                <?php endwhile; ?>
                <tr style="font-weight:700;background:#f8fafc;"><td colspan="3" style="text-align:right;padding:12px 14px;">TOTAL</td><td><?= number_format($tjml,0,',','.') ?> pcs</td><td colspan="2"></td></tr>
                </tbody><?php break;

            case 'persediaan_bahan': ?>
                <thead><tr><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th>Stok</th><th>Minimum</th><th>Harga Satuan</th><th>Status</th></tr></thead>
                <tbody><?php while($r=mysqli_fetch_assoc($data)): $kt=$r['stok']<=$r['stok_minimum']; ?>
                <tr class="<?= $r['stok']==0?'stok-habis':($kt?'stok-kritis':'') ?>"><td class="td-muted"><?= htmlspecialchars($r['kode_bahan']??'-') ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td><td><?= $r['satuan'] ?></td><td style="font-weight:600;color:<?= $r['stok']==0?'#b91c1c':($kt?'#b45309':'#166534') ?>;"><?= number_format($r['stok'],0,',','.') ?></td><td><?= $r['stok_minimum'] ?></td><td>Rp <?= number_format($r['harga_satuan'],0,',','.') ?></td><td><?= $kt ? '<span style="color:#b45309;font-weight:600;">⚠ Minimum</span>' : '✓ Aman' ?></td></tr>
                <?php endwhile; ?></tbody><?php break;

            case 'persediaan_produk': ?>
                <thead><tr><th>Kode</th><th>Nama Produk</th><th>Satuan</th><th>Stok</th><th>Harga</th></tr></thead>
                <tbody><?php while($r=mysqli_fetch_assoc($data)): ?>
                <tr><td class="td-muted"><?= htmlspecialchars($r['kode_produk']??'-') ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td><td><?= $r['satuan'] ?></td><td style="font-weight:600;"><?= number_format($r['stok'],0,',','.') ?></td><td><?= $r['harga']?'Rp '.number_format($r['harga'],0,',','.'):'—' ?></td></tr>
                <?php endwhile; ?></tbody><?php break;

            case 'hpp': ?>
                <thead><tr><th>No</th><th>No Batch</th><th>Produk</th><th>Jumlah</th><th>Total Biaya Bahan</th><th>HPP/pcs</th><th>Tanggal</th></tr></thead>
                <tbody><?php $no=1; while($r=mysqli_fetch_assoc($data)): ?>
                <tr><td><?= $no++ ?></td><td class="td-muted"><?= htmlspecialchars($r['no_batch']??'-') ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_produk']) ?></td><td><?= number_format($r['jumlah'],0,',','.') ?></td><td>Rp <?= number_format($r['total_biaya'],0,',','.') ?></td><td style="font-weight:700;color:#166534;">Rp <?= number_format($r['hpp'],0,',','.') ?></td><td class="td-muted"><?= date('d M Y',strtotime($r['tanggal'])) ?></td></tr>
                <?php endwhile; ?></tbody><?php break;

            case 'opname': ?>
                <thead><tr><th>Tanggal</th><th>Item (Bahan/Produk)</th><th>Tipe</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th><th>Alasan</th><th>Admin</th></tr></thead>
                <tbody><?php while($r=mysqli_fetch_assoc($data)): $sl=(int)$r['selisih']; ?>
                <tr><td class="td-muted"><?= date('d M Y',strtotime($r['tanggal'])) ?></td><td class="td-bold"><?= htmlspecialchars($r['nama_item']) ?></td><td><span style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px;"><?= strtoupper($r['tipe_item'] ?? 'BAHAN') ?></span></td><td><?= $r['stok_sistem'] ?> <?= $r['satuan'] ?></td><td><?= $r['stok_fisik'] ?> <?= $r['satuan'] ?></td><td style="font-weight:700;color:<?= $sl>0?'#166534':($sl<0?'#b91c1c':'#64748b') ?>;"><?= $sl>0?'+'.$sl:$sl ?></td><td style="font-size:12.5px;"><?= htmlspecialchars($r['alasan']??'—') ?></td><td class="td-muted"><?= htmlspecialchars($r['username']??'-') ?></td></tr>
                <?php endwhile; ?></tbody><?php break;

            case 'rekap_supplier': ?>
                <thead><tr><th>Supplier</th><th>Jumlah Transaksi</th><th>Total Nilai Pembelian</th></tr></thead>
                <tbody><?php while($r=mysqli_fetch_assoc($data)): ?>
                <tr><td class="td-bold"><?= htmlspecialchars($r['nama_supplier']) ?></td><td><?= $r['total_trx'] ?> transaksi</td><td style="font-weight:600;">Rp <?= number_format($r['total_nilai'],0,',','.') ?></td></tr>
                <?php endwhile; ?></tbody><?php break;

            case 'rekap_bahan': ?>
                <thead><tr><th>Nama Bahan</th><th>Satuan</th><th>Total Digunakan</th><th>Total Biaya</th></tr></thead>
                <tbody><?php while($r=mysqli_fetch_assoc($data)): ?>
                <tr><td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td><td><?= $r['satuan'] ?></td><td><?= number_format($r['total_pakai'],0,',','.') ?></td><td>Rp <?= number_format($r['total_biaya'],0,',','.') ?></td></tr>
                <?php endwhile; ?></tbody><?php break;
        }
        ?>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:48px;color:#94a3b8;">Tidak ada data untuk laporan ini pada periode yang dipilih.</div>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php if($cetak): ?>
<script>window.onload = function(){ window.print(); };</script>
<?php endif; ?>
</body>
</html>