<?php
session_start();
require_once '../../config/database.php';

// --- [LOGIC 1] AJAX HANDLER: QUICK ADD SUPPLIER ---
if (isset($_POST['ajax_add_supplier'])) {
    header('Content-Type: application/json');
    
    $nama = clean_input($_POST['nama']);
    $telp = clean_input($_POST['telp']);
    $alamat = clean_input($_POST['alamat']);

    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama Supplier wajib diisi!']);
    } else {
        $stmt = $conn->prepare("INSERT INTO supplier (nama, no_telp, alamat) VALUES (?, ?, ?)");
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

// --- [LOGIC 2] PROSES SIMPAN PEMBELIAN ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$error = '';

if (isset($_POST['simpan_pembelian'])) {
    $id_supp = clean_input($_POST['id_supp']);
    $tgl     = clean_input($_POST['tgl_pembelian']);
    $note    = clean_input($_POST['catatan']);
    $bahan_ids = $_POST['id_bahan']; 
    $qtys      = $_POST['jumlah'];
    $hargas    = $_POST['harga_satuan']; // Input Manual untuk Pembelian
    
    if (empty($id_supp) || empty($bahan_ids)) {
        $error = "Data supplier dan bahan tidak boleh kosong!";
    } else {
        $conn->begin_transaction();
        try {
            $grand_total = 0;
            $items_fix = [];

            for ($i = 0; $i < count($bahan_ids); $i++) {
                $pid = $bahan_ids[$i];
                $qty = $qtys[$i];
                $prc = str_replace('.', '', $hargas[$i]); // Hapus titik format rupiah

                if(!empty($pid) && $qty > 0) {
                    $sub = $prc * $qty;
                    $grand_total += $sub;
                    $items_fix[] = ['id' => $pid, 'qty' => $qty, 'harga' => $prc, 'sub' => $sub];
                }
            }

            if (empty($items_fix)) throw new Exception("Belum ada item dipilih.");

            // Insert Header
            $stmt = $conn->prepare("INSERT INTO pembelian (id_supp, tgl, total_beli, note) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $id_supp, $tgl, $grand_total, $note);
            $stmt->execute();
            $id_beli = $conn->insert_id;

            // Insert Detail & UPDATE STOK (BERTAMBAH)
            $stmt_detail = $conn->prepare("INSERT INTO detail_pembelian (id_beli, id_bahan, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stok = $conn->prepare("UPDATE bahan_baku SET stok = stok + ? WHERE id_bahan = ?");

            foreach ($items_fix as $item) {
                // Masuk Detail
                $stmt_detail->bind_param("iiidd", $id_beli, $item['id'], $item['qty'], $item['harga'], $item['sub']);
                $stmt_detail->execute();

                // Tambah Stok
                $stmt_stok->bind_param("ii", $item['qty'], $item['id']);
                $stmt_stok->execute();
            }

            $conn->commit();
            header("Location: index.php?new_buy=" . $id_beli);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Ambil Data Awal
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY nama ASC");
$bahans = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");
$js_bahans = [];
while($b = $bahans->fetch_assoc()) { $js_bahans[] = $b; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pembelian</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        .text-brown { color: var(--primary-color) !important; }
        .select2-container .select2-selection--single { height: 31px !important; font-size: 0.85rem; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { padding-top: 2px; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center gap-2">
            <div class="logo-icon">🍪</div>
            <div class="logo-text text-start">
                <h5 class="mb-0 fw-bold" style="font-size: 16px;">Dewi Cookies</h5>
            </div>
        </div>
        <div class="sidebar-nav mt-3">
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
            <a href="index.php" class="nav-link active"><i class="bi bi-cart-plus"></i> <span>Pembelian</span></a>
            <a href="../penjualan/index.php" class="nav-link"><i class="bi bi-cash-coin"></i> <span>Penjualan</span></a>
            <div class="nav-section-title">Reports</div>
            <a href="../laporan/index.php" class="nav-link"><i class="bi bi-graph-up"></i> <span>Laporan</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title"><h4 class="m-0">Pembelian Baru</h4></div>
            </div>
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info d-none d-md-block text-end">
                        <span class="name d-block text-dark fw-bold" style="font-size: 13px;"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        <span class="role d-block text-muted" style="font-size: 10px;"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <div class="user-avatar bg-primary text-white shadow-sm">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                    </div>
                </div>
                <div class="dropdown-menu-custom">
                    <a href="../../logout.php" class="dropdown-item-custom logout text-danger"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <form method="POST" action="">
                <div class="content-box mb-3">
                    <div class="row align-items-center g-2">
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Tanggal</label>
                            <input type="date" name="tgl_pembelian" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-muted fw-bold">Supplier</label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="id_supp" id="selectSupplier" class="form-select" required>
                                        <option value="">-- Cari Supplier --</option>
                                        <?php foreach ($suppliers as $s): ?>
                                            <option value="<?php echo $s['id_supp']; ?>"><?php echo $s['nama']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal" title="Supplier Baru">
                                    <i class="bi bi-person-plus-fill"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional (No Invoice, dll)">
                        </div>
                    </div>
                </div>

                <div class="content-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-brown m-0"><i class="bi bi-box-seam"></i> Bahan Baku Masuk</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem;" onclick="addRow()">
                            <i class="bi bi-plus-lg"></i> Tambah Item
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th style="width: 35%;">Bahan Baku</th>
                                    <th style="width: 20%;">Harga Beli (Rp)</th>
                                    <th style="width: 12%;">Jumlah</th>
                                    <th style="width: 23%;">Subtotal</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="3" class="text-end fw-bold py-2 small">TOTAL PEMBELIAN :</td>
                                    <td colspan="2" class="py-2 text-end">
                                        <h5 class="fw-bold text-primary m-0" id="grandTotal" style="font-size:1.1rem;">Rp 0</h5>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="index.php" class="btn btn-sm btn-light border px-3">Batal</a>
                        <button type="submit" name="simpan_pembelian" class="btn btn-sm btn-primary px-3" onclick="return confirm('Simpan data pembelian?')">
                            <i class="bi bi-save"></i> Simpan & Restok
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold">Tambah Supplier Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAddSupplier">
                        <div class="mb-2">
                            <label class="small text-muted">Nama Supplier *</label>
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
        const bahans = <?php echo json_encode($js_bahans); ?>;

        $(document).ready(function() {
            $('#selectSupplier').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Supplier --',
                width: '100%'
            });
            addRow();
        });

        function addRow() {
            let options = '<option value="">-- Cari Bahan --</option>';
            bahans.forEach(b => options += `<option value="${b.id_bahan}">${b.nama_bahan} (${b.satuan})</option>`);
            
            const tr = `
                <tr>
                    <td>
                        <select name="id_bahan[]" class="form-select form-select-sm select2-bahan" required>
                            ${options}
                        </select>
                    </td>
                    <td><input type="text" name="harga_satuan[]" class="form-control form-control-sm text-end price" placeholder="0" onkeyup="calcTotal()"></td>
                    <td><input type="number" name="jumlah[]" class="form-control form-control-sm text-center qty" min="1" value="1" onchange="calcTotal()" onkeyup="calcTotal()" required></td>
                    <td><input type="text" class="form-control form-control-sm bg-light text-end sub" readonly placeholder="0"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
                </tr>
            `;
            $('#cartBody').append(tr);

            $('.select2-bahan:last').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Bahan --',
                dropdownCssClass: "select2-sm",
                width: '100%'
            });
        }

        function calcTotal() {
            let total = 0;
            $('#cartBody tr').each(function() {
                const row = $(this);
                const priceStr = row.find('.price').val().replace(/\./g,'') || '0';
                const p = parseInt(priceStr);
                const q = parseInt(row.find('.qty').val() || 0);
                const sub = p * q;
                
                row.find('.sub').val(sub.toLocaleString('id-ID'));
                total += sub;
            });
            $('#grandTotal').text('Rp ' + total.toLocaleString('id-ID'));
        }

        function removeRow(btn) {
            $(btn).closest('tr').remove();
            calcTotal();
        }

        // Quick Add Supplier
        $('#formAddSupplier').on('submit', function(e){
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: '', 
                type: 'POST',
                data: {
                    ajax_add_supplier: true,
                    nama: $('#new_nama').val(),
                    telp: $('#new_telp').val(),
                    alamat: $('#new_alamat').val()
                },
                success: function(res) {
                    if(res.status === 'success') {
                        var newOption = new Option(res.nama, res.id, true, true);
                        $('#selectSupplier').append(newOption).trigger('change');
                        $('#addSupplierModal').modal('hide');
                        $('#formAddSupplier')[0].reset();
                        alert('Supplier berhasil ditambahkan!');
                    } else {
                        alert(res.message);
                    }
                },
                error: function() { alert('Error sistem.'); },
                complete: function() { btn.prop('disabled', false).text('Simpan & Pilih'); }
            });
        });

        // Toggle Sidebar
        const btnMobile = document.getElementById('btnMobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if(btnMobile) { btnMobile.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); }
        if(overlay) { overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }
    </script>
</body>
</html>