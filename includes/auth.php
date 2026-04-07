<?php
// includes/auth.php — Helper untuk proteksi halaman & cek role

function requireRole(int|array $role): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['id_user'])) {
        header("Location: " . getBaseUrl() . "/auth/login.php");
        exit;
    }
    $roles = is_array($role) ? $role : [$role];
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        echo '<div style="font-family:sans-serif;text-align:center;padding:60px;">
            <h2>403 — Akses Ditolak</h2>
            <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <a href="javascript:history.back()">Kembali</a></div>';
        exit;
    }
}

function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['id_user'])) {
        header("Location: " . getBaseUrl() . "/auth/login.php");
        exit;
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['id_user'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? 0,
        'role_name'=> $_SESSION['role_name'] ?? '',
    ];
}

function getBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // Calculate depth from project root
    $depth = substr_count(str_replace('/si-ud-kembang-tahu', '', $script), '/');
    return '/si-ud-kembang-tahu';
}

function baseUrl(string $path = ''): string {
    return '/si-ud-kembang-tahu/' . ltrim($path, '/');
}

// Role constants
define('ROLE_OWNER',    1);
define('ROLE_PRODUKSI', 2);
define('ROLE_GUDANG',   3);
