<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo "ID tidak valid."; exit; }

$pembelian = mysqli_fetch_assoc(mysqli_query($conn,"SELECT pm.*, s.nama_supplier, s.alamat, s.no_telp FROM pembelian pm LEFT JOIN supplier s ON pm.id_supplier=s.id_supplier WHERE pm.id_pembelian=$id"));
if (!$pembelian) { echo "Data pembelian tidak ditemukan."; exit; }

$detail = mysqli_query($conn,"SELECT dp.*, bb.nama_bahan, bb.satuan FROM detail_pembelian dp LEFT JOIN bahan_baku bb ON dp.id_bahan=bb.id_bahan WHERE dp.id_pembelian=$id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bukti Pembelian #<?= $id ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #000; padding: 30px; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 14px; }
    .header h2 { font-size: 18px; }
    .header p  { font-size: 12px; color: #333; margin-top: 4px; }
    .info-row  { display: flex; gap: 30px; margin-bottom: 14px; }
    .info-box  { flex: 1; }
    .info-box label { font-size: 11px; color: #666; display: block; }
    .info-box span  { font-size: 13px; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { padding: 8px 10px; border: 1px solid #ccc; font-size: 12.5px; }
    th { background: #f5f5f5; font-weight: 600; }
    .total-row td { font-weight: 700; font-size: 14px; border-top: 2px solid #000; }
    .footer { margin-top: 30px; display: flex; justify-content: space-between; }
    .ttd { text-align: center; width: 180px; }
    .ttd-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 6px; font-size: 12px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:20px;">
    <button onclick="window.print()" style="padding:8px 20px;background:#16a34a;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">🖨 Cetak / Simpan PDF</button>
    <button onclick="window.history.back()" style="padding:8px 20px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;">← Kembali</button>
</div>

<div class="header">
    <h2>UD. Kulit Kembang Tahu & Tahu</h2>
    <p>BUKTI PEMBELIAN BAHAN BAKU</p>
    <p>No. Transaksi: <strong>PBL-<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></strong></p>
</div>

<div class="info-row">
    <div class="info-box"><label>Tanggal Pembelian</label><span><?= date('d F Y', strtotime($pembelian['tanggal'])) ?></span></div>
    <div class="info-box"><label>Supplier</label><span><?= htmlspecialchars($pembelian['nama_supplier']) ?></span></div>
    <div class="info-box"><label>Telepon Supplier</label><span><?= htmlspecialchars($pembelian['no_telp'] ?: '-') ?></span></div>
</div>

<table>
    <thead><tr><th>No</th><th>Nama Bahan Baku</th><th>Satuan</th><th style="text-align:right;">Jumlah</th><th style="text-align:right;">Harga Satuan</th><th style="text-align:right;">Subtotal</th></tr></thead>
    <tbody>
    <?php $no=1; $grand=0; while($r=mysqli_fetch_assoc($detail)): $sub=$r['jumlah']*$r['harga']; $grand+=$sub; ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($r['nama_bahan']) ?></td>
        <td><?= $r['satuan'] ?></td>
        <td style="text-align:right;"><?= number_format($r['jumlah'],0,',','.') ?></td>
        <td style="text-align:right;">Rp <?= number_format($r['harga'],0,',','.') ?></td>
        <td style="text-align:right;">Rp <?= number_format($sub,0,',','.') ?></td>
    </tr>
    <?php endwhile; ?>
    <tr class="total-row"><td colspan="5" style="text-align:right;">TOTAL</td><td style="text-align:right;">Rp <?= number_format($grand,0,',','.') ?></td></tr>
    </tbody>
</table>

<div class="footer">
    <div class="ttd"><div class="ttd-line">Admin Gudang</div></div>
    <div class="ttd"><div class="ttd-line">Pimpinan / Owner</div></div>
</div>
</body>
</html>
