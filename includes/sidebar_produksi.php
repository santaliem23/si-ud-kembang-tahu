<?php
// includes/sidebar_produksi.php
$cur = basename($_SERVER['SCRIPT_NAME']);
$dir = basename(dirname($_SERVER['SCRIPT_NAME']));
function prodActive(string $d, string $f = ''): string {
    global $dir, $cur;
    if ($f) return ($dir === $d && $cur === $f) ? 'active' : '';
    return ($dir === $d) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo" style="background:#7c3aed;">P</div>
        <div>
            <div class="brand-name">Kembang Tahu</div>
            <div class="brand-sub">Admin Produksi</div>
        </div>
    </div>

    <p class="sidebar-section">Utama</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/dashboard/admin_produksi.php" class="<?= prodActive('dashboard','admin_produksi.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a></li>
    </ul>

    <p class="sidebar-section">Produksi</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/produksi/input.php" class="<?= prodActive('produksi','input.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Input Produksi
        </a></li>
        <li><a href="/si-ud-kembang-tahu/produksi/index.php" class="<?= prodActive('produksi','index.php') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Riwayat Produksi
        </a></li>
        <li><a href="/si-ud-kembang-tahu/bom/admin_produksi.php" class="<?= prodActive('bom') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1" ry="1"/></svg>
            Bill of Material
        </a></li>
    </ul>

    <p class="sidebar-section">Laporan</p>
    <ul class="sidebar-menu">
        <li><a href="/si-ud-kembang-tahu/hpp/admin_produksi.php" class="<?= prodActive('hpp') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Perhitungan HPP
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <div style="padding: 8px 12px; margin-bottom:8px; color: var(--gray-400); font-size: 12px;">
            <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <span style="background:#1e293b;padding:2px 8px;border-radius:20px;margin-left:6px;font-size:10px;color:#94a3b8;">Produksi</span>
        </div>
        <a href="/si-ud-kembang-tahu/auth/logout.php">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</div>
