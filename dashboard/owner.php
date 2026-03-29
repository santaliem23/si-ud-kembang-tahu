<?php
session_start();

// =====================
// CEK ROLE (OWNER = 1)
// =====================
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/database.php';

// =====================
// FILTER TANGGAL
// =====================
$awal  = $_GET['awal'] ?? '';
$akhir = $_GET['akhir'] ?? '';

$where = "";
if (!empty($awal) && !empty($akhir)) {
    $where = "WHERE tanggal BETWEEN '$awal' AND '$akhir'";
}

// =====================
// TOTAL PRODUKSI
// =====================
$q1 = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM produksi $where");
$d1 = mysqli_fetch_assoc($q1);
$total_produksi = $d1['total'] ?? 0;

// =====================
// TOTAL PEMBELIAN
// =====================
$q2 = mysqli_query($conn, "SELECT SUM(total) as total FROM pembelian $where");
$d2 = mysqli_fetch_assoc($q2);
$total_pembelian = $d2['total'] ?? 0;

// =====================
// TOTAL STOK (TIDAK DIFILTER)
// =====================
$q3 = mysqli_query($conn, "SELECT SUM(stok) as total FROM produk");
$d3 = mysqli_fetch_assoc($q3);
$total_stok = $d3['total'] ?? 0;

// =====================
// CHART PRODUKSI (FULL 12 BULAN)
// =====================
$data_chart = array_fill(1, 12, 0);

$query_chart = mysqli_query($conn, "
    SELECT MONTH(tanggal) as bulan, SUM(jumlah) as total
    FROM produksi
    $where
    GROUP BY MONTH(tanggal)
");

while ($row = mysqli_fetch_assoc($query_chart)) {
    $data_chart[$row['bulan']] = $row['total'];
}

$bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$data_produksi = array_values($data_chart);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Owner</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fa;
}

.sidebar {
    width: 220px;
    height: 100vh;
    background: #ffffff;
    position: fixed;
    top: 0;
    left: 0;
    border-right: 1px solid #ddd;
    padding: 20px;
}

.sidebar h2 {
    color: green;
}

.sidebar a {
    display: block;
    padding: 10px;
    color: #333;
    text-decoration: none;
    margin-top: 10px;
    border-radius: 8px;
}

.sidebar a:hover {
    background: #e8f5e9;
}

.main {
    margin-left: 260px;
    padding: 30px 40px;
}

.card-container {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.card {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.card h4 {
    margin: 0;
    color: #777;
}

.card h2 {
    margin: 10px 0 0;
}

.filter-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

input {
    padding: 8px;
    margin-right: 10px;
}

button {
    padding: 10px 15px;
    background: green;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.chart-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Tofu Pro</h2>
    <a href="#">Dashboard</a>
    <a href="../produksi/index.php">Produksi</a>
    <a href="../gudang/index.php">Gudang</a>
    <a href="../pembelian/index.php">Pembelian</a>
    <a href="../hpp/index.php">HPP</a>
    <a href="../laporan/index.php">Laporan</a>
    <a href="../user/index.php">Manajemen User</a>
    <a href="../auth/logout.php">Logout</a>
</div>

<div class="main">
    <h1>Dashboard Owner</h1>
    <p>Selamat datang, <b><?= $_SESSION['username']; ?></b> 👋</p>

    <!-- CARD -->
    <div class="card-container">
        <div class="card">
            <h4>Total Produksi</h4>
            <h2><?= number_format($total_produksi); ?> pcs</h2>
        </div>
        <div class="card">
            <h4>Total Pembelian</h4>
            <h2>Rp <?= number_format($total_pembelian); ?></h2>
        </div>
        <div class="card">
            <h4>Total Stok</h4>
            <h2><?= number_format($total_stok); ?> pcs</h2>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-box">
        <h3>Filter Laporan</h3>
        <form method="GET">
            <input type="date" name="awal" value="<?= $awal ?>">
            <input type="date" name="akhir" value="<?= $akhir ?>">
            <button type="submit">Tampilkan</button>
        </form>
    </div>

    <!-- CHART -->
    <div class="chart-box">
        <h3>Grafik Produksi Bulanan</h3>
        <canvas id="chartProduksi"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('chartProduksi');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($bulan); ?>,
        datasets: [{
            label: 'Produksi',
            data: <?= json_encode($data_produksi); ?>,
            borderColor: 'green',
            fill: false,
            tension: 0.4
        }]
    },
    options: {
        responsive: true
    }
});
</script>

</body>
</html>