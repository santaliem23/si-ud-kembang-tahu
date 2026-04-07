<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';
requireRole([ROLE_OWNER, ROLE_GUDANG]);

$success = $error = '';
$role = $_SESSION['role'];

// ============================================================
// PROSES SIMPAN PEMBELIAN
// ============================================================
if (isset($_POST['simpan_pembelian'])) {
    $id_supplier = (int)$_POST['id_supplier'];
    $tanggal     = $_POST['tanggal'];
    $id_bahans   = $_POST['id_bahan'] ?? [];
    $jumlahs     = $_POST['jumlah'] ?? [];
    $hargas      = $_POST['harga'] ?? [];

    if (empty($id_bahans) || $id_supplier == 0) {
        $error = "Pilih supplier dan tambahkan minimal 1 bahan baku.";
    } else {
        // Hitung total
        $total = 0;
        foreach ($id_bahans as $k => $id_b) {
            if ($id_b && $jumlahs[$k] > 0) {
                $total += (int)$jumlahs[$k] * (int)$hargas[$k];
            }
        }

        // Insert header pembelian
        $stmt = mysqli_prepare($conn, "INSERT INTO pembelian (tanggal, id_supplier, total) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sii", $tanggal, $id_supplier, $total);
        mysqli_stmt_execute($stmt);
        $id_pembelian = mysqli_insert_id($conn);

        // Insert detail & update stok
        foreach ($id_bahans as $k => $id_b) {
            $id_b  = (int)$id_b;
            $jml   = (int)$jumlahs[$k];
            $harga = (int)$hargas[$k];
            if ($id_b <= 0 || $jml <= 0) continue;

            $subtotal = $jml * $harga;

            // Insert detail_pembelian
            $stmt2 = mysqli_prepare($conn, "INSERT INTO detail_pembelian (id_pembelian, id_bahan, jumlah, harga) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "iiii", $id_pembelian, $id_b, $jml, $harga);
            mysqli_stmt_execute($stmt2);

            // Ambil stok sebelum untuk log
            $stok_before = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok FROM bahan_baku WHERE id_bahan=$id_b"))['stok'];
            $stok_after  = $stok_before + $jml;

            // Update stok bahan baku
            mysqli_query($conn, "UPDATE bahan_baku SET stok = stok + $jml, harga_satuan = $harga WHERE id_bahan = $id_b");

            // Catat ke stok_log
            $ket = "Pembelian #$id_pembelian";
            $id_u = $_SESSION['id_user'];
            $stmt3 = mysqli_prepare($conn,"INSERT INTO stok_log (id_bahan, tipe_item, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, id_referensi, id_user) VALUES (?,?,?,?,?,?,?,?,?)");
            $tipe_item = 'bahan'; $tipe_trx = 'masuk';
            mysqli_stmt_bind_param($stmt3,"isiiiisii",$id_b,$tipe_item,$tipe_trx,$jml,$stok_before,$stok_after,$ket,$id_pembelian,$id_u);
            mysqli_stmt_execute($stmt3);
        }

        $success = "Pembelian berhasil disimpan! Total: Rp " . number_format($total,0,',','.');
        header("Location: " . $_SERVER['PHP_SELF'] . "?berhasil=1&id=" . $id_pembelian);
        exit;
    }
}

if (isset($_GET['berhasil'])) {
    $success = "Pembelian berhasil disimpan!";
}

$qSupplier  = mysqli_query($conn,"SELECT * FROM supplier WHERE is_active=1 ORDER BY nama_supplier");
$qBahan     = mysqli_query($conn,"SELECT id_bahan, nama_bahan, satuan, harga_satuan FROM bahan_baku WHERE is_active=1 ORDER BY nama_bahan");

// Filter riwayat
$filter_dari  = $_GET['dari'] ?? date('Y-m-01');
$filter_sampai= $_GET['sampai'] ?? date('Y-m-d');
$filter_sup   = (int)($_GET['id_supplier'] ?? 0);
$where_riwayat = "WHERE pm.tanggal BETWEEN '$filter_dari' AND '$filter_sampai'";
if ($filter_sup) $where_riwayat .= " AND pm.id_supplier=$filter_sup";

$riwayat = mysqli_query($conn,"SELECT pm.*, s.nama_supplier FROM pembelian pm LEFT JOIN supplier s ON pm.id_supplier=s.id_supplier $where_riwayat ORDER BY pm.id_pembelian DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pembelian Bahan Baku — SI UD Kembang Tahu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/si-ud-kembang-tahu/assets/css/main.css">
</head>
<body>
<?php if($role==ROLE_OWNER) include '../includes/sidebar_owner.php';
      else include '../includes/sidebar_gudang.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>Pembelian Bahan Baku</h1>
        <p>Input transaksi pembelian multi-item. Stok bahan baku akan otomatis bertambah setelah tersimpan.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span><?= $success ?></span></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg><span><?= $error ?></span></div><?php endif; ?>

    <?php if($role != ROLE_OWNER): ?>
    <div class="card">
        <div class="card-title">Form Input Pembelian Baru</div>
        <div class="card-sub">Tambahkan bahan baku sebanyak yang diperlukan dalam satu transaksi</div>
        <form method="POST" id="formPembelian">
            <div class="form-grid-2" style="margin-bottom:16px;">
                <div class="form-group">
                    <label>Supplier <span style="color:#ef4444">*</span></label>
                    <select name="id_supplier" class="form-control" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php while($s=mysqli_fetch_assoc($qSupplier)): ?>
                        <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Pembelian <span style="color:#ef4444">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div style="margin-bottom:12px;font-size:13px;font-weight:600;color:#374151;">Daftar Bahan yang Dibeli</div>
            <div class="table-wrap" style="margin-bottom:12px;">
            <table id="tabelBahan">
                <thead><tr><th>Bahan Baku</th><th>Jumlah</th><th>Harga Satuan (Rp)</th><th>Subtotal</th><th></th></tr></thead>
                <tbody id="rowBahan">
                    <tr class="row-bahan">
                        <td>
                            <select name="id_bahan[]" class="form-control sel-bahan" required>
                                <option value="">-- Pilih Bahan --</option>
                                <?php mysqli_data_seek($qBahan,0); while($b=mysqli_fetch_assoc($qBahan)): ?>
                                <option value="<?= $b['id_bahan'] ?>" data-harga="<?= $b['harga_satuan'] ?>"><?= htmlspecialchars($b['nama_bahan']) ?> (<?= $b['satuan'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td><input type="number" name="jumlah[]" class="form-control inp-jumlah" min="1" placeholder="0" required onchange="hitungSubtotal(this)"></td>
                        <td><input type="number" name="harga[]" class="form-control inp-harga" min="0" placeholder="0" required onchange="hitungSubtotal(this)"></td>
                        <td><span class="subtotal" style="font-weight:600;color:#166534;">Rp 0</span></td>
                        <td><button type="button" class="btn btn-ghost btn-sm" onclick="hapusBaris(this)">✕</button></td>
                    </tr>
                </tbody>
            </table>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <button type="button" class="btn btn-ghost" onclick="tambahBaris()">+ Tambah Bahan</button>
                <div style="display:flex;gap:16px;align-items:center;">
                    <div style="font-size:15px;font-weight:700;color:#0f172a;">Total: <span id="totalFinal" style="color:#16a34a;">Rp 0</span></div>
                    <button type="submit" name="simpan_pembelian" class="btn btn-primary" onclick="return confirm('Simpan transaksi pembelian ini? Stok akan langsung diperbarui.')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Simpan Pembelian
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- FILTER RIWAYAT -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="card-title">Riwayat Pembelian</div>
                <div class="card-sub"><?= mysqli_num_rows($riwayat) ?> transaksi ditemukan</div>
            </div>
            <form method="GET" class="filter-bar" style="margin:0;">
                <div class="form-group"><label style="font-size:12px;">Dari</label><input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>"></div>
                <div class="form-group"><label style="font-size:12px;">Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>"></div>
                <div class="form-group" style="align-self:flex-end;"><button type="submit" class="btn btn-ghost">Filter</button></div>
            </form>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>No</th><th>Tanggal</th><th>Supplier</th><th>Total</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; if(mysqli_num_rows($riwayat)>0): while($r=mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td class="td-muted"><?= $no++ ?></td>
                <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                <td class="td-bold"><?= htmlspecialchars($r['nama_supplier'] ?? '-') ?></td>
                <td style="font-weight:600;color:#166534;">Rp <?= number_format($r['total'],0,',','.') ?></td>
                <td><a href="cetak.php?id=<?= $r['id_pembelian'] ?>" target="_blank" class="btn btn-ghost btn-sm">Cetak</a></td>
            </tr>
            <?php $no++; endwhile; else: ?>
            <tr><td colspan="5" class="td-empty">Tidak ada data pembelian pada periode ini</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
const templateRow = `<tr class="row-bahan">
    <td><select name="id_bahan[]" class="form-control sel-bahan" required>
        <option value="">-- Pilih Bahan --</option>
        <?php mysqli_data_seek($qBahan,0); while($b=mysqli_fetch_assoc($qBahan)): echo '<option value="'.$b['id_bahan'].'" data-harga="'.$b['harga_satuan'].'">'.htmlspecialchars($b['nama_bahan']).' ('.$b['satuan'].')</option>'; endwhile; ?>
    </select></td>
    <td><input type="number" name="jumlah[]" class="form-control inp-jumlah" min="1" placeholder="0" required onchange="hitungSubtotal(this)"></td>
    <td><input type="number" name="harga[]" class="form-control inp-harga" min="0" placeholder="0" required onchange="hitungSubtotal(this)"></td>
    <td><span class="subtotal" style="font-weight:600;color:#166534;">Rp 0</span></td>
    <td><button type="button" class="btn btn-ghost btn-sm" onclick="hapusBaris(this)">✕</button></td></tr>`;

function tambahBaris() {
    document.getElementById('rowBahan').insertAdjacentHTML('beforeend', templateRow);
    // Attach auto-price change event
    const rows = document.querySelectorAll('.sel-bahan');
    const lastSel = rows[rows.length - 1];
    lastSel.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const harga = opt.getAttribute('data-harga') || 0;
        const row = this.closest('tr');
        row.querySelector('.inp-harga').value = harga;
    });
}

function hapusBaris(btn) {
    const rows = document.querySelectorAll('.row-bahan');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    hitungTotal();
}

function hitungSubtotal(el) {
    const row = el.closest('tr');
    const jml = parseFloat(row.querySelector('.inp-jumlah').value) || 0;
    const hrg = parseFloat(row.querySelector('.inp-harga').value) || 0;
    const sub = jml * hrg;
    row.querySelector('.subtotal').textContent = 'Rp ' + sub.toLocaleString('id-ID');
    hitungTotal();
}

function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.row-bahan').forEach(row => {
        const jml = parseFloat(row.querySelector('.inp-jumlah')?.value) || 0;
        const hrg = parseFloat(row.querySelector('.inp-harga')?.value) || 0;
        total += jml * hrg;
    });
    document.getElementById('totalFinal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Auto-fill harga saat pilih bahan
document.querySelectorAll('.sel-bahan').forEach(sel => {
    sel.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const harga = opt.getAttribute('data-harga') || 0;
        const row = this.closest('tr');
        row.querySelector('.inp-harga').value = harga;
    });
});
</script>
</body>
</html>
