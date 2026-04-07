<?php
// includes/sidebar_owner.php
global $conn;
// Hitung stok minimum untuk badge
$badge_min = 0;
if (isset($conn)) {
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bahan_baku WHERE stok <= stok_minimum AND is_active = 1");
    if ($r) $badge_min = mysqli_fetch_assoc($r)['c'] ?? 0;
}
$cur = basename($_SERVER['SCRIPT_NAME']);
$dir = basename(dirname($_SERVER['SCRIPT_NAME']));
function ownerActive(string $d, string $f = ''): string {
    global $dir, $cur;
    if ($f) return ($dir === $d && $cur === $f) ? 'active' : '';
    return ($dir === $d) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">K</div>
        <div>
            <div class="brand-name">Kembang Tahu</div>
            <div class="brand-sub">Owner Portal</div>
        </div>
    </div>

    <p class="sidebar-section">Utama</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/dashboard/owner.php" class="<?= ownerActive('dashboard','owner.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a></li>
    </ul>

    <p class="sidebar-section">Master Data</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/bahan_baku/owner.php" class="<?= ownerActive('bahan_baku') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Bahan Baku
        </a></li>
        <li><a href="/si-ud-kembang-tahu/produk/owner.php" class="<?= ownerActive('produk') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg>
            Produk Jadi
        </a></li>
        <li><a href="/si-ud-kembang-tahu/supplier/owner.php" class="<?= ownerActive('supplier') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Supplier
        </a></li>
        <li><a href="/si-ud-kembang-tahu/bom/owner.php" class="<?= ownerActive('bom') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1" ry="1"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
            Bill of Material
        </a></li>
        <li><a href="/si-ud-kembang-tahu/user/owner.php" class="<?= ownerActive('user') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
            Manajemen User
        </a></li>
    </ul>

    <p class="sidebar-section">Transaksi</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/pembelian/owner.php" class="<?= ownerActive('pembelian') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Pembelian
        </a></li>
        <li><a href="/si-ud-kembang-tahu/produksi/owner.php" class="<?= ownerActive('produksi') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Produksi
        </a></li>
    </ul>

    <p class="sidebar-section">Persediaan</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/gudang/owner.php" class="<?= ownerActive('gudang') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            Persediaan
            <?php if($badge_min > 0): ?>
                <span class="badge-count"><?= $badge_min ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="/si-ud-kembang-tahu/stock_opname/owner.php" class="<?= ownerActive('stock_opname') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Stock Opname
        </a></li>
        <li><a href="/si-ud-kembang-tahu/hpp/owner.php" class="<?= ownerActive('hpp') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            HPP
        </a></li>
    </ul>

    <p class="sidebar-section">Laporan</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/laporan/owner.php" class="<?= ownerActive('laporan') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Semua Laporan
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <div style="padding: 8px 12px; margin-bottom:8px; color: var(--gray-400); font-size: 12px;">
            <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <span style="background:#1e293b;padding:2px 8px;border-radius:20px;margin-left:6px;font-size:10px;color:#94a3b8;">Owner</span>
        </div>
        <a href="/si-ud-kembang-tahu/auth/logout.php">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</div>
