<?php
session_start();
require_once '../../config/database.php';

// --- [LOGIC 1] AJAX HANDLER: TAMBAH CUSTOMER VIA POP-UP ---
if (isset($_POST['ajax_add_customer'])) {
    header('Content-Type: application/json');
    
    $nama = clean_input($_POST['nama']);
    $telp = clean_input($_POST['telp']);
    $alamat = clean_input($_POST['alamat']);

    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi!']);
    } else {
        $stmt = $conn->prepare("INSERT INTO customer (nama, no_telp, alamat) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $telp, $alamat);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success', 
                'id' => $conn->insert_id, 
                'nama' => $nama
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal simpan database.']);
        }
    }
    exit();
}

// --- [LOGIC 2] PROSES SIMPAN TRANSAKSI ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$error = '';

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
            header("Location: index.php?new_nota=" . $id_penjualan);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Ambil Data Awal
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        .sidebar { width: 260px; position: fixed; top: 0; left: 0; bottom: 0; transition: transform 0.3s ease-in-out; z-index: 1050; }
        .main-content { margin-left: 260px; transition: margin-left 0.3s ease-in-out; }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1040; cursor: pointer; }
        #mobile-toggle, #sidebar-close { display: none; }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            #mobile-toggle { display: block; font-size: 1.5rem; cursor: pointer; margin-right: 15px; color: var(--primary-color); }
            body.show-sidebar .sidebar { transform: translateX(0); }
            body.show-sidebar .overlay { display: block; }
        }

        .text-brown { color: var(--primary-color) !important; }
        
        /* Select2 Tweak */
        .select2-container .select2-selection--single { height: 31px !important; font-size: 0.85rem; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { padding-top: 2px; }
    </style>
</head>
<body>
    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar">
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="logo-icon">🍪</div>
                <div class="logo-text" style="margin-left: 10px;">
                    <h5 style="margin:0; font-size:16px;">Dewi Cookies</h5>
                </div>
            </div>
            <i class="bi bi-x-lg fs-4" id="sidebar-close" onclick="toggleSidebar()"></i>
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
                <div class="content-box mb-3">
                    <div class="row align-items-center g-2">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Tanggal</label>
                            <input type="date" name="tgl_penjualan" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="small text-muted fw-bold">Pelanggan</label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="id_cust" id="selectCustomer" class="form-select" required>
                                        <option value="">-- Cari Nama Customer --</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?php echo $c['id_cust']; ?>"><?php echo $c['nama']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Customer Baru">
                                    <i class="bi bi-person-plus-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-brown m-0"><i class="bi bi-cart3"></i> Keranjang</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem;" onclick="addRow()">
                            <i class="bi bi-plus-lg"></i> Item
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th style="width: 35%;">Produk</th>
                                    <th style="width: 20%;">Harga</th>
                                    <th style="width: 12%;">Qty</th>
                                    <th style="width: 23%;">Subtotal</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="3" class="text-end fw-bold py-2 small">TOTAL :</td>
                                    <td colspan="2" class="py-2 text-end">
                                        <h5 class="fw-bold text-success m-0" id="grandTotal" style="font-size:1.1rem;">Rp 0</h5>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="index.php" class="btn btn-sm btn-light border px-3">Batal</a>
                        <button type="submit" name="simpan_transaksi" class="btn btn-sm btn-primary px-3" onclick="return confirm('Simpan transaksi?')">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold">Tambah Customer Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAddCustomer">
                        <div class="mb-2">
                            <label class="small text-muted">Nama Lengkap *</label>
                            <input type="text" id="new_nama" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted">No. Telepon</label>
                            <input type="text" id="new_telp" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted">Alamat</label>
                            <textarea id="new_alamat" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Simpan & Pilih</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const products = <?php echo json_encode($js_products); ?>;

        // --- 1. INIT SELECT2 CUSTOMER ---
        $(document).ready(function() {
            $('#selectCustomer').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Nama Customer --',
                width: '100%'
            });
            // Tambah 1 baris saat load
            addRow();
        });

        // --- 2. TAMBAH BARIS PRODUK ---
        function addRow() {
            let options = '<option value="">-- Cari Produk --</option>';
            products.forEach(p => options += `<option value="${p.id_produk}" data-price="${p.harga_jual}" data-stok="${p.stok}">${p.nama_produk}</option>`);
            
            const tr = `
                <tr>
                    <td>
                        <select name="id_produk[]" class="form-select form-select-sm select2-produk" required onchange="updateRow(this)">
                            ${options}
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm bg-light text-end price" readonly placeholder="0"></td>
                    <td><input type="number" name="jumlah[]" class="form-control form-control-sm text-center qty" min="1" value="1" onchange="calcTotal()" onkeyup="calcTotal()" required></td>
                    <td><input type="text" class="form-control form-control-sm bg-light text-end sub" readonly placeholder="0"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
                </tr>
            `;
            $('#cartBody').append(tr);

            $('.select2-produk:last').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Produk --',
                dropdownCssClass: "select2-sm",
                width: '100%'
            });
        }

        // --- 3. LOGIC HITUNG TOTAL (FIXED) ---
        function calcTotal() {
            let total = 0;
            // jQuery .each() loop yang benar
            $('#cartBody tr').each(function() {
                const row = $(this);
                // Ambil harga, hapus titik ribuan jika ada
                const priceText = row.find('.price').val() || '0';
                const p = parseInt(priceText.replace(/\./g,'')) || 0;
                const q = parseInt(row.find('.qty').val()) || 0;
                
                const sub = p * q;
                row.find('.sub').val(sub.toLocaleString('id-ID'));
                
                total += sub;
            });
            $('#grandTotal').text('Rp ' + total.toLocaleString('id-ID'));
        }

        function updateRow(el) {
            // Karena pakai Select2, kita ambil data dari option yang dipilih (tetap di dalam elemen select asli)
            const opt = $(el).find(':selected');
            const row = $(el).closest('tr');
            
            const price = parseInt(opt.data('price') || 0);
            const stok = parseInt(opt.data('stok') || 0);

            row.find('.price').val(price.toLocaleString('id-ID'));
            row.find('.qty').attr('max', stok);
            
            calcTotal();
        }

        function removeRow(btn) {
            $(btn).closest('tr').remove();
            calcTotal();
        }

        // --- 4. QUICK ADD CUSTOMER ---
        $('#formAddCustomer').on('submit', function(e){
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: '', 
                type: 'POST',
                data: {
                    ajax_add_customer: true,
                    nama: $('#new_nama').val(),
                    telp: $('#new_telp').val(),
                    alamat: $('#new_alamat').val()
                },
                success: function(res) {
                    if(res.status === 'success') {
                        var newOption = new Option(res.nama, res.id, true, true);
                        $('#selectCustomer').append(newOption).trigger('change');
                        $('#addCustomerModal').modal('hide');
                        $('#formAddCustomer')[0].reset();
                        alert('Customer berhasil ditambahkan!');
                    } else {
                        alert(res.message);
                    }
                },
                error: function() { alert('Error sistem.'); },
                complete: function() { btn.prop('disabled', false).text('Simpan & Pilih'); }
            });
        });

        function toggleSidebar() { document.body.classList.toggle('show-sidebar'); }
    </script>
</body>
</html>