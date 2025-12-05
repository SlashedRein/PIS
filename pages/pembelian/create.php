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
    
    $bahan_ids = isset($_POST['id_bahan']) ? $_POST['id_bahan'] : []; 
    $qtys      = isset($_POST['jumlah']) ? $_POST['jumlah'] : [];
    $hargas    = isset($_POST['harga_satuan']) ? $_POST['harga_satuan'] : [];
    
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
                
                // PENTING: Hapus titik format rupiah sebelum simpan ke DB
                // Contoh: "15.000" jadi "15000"
                $raw_harga = $hargas[$i];
                $prc = (float) str_replace('.', '', $raw_harga); 

                if(!empty($pid) && $qty > 0) {
                    $sub = $prc * $qty;
                    $grand_total += $sub;
                    $items_fix[] = ['id' => $pid, 'qty' => $qty, 'harga' => $prc, 'sub' => $sub];
                }
            }

            if (empty($items_fix)) throw new Exception("Belum ada item valid yang dipilih.");

            // 1. Insert Header Pembelian
            $stmt = $conn->prepare("INSERT INTO pembelian (id_supp, tgl, total_beli, note) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $id_supp, $tgl, $grand_total, $note);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan header transaksi: " . $stmt->error);
            }
            $id_beli = $conn->insert_id;

            // 2. Insert Detail & UPDATE STOK
            $stmt_detail = $conn->prepare("INSERT INTO detail_pembelian (id_beli, id_bahan, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stok = $conn->prepare("UPDATE bahan_baku SET stok = stok + ? WHERE id_bahan = ?");

            foreach ($items_fix as $item) {
                // Masuk Detail
                $stmt_detail->bind_param("iiidd", $id_beli, $item['id'], $item['qty'], $item['harga'], $item['sub']);
                if (!$stmt_detail->execute()) {
                    throw new Exception("Gagal menyimpan detail item: " . $stmt_detail->error);
                }

                // Tambah Stok
                $stmt_stok->bind_param("ii", $item['qty'], $item['id']);
                $stmt_stok->execute();
            }

            $conn->commit();
            
            // Redirect Sukses
            header("Location: index.php?new_buy=" . $id_beli);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi Kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil Data Awal untuk Dropdown
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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        .text-brown { color: var(--primary-color) !important; }
        .select2-container .select2-selection--single { height: 31px !important; font-size: 0.85rem; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { padding-top: 2px; }
    </style>
</head>
<body>
    
    <?php include '../../includes/sidebar.php'; ?>

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
                    <a href="../logout.php" class="dropdown-item-custom logout text-danger" id="btnLogout"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form id="formPembelian" method="POST" action="">
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
                        <button type="button" id="btnSimpan" class="btn btn-sm btn-primary px-3">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const bahans = <?php echo json_encode($js_bahans); ?>;

        $(document).ready(function() {
            // Init Select2 untuk Supplier
            $('#selectSupplier').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Supplier --',
                width: '100%'
            });
            // Tambah baris pertama
            addRow();
        });

        // --- 1. EVENT DELEGATION UNTUK HITUNG OTOMATIS ---
        // Ini kuncinya: Biar baris baru pun eventnya tetap jalan
        
        // Format Rupiah saat mengetik harga
        $(document).on('keyup', '.price', function() {
            let val = $(this).val().replace(/\D/g, ''); // Ambil angka saja
            if (val === '') {
                $(this).val('');
            } else {
                $(this).val(parseInt(val).toLocaleString('id-ID')); // Format ribuan
            }
            calcTotal(); // Hitung ulang
        });

        // Hitung ulang saat Qty berubah
        $(document).on('input change', '.qty', function() {
            calcTotal();
        });

        // --- 2. FUNGSI HITUNG TOTAL ---
        function calcTotal() {
            let total = 0;
            $('#cartBody tr').each(function() {
                const row = $(this);
                
                // Ambil Harga (Hapus titik dulu)
                let priceRaw = row.find('.price').val() || '0';
                let price = parseInt(priceRaw.replace(/\./g, '')) || 0;
                
                // Ambil Qty
                let qty = parseInt(row.find('.qty').val()) || 0;
                
                // Hitung Subtotal
                let sub = price * qty;
                row.find('.sub').val(sub.toLocaleString('id-ID'));
                
                total += sub;
            });
            $('#grandTotal').text('Rp ' + total.toLocaleString('id-ID'));
        }

        // --- 3. FUNGSI TAMBAH BARIS ---
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
                    <td>
                        <input type="text" name="harga_satuan[]" class="form-control form-control-sm text-end price" 
                               placeholder="0"> </td>
                    <td>
                        <input type="number" name="jumlah[]" class="form-control form-control-sm text-center qty" 
                               min="1" value="1" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm bg-light text-end sub" readonly placeholder="0">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
                    </td>
                </tr>
            `;
            $('#cartBody').append(tr);

            // Init Select2 untuk baris baru saja
            $('.select2-bahan:last').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari Bahan --',
                dropdownCssClass: "select2-sm",
                width: '100%'
            });
        }

        function removeRow(btn) {
            if($('#cartBody tr').length > 1) {
                $(btn).closest('tr').remove();
                calcTotal();
            } else {
                const row = $(btn).closest('tr');
                row.find('select').val(null).trigger('change');
                row.find('input').val('');
                row.find('.qty').val(1);
                calcTotal();
            }
        }

        // --- SWEETALERT KONFIRMASI SIMPAN ---
        $('#btnSimpan').on('click', function(e) {
            e.preventDefault();
            
            const supplier = $('#selectSupplier').val();
            if (!supplier) {
                Swal.fire('Error', 'Silakan pilih supplier dulu.', 'error');
                return;
            }

            Swal.fire({
                title: 'Simpan Pembelian?',
                text: "Stok bahan baku akan bertambah otomatis.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8B4513',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('formPembelian');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'simpan_pembelian';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    
                    form.submit();
                }
            });
        });

        // --- SWEETALERT LOGOUT ---
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar'
            }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });

        // --- QUICK ADD SUPPLIER ---
        $('#formAddSupplier').on('submit', function(e){
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: '', type: 'POST',
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
                        Swal.fire('Sukses', 'Supplier berhasil ditambahkan!', 'success');
                    } else { Swal.fire('Gagal', res.message, 'error'); }
                },
                error: function() { Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'); },
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