<?php
session_start();
require_once '../../config/database.php';

// Cek Login & Owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Akses Ditolak");
}

// Ambil Parameter
$type  = isset($_GET['type']) ? $_GET['type'] : 'harian';
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end   = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');

// Judul Laporan
$title = "LAPORAN KEUANGAN";
if ($type == 'harian') $title = "LAPORAN TRANSAKSI HARIAN (UMUM)";
if ($type == 'mingguan') $title = "LAPORAN TAGIHAN PELANGGAN TETAP";
if ($type == 'bulanan') $title = "LAPORAN REKAPITULASI OMSET BULANAN";

$periode_label = date('d F Y', strtotime($start)) . " s/d " . date('d F Y', strtotime($end));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak <?php echo $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background-color: #eee;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none;
            }

            @page {
                size: A4;
                margin: 2cm;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>DEWI COOKIES</h2>
        <p>Perum Telaga Murni Blok C8 No.18, Cikarang Barat</p>
        <hr>
        <h3><?php echo $title; ?></h3>
        <p>Periode: <?php echo $periode_label; ?></p>
    </div>

    <?php if ($type == 'harian'): ?>
        <?php
        $query = "SELECT p.*, IFNULL(c.nama, 'Umum') as nama_cust 
                  FROM penjualan p LEFT JOIN customer c ON p.id_cust = c.id_cust
                  WHERE p.tgl_penjualan BETWEEN '$start' AND '$end' 
                  AND NOT (IFNULL(c.nama, '') LIKE '%PT%' OR IFNULL(c.nama, '') LIKE '%CV%')
                  ORDER BY p.tgl_penjualan DESC";
        $res = $conn->query($query);
        ?>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>No Nota</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th width="20%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                $total = 0;
                while ($row = $res->fetch_assoc()): $total += $row['total']; ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td class="text-center">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tgl_penjualan'])); ?></td>
                        <td><?php echo $row['nama_cust']; ?></td>
                        <td class="text-right"><?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="4" class="text-right bold">TOTAL PENDAPATAN</td>
                    <td class="text-right bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

    <?php elseif ($type == 'mingguan'): ?>
        <?php
        $query = "SELECT IFNULL(c.nama, 'Tanpa Nama') as nama_cust,
                         MIN(p.tgl_penjualan) as tgl_awal, MAX(p.tgl_penjualan) as tgl_akhir,
                         COUNT(p.id_penjualan) as jml_trx, SUM(p.total) as total
                  FROM penjualan p LEFT JOIN customer c ON p.id_cust = c.id_cust
                  WHERE p.tgl_penjualan BETWEEN '$start' AND '$end' 
                  AND (IFNULL(c.nama, '') LIKE '%PT%' OR IFNULL(c.nama, '') LIKE '%CV%')
                  GROUP BY c.id_cust, YEAR(p.tgl_penjualan), WEEK(p.tgl_penjualan, 1)";
        $res = $conn->query($query);
        ?>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Customer (Perusahaan)</th>
                    <th>Periode Mingguan</th>
                    <th width="10%">Jml Nota</th>
                    <th width="20%">Total Tagihan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                $total = 0;
                while ($row = $res->fetch_assoc()): $total += $row['total']; ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo $row['nama_cust']; ?></td>
                        <td class="text-center"><?php echo date('d/m', strtotime($row['tgl_awal'])) . " - " . date('d/m/Y', strtotime($row['tgl_akhir'])); ?></td>
                        <td class="text-center"><?php echo $row['jml_trx']; ?></td>
                        <td class="text-right"><?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="4" class="text-right bold">TOTAL TAGIHAN</td>
                    <td class="text-right bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

    <?php elseif ($type == 'bulanan'): ?>
        <?php
        $query = "SELECT DATE_FORMAT(tgl_penjualan, '%Y-%m') as periode, COUNT(*) as jml, SUM(total) as omset 
                  FROM penjualan 
                  WHERE tgl_penjualan BETWEEN '$start' AND '$end'
                  GROUP BY periode ORDER BY periode DESC";
        $res = $conn->query($query);
        ?>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Bulan</th>
                    <th>Tahun</th>
                    <th width="15%">Jml Transaksi</th>
                    <th width="25%">Total Omset</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                $total = 0;
                while ($row = $res->fetch_assoc()): $total += $row['omset']; ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo date('F', strtotime($row['periode'])); ?></td>
                        <td class="text-center"><?php echo date('Y', strtotime($row['periode'])); ?></td>
                        <td class="text-center"><?php echo $row['jml']; ?> Nota</td>
                        <td class="text-right"><?php echo number_format($row['omset'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="4" class="text-right bold">GRAND TOTAL OMSET</td>
                    <td class="text-right bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: right;">
        <p>Cikarang, <?php echo date('d F Y'); ?></p>
        <br><br><br>
        <p>( Owner / Admin )</p>
    </div>

    <script>
        window.print();
    </script>
</body>

</html>