<?php
// pages/penjualan/struk.php
include '../../config/database.php';

// 1. Cek ID Transaksi
if (!isset($_GET['id'])) {
    die("ID Transaksi tidak ditemukan.");
}
$id_penjualan = mysqli_real_escape_string($conn, $_GET['id']);

// 2. Ambil Data Header Transaksi
$queryHeader = "SELECT p.*, c.nama AS nama_customer 
                FROM penjualan p 
                LEFT JOIN customer c ON p.id_cust = c.id_cust 
                WHERE p.id_penjualan = '$id_penjualan'";
$resultHeader = mysqli_query($conn, $queryHeader);
$dataHeader = mysqli_fetch_assoc($resultHeader);

if (!$dataHeader) {
    die("Data transaksi tidak ditemukan.");
}

// 3. Ambil Data Detail Barang
$queryDetail = "SELECT dp.*, prod.nama_produk 
                FROM detail_penjualan dp 
                JOIN produk prod ON dp.id_produk = prod.id_produk 
                WHERE dp.id_penjualan = '$id_penjualan'";
$resultDetail = mysqli_query($conn, $queryDetail);

// --- INFO TOKO (Hardcode sesuai gambar Nota) ---
$toko_nama    = "DEWI COOKIES";
$toko_sub     = "Aneka Kue Kering";
$toko_alamat  = "Perum Telaga Murni Blok C8 No.18, Cikarang Barat";
$toko_hp      = "0852-8756-0800";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?php echo $id_penjualan; ?></title>
    <style>
        /* Reset & Base */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Font modern */
            font-size: 10px;
            /* Ukuran standar struk 58mm */
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            color: #000;
        }

        /* Container Struk (Simulasi Kertas 58mm) */
        .struk-container {
            width: 58mm;
            /* Lebar standar printer thermal kecil */
            min-height: 100mm;
            background: #fff;
            margin: 20px auto;
            padding: 10px 5px;
            /* Padding kiri-kanan tipis */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Elemen Desain */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .header {
            margin-bottom: 10px;
        }

        .logo {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .sub-logo {
            font-size: 9px;
            margin-bottom: 5px;
            color: #555;
        }

        .address {
            font-size: 8px;
            color: #333;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .dashed-line {
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }

        /* Info Transaksi */
        .meta-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 2px;
        }

        /* Tabel Barang */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        td {
            vertical-align: top;
            padding: 2px 0;
        }

        .item-name {
            font-size: 10px;
            font-weight: 600;
            padding-bottom: 2px;
            display: block;
            /* Agar nama barang panjang turun ke bawah */
        }

        .item-detail {
            font-size: 9px;
            color: #333;
        }

        .item-price {
            font-size: 10px;
            font-weight: 600;
        }

        /* Total Section */
        .total-section {
            margin-top: 5px;
        }

        .row-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }

        .grand-total {
            font-size: 12px;
            font-weight: 800;
            margin-top: 5px;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #333;
        }

        .footer p {
            margin: 2px 0;
        }

        /* Print Settings */
        @media print {
            body {
                background: #fff;
            }

            .struk-container {
                width: 100%;
                /* Full width saat diprint */
                margin: 0;
                box-shadow: none;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Tombol Print (Floating) */
        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25D366;
            /* Warna WA/Hijau */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            font-family: sans-serif;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="struk-container">
        <div class="header text-center">
            <div class="logo"><?php echo $toko_nama; ?></div>
            <div class="sub-logo"><?php echo $toko_sub; ?></div>
            <div class="address">
                <?php echo $toko_alamat; ?><br>
                WA: <?php echo $toko_hp; ?>
            </div>
        </div>

        <div class="dashed-line"></div>

        <div class="meta-info">
            <span>No: #<?php echo str_pad($id_penjualan, 6, '0', STR_PAD_LEFT); ?></span>
            <span><?php echo date('d/m/y H:i', strtotime($dataHeader['tgl_penjualan'])); ?></span>
        </div>
        <div class="meta-info">
            <span>Cust: <?php echo substr(($dataHeader['nama_customer'] ?: 'Umum'), 0, 15); ?></span>
            <span>Kasir: Admin</span>
        </div>

        <div class="dashed-line"></div>

        <table>
            <?php
            $total_qty = 0;
            while ($row = mysqli_fetch_assoc($resultDetail)) :
                $total_qty += $row['jumlah'];
            ?>
                <tr>
                    <td colspan="2">
                        <span class="item-name"><?php echo $row['nama_produk']; ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="item-detail">
                        <?php echo $row['jumlah']; ?> x <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?>
                    </td>
                    <td class="text-right item-price">
                        <?php echo number_format($row['sub_total'], 0, ',', '.'); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <div class="dashed-line"></div>

        <div class="total-section">
            <div class="row-total">
                <span>Total Item</span>
                <span><?php echo $total_qty; ?></span>
            </div>
            <div class="row-total grand-total">
                <span>TOTAL</span>
                <span>Rp <?php echo number_format($dataHeader['total'], 0, ',', '.'); ?></span>
            </div>
        </div>

        <div class="dashed-line"></div>

        <div class="footer">
            <p class="bold">TERIMA KASIH</p>
            <p>Selamat Menikmati Cookies Kami</p>
            <p>IG: @dewicookies</p>
        </div>

        <br>
        <div class="text-center" style="font-size: 8px;">.</div>
    </div>

    <button class="no-print btn-print" onclick="window.print()">
        🖨️ Cetak
    </button>

    <script>
        // Auto print jika dibuka di popup
        // window.print();
    </script>
</body>

</html>