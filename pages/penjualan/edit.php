<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_penjualan = clean_input($_GET['id']);
$error = '';
$success = '';

// --- PROSES SIMPAN DATA (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cust = clean_input($_POST['id_cust']);
    $tgl = clean_input($_POST['tgl_penjualan']);
    $produk_ids = $_POST['id_produk']; // Array
    $qtys = $_POST['jumlah']; // Array

    if (empty($produk_ids) || count($produk_ids) == 0) {
        $error = "Harap masukkan minimal 1 barang.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. KEMBALIKAN STOK LAMA (Rollback Stock)
            $old_items = $conn->query("SELECT id_produk, jumlah FROM detail_penjualan WHERE id_penjualan = '$id_penjualan'");
            while ($old = $old_items->fetch_assoc()) {
                $conn->query("UPDATE produk SET stok = stok + {$old['jumlah']} WHERE id_produk = {$old['id_produk']}");
            }

            // 2. HAPUS DETAIL LAMA
            $conn->query("DELETE FROM detail_penjualan WHERE id_penjualan = '$id_penjualan'");

            // 3. HITUNG TOTAL BARU & UPDATE HEADER
            $total_transaksi = 0;
            // Kita butuh harga saat ini untuk perhitungan (atau bisa input manual jika ada fitur ubah harga)
            // Di sini kita ambil harga dari database produk master

            // Siapkan statement untuk insert detail baru
            $stmt_detail = $conn->prepare("INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

            for ($i = 0; $i < count($produk_ids); $i++) {
                $pid = $produk_ids[$i];
                $qty = $qtys[$i];

                // Ambil harga terbaru (atau bisa pakai logika harga lama jika tidak ingin berubah)
                $res_prod = $conn->query("SELECT harga_jual FROM produk WHERE id_produk = '$pid'");
                $d_prod = $res_prod->fetch_assoc();
                $harga = $d_prod['harga_jual'];
                $subtotal = $harga * $qty;
                $total_transaksi += $subtotal;

                // Insert Detail Baru
                $stmt_detail->bind_param("iiidd", $id_penjualan, $pid, $qty, $harga, $subtotal);
                $stmt_detail->execute();

                // Potong Stok Baru
                $stmt_update_stok->bind_param("ii", $qty, $pid);
                $stmt_update_stok->execute();
            }

            // Update Header Penjualan
            $stmt_header = $conn->prepare("UPDATE penjualan SET id_cust=?, tgl_penjualan=?, total=? WHERE id_penjualan=?");
            $stmt_header->bind_param("isdi", $id_cust, $tgl, $total_transaksi, $id_penjualan);
            $stmt_header->execute();

            $conn->commit();
            header("Location: index.php?new_nota=$id_penjualan"); // Redirect biar refresh
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal mengupdate: " . $e->getMessage();
        }
    }
}

// --- AMBIL DATA LAMA (GET) ---
// 1. Data Header
$queryHeader = "SELECT * FROM penjualan WHERE id_penjualan = '$id_penjualan'";
$header = $conn->query($queryHeader)->fetch_assoc();

// 2. Data Detail Items
$queryDetail = "SELECT dp.*, p.nama_produk, p.harga_jual 
                FROM detail_penjualan dp 
                JOIN produk p ON dp.id_produk = p.id_produk 
                WHERE dp.id_penjualan = '$id_penjualan'";
$resDetail = $conn->query($queryDetail);
$details = [];
while ($row = $resDetail->fetch_assoc()) {
    $details[] = $row;
}

// 3. Master Data untuk Dropdown
$customers = $conn->query("SELECT * FROM customer ORDER BY nama ASC");
$products = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .bg-brown {
            background-color: #8B4513;
            color: white;
        }
    </style>
</head>

<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content p-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-primary">Edit Transaksi #<?php echo str_pad($id_penjualan, 6, '0', STR_PAD_LEFT); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tgl_penjualan" class="form-control" value="<?php echo $header['tgl_penjualan']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="id_cust" class="form-select">
                                <option value="">-- Umum --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['id_cust']; ?>" <?php echo ($header['id_cust'] == $c['id_cust']) ? 'selected' : ''; ?>>
                                        <?php echo $c['nama']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6>Detail Barang</h6>
                    <table class="table table-bordered" id="tableItems">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th width="150">Harga</th>
                                <th width="100">Qty</th>
                                <th width="200">Subtotal</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="containerItems">
                            <?php foreach ($details as $index => $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <select name="id_produk[]" class="form-select select-produk" onchange="updatePrice(this)" required>
                                            <option value="">Pilih Produk</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?php echo $p['id_produk']; ?>"
                                                    data-harga="<?php echo $p['harga_jual']; ?>"
                                                    <?php echo ($p['id_produk'] == $item['id_produk']) ? 'selected' : ''; ?>>
                                                    <?php echo $p['nama_produk']; ?> (Stok: <?php echo $p['stok'] + $item['jumlah']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-harga-view" value="<?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>" readonly>
                                        <input type="hidden" class="input-harga" value="<?php echo $item['harga_satuan']; ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="jumlah[]" class="form-control input-qty" value="<?php echo $item['jumlah']; ?>" min="1" onchange="hitungSubtotal(this)" onkeyup="hitungSubtotal(this)" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-subtotal-view" value="<?php echo number_format($item['sub_total'], 0, ',', '.'); ?>" readonly>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-success btn-sm mb-3" onclick="tambahBaris()"><i class="bi bi-plus-lg"></i> Tambah Baris</button>

                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fw-bold">Grand Total</span>
                                <input type="text" id="grandTotal" class="form-control fw-bold text-end" value="<?php echo number_format($header['total'], 0, ',', '.'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Data Produk Master (Untuk baris baru)
        const productsData = `
            <option value="">Pilih Produk</option>
            <?php foreach ($products as $p): ?>
                <option value="<?php echo $p['id_produk']; ?>" data-harga="<?php echo $p['harga_jual']; ?>">
                    <?php echo addslashes($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>)
                </option>
            <?php endforeach; ?>
        `;

        function tambahBaris() {
            const tr = `
            <tr class="item-row">
                <td>
                    <select name="id_produk[]" class="form-select select-produk" onchange="updatePrice(this)" required>
                        ${productsData}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control input-harga-view" readonly>
                    <input type="hidden" class="input-harga" value="0">
                </td>
                <td>
                    <input type="number" name="jumlah[]" class="form-control input-qty" value="1" min="1" onchange="hitungSubtotal(this)" onkeyup="hitungSubtotal(this)" required>
                </td>
                <td><input type="text" class="form-control input-subtotal-view" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)"><i class="bi bi-trash"></i></button></td>
            </tr>`;
            document.getElementById('containerItems').insertAdjacentHTML('beforeend', tr);
        }

        function hapusBaris(btn) {
            btn.closest('tr').remove();
            hitungGrandTotal();
        }

        function updatePrice(select) {
            const option = select.options[select.selectedIndex];
            const harga = option.getAttribute('data-harga') || 0;
            const row = select.closest('tr');

            row.querySelector('.input-harga').value = harga;
            row.querySelector('.input-harga-view').value = parseInt(harga).toLocaleString('id-ID');
            hitungSubtotal(select);
        }

        function hitungSubtotal(el) {
            const row = el.closest('tr');
            const harga = row.querySelector('.input-harga').value || 0;
            const qty = row.querySelector('.input-qty').value || 0;
            const subtotal = harga * qty;

            row.querySelector('.input-subtotal-view').value = subtotal.toLocaleString('id-ID');
            hitungGrandTotal();
        }

        function hitungGrandTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const harga = row.querySelector('.input-harga').value || 0;
                const qty = row.querySelector('.input-qty').value || 0;
                total += (harga * qty);
            });
            document.getElementById('grandTotal').value = "Rp " + total.toLocaleString('id-ID');
        }
    </script>
</body>

</html>