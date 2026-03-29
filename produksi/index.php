<?php
session_start();
include '../config/database.php';

$query = mysqli_query($conn, "
    SELECT produksi.*, produk.nama_produk 
    FROM produksi
    JOIN produk ON produksi.id_produk = produk.id_produk
    ORDER BY tanggal DESC
");
?>

<h2>Data Produksi</h2>
<a href="tambah.php">+ Tambah Produksi</a>

<table border="1" cellpadding="10">
    <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Produk</th>
        <th>Jumlah</th>
        <th>Aksi</th>
    </tr>

    <?php $no=1; while($row = mysqli_fetch_assoc($query)) { ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $row['tanggal'] ?></td>
        <td><?= $row['nama_produk'] ?></td>
        <td><?= $row['jumlah'] ?></td>
        <td>
            <a href="hapus.php?id=<?= $row['id_produksi'] ?>" onclick="return confirm('Hapus?')">Hapus</a>
        </td>
    </tr>
    <?php } ?>
</table>