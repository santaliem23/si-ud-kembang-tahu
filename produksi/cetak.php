<?php
session_start();
include '../config/database.php';

// Cek apakah user adalah Role 2 (Produksi) atau 1 (Owner)
if (!isset($_SESSION['role'])) {
    die("Akses ditolak. Silahkan login.");
}

// Ambil data produksi
$qRiwayat = mysqli_query($conn, "SELECT produksi.*, produk.nama_produk FROM produksi JOIN produk ON produksi.id_produk = produk.id_produk ORDER BY tanggal DESC, id_produksi DESC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Riwayat Produksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            font-size: 14px;
        }
        .signature-area {
            display: inline-block;
            text-align: center;
        }
        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #333;
            width: 150px;
        }
        @media print {
            body { margin: 0; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Riwayat Produksi Tahu</h1>
        <p>Sistem Informasi UD Kulit Kembang Tahu</p>
        <p>Tanggal Cetak: <?= date('d M Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Produksi</th>
                <th>Kode Batch</th>
                <th>Jenis Produk</th>
                <th>Jumlah Produksi (kg)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($qRiwayat)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td>BATCH-<?= str_pad($row['id_produksi'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= number_format($row['jumlah']) ?> kg</td>
                    <td><?= ucfirst($row['status']) ?></td>
                </tr>
            <?php endwhile; ?>
            
            <?php if (mysqli_num_rows($qRiwayat) == 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada data produksi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-area">
            <p>Admin Produksi,</p>
            <div class="signature-line"></div>
            <p><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
        </div>
    </div>

    <script>
        // Memicu dialog print browser secara otomatis saat halaman dibuka
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
