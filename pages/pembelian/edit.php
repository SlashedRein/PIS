<?php
session_start();
require_once '../../config/database.php';

// 1. Cek Login & Hak Akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
// Proteksi Owner
$role = $_SESSION['role'];
if ($role !== 'owner') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_beli = clean_input($_GET['id']);
$error = '';

// --- PROSES SIMPAN (POST) DENGAN LOGIKA KONVERSI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_supp = clean_input($_POST['id_supp']);
    $tgl = clean_input($_POST['tgl']);
    $note = clean_input($_POST['note']);

    // Data Baru
    $bahan_ids  = isset($_POST['id_bahan']) ? $_POST['id_bahan'] : [];
    $qty_packs  = isset($_POST['qty_pack']) ? $_POST['qty_pack'] : [];
    $isi_packs  = isset($_POST['isi_per_pack']) ? $_POST['isi_per_pack'] : [];
    $harga_packs= isset($_POST['harga_per_pack']) ? $_POST['harga_per_pack'] : [];

    if (empty($bahan_ids)) {
        $error = "Minimal 1 bahan baku.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. ROLLBACK STOK LAMA
            // Kita harus mengembalikan stok seperti sebelum transaksi ini terjadi
            $old_items = $conn->query("SELECT id_bahan, jumlah FROM detail_pembelian WHERE id_beli = '$id_beli'");
            while ($old = $old_items->fetch_assoc()) {
                $conn->query("UPDATE bahan_baku SET stok = stok - {$old['jumlah']} WHERE id_bahan = {$old['id_bahan']}");
            }

            // 2. HAPUS DETAIL LAMA
            $conn->query("DELETE FROM detail_pembelian WHERE id_beli = '$id_beli'");

            // 3. HITUNG ULANG & INSERT BARU
            $grand_total = 0;
            $items_fix = [];

            for ($i = 0; $i < count($bahan_ids); $i++) {
                $pid = $bahan_ids[$i];
                $q_pack = (float) $qty_packs[$i]; 
                $isi    = (float) str_replace('.', '', $isi_packs[$i]);
                $h_pack = (float) str_replace('.', '', $harga_packs[$i]);

                if(!empty($pid) && $q_pack > 0 && $isi > 0) {
                    // Hitung Subtotal (Rupiah)
                    $subtotal = $q_pack * $h_pack;
                    $grand_total += $subtotal;

                    // Hitung Stok Masuk (Gram/Pcs)
                    $total_stok_masuk = $q_pack * $isi;

                    // Hitung HPP Baru per unit
                    $harga_satuan_db = ($total_stok_masuk > 0) ? ($subtotal / $total_stok_masuk) : 0;

                    $items_fix[] = [
                        'id' => $pid, 
                        'qty_db' => $total_stok_masuk, 
                        'harga_db' => $harga_satuan_db, 
                        'sub' => $subtotal
                    ];
                }
            }

            if (empty($items_fix)) throw new Exception("Belum ada item valid.");

            // 4. INSERT DETAIL BARU & UPDATE STOK
            $stmt_detail = $conn->prepare("INSERT INTO detail_pembelian (id_beli, id_bahan, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE bahan_baku SET stok = stok + ? WHERE id_bahan = ?");

            foreach ($items_fix as $item) {
                $stmt_detail->bind_param("iiidd", $id_beli, $item['id'], $item['qty_db'], $item['harga_db'], $item['sub']);
                $stmt_detail->execute();

                $stmt_stock->bind_param("ii", $item['qty_db'], $item['id']);
                $stmt_stock->execute();
            }

            // 5. UPDATE HEADER
            $stmt_head = $conn->prepare("UPDATE pembelian SET id_supp=?, tgl=?, total_beli=?, note=? WHERE id_beli=?");
            $stmt_head->bind_param("isdsi", $id_supp, $tgl, $grand_total, $note, $id_beli);
            $stmt_head->execute();

            $conn->commit();
            header("Location: index.php?new_buy=$id_beli"); // Redirect
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// --- LOAD DATA LAMA ---
$header = $conn->query("SELECT * FROM pembelian WHERE id_beli = '$id_beli'")->fetch_assoc();
if(!$header) { header("Location: index.php"); exit(); }

$resDet = $conn->query("SELECT dp.*, b.nama_bahan, b.satuan FROM detail_pembelian dp JOIN bahan_baku b ON dp.id_bahan = b.id_bahan WHERE dp.id_beli = '$id_beli'");
$details = [];
while ($row = $resDet->fetch_assoc()) {
    // Estimasi balik nilai Pack & Isi (Karena DB cuma simpan total qty)
    // Asumsi default: Isi per pack = 1 (jika tidak bisa ditebak), jadi Qty Pack = Total Qty
    // User harus input ulang konversinya jika ingin revisi, karena data 'isi_per_pack' tidak disimpan di DB.
    $row['qty_pack'] = 1; 
    $row['isi_pack'] = $row['jumlah']; 
    $row['harga_pack'] = $row['sub_total']; 
    $details[] = $row;
}

$suppliers = $conn->query("SELECT * FROM supplier ORDER BY nama ASC");
$bahans = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");
$js_bahans = [];
while($b = $bahans->fetch_assoc()) { $js_bahans[] = $b; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pembelian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .text-brown { color: var(--primary-color) !important; }
        .select2-container .select2-selection--single { height: 31px !important; font-size: 0.85rem; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { padding-top: 2px; }
        .input-group-text { font-size: 0.8rem; background: #f8f9fa; }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content p-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-success">Edit Pembelian #<?php echo str_pad($id_beli, 6, '0', STR_PAD_LEFT); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                
                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle"></i> <strong>Perhatian:</strong> Karena sistem hanya menyimpan total stok, data "Isi per Kemasan" direset menjadi total qty. Silakan sesuaikan kembali jika perlu.
                </div>

                <form method="POST">
                    <div class="row mb-3 g-2">
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Tanggal</label>
                            <input type="date" name="tgl" class="form-control form-control-sm" value="<?php echo $header['tgl']; ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted">Supplier</label>
                            <select name="id_supp" id="selectSupplier" class="form-select form-select-sm" required>
                                <option value="">Pilih Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id_supp']; ?>" <?php echo ($s['id_supp'] == $header['id_supp']) ? 'selected' : ''; ?>>
                                        <?php echo $s['nama']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Catatan</label>
                            <input type="text" name="note" class="form-control form-control-sm" value="<?php echo $header['note']; ?>">
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th width="30%">Bahan Baku</th>
                                    <th width="20%">Harga/Kemasan (Rp)</th>
                                    <th width="12%">Jml Beli</th>
                                    <th width="18%">Isi per Kemasan</th>
                                    <th width="20%">Subtotal</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <?php foreach ($details as $d): ?>
                                    <tr class="item-row">
                                        <td>
                                            <select name="id_bahan[]" class="form-select form-select-sm select2-bahan" required>
                                                <?php foreach ($js_bahans as $b): ?>
                                                    <option value="<?php echo $b['id_bahan']; ?>" data-satuan="<?php echo $b['satuan']; ?>" <?php echo ($b['id_bahan'] == $d['id_bahan']) ? 'selected' : ''; ?>>
                                                        <?php echo $b['nama_bahan']; ?> (<?php echo $b['satuan']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" name="harga_per_pack[]" class="form-control text-end format-angka harga-pack" 
                                                       value="<?php echo number_format($d['harga_pack'], 0, ',', '.'); ?>" required>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="qty_pack[]" class="form-control form-control-sm text-center qty-pack" 
                                                   min="0.1" step="any" value="<?php echo $d['qty_pack']; ?>" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="isi_per_pack[]" class="form-control text-center format-angka isi-pack" 
                                                       value="<?php echo number_format($d['isi_pack'], 0, ',', '.'); ?>" required>
                                                <span class="input-group-text satuan-label" style="min-width:50px; justify-content:center;"><?php echo $d['satuan']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end bg-light sub" 
                                                   value="<?php echo number_format($d['sub_total'], 0, ',', '.'); ?>" readonly>
                                        </td>
                                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm py-0" onclick="delRow(this)"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold py-2 small">TOTAL :</td>
                                    <td class="text-end fw-bold text-primary py-2" id="grandTotal">Rp <?php echo number_format($header['total_beli'], 0, ',', '.'); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-success btn-sm mb-3" onclick="addRow()"><i class="bi bi-plus-lg"></i> Tambah Item</button>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="index.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
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
        const bahans = <?php echo json_encode($js_bahans); ?>;

        $(document).ready(function() {
            $('#selectSupplier').select2({ theme: 'bootstrap-5', width: '100%' });
            $('.select2-bahan').select2({ theme: 'bootstrap-5', width: '100%', dropdownCssClass: "select2-sm" });
        });

        // Event Delegation
        $(document).on('keyup', '.format-angka', function() {
            let val = $(this).val().replace(/\D/g, '');
            $(this).val(val ? parseInt(val).toLocaleString('id-ID') : '');
            calcTotal();
        });
        
        $(document).on('input change', '.qty-pack', function() { calcTotal(); });

        $(document).on('select2:select', '.select2-bahan', function(e){
            let satuan = $(this).find(':selected').data('satuan');
            $(this).closest('tr').find('.satuan-label').text(satuan);
        });

        function addRow() {
            let options = '<option value="">-- Pilih --</option>';
            bahans.forEach(b => options += `<option value="${b.id_bahan}" data-satuan="${b.satuan}">${b.nama_bahan} (${b.satuan})</option>`);
            
            const tr = `
                <tr class="item-row">
                    <td><select name="id_bahan[]" class="form-select form-select-sm select2-bahan" required>${options}</select></td>
                    <td><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" name="harga_per_pack[]" class="form-control text-end format-angka harga-pack" placeholder="0" required></div></td>
                    <td><input type="number" name="qty_pack[]" class="form-control form-control-sm text-center qty-pack" min="0.1" step="any" value="1" required></td>
                    <td><div class="input-group input-group-sm"><input type="text" name="isi_per_pack[]" class="form-control text-center format-angka isi-pack" placeholder="1" value="1" required><span class="input-group-text satuan-label" style="min-width:50px; justify-content:center;">Unit</span></div></td>
                    <td><input type="text" class="form-control form-control-sm text-end bg-light sub" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm py-0" onclick="delRow(this)"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            
            $('#cartBody').append(tr);
            $('.select2-bahan:last').select2({ theme: 'bootstrap-5', width: '100%', dropdownCssClass: "select2-sm" });
        }

        function delRow(btn) {
            btn.closest('tr').remove();
            calcTotal();
        }

        function calcTotal() {
            let total = 0;
            $('#cartBody tr').each(function() {
                const row = $(this);
                let hPack = parseInt(row.find('.harga-pack').val().replace(/\./g,'') || 0);
                let qPack = parseFloat(row.find('.qty-pack').val() || 0);
                let sub = hPack * qPack;
                
                row.find('.sub').val(sub.toLocaleString('id-ID'));
                total += sub;
            });
            $('#grandTotal').text('Rp ' + total.toLocaleString('id-ID'));
        }

        // Logout
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); const h = this.getAttribute('href');
            Swal.fire({ title: 'Keluar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya', confirmButtonColor: '#d33' }).then((r) => { if(r.isConfirmed) window.location.href = h; });
        });
    </script>
</body>
</html>