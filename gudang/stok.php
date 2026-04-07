<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin_gudang" && $_SESSION['role'] != 3) {
    header("Location: ../auth/login.php");
    exit;
}

$success = "";
$error = "";

// Cek aksi hapus
if (isset($_GET['delete'])) {
    $id_del = $_GET['delete'];
    if (mysqli_query($conn, "DELETE FROM bahan_baku WHERE id_bahan = '$id_del'")) {
        $success = "Bahan baku berhasil dihapus!";
    } else {
        $error = "Gagal menghapus! Mungkin data ini sedang digunakan di tabel lain.";
    }
}

// Cek aksi simpan/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_bahan = $_POST['nama_bahan'];
    $satuan = $_POST['satuan'];
    $stok = $_POST['stok'];
    $id_bahan = $_POST['id_bahan'] ?? '';

    if (!empty($id_bahan)) {
        // Update
        $qUpdate = "UPDATE bahan_baku SET nama_bahan = '$nama_bahan', satuan = '$satuan', stok = '$stok' WHERE id_bahan = '$id_bahan'";
        if (mysqli_query($conn, $qUpdate)) {
            $success = "Bahan baku berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui data!";
        }
    } else {
        // Insert
        $qInsert = "INSERT INTO bahan_baku (nama_bahan, satuan, stok) VALUES ('$nama_bahan', '$satuan', '$stok')";
        if (mysqli_query($conn, $qInsert)) {
            $success = "Bahan baku baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan data!";
        }
    }
}

// Data untuk Edit
$editData = null;
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $qEdit = mysqli_query($conn, "SELECT * FROM bahan_baku WHERE id_bahan = '$id_edit'");
    if (mysqli_num_rows($qEdit) > 0) {
        $editData = mysqli_fetch_assoc($qEdit);
    }
}

// Data Tabel Stok Bahan
$qBahan = mysqli_query($conn, "SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Bahan - Tofu Pro</title>
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
            margin-bottom: 16px;
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

        .action-links a {
            font-size: 13px;
            text-decoration: none;
            margin-right: 12px;
            font-weight: 500;
        }

        .action-links a.edit {
            color: #3b82f6;
        }

        .action-links a.delete {
            color: #ef4444;
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
                <a href="stok.php" class="active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    Stok Bahan
                </a>
            </li>
            <li>
                <a href="../pembelian/input.php">
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
            <h1>Kelola Stok Bahan</h1>
            <p>Manajemen data master dan pantau ketersediaan bahan baku</p>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Form Tambah/Edit -->
        <div class="form-container">
            <h3 style="margin-bottom: 20px; font-size: 16px; color: #374151;">
                <?= $editData ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru' ?>
            </h3>
            
            <form method="POST" action="stok.php">
                <input type="hidden" name="id_bahan" value="<?= $editData['id_bahan'] ?? '' ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="nama_bahan">Nama Bahan Baku</label>
                        <input type="text" id="nama_bahan" name="nama_bahan" class="form-control" value="<?= $editData['nama_bahan'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="satuan">Satuan</label>
                        <input type="text" id="satuan" name="satuan" class="form-control" placeholder="Contoh: kg, liter, pcs" value="<?= $editData['satuan'] ?? '' ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="stok">Stok Awal / Saat Ini</label>
                        <input type="number" id="stok" name="stok" class="form-control" value="<?= $editData['stok'] ?? '0' ?>" required>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-primary">
                        <?= $editData ? 'Simpan Perubahan' : 'Simpan Bahan Baku' ?>
                    </button>
                    <?php if($editData): ?>
                        <a href="stok.php" style="margin-left: 12px; color: #4b5563; text-decoration: none; font-size: 14px;">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Stok -->
        <div class="table-section">
            <h3>Daftar Stok Bahan Baku</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Bahan</th>
                        <th>Satuan</th>
                        <th>Stok Saat Ini</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qBahan) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($qBahan)): 
                            $is_rendah = false;
                            $nama_lower = strtolower($row['nama_bahan']);
                            if ($row['stok'] <= 200 && strpos($nama_lower, 'biang') === false) {
                                $is_rendah = true;
                            } else if ($row['stok'] == 0) {
                                $is_rendah = true;
                            }
                        ?>
                            <tr>
                                <td>B-<?= str_pad($row['id_bahan'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                <td><?= htmlspecialchars($row['satuan']) ?></td>
                                <td style="font-weight: 500; font-size: 16px;"><?= number_format($row['stok']) ?></td>
                                <td>
                                    <?php if (!$is_rendah): ?>
                                        <span class="badge success">Aman</span>
                                    <?php else: ?>
                                        <span class="badge warning">Rendah</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-links">
                                    <a href="?edit=<?= $row['id_bahan'] ?>" class="edit">Edit</a>
                                    <a href="?delete=<?= $row['id_bahan'] ?>" class="delete" onclick="return confirm('Yakin ingin menghapus bahan baku ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9ca3af; padding: 30px;">
                                Belum ada data bahan baku.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
