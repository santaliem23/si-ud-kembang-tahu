<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: ../auth/login.php");
    exit;
}

$pesan_sukses = '';
$pesan_error = '';

if (isset($_POST['jual_produk'])) {
    $id_produk_dijual = $_POST['id_produk'];
    $jumlah_diminta = (int)$_POST['jumlah'];
    
    // Cek total stok tersedia (belum kadaluarsa dan stok > 0)
    $cek_stok = mysqli_query($conn, "SELECT SUM(stok_sekarang) as total FROM batch_produk WHERE id_produk = '$id_produk_dijual' AND tanggal_expired >= CURDATE() AND stok_sekarang > 0");
    $data_stok = mysqli_fetch_assoc($cek_stok);
    $total_stok = $data_stok['total'] ?? 0;

    if ($total_stok >= $jumlah_diminta && $jumlah_diminta > 0) {
        
        // Simpan header transaksi penjualan
        $harga_q = mysqli_query($conn, "SELECT harga FROM produk WHERE id_produk = '$id_produk_dijual'");
        $harga_p = mysqli_fetch_assoc($harga_q)['harga'] ?? 0;
        $total_harga = $harga_p * $jumlah_diminta;

        mysqli_query($conn, "INSERT INTO penjualan (tanggal, total) VALUES (CURDATE(), '$total_harga')");
        $id_penjualan = mysqli_insert_id($conn);

        // Ambil batch berdasarkan EXPIRED TERDEKAT (FIFO)
        $query_batch = "SELECT * FROM batch_produk 
                        WHERE id_produk = '$id_produk_dijual' 
                        AND tanggal_expired >= CURDATE() 
                        AND stok_sekarang > 0 
                        ORDER BY tanggal_expired ASC";
        
        $result_batch = mysqli_query($conn, $query_batch);
        $sisa_kebutuhan = $jumlah_diminta;

        while($batch = mysqli_fetch_assoc($result_batch)) {
            if ($sisa_kebutuhan > 0) {
                $id_batch = $batch['id_batch'];
                $stok_di_batch = (int)$batch['stok_sekarang'];

                if ($stok_di_batch >= $sisa_kebutuhan) {
                    // Batch ini cukup menutupi sisa
                    $stok_baru = $stok_di_batch - $sisa_kebutuhan;
                    
                    // Update batch
                    mysqli_query($conn, "UPDATE batch_produk SET stok_sekarang = $stok_baru WHERE id_batch = '$id_batch'");
                    
                    // Masukkan ke detail (catat batch mana yg terpotong jika ingin history detail FIFO)
                    mysqli_query($conn, "INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga) VALUES ('$id_penjualan', '$id_produk_dijual', '$sisa_kebutuhan', '$harga_p')");

                    $sisa_kebutuhan = 0;
                    break;
                } else {
                    // Habiskan batch ini (kurang dari kebutuhan)
                    mysqli_query($conn, "UPDATE batch_produk SET stok_sekarang = 0 WHERE id_batch = '$id_batch'");
                    
                    // Detail penjualan untuk batch ini
                    mysqli_query($conn, "INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga) VALUES ('$id_penjualan', '$id_produk_dijual', '$stok_di_batch', '$harga_p')");
                    
                    $sisa_kebutuhan -= $stok_di_batch;
                }
            }
        }
        $pesan_sukses = 'Penjualan berhasil! Stok berhasil dikurangi otomatis menggunakan metode FIFO.';
    } else {
        $pesan_error = 'Opsi ditolak! Stok tidak mencukupi atau produk tersisa sudah kedaluwarsa.';
    }
}

// Data Produk untuk Form
$qProduk = mysqli_query($conn, "
    SELECT p.id_produk, p.nama_produk, p.harga, SUM(b.stok_sekarang) as stok_aktif 
    FROM produk p 
    LEFT JOIN batch_produk b ON p.id_produk = b.id_produk AND b.stok_sekarang > 0 AND b.tanggal_expired >= CURDATE()
    GROUP BY p.id_produk
");

// Data Riwayat Penjualan
$qRiwayat = mysqli_query($conn, "SELECT p.*, SUM(d.jumlah) as total_item 
                                 FROM penjualan p 
                                 LEFT JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan 
                                 GROUP BY p.id_penjualan 
                                 ORDER BY p.id_penjualan DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penjualan - Tofu Pro Owner</title>
<link rel="stylesheet" href="../assets/css/owner.css">
<style>
    .form-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        border: 1px solid #f3f4f6;
        margin-bottom: 30px;
    }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #374151; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
    .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; }
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 14px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <div class="brand-icon">T</div>
            <div class="brand-text">
                <h2>Tofu Pro</h2>
                <p>Management System</p>
            </div>
        </div>

        <ul class="menu">
            <li><a href="../dashboard/owner.php">Dashboard</a></li>
            <li><a href="../produksi/owner.php">Produksi</a></li>
            <li><a href="../gudang/owner.php">Gudang</a></li>
            <li><a href="../pembelian/owner.php">Pembelian</a></li>
            <li><a href="../penjualan/owner.php" class="active">Penjualan </a></li>
            <li><a href="../hpp/owner.php">HPP</a></li>
            <li><a href="../laporan/owner.php">Laporan</a></li>
            <li><a href="../user/owner.php">Manajemen User</a></li>
        </ul>
    </div>

<div class="main-content">
    <div class="header">
        <h1>Kasir Penjualan (FIFO)</h1>
        <p>Proses penjualan barang, sistem akan otomatis memotong produk dengan masa kadaluarsa terdekat.</p>
    </div>

    <?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success"><?= $pesan_sukses ?></div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)): ?>
    <div class="alert alert-error"><?= $pesan_error ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="id_produk">Pilih Produk</label>
                <select id="id_produk" name="id_produk" required>
                    <option value="">-- Pilih Produk --</option>
                    <?php while ($p = mysqli_fetch_assoc($qProduk)): ?>
                        <option value="<?= $p['id_produk'] ?>">
                            <?= $p['nama_produk'] ?> (Stok Aman: <?= number_format($p['stok_aktif'] ?? 0, 0, ',', '.') ?> | Rp <?= number_format($p['harga'] ?? 0, 0, ',', '.') ?>/pcs)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah Jual</label>
                <input type="number" id="jumlah" name="jumlah" min="1" required placeholder="Cth: 10">
            </div>

            <button type="submit" name="jual_produk" class="btn-primary">Proses Penjualan</button>
        </form>
    </div>

    <div class="table-section">
        <h3>Riwayat Penjualan Terakhir</h3>
        <table>
            <thead>
                <tr>
                    <th>ID TRX</th>
                    <th>Tanggal</th>
                    <th>Total Item Terjual</th>
                    <th>Total Rp</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($qRiwayat) > 0){ 
                    while($r = mysqli_fetch_assoc($qRiwayat)){ ?>
                    <tr>
                        <td>TRX-<?= str_pad($r['id_penjualan'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= $r['tanggal'] ?></td>
                        <td><?= $r['total_item'] ?> pcs</td>
                        <td>Rp <?= number_format($r['total'], 0, ',', '.') ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="4" style="text-align:center;">Belum ada penjualan.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
