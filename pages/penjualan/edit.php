<?php
session_start();
require_once '../../config/database.php';

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// 2. LOGIKA HAK AKSES
// Sesuai request: Owner DAN Karyawan boleh edit penjualan.
// Jadi kita TIDAK memblokir role apapun di sini, selama dia sudah login.

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
    
    $produk_ids = isset($_POST['id_produk']) ? $_POST['id_produk'] : [];
    $qtys       = isset($_POST['jumlah']) ? $_POST['jumlah'] : [];

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

            // 3. INSERT ULANG & POTONG STOK BARU
            $total_transaksi = 0;
            
            $stmt_detail = $conn->prepare("INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

            for ($i = 0; $i < count($produk_ids); $i++) {
                $pid = $produk_ids[$i];
                $qty = $qtys[$i];

                // Cek Stok & Harga Terbaru
                $res_prod = $conn->query("SELECT harga_jual, stok FROM produk WHERE id_produk = '$pid'");
                $d_prod = $res_prod->fetch_assoc();
                
                // Validasi Stok (Stok Saat Ini + Stok yg baru dikembalikan tadi)
                if ($d_prod['stok'] < $qty) {
                    throw new Exception("Stok tidak cukup untuk produk ID: $pid");
                }

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
            header("Location: index.php?new_nota=$id_penjualan"); 
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
if(!$header) { header("Location: index.php"); exit(); }

// 2. Data Detail Items
$queryDetail = "SELECT dp.*, p.nama_produk, p.harga_jual, p.stok 
                FROM detail_penjualan dp 
                JOIN produk p ON dp.id_produk = p.id_produk 
                WHERE dp.id_penjualan = '$id_penjualan'";
$resDetail = $conn->query($queryDetail);
$details = [];
while ($row = $resDetail->fetch_assoc()) {
    $details[] = $row;
}

// 3. Master Data untuk Dropdown (Select2)
$customers = $conn->query("SELECT * FROM customer ORDER BY nama ASC");
$products = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
$js_products = []; // Simpan ke array JS untuk baris baru
while($p = $products->fetch_assoc()) { $js_products[] = $p; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Penjualan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .select2-container .select2-selection--single { height: 38px !important; padding-top: 4px; }
        .text-brown { color: var(--primary-color) !important; }
    </style>
</head>

<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content p-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-brown">Edit Transaksi #<?php echo str_pad($id_penjualan, 6, '0', STR_PAD_LEFT); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form id="formEdit" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Tanggal</label>
                            <input type="date" name="tgl_penjualan" class="form-control" value="<?php echo $header['tgl_penjualan']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Customer</label>
                            <select name="id_cust" id="selectCustomer" class="form-select">
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
                    <h6 class="fw-bold mb-3"><i class="bi bi-cart"></i> Detail Barang</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="40%">Produk</th>
                                    <th width="20%">Harga</th>
                                    <th width="15%">Qty</th>
                                    <th width="20%">Subtotal</th>
                                    <th width="5%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="containerItems">
                                <?php foreach ($details as $d): ?>
                                    <tr class="item-row">
                                        <td>
                                            <select name="id_produk[]" class="form-select select2-produk" required onchange="updateRow(this)">
                                                <option value="">Pilih Produk</option>
                                                <?php foreach ($js_products as $p): 
                                                    // Stok yg bisa dipakai = Stok Gudang + Stok yang sedang dipakai di transaksi ini
                                                    $stok_tersedia = $p['stok'] + ($p['id_produk'] == $d['id_produk'] ? $d['jumlah'] : 0);
                                                ?>
                                                    <option value="<?php echo $p['id_produk']; ?>"
                                                        data-price="<?php echo $p['harga_jual']; ?>"
                                                        data-stok="<?php echo $stok_tersedia; ?>"
                                                        <?php echo ($p['id_produk'] == $d['id_produk']) ? 'selected' : ''; ?>>
                                                        <?php echo $p['nama_produk']; ?> (Stok: <?php echo $stok_tersedia; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-end bg-light price" value="<?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="number" name="jumlah[]" class="form-control text-center qty" value="<?php echo $d['jumlah']; ?>" min="1" onchange="calcTotal()" onkeyup="calcTotal()" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-end bg-light sub" value="<?php echo number_format($d['sub_total'], 0, ',', '.'); ?>" readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-success btn-sm" onclick="addRow()"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row justify-content-end mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fw-bold">Grand Total</span>
                                <input type="text" id="grandTotal" class="form-control fw-bold text-end bg-light" value="<?php echo number_format($header['total'], 0, ',', '.'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-light border px-4">Kembali</a>
                        <button type="button" id="btnSimpan" class="btn btn-primary fw-bold px-4"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Data Produk dari PHP ke JS (untuk baris baru)
        const products = <?php echo json_encode($js_products); ?>;

        $(document).ready(function() {
            // 1. Init Select2 untuk Customer
            $('#selectCustomer').select2({ theme: 'bootstrap-5', width: '100%' });

            // 2. Init Select2 untuk Produk yang sudah ada (Load Data Lama)
            $('.select2-produk').select2({ theme: 'bootstrap-5', width: '100%' });

            // 3. Event Listener Select2 (Agar harga berubah saat pilih produk)
            $(document).on('select2:select', '.select2-produk', function(e) {
                updateRow(this);
            });
        });

        // --- TAMBAH BARIS BARU ---
        function addRow() {
            let options = '<option value="">Pilih Produk</option>';
            products.forEach(p => {
                options += `<option value="${p.id_produk}" data-price="${p.harga_jual}" data-stok="${p.stok}">${p.nama_produk} (Stok: ${p.stok})</option>`;
            });

            const tr = `
                <tr class="item-row">
                    <td>
                        <select name="id_produk[]" class="form-select select2-produk" required>${options}</select>
                    </td>
                    <td><input type="text" class="form-control text-end bg-light price" readonly placeholder="0"></td>
                    <td><input type="number" name="jumlah[]" class="form-control text-center qty" value="1" min="1" onchange="calcTotal()" onkeyup="calcTotal()" required></td>
                    <td><input type="text" class="form-control text-end bg-light sub" readonly placeholder="0"></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            
            $('#containerItems').append(tr);
            
            // Init Select2 di baris baru
            $('.select2-produk:last').select2({ theme: 'bootstrap-5', width: '100%' });
        }

        // --- UPDATE DATA BARIS (Harga & Max Qty) ---
        function updateRow(el) {
            const row = $(el).closest('tr');
            const selectedOption = $(el).find(':selected');
            
            const price = parseInt(selectedOption.attr('data-price') || 0);
            const stok = parseInt(selectedOption.attr('data-stok') || 0);

            row.find('.price').val(price.toLocaleString('id-ID'));
            row.find('.qty').attr('max', stok); // Cegah input lebih dari stok
            
            calcTotal();
        }

        // --- HITUNG TOTAL ---
        function calcTotal() {
            let total = 0;
            $('.item-row').each(function() {
                const row = $(this);
                const price = parseInt(row.find('.price').val().replace(/\./g, '') || 0);
                const qty = parseInt(row.find('.qty').val() || 0);
                const sub = price * qty;

                row.find('.sub').val(sub.toLocaleString('id-ID'));
                total += sub;
            });
            $('#grandTotal').val("Rp " + total.toLocaleString('id-ID'));
        }

        // --- HAPUS BARIS ---
        function removeRow(btn) {
            if ($('.item-row').length > 1) {
                $(btn).closest('tr').remove();
                calcTotal();
            } else {
                Swal.fire('Info', 'Minimal harus ada 1 barang.', 'info');
            }
        }

        // --- SIMPAN DENGAN SWEETALERT ---
        $('#btnSimpan').on('click', function() {
            Swal.fire({
                title: 'Simpan Perubahan?',
                text: "Stok akan disesuaikan otomatis.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8B4513',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#formEdit').submit();
                }
            });
        });

        // --- LOGOUT SWEETALERT ---
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); const href = this.getAttribute('href');
            Swal.fire({ title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Keluar' }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });
    </script>
</body>
</html>