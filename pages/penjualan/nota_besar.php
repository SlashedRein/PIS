<?php
// pages/penjualan/nota_besar.php
include '../../config/database.php';

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}
$id = clean_input($_GET['id']);

// Ambil Data Header
$queryHeader = "SELECT p.*, c.nama AS nama_cust, c.alamat, c.no_telp 
                FROM penjualan p 
                LEFT JOIN customer c ON p.id_cust = c.id_cust 
                WHERE p.id_penjualan = '$id'";
$data = $conn->query($queryHeader)->fetch_assoc();

// Ambil Detail Item
$queryDetail = "SELECT dp.*, prod.nama_produk 
                FROM detail_penjualan dp 
                JOIN produk prod ON dp.id_produk = prod.id_produk 
                WHERE dp.id_penjualan = '$id'";
$details = $conn->query($queryDetail);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Penjualan #<?php echo $id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #555;
        }

        /* Layout Kertas (A5 atau Setengah Kuarto biar pas nota) */
        .nota-box {
            width: 210mm;
            /* A4 width */
            min-height: 140mm;
            /* A5 height approx */
            margin: 0 auto;
            background: #fff;
            position: relative;
        }

        /* Header Logo & Alamat */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .header-left {
            width: 60%;
        }

        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #D4AF37;
            /* Warna Emas */
            font-family: 'Comic Sans MS', cursive, sans-serif;
            /* Font mirip logo */
            margin-bottom: 5px;
        }

        .logo-sub {
            font-size: 14px;
            color: #D4AF37;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .address {
            font-size: 11px;
            line-height: 1.4;
            color: #666;
        }

        /* Header Kanan (Kepada) */
        .header-right {
            width: 35%;
            text-align: left;
            padding-top: 10px;
        }

        .date-line {
            border-bottom: 1px dotted #ccc;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }

        .to-label {
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .to-line {
            border-bottom: 1px dotted #ccc;
            display: block;
            padding-bottom: 2px;
            margin-bottom: 5px;
            min-height: 15px;
        }

        /* No Nota */
        .nota-no {
            font-size: 14px;
            font-weight: bold;
            color: #D4AF37;
            margin-bottom: 5px;
            margin-top: 20px;
        }

        /* Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border: 1px solid #999;
        }

        th {
            background-color: #E8D880;
            /* Background Kuning Header */
            color: #fff;
            padding: 8px;
            border: 1px solid #999;
            text-align: center;
            font-weight: bold;
            color: #555;
            /* Text header abu gelap */
        }

        td {
            padding: 6px 8px;
            border: 1px solid #999;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Footer Total */
        .total-row td {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        /* Tanda Tangan */
        .footer-sig {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 20px;
            text-align: center;
        }

        .sig-box {
            width: 30%;
        }

        .sig-line {
            border-top: 1px dotted #999;
            margin-top: 60px;
        }

        .disclaimer {
            margin-top: 30px;
            font-size: 10px;
            font-style: italic;
            color: #D4AF37;
            font-weight: bold;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="nota-box">
        <div class="header">
            <div class="header-left">
                <div class="logo-text">dewi <span style="color: #444;">Cookies</span></div>
                <div class="logo-sub">Aneka Kue Kering</div>
                <div class="address">
                    Perum Telaga Murni Blok C8 No.18<br>
                    Jln. Mangga II Desa Telaga Murni<br>
                    Kec. Cikarang Barat, Kab. Bekasi - 17520<br><br>
                    HP: 0852 87560 800 | WhatsApp: 0812 9631 6967<br>
                    Email: dewicookies73@gmail.com
                </div>
                <div class="nota-no">Nota No. <?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="header-right">
                <div class="date-line">Cikarang, <?php echo date('d F Y', strtotime($data['tgl_penjualan'])); ?></div>
                <div class="to-label">Kepada Yth,</div>
                <div class="to-line"><?php echo $data['nama_cust'] ? $data['nama_cust'] : '.......................................'; ?></div>
                <div class="to-line"><?php echo $data['alamat'] ? substr($data['alamat'], 0, 30) : '.......................................'; ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">NO.</th>
                    <th width="45%">Nama Menu</th>
                    <th width="20%">Harga Satuan</th>
                    <th width="25%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = $details->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td>
                            <?php echo $row['nama_produk']; ?>
                            <span style="float:right; font-size:10px; color:#666;">(x<?php echo $row['jumlah']; ?>)</span>
                        </td>
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
                    </tr>
                <?php endfor; ?>

                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL Rp.</td>
                    <td class="text-right"><?php echo number_format($data['total'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-sig">
            <div class="sig-box">
                Penerima,
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                Hormat kami,
                <div class="sig-line"></div>
            </div>
        </div>

        <div class="disclaimer">
            Barang yang sudah dibeli tidak dapat ditukar/<br>
            dikembalikan, kecuali ada perjanjian Terima Kasih
        </div>

        <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #D4AF37; color: white; border: none; cursor: pointer; border-radius: 5px;">🖨️ Cetak Nota</button>
        </div>
    </div>

</body>

</html>