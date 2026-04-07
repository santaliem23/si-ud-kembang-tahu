<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);
$role = $_SESSION['role'];

$items = mysqli_query($conn,"SELECT * FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1 ORDER BY stok ASC");
$total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bahan_baku WHERE stok<=stok_minimum AND is_active=1"))['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Stok Minimum — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>🚨 Daftar Stok Minimum</h1>
        <p>Bahan baku yang mencapai atau melewati batas stok minimum — perlu segera ditangani</p>
    </div>

    <?php if($total == 0): ?>
    <div class="card" style="text-align:center;padding:60px;">
        <div style="font-size:48px;margin-bottom:16px;">✅</div>
        <h2 style="color:#166534;margin-bottom:8px;">Semua Stok Aman!</h2>
        <p style="color:#64748b;">Tidak ada bahan baku yang mencapai batas minimum saat ini.</p>
    </div>
    <?php else: ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <div><strong><?= $total ?> bahan baku</strong> perlu perhatian segera.
        <?php if($role==ROLE_GUDANG): ?> <a href="/si-ud-kembang-tahu/pembelian/admin_gudang.php" style="color:inherit;font-weight:700;">Lakukan Pembelian Sekarang →</a><?php endif; ?></div>
    </div>

    <div class="card">
        <div class="table-wrap">
        <table>
            <thead><tr><th>Nama Bahan</th><th>Satuan</th><th>Stok Saat Ini</th><th>Batas Minimum</th><th>Kekurangan</th><th>Status</th><?php if($role==ROLE_GUDANG): ?><th>Aksi</th><?php endif; ?></tr></thead>
            <tbody>
            <?php while($r=mysqli_fetch_assoc($items)):
                $kurang = max(0, $r['stok_minimum'] - $r['stok']);
                $habis  = $r['stok'] == 0;
            ?>
            <tr class="<?= $habis ? 'stok-habis' : 'stok-kritis' ?>">
                <td class="td-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td>
                <td><?= $r['satuan'] ?></td>
                <td style="font-weight:700;color:<?= $habis?'#b91c1c':'#b45309' ?>;"><?= number_format($r['stok'],0,',','.') ?></td>
                <td class="td-muted"><?= number_format($r['stok_minimum'],0,',','.') ?></td>
                <td style="font-weight:600;color:#b91c1c;"><?= $kurang > 0 ? $kurang.' '.$r['satuan'] : 'Persis minimum' ?></td>
                <td><?= $habis ? '<span class="badge badge-danger">HABIS</span>' : '<span class="badge badge-minimum">MINIMUM</span>' ?></td>
                <?php if($role==ROLE_GUDANG): ?>
                <td><a href="/si-ud-kembang-tahu/pembelian/admin_gudang.php" class="btn btn-primary btn-sm">+ Beli</a></td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
