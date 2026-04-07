<?php
session_start();
include '../config/database.php';

// Cek apakah user adalah Role 2 (Produksi)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 2) {
    header("Location: ../auth/login.php");
    exit;
}

// Data Tabel Seluruh Riwayat Produksi
$qRiwayat = mysqli_query($conn, "SELECT produksi.*, produk.nama_produk FROM produksi JOIN produk ON produksi.id_produk = produk.id_produk ORDER BY tanggal DESC, id_produksi DESC");

// Hitung total data
$total_data = mysqli_num_rows($qRiwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Produksi - Tofu Pro</title>
    <link rel="stylesheet" href="../assets/css/admin_produksi.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            border: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <div class="brand-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="7" height="7" rx="1" stroke="white" stroke-width="2"/>
                    <rect x="14" y="3" width="7" height="7" rx="1" stroke="white" stroke-width="2"/>
                    <rect x="14" y="14" width="7" height="7" rx="1" stroke="white" stroke-width="2"/>
                    <rect x="3" y="14" width="7" height="7" rx="1" stroke="white" stroke-width="2"/>
                </svg>
            </div>
            <div class="brand-text">
                <h2>Tofu Pro</h2>
                <p>Production System</p>
            </div>
        </div>

        <ul class="menu">
            <li>
                <a href="../dashboard/admin_produksi.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="input.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    Input Produksi
                </a>
            </li>
            <li>
                <a href="produksi.php" class="active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Riwayat Produksi
                </a>
            </li>
        </ul>

        <!-- LOGOUT DI BAWAH -->
        <div class="sidebar-logout" style="position: absolute; bottom: 30px; width: calc(100% - 40px);">
            <a href="../auth/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #ef4444; font-weight: 500; font-size: 14px; transition: all 0.2s ease;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Keluar (Logout)
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Riwayat Produksi Lengkap</h1>
                <p>Menampilkan semua data batch olahan tahu (Total: <?= $total_data ?> Data)</p>
            </div>
            <a href="cetak.php" target="_blank" class="btn-primary" style="background-color: #3b82f6;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Cetak Report
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Produksi</th>
                        <th>Kode Batch</th>
                        <th>Jenis Produk</th>
                        <th>Jumlah Produksi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qRiwayat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($qRiwayat)): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><span style="font-family: monospace; color: #10b981;">BATCH-<?= str_pad($row['id_produksi'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                <td><strong style="color: #4b5563; font-weight: 500; font-size: 13px;"><?= $row['nama_produk'] ?></strong></td>
                                <td><?= number_format($row['jumlah']) ?> kg</td>
                                <td>
                                    <?php if (strtolower($row['status']) == 'selesai'): ?>
                                        <span class="badge success">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                            Selesai
                                        </span>
                                    <?php else: ?>
                                        <span class="badge warning">
                                            Proses
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9ca3af; padding: 40px;">
                                Belum ada data produksi yang tercatat.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
