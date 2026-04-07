-- =============================================
-- MIGRATION: SI UD Kembang Tahu
-- Sesuai Proposal KP Santalie Masli
-- =============================================

-- bahan_baku: tambah kolom yang belum ada
ALTER TABLE bahan_baku
    ADD COLUMN IF NOT EXISTS kode_bahan VARCHAR(20) DEFAULT NULL AFTER id_bahan,
    ADD COLUMN IF NOT EXISTS harga_satuan INT DEFAULT 0 AFTER stok,
    ADD COLUMN IF NOT EXISTS stok_minimum INT DEFAULT 0 AFTER harga_satuan;

-- Auto-generate kode_bahan untuk data lama
UPDATE bahan_baku SET kode_bahan = CONCAT('BB', LPAD(id_bahan, 4, '0')) WHERE kode_bahan IS NULL;

-- produk: tambah kolom yang belum ada
ALTER TABLE produk
    ADD COLUMN IF NOT EXISTS kode_produk VARCHAR(20) DEFAULT NULL AFTER id_produk,
    ADD COLUMN IF NOT EXISTS satuan VARCHAR(50) DEFAULT 'pcs' AFTER nama_produk,
    ADD COLUMN IF NOT EXISTS stok INT DEFAULT 0;

-- Auto-generate kode_produk untuk data lama
UPDATE produk SET kode_produk = CONCAT('PRD', LPAD(id_produk, 4, '0')) WHERE kode_produk IS NULL;

-- supplier: pastikan is_active ada
ALTER TABLE supplier
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- bom: tambah kolom yang dibutuhkan
ALTER TABLE bom
    ADD COLUMN IF NOT EXISTS kode_bom VARCHAR(20) DEFAULT NULL AFTER id_bom,
    ADD COLUMN IF NOT EXISTS tanggal_mulai DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Auto-generate kode_bom untuk data lama
UPDATE bom SET kode_bom = CONCAT('BOM', LPAD(id_bom, 4, '0')) WHERE kode_bom IS NULL;

-- produksi: tambah kolom HPP
ALTER TABLE produksi
    ADD COLUMN IF NOT EXISTS no_batch VARCHAR(30) DEFAULT NULL AFTER id_produksi,
    ADD COLUMN IF NOT EXISTS total_biaya BIGINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS hpp DECIMAL(15,2) DEFAULT 0.00;

-- detail_bom: cek struktur (sudah ada, pastikan jumlah ada)
-- Sudah ada: id_detail_bom, id_bom, id_bahan, jumlah

-- Buat tabel detail_produksi (histori bahan yang dipakai saat produksi)
CREATE TABLE IF NOT EXISTS `detail_produksi` (
    `id_detail_produksi` INT AUTO_INCREMENT PRIMARY KEY,
    `id_produksi` INT NOT NULL,
    `id_bahan` INT NOT NULL,
    `jumlah_pakai` INT NOT NULL DEFAULT 0,
    `harga_satuan` INT NOT NULL DEFAULT 0,
    `subtotal` BIGINT NOT NULL DEFAULT 0,
    FOREIGN KEY (`id_produksi`) REFERENCES `produksi`(`id_produksi`),
    FOREIGN KEY (`id_bahan`) REFERENCES `bahan_baku`(`id_bahan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Buat tabel stok_log (audit trail semua pergerakan stok)
CREATE TABLE IF NOT EXISTS `stok_log` (
    `id_log` INT AUTO_INCREMENT PRIMARY KEY,
    `id_bahan` INT DEFAULT NULL COMMENT 'NULL jika produk',
    `tipe_item` ENUM('bahan','produk') NOT NULL DEFAULT 'bahan',
    `tipe_transaksi` ENUM('masuk','keluar','opname') NOT NULL,
    `jumlah` INT NOT NULL DEFAULT 0,
    `stok_sebelum` INT NOT NULL DEFAULT 0,
    `stok_sesudah` INT NOT NULL DEFAULT 0,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `id_referensi` INT DEFAULT NULL COMMENT 'id_pembelian atau id_produksi atau id_opname',
    `id_user` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Buat tabel stock_opname yang lebih lengkap jika belum ada
CREATE TABLE IF NOT EXISTS `stock_opname` (
    `id_opname` INT AUTO_INCREMENT PRIMARY KEY,
    `id_bahan` INT NOT NULL,
    `stok_sistem` INT NOT NULL DEFAULT 0,
    `stok_fisik` INT NOT NULL DEFAULT 0,
    `selisih` INT NOT NULL DEFAULT 0,
    `alasan` TEXT DEFAULT NULL,
    `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `id_user` INT DEFAULT NULL,
    FOREIGN KEY (`id_bahan`) REFERENCES `bahan_baku`(`id_bahan`),
    FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Drop tabel lama stock_opname jika strukturnya berbeda dan buat ulang
-- (skip jika sudah sesuai)

-- Update stok produk berdasarkan batch_produk yang masih aktif (jika ada)
UPDATE produk p 
SET p.stok = IFNULL((
    SELECT SUM(b.stok_sekarang) FROM batch_produk b WHERE b.id_produk = p.id_produk AND b.stok_sekarang > 0
), 0);
