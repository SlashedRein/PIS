<?php
session_start();
require_once '../../config/database.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$role = $_SESSION['role']; // Simpan role

// --- [LOGIC 1] AJAX HANDLER: DETAIL PEMBELIAN ---
if (isset($_POST['get_detail_beli'])) {
    $id_beli = clean_input($_POST['id_beli']);
    
    // Ambil Detail Bahan
    $query = "SELECT dp.*, b.nama_bahan, b.satuan 
              FROM detail_pembelian dp 
              JOIN bahan_baku b ON dp.id_bahan = b.id_bahan 
              WHERE dp.id_beli = '$id_beli'";
    $result = $conn->query($query);
    
    // Ambil Header (Total)
    $header = $conn->query("SELECT total_beli, note FROM pembelian WHERE id_beli = '$id_beli'")->fetch_assoc();

    $html = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $row['nama_bahan'] . '</td>';
            $html .= '<td class="text-center">' . $row['jumlah'] . ' ' . $row['satuan'] . '</td>';
            $html .= '<td class="text-end">' . format_rupiah($row['harga_satuan']) . '</td>';
            $html .= '<td class="text-end fw-bold">' . format_rupiah($row['sub_total']) . '</td>';
            $html .= '</tr>';
        }
        // Baris Total & Note
        $html .= '<tr class="table-light fw-bold">';
        $html .= '<td colspan="3" class="text-end">TOTAL PEMBELIAN :</td>';
        $html .= '<td class="text-end text-primary">' . format_rupiah($header['total_beli']) . '</td>';
        $html .= '</tr>';
        if(!empty($header['note'])) {
            $html .= '<tr><td colspan="4" class="text-muted small"><i class="bi bi-sticky"></i> Catatan: '.$header['note'].'</td></tr>';
        }
    } else {
        $html .= '<tr><td colspan="4" class="text-center text-muted">Detail tidak ditemukan.</td></tr>';
    }
    
    echo $html;
    exit();
}

// --- [LOGIC 2] HAPUS TRANSAKSI (HANYA OWNER) ---
if (isset($_GET['delete'])) {
    // Proteksi Backend
    if ($role !== 'owner') {
        echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
        exit();
    }

    $id_beli = clean_input($_GET['delete']);
    
    // Ambil item untuk kembalikan stok (karena pembelian dihapus, stok harus dikurangi lagi)
    $query_items = "SELECT id_bahan, jumlah FROM detail_pembelian WHERE id_beli = ?";
    $stmt_items = $conn->prepare($query_items);
    $stmt_items->bind_param("i", $id_beli);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    
    $conn->begin_transaction();
    try {
        $stmt_restock = $conn->prepare("UPDATE bahan_baku SET stok = stok - ? WHERE id_bahan = ?");
        while ($item = $result_items->fetch_assoc()) {
            $stmt_restock->bind_param("ii", $item['jumlah'], $item['id_bahan']);
            $stmt_restock->execute();
        }
        $conn->query("DELETE FROM detail_pembelian WHERE id_beli = $id_beli");
        $conn->query("DELETE FROM pembelian WHERE id_beli = $id_beli");
        
        $conn->commit();
        header("Location: index.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Gagal: " . $e->getMessage();
    }
}

// --- FILTER ---
$where_clauses = ["1=1"];
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = clean_input($_GET['q']);
    $where_clauses[] = "(s.nama LIKE '%$q%' OR p.id_beli = '$q')";
}
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start = $_GET['start_date'];
    $end = $_GET['end_date'] ?? date('Y-m-d');
    $where_clauses[] = "p.tgl BETWEEN '$start' AND '$end'";
}
$where_sql = implode(' AND ', $where_clauses);

// Query Utama
$query = "SELECT p.*, s.nama as nama_supplier, 
          (SELECT COUNT(*) FROM detail_pembelian WHERE id_beli = p.id_beli) as jumlah_item
          FROM pembelian p 
          LEFT JOIN supplier s ON p.id_supp = s.id_supp 
          WHERE $where_sql
          ORDER BY p.tgl DESC, p.id_beli DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembelian - Dewi Cookies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .text-brown { color: var(--primary-color) !important; }
        .btn-brown { background-color: var(--primary-color); color: white; border: none; }
        .btn-brown:hover { background-color: #6F3410; color: white; }
    </style>
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title"><h4 class="m-0">Riwayat Pembelian</h4></div>
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
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success">Transaksi berhasil dihapus & stok bahan baku dikurangi kembali.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['new_buy'])): ?>
                <div class="alert alert-success">Pembelian berhasil disimpan! Stok bahan baku bertambah.</div>
            <?php endif; ?>

            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-bold text-brown m-0">Daftar Pembelian Bahan</h5>
                    
                    <a href="create.php" class="btn-add text-decoration-none">
                        <i class="bi bi-plus-lg"></i> Pembelian Baru
                    </a>
                </div>

                <form method="GET" action="" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari Supplier / ID..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>" placeholder="Dari Tanggal">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>" placeholder="Sampai Tanggal">
                        </div>
                        <div class="col-md-3 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-brown px-4 fw-bold shadow-sm">Filter</button>
                            <?php if(isset($_GET['q']) || isset($_GET['start_date'])): ?>
                                <a href="index.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID Beli</th>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Item</th>
                                <th>Total Pembelian</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?php echo str_pad($row['id_beli'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                    <td><?php echo format_tanggal($row['tgl']); ?></td>
                                    <td><?php echo $row['nama_supplier'] ? $row['nama_supplier'] : '<em class="text-muted">Umum</em>'; ?></td>
                                    <td><?php echo $row['jumlah_item']; ?> jenis</td>
                                    <td><strong class="text-primary"><?php echo format_rupiah($row['total_beli']); ?></strong></td>
                                    <td>
                                        <button class="btn btn-sm btn-info text-white shadow-sm" 
                                                onclick="showDetail(<?php echo $row['id_beli']; ?>, '<?php echo $row['nama_supplier']; ?>')" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <?php if ($role == 'owner'): ?>
                                            <a href="edit.php?id=<?php echo $row['id_beli']; ?>" class="btn btn-sm btn-warning text-white shadow-sm" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id_beli']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus transaksi pembelian ini? Stok akan dikurangi kembali.')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5">Belum ada data pembelian.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Detail Pembelian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="px-3 pb-2 text-muted small">Supplier: <strong id="detailSuppName" class="text-dark">...</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Bahan Baku</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end pe-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detailContent"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // NOTE: Sidebar toggle sudah include di sidebar.php

        // Detail AJAX
        function showDetail(idBeli, suppName) {
            $('#detailSuppName').text(suppName);
            $('#detailContent').html('<tr><td colspan="4" class="text-center py-3 text-muted">Memuat...</td></tr>');
            var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();

            $.ajax({
                url: '', 
                type: 'POST',
                data: { get_detail_beli: true, id_beli: idBeli },
                success: function(response) { $('#detailContent').html(response); }
            });
        }

        // SWEETALERT LOGOUT
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar', cancelButtonText: 'Batal', reverseButtons: true
            }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });
    </script>
</body>
</html>