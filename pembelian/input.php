<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin_gudang" && $_SESSION['role'] != 3) {
    header("Location: ../auth/login.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $id_supplier = $_POST['id_supplier'];
    $id_bahan = $_POST['id_bahan'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $total = $jumlah * $harga;

    // 1. Insert ke tabel pembelian
    $qBeli = "INSERT INTO pembelian (tanggal, id_supplier, total) VALUES ('$tanggal', '$id_supplier', '$total')";
    if (mysqli_query($conn, $qBeli)) {
        $id_pembelian = mysqli_insert_id($conn);

        // 2. Insert ke detail_pembelian
        $qDetail = "INSERT INTO detail_pembelian (id_pembelian, id_bahan, jumlah, harga) VALUES ('$id_pembelian', '$id_bahan', '$jumlah', '$harga')";
        mysqli_query($conn, $qDetail);

        // 3. Update stok bahan_baku
        $qUpdateStok = "UPDATE bahan_baku SET stok = stok + $jumlah WHERE id_bahan = '$id_bahan'";
        mysqli_query($conn, $qUpdateStok);

        $success = "Data pembelian berhasil disimpan!";
    } else {
        $error = "Gagal menyimpan data pembelian.";
    }
}

// Data untuk Dropdown
$qSupplier = mysqli_query($conn, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
$qBahan = mysqli_query($conn, "SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");

// Riwayat 5 pembelian terakhir
$qRiwayat = mysqli_query($conn, "
    SELECT p.tanggal, s.nama_supplier, b.nama_bahan, dp.jumlah, b.satuan, dp.harga, (dp.jumlah * dp.harga) as total
    FROM detail_pembelian dp
    JOIN pembelian p ON dp.id_pembelian = p.id_pembelian
    JOIN bahan_baku b ON dp.id_bahan = b.id_bahan
    LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
    ORDER BY p.tanggal DESC LIMIT 5
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pembelian Bahan - Tofu Pro</title>
    <link rel="stylesheet" href="../assets/css/admin_gudang.css">
    <style>
        .form-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            border: 1px solid #f3f4f6;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-family: inherit;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <div class="brand-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="7" height="7" rx="1" stroke="white" stroke-width="2" />
                    <rect x="14" y="3" width="7" height="7" rx="1" stroke="white" stroke-width="2" />
                    <rect x="14" y="14" width="7" height="7" rx="1" stroke="white" stroke-width="2" />
                    <rect x="3" y="14" width="7" height="7" rx="1" stroke="white" stroke-width="2" />
                </svg>
            </div>
            <div class="brand-text">
                <h2>Tofu Pro</h2>
                <p>Warehouse System</p>
            </div>
        </div>

        <ul class="menu">
            <li>
                <a href="../dashboard/admin_gudang.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="../gudang/stok.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    Stok Bahan
                </a>
            </li>
            <li>
                <a href="input.php" class="active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    Pembelian Bahan
                </a>
            </li>
        </ul>

        <div class="sidebar-logout" style="position: absolute; bottom: 30px; width: calc(100% - 40px);">
            <a href="../auth/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #ef4444; font-weight: 500; font-size: 14px; transition: all 0.2s ease;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Keluar (Logout)
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <h1>Input Pembelian Bahan</h1>
            <p>Catat pengeluaran untuk pembelian bahan baku</p>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="tanggal">Tanggal Pembelian</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">Supplier</label>
                        <select id="id_supplier" name="id_supplier" class="form-control" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php while($s = mysqli_fetch_assoc($qSupplier)): ?>
                                <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_bahan">Bahan Baku</label>
                    <select id="id_bahan" name="id_bahan" class="form-control" required>
                        <option value="">-- Pilih Bahan Baku --</option>
                        <?php while($b = mysqli_fetch_assoc($qBahan)): ?>
                            <option value="<?= $b['id_bahan'] ?>"><?= htmlspecialchars($b['nama_bahan']) ?> (<?= htmlspecialchars($b['satuan']) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="jumlah">Jumlah Beli</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="harga">Harga Satuan (Rp)</label>
                        <input type="number" id="harga" name="harga" class="form-control" min="0" required>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="submit" class="btn-primary">Simpan Pembelian</button>
                    <a href="../dashboard/admin_gudang.php" style="margin-left: 12px; color: #4b5563; text-decoration: none; font-size: 14px;">Batal</a>
                </div>
            </form>
        </div>

        <div class="table-section">
            <h3>Riwayat Pembelian Terakhir</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Bahan Baku</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qRiwayat) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($qRiwayat)): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($r['nama_supplier']) ?></td>
                                <td><?= htmlspecialchars($r['nama_bahan']) ?></td>
                                <td><?= number_format($r['jumlah']) ?> <?= htmlspecialchars($r['satuan']) ?></td>
                                <td>Rp <?= number_format($r['harga']) ?></td>
                                <td style="font-weight: 500;">Rp <?= number_format($r['total']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9ca3af; padding: 30px;">
                                Belum ada data pembelian.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
