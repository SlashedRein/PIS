<?php
// pages/pembelian/struk.php
include '../../config/database.php';

// 1. Cek ID
if (!isset($_GET['id'])) {
    die("ID Transaksi tidak ditemukan.");
}
$id_beli = mysqli_real_escape_string($conn, $_GET['id']);

// 2. Ambil Data Header (Join Supplier)
$queryHeader = "SELECT p.*, s.nama AS nama_supplier, s.alamat AS alamat_supplier, s.no_telp AS telp_supplier, s.no_rek 
                FROM pembelian p 
                LEFT JOIN supplier s ON p.id_supp = s.id_supp 
                WHERE p.id_beli = '$id_beli'";
$resultHeader = mysqli_query($conn, $queryHeader);
$header = mysqli_fetch_assoc($resultHeader);

if (!$header) {
    die("Data tidak ditemukan.");
}

// 3. Ambil Detail Barang
$queryDetail = "SELECT dp.*, b.nama_bahan, b.satuan 
                FROM detail_pembelian dp 
                JOIN bahan_baku b ON dp.id_bahan = b.id_bahan 
                WHERE dp.id_beli = '$id_beli'";
$resultDetail = mysqli_query($conn, $queryDetail);

// --- CONFIG HALAMAN ---
$toko_kita = "DEWI COOKIES (Cikarang)";
$alamat_kita = "Perum Telaga Murni Jl.Mangga 2 Block C8 No.18";
$telp_kita = "0812 9631 5967";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice Pembelian #<?php echo $id_beli; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 20px;
            background: #f0f0f0;
            /* Background abu saat preview */
        }

        /* Area Kertas A4 */
        .invoice-box {
            background: #fff;
            max-width: 210mm;
            /* Lebar A4 */
            margin: 0 auto;
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            min-height: 297mm;
            /* Tinggi A4 */
            position: relative;
        }

        /* HEADER ATAS */
        .header-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .header-left h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header-left p {
            margin: 2px 0;
        }

        /* KOTAK KANAN ATAS (DATE, DUE, TOP) */
        .header-right-box {
            border: 1px solid #000;
            width: 300px;
        }

        .box-row {
            display: flex;
            border-bottom: 1px solid #000;
        }

        .box-row:last-child {
            border-bottom: none;
        }

        .box-col {
            flex: 1;
            padding: 5px;
            text-align: center;
            border-right: 1px solid #000;
            font-weight: bold;
            font-size: 11px;
        }

        .box-col:last-child {
            border-right: none;
        }

        .box-val {
            font-weight: normal;
        }

        /* KEPADA (TO) */
        .to-section {
            margin-bottom: 20px;
        }

        .to-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* TABEL UTAMA */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 10px;
        }

        table.main-table th {
            border: 1px solid #000;
            background: #e0e0e0;
            padding: 8px;
            text-align: center;
            font-size: 11px;
        }

        table.main-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* FOOTER INFO */
        .footer-total {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* TANDA TANGAN 3 KOLOM */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            text-align: center;
        }

        .sig-box {
            width: 30%;
        }

        .sig-space {
            height: 80px;
        }

        .sig-line {
            border-top: 1px dotted #000;
            margin-top: 5px;
            display: inline-block;
            width: 80%;
        }

        /* DISCLAIMER BAWAH */
        .disclaimer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            font-style: italic;
        }

        /* INFO REKENING KIRI BAWAH */
        .bank-info {
            margin-top: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        /* PRINT SETTINGS */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .invoice-box {
                box-shadow: none;
                padding: 0;
                margin: 0;
                border: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-box">

        <div class="header-container">
            <div class="header-left">
                <h2><?php echo strtoupper($header['nama_supplier']); ?></h2>
                <p>Supplier Bahan Kue & Roti</p>
                <p><?php echo $header['alamat_supplier'] ? $header['alamat_supplier'] : '-'; ?></p>
                <p>Wa. <?php echo $header['telp_supplier'] ? $header['telp_supplier'] : '-'; ?></p>
            </div>

            <div class="header-right-box">
                <div class="box-row">
                    <div class="box-col">DATE</div>
                    <div class="box-col">DUE</div>
                    <div class="box-col">TOP</div>
                </div>
                <div class="box-row">
                    <div class="box-col box-val"><?php echo date('d M Y', strtotime($header['tgl'])); ?></div>
                    <div class="box-col box-val"><?php echo date('d M Y', strtotime($header['tgl'])); ?></div>
                    <div class="box-col box-val">0 Days</div>
                </div>
            </div>
        </div>

        <div class="to-section">
            <div class="to-label">To:</div>
            <strong><?php echo $toko_kita; ?></strong><br>
            <?php echo $alamat_kita; ?><br>
            <?php echo $telp_kita; ?>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 45%; text-align: left;">NAME</th>
                    <th style="width: 10%;">QTY</th>
                    <th style="width: 20%; text-align: right;">PRICE (RP)</th>
                    <th style="width: 20%; text-align: right;">TOTAL (RP)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $grand_qty = 0;
                while ($row = mysqli_fetch_assoc($resultDetail)):
                    $grand_qty += $row['jumlah'];
                ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo $row['nama_bahan']; ?></td>
                        <td class="text-center"><?php echo $row['jumlah']; ?></td>
                        <td class="text-right"><?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                        <td class="text-right"><?php echo number_format($row['sub_total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>

                <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="footer-total">
            Total Qty : <?php echo $grand_qty; ?> &nbsp;&nbsp;|&nbsp;&nbsp; Amount : Rp <?php echo number_format($header['total_beli'], 0, ',', '.'); ?>
        </div>

        <div style="font-weight: bold; margin-bottom: 20px;">
            Remaining : <?php echo number_format($header['total_beli'], 0, ',', '.'); ?>
        </div>

        <div class="signature-section">
            <div class="sig-box">
                Penerima (Cap+TTD)<br>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                Pembawa/Pengemudi :<br>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                Mengetahui (SPV+ADM)<br>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
            </div>
        </div>

        <div class="disclaimer">
            Periksa kembali uang kembalian & barang anda.<br>
            barang yang sudah dibeli tidak dapat ditukar/dikembalikan<br>
            Terimakasih,sehat selalu, Amin.
        </div>

        <div class="bank-info">
            NO REKENING <?php echo strtoupper($header['nama_supplier']); ?><br>
            ATAS NAMA : <?php echo $header['nama_supplier']; ?><br>
            NO REKENING : <?php echo $header['no_rek'] ? $header['no_rek'] : '-'; ?>
        </div>

        <div style="font-size: 10px; margin-top: 10px; font-style: italic;">
            Print by: System at <?php echo date('d M Y H:i'); ?>
        </div>

    </div>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px;">
            🖨️ Cetak Invoice
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; margin-left: 10px;">
            Tutup
        </button>
    </div>

    <script>
    </script>
</body>

</html>