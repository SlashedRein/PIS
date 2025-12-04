<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_beli = clean_input($_GET['id']);
$error = '';

// --- PROSES SIMPAN (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_supp = clean_input($_POST['id_supp']);
    $tgl = clean_input($_POST['tgl']);
    $note = clean_input($_POST['note']);

    $bahan_ids = $_POST['id_bahan'];
    $qtys = $_POST['jumlah'];
    $hargas = $_POST['harga_satuan']; // Harga beli bisa berubah manual

    if (empty($bahan_ids)) {
        $error = "Minimal 1 bahan baku.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. ROLLBACK STOK LAMA (Kurangi stok karena pembelian lama dihapus)
            $old_items = $conn->query("SELECT id_bahan, jumlah FROM detail_pembelian WHERE id_beli = '$id_beli'");
            while ($old = $old_items->fetch_assoc()) {
                $conn->query("UPDATE bahan_baku SET stok = stok - {$old['jumlah']} WHERE id_bahan = {$old['id_bahan']}");
            }

            // 2. HAPUS DETAIL LAMA
            $conn->query("DELETE FROM detail_pembelian WHERE id_beli = '$id_beli'");

            // 3. INSERT BARU & TAMBAH STOK
            $total_beli = 0;
            $stmt_detail = $conn->prepare("INSERT INTO detail_pembelian (id_beli, id_bahan, jumlah, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE bahan_baku SET stok = stok + ? WHERE id_bahan = ?");

            for ($i = 0; $i < count($bahan_ids); $i++) {
                $bid = $bahan_ids[$i];
                $qty = $qtys[$i];
                $harga = $hargas[$i];
                $subtotal = $qty * $harga;
                $total_beli += $subtotal;

                // Insert
                $stmt_detail->bind_param("iiidd", $id_beli, $bid, $qty, $harga, $subtotal);
                $stmt_detail->execute();

                // Update Stok
                $stmt_stock->bind_param("ii", $qty, $bid);
                $stmt_stock->execute();
            }

            // Update Header
            $stmt_head = $conn->prepare("UPDATE pembelian SET id_supp=?, tgl=?, total_beli=?, note=? WHERE id_beli=?");
            $stmt_head->bind_param("isdsi", $id_supp, $tgl, $total_beli, $note, $id_beli);
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
$resDet = $conn->query("SELECT dp.*, b.nama_bahan FROM detail_pembelian dp JOIN bahan_baku b ON dp.id_bahan = b.id_bahan WHERE dp.id_beli = '$id_beli'");
$details = [];
while ($row = $resDet->fetch_assoc()) $details[] = $row;

$suppliers = $conn->query("SELECT * FROM supplier ORDER BY nama ASC");
$bahans = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Pembelian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>

<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content p-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-success">Edit Pembelian #<?php echo $id_beli; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tgl" class="form-control" value="<?php echo $header['tgl']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="id_supp" class="form-select" required>
                                <option value="">Pilih Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id_supp']; ?>" <?php echo ($s['id_supp'] == $header['id_supp']) ? 'selected' : ''; ?>>
                                        <?php echo $s['nama']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control" value="<?php echo $header['note']; ?>">
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Bahan Baku</th>
                                <th width="150">Harga Beli</th>
                                <th width="100">Qty</th>
                                <th width="200">Subtotal</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pembelianItems">
                            <?php foreach ($details as $d): ?>
                                <tr class="item-row">
                                    <td>
                                        <select name="id_bahan[]" class="form-select" required>
                                            <?php foreach ($bahans as $b): ?>
                                                <option value="<?php echo $b['id_bahan']; ?>" <?php echo ($b['id_bahan'] == $d['id_bahan']) ? 'selected' : ''; ?>>
                                                    <?php echo $b['nama_bahan']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="harga_satuan[]" class="form-control input-harga" value="<?php echo $d['harga_satuan']; ?>" onkeyup="hitungSub(this)" required>
                                    </td>
                                    <td>
                                        <input type="number" name="jumlah[]" class="form-control input-qty" value="<?php echo $d['jumlah']; ?>" min="1" onkeyup="hitungSub(this)" onchange="hitungSub(this)" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sub text-end" value="<?php echo number_format($d['sub_total'], 0, ',', '.'); ?>" readonly>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-success btn-sm mb-3" onclick="addRow()"><i class="bi bi-plus"></i> Tambah Bahan</button>

                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fw-bold">Total</span>
                                <input type="text" id="grandTotal" class="form-control fw-bold text-end" value="Rp <?php echo number_format($header['total_beli'], 0, ',', '.'); ?>" readonly>
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
        const bahanOptions = `
            <option value="">Pilih Bahan</option>
            <?php foreach ($bahans as $b): ?>
                <option value="<?php echo $b['id_bahan']; ?>"><?php echo addslashes($b['nama_bahan']); ?></option>
            <?php endforeach; ?>
        `;

        function addRow() {
            const tr = `
            <tr class="item-row">
                <td><select name="id_bahan[]" class="form-select" required>${bahanOptions}</select></td>
                <td><input type="number" name="harga_satuan[]" class="form-control input-harga" placeholder="Harga Beli" onkeyup="hitungSub(this)" required></td>
                <td><input type="number" name="jumlah[]" class="form-control input-qty" value="1" min="1" onkeyup="hitungSub(this)" onchange="hitungSub(this)" required></td>
                <td><input type="text" class="form-control input-sub text-end" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)"><i class="bi bi-trash"></i></button></td>
            </tr>`;
            document.getElementById('pembelianItems').insertAdjacentHTML('beforeend', tr);
        }

        function delRow(btn) {
            btn.closest('tr').remove();
            calcTotal();
        }

        function hitungSub(el) {
            const row = el.closest('tr');
            const h = row.querySelector('.input-harga').value || 0;
            const q = row.querySelector('.input-qty').value || 0;
            const sub = h * q;
            row.querySelector('.input-sub').value = sub.toLocaleString('id-ID');
            calcTotal();
        }

        function calcTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const h = row.querySelector('.input-harga').value || 0;
                const q = row.querySelector('.input-qty').value || 0;
                total += (h * q);
            });
            document.getElementById('grandTotal').value = "Rp " + total.toLocaleString('id-ID');
        }
    </script>
</body>

</html>