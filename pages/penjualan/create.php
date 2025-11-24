<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$error = '';

// --- PROSES SIMPAN ---
if (isset($_POST['simpan_transaksi'])) {
    $id_cust = clean_input($_POST['id_cust']);
    $tgl     = clean_input($_POST['tgl_penjualan']);
    $produk_ids = $_POST['id_produk']; 
    $qtys       = $_POST['jumlah'];    
    
    if (empty($id_cust) || empty($produk_ids)) {
        $error = "Data tidak lengkap!";
    } else {
        $conn->begin_transaction();
        try {
            $grand_total = 0;
            $items_fix = [];

            for ($i = 0; $i < count($produk_ids); $i++) {
                $pid = $produk_ids[$i];
                $qty = $qtys[$i];

                if(!empty($pid) && $qty > 0) {
                    $cek = $conn->query("SELECT harga_jual, stok FROM produk WHERE id_produk = $pid")->fetch_assoc();
                    if ($cek['stok'] < $qty) throw new Exception("Stok kurang utk produk ID: $pid");

                    $sub = $cek['harga_jual'] * $qty;
                    $grand_total += $sub;
                    $items_fix[] = ['id' => $pid, 'qty' => $qty, 'harga' => $cek['harga_jual'], 'sub' => $sub];
                }
            }

            if (empty($items_fix)) throw new Exception("Belum ada produk dipilih.");

            $stmt = $conn->prepare("INSERT INTO penjualan (id_cust, tgl_penjualan, total) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $id_cust, $tgl, $grand_total);
            $stmt->execute();
            $id_penjualan = $conn->insert_id;

            $stmt_detail = $conn->prepare("INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

            foreach ($items_fix as $item) {
                $stmt_detail->bind_param("iiidd", $id_penjualan, $item['id'], $item['qty'], $item['harga'], $item['sub']);
                $stmt_detail->execute();
                $stmt_stok->bind_param("ii", $item['qty'], $item['id']);
                $stmt_stok->execute();
            }

            $conn->commit();
            header("Location: nota.php?id=" . $id_penjualan);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$customers = $conn->query("SELECT * FROM customer ORDER BY nama ASC");
$products = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");
$js_products = [];
while($p = $products->fetch_assoc()) { $js_products[] = $p; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        .sidebar { width: 260px; position: fixed; top: 0; left: 0; bottom: 0; transition: transform 0.3s ease-in-out; z-index: 1050; }
        .main-content { margin-left: 260px; transition: margin-left 0.3s ease-in-out; }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1040; cursor: pointer; }
        #mobile-toggle { display: none; }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            #mobile-toggle { display: block; font-size: 1.5rem; cursor: pointer; margin-right: 15px; color: var(--primary-color); }
            body.show-sidebar .sidebar { transform: translateX(0); }
            body.show-sidebar .overlay { display: block; }
        }
        
        /* Override Text Color jadi Coklat */
        .text-brown { color: var(--primary-color) !important; }
    </style>
</head>
<body>
    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🍪</div>
            <div class="logo-text" style="margin-left: 10px;">
                <h5 style="margin:0; font-size:16px;">Dewi Cookies</h5>
            </div>
        </div>
        
        <div class="sidebar-nav">
             <div class="nav-section-title">Main Menu</div>
             <a href="../dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
             
             <div class="nav-section-title">Master Data</div>
             <a href="../supplier/index.php" class="nav-link"><i class="bi bi-building"></i> <span>Supplier</span></a>
             <a href="../customer/index.php" class="nav-link"><i class="bi bi-people"></i> <span>Customer</span></a>

             <div class="nav-section-title">Inventory</div>
             <a href="../bahan-baku/index.php" class="nav-link"><i class="bi bi-box-seam"></i> <span>Bahan Baku</span></a>
             <a href="../produk/index.php" class="nav-link"><i class="bi bi-grid"></i> <span>Produk</span></a>
             <a href="../resep/index.php" class="nav-link"><i class="bi bi-journal-text"></i> <span>Resep</span></a>

             <div class="nav-section-title">Transaksi</div>
             <a href="../pembelian/index.php" class="nav-link"><i class="bi bi-cart-plus"></i> <span>Pembelian</span></a>
             <a href="index.php" class="nav-link active"><i class="bi bi-cash-coin"></i> <span>Penjualan</span></a>

             <div class="nav-section-title">Reports</div>
             <a href="../laporan/index.php" class="nav-link"><i class="bi bi-graph-up"></i> <span>Laporan</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list" id="mobile-toggle" onclick="toggleSidebar()"></i>
                <div class="page-title"><h4 class="m-0">Transaksi Baru</h4></div>
            </div>
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info"><span class="name"><?php echo $_SESSION['nama_lengkap']; ?></span></div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?></div>
                </div>
                <div class="dropdown-menu-custom">
                    <a href="../../logout.php" class="dropdown-item-custom logout" onclick="return confirm('Yakin ingin keluar?')">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <form method="POST" action="">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="content-box h-100">
                            <h5 class="fw-bold mb-3 text-brown">Pelanggan</h5>
                            <div class="mb-3">
                                <label>Tanggal</label>
                                <input type="date" name="tgl_penjualan" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Customer</label>
                                <select name="id_cust" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id_cust']; ?>"><?php echo $c['nama']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="content-box h-100">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="fw-bold text-brown">Keranjang</h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRow()">+ Baris</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr><th width="40%">Produk</th><th width="20%">Harga</th><th width="15%">Qty</th><th width="20%">Subtotal</th><th></th></tr>
                                    </thead>
                                    <tbody id="cartBody"></tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold pt-3">TOTAL:</td>
                                            <td colspan="2" class="pt-3"><h5 class="fw-bold text-success" id="grandTotal">Rp 0</h5></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="index.php" class="btn btn-light border">Batal</a>
                                <button type="submit" name="simpan_transaksi" class="btn btn-primary" onclick="return confirm('Simpan?')">Simpan & Cetak</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const products = <?php echo json_encode($js_products); ?>;

        function addRow() {
            const tr = document.createElement('tr');
            let options = '<option value="">-- Pilih --</option>';
            products.forEach(p => options += `<option value="${p.id_produk}" data-price="${p.harga_jual}" data-stok="${p.stok}">${p.nama_produk} (Stok: ${p.stok})</option>`);
            tr.innerHTML = `<td><select name="id_produk[]" class="form-select" required onchange="updateRow(this)">${options}</select></td>
                            <td><input type="text" class="form-control bg-light price" readonly></td>
                            <td><input type="number" name="jumlah[]" class="form-control qty" min="1" value="1" onchange="calcTotal()" onkeyup="calcTotal()" required></td>
                            <td><input type="text" class="form-control bg-light sub" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();calcTotal()">x</button></td>`;
            document.getElementById('cartBody').appendChild(tr);
        }

        function updateRow(el) {
            const row = el.closest('tr');
            const opt = el.options[el.selectedIndex];
            row.querySelector('.price').value = parseInt(opt.getAttribute('data-price')||0).toLocaleString('id-ID');
            row.querySelector('.qty').setAttribute('max', opt.getAttribute('data-stok')||0);
            calcTotal();
        }

        function calcTotal() {
            let total = 0;
            document.querySelectorAll('#cartBody tr').forEach(r => {
                const p = parseInt(r.querySelector('.price').value.replace(/\./g,'')||0);
                const q = r.querySelector('.qty').value||0;
                const sub = p*q;
                r.querySelector('.sub').value = 'Rp '+sub.toLocaleString('id-ID');
                total += sub;
            });
            document.getElementById('grandTotal').innerText = 'Rp '+total.toLocaleString('id-ID');
        }

        function toggleSidebar() {
            document.body.classList.toggle('show-sidebar');
        }

        document.addEventListener('DOMContentLoaded', addRow);
    </script>
</body>
</html>