<?php
include "config/database.php";
$tables = ['produksi', 'produk'];
foreach ($tables as $table) {
    if ($res = mysqli_query($conn, "SHOW COLUMNS FROM $table")) {
        echo "$table:\n";
        while($r = mysqli_fetch_assoc($res)) { echo "- {$r['Field']} ({$r['Type']})\n"; }
    }
}
?>