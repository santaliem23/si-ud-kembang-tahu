<?php
// includes/sidebar_gudang.php
global $conn;
$badge_min = 0;
if (isset($conn)) {
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bahan_baku WHERE stok <= stok_minimum AND is_active = 1");
    if ($r) $badge_min = mysqli_fetch_assoc($r)['c'] ?? 0;
}
$cur = basename($_SERVER['SCRIPT_NAME']);
$dir = basename(dirname($_SERVER['SCRIPT_NAME']));
function gudangActive(string $d, string $f = ''): string {
    global $dir, $cur;
    if ($f) return ($dir === $d && $cur === $f) ? 'active' : '';
    return ($dir === $d) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo" style="background:#0369a1;">G</div>
        <div>
            <div class="brand-name">Kembang Tahu</div>
            <div class="brand-sub">Admin Gudang</div>
        </div>
    </div>

    <p class="sidebar-section">Utama</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/dashboard/admin_gudang.php" class="<?= gudangActive('dashboard','admin_gudang.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a></li>
    </ul>

    <p class="sidebar-section">Master Data</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/bahan_baku/admin_gudang.php" class="<?= gudangActive('bahan_baku') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Bahan Baku
        </a></li>
        <li><a href="/si-ud-kembang-tahu/supplier/admin_gudang.php" class="<?= gudangActive('supplier') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
            Supplier
        </a></li>
    </ul>

    <p class="sidebar-section">Transaksi</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/pembelian/admin_gudang.php" class="<?= gudangActive('pembelian') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Pembelian Bahan
        </a></li>
    </ul>

    <p class="sidebar-section">Persediaan</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/gudang/admin_gudang.php" class="<?= gudangActive('gudang','admin_gudang.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            Monitoring Stok
            <?php if($badge_min > 0): ?>
                <span class="badge-count"><?= $badge_min ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="/si-ud-kembang-tahu/gudang/stok_minimum.php" class="<?= gudangActive('gudang','stok_minimum.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Stok Minimum
            <?php if($badge_min > 0): ?>
                <span class="badge-count" style="background:var(--warning);"><?= $badge_min ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="/si-ud-kembang-tahu/stock_opname/admin_gudang.php" class="<?= gudangActive('stock_opname') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Stock Opname
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <div style="padding: 8px 12px; margin-bottom:8px; color: var(--gray-400); font-size: 12px;">
            <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <span style="background:#1e293b;padding:2px 8px;border-radius:20px;margin-left:6px;font-size:10px;color:#94a3b8;">Gudang</span>
        </div>
        <a href="/si-ud-kembang-tahu/auth/logout.php">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</div>
