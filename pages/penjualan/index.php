<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- [LOGIC 1] AJAX DETAIL NOTA ---
if (isset($_POST['get_detail'])) {
    $id = clean_input($_POST['id']);
    $query = "SELECT dp.*, p.nama_produk, p.satuan 
              FROM detail_penjualan dp 
              JOIN produk p ON dp.id_produk = p.id_produk 
              WHERE dp.id_penjualan = '$id'";
    $result = $conn->query($query);
    $header = $conn->query("SELECT total FROM penjualan WHERE id_penjualan = '$id'")->fetch_assoc();

    $html = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>'.$row['nama_produk'].'</td>
                <td class="text-center">'.$row['jumlah'].' '.$row['satuan'].'</td>
                <td class="text-end">'.format_rupiah($row['harga_satuan']).'</td>
                <td class="text-end fw-bold">'.format_rupiah($row['sub_total']).'</td>
            </tr>';
        }
        $html .= '<tr class="table-light fw-bold"><td colspan="3" class="text-end">TOTAL :</td><td class="text-end text-success">'.format_rupiah($header['total']).'</td></tr>';
    } else {
        $html .= '<tr><td colspan="4" class="text-center text-muted">Detail tidak ditemukan.</td></tr>';
    }
    echo $html;
    exit();
}

// --- [LOGIC 2] HAPUS TRANSAKSI ---
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    // Ambil item untuk restore stok
    $items = $conn->query("SELECT id_produk, jumlah FROM detail_penjualan WHERE id_penjualan = '$id'");
    
    $conn->begin_transaction();
    try {
        $stmt_restock = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        while ($row = $items->fetch_assoc()) {
            $stmt_restock->bind_param("ii", $row['jumlah'], $row['id_produk']);
            $stmt_restock->execute();
        }
        $conn->query("DELETE FROM detail_penjualan WHERE id_penjualan = '$id'");
        $conn->query("DELETE FROM penjualan WHERE id_penjualan = '$id'");
        $conn->commit();
        $success = "Transaksi dihapus & stok dikembalikan.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal: " . $e->getMessage();
    }
}

// --- FILTER ---
$where = "1=1";
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = clean_input($_GET['q']);
    $where .= " AND (c.nama LIKE '%$q%' OR p.id_penjualan = '$q')";
}
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start = $_GET['start_date'];
    $end = $_GET['end_date'] ?? date('Y-m-d');
    $where .= " AND p.tgl_penjualan BETWEEN '$start' AND '$end'";
}

$query = "SELECT p.*, c.nama as nama_customer, 
          (SELECT COUNT(*) FROM detail_penjualan WHERE id_penjualan = p.id_penjualan) as jml_item
          FROM penjualan p 
          LEFT JOIN customer c ON p.id_cust = c.id_cust 
          WHERE $where ORDER BY p.tgl_penjualan DESC, p.id_penjualan DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan - Dewi Cookies</title>
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
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center gap-2">
            <div class="logo-icon">🍪</div>
            <div class="logo-text text-start"><h5 class="mb-0 fw-bold" style="font-size: 16px;">Dewi Cookies</h5></div>
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
            <a href="../pembelian/index.php" class="nav-link"><i class="bi bi-cart-plus"></i> <span>Pembelian</span></a>
            <a href="index.php" class="nav-link active"><i class="bi bi-cash-coin"></i> <span>Penjualan</span></a>
            <div class="nav-section-title">Reports</div>
            <a href="../laporan/index.php" class="nav-link"><i class="bi bi-graph-up"></i> <span>Laporan</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title"><h4 class="m-0">Riwayat Penjualan</h4></div>
            </div>
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info d-none d-md-block text-end">
                        <span class="name d-block text-dark fw-bold" style="font-size: 13px;"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        <span class="role d-block text-muted" style="font-size: 10px;"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <div class="user-avatar bg-primary text-white shadow-sm"><?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?></div>
                </div>
                <div class="dropdown-menu-custom">
                    <a href="#" class="dropdown-item-custom logout text-danger" id="btnLogout"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            <?php if ($success): ?><div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div><?php endif; ?>

            <?php if (isset($_GET['new_nota'])): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                    <span><i class="bi bi-check-circle-fill"></i> Transaksi Berhasil!</span>
                    <a href="nota.php?id=<?php echo $_GET['new_nota']; ?>" target="_blank" class="btn btn-sm btn-brown"><i class="bi bi-printer"></i> Cetak Nota</a>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-bold text-brown m-0">Daftar Transaksi</h5>
                    <a href="create.php" class="btn btn-brown rounded-3 px-4"><i class="bi bi-plus-lg"></i> Transaksi Baru</a>
                </div>

                <form method="GET" action="" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari Nota / Nama..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>"></div>
                        <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>"></div>
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
                        <thead class="bg-light">
                            <tr><th>No Nota</th><th>Tanggal</th><th>Customer</th><th>Item</th><th>Total</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary font-monospace">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                    <td><?php echo format_tanggal($row['tgl_penjualan']); ?></td>
                                    <td><?php echo $row['nama_customer'] ? $row['nama_customer'] : '<em class="text-muted">Umum</em>'; ?></td>
                                    <td><?php echo $row['jml_item']; ?> jenis</td>
                                    <td><strong class="text-success"><?php echo format_rupiah($row['total']); ?></strong></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info text-white me-1" onclick="showDetail(<?php echo $row['id_penjualan']; ?>, '<?php echo $row['nama_customer']; ?>')"><i class="bi bi-eye"></i></button>
                                        <a href="nota.php?id=<?php echo $row['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-printer"></i></a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('?delete=<?php echo $row['id_penjualan']; ?>')"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5">Belum ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Detail Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-0">
                    <div class="px-3 pb-2 text-muted small">Customer: <strong id="detailName" class="text-dark">...</strong></div>
                    <table class="table table-sm table-striped mb-0"><thead class="table-light"><tr><th class="ps-3">Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end pe-3">Subtotal</th></tr></thead><tbody id="detailContent"></tbody></table>
                </div>
                <div class="modal-footer border-top-0 py-2"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const btnMobile=document.getElementById('btnMobileToggle'), sidebar=document.getElementById('sidebar'), overlay=document.getElementById('sidebarOverlay');
        if(btnMobile){btnMobile.addEventListener('click',()=>{sidebar.classList.add('show');overlay.classList.add('show')});}
        if(overlay){overlay.addEventListener('click',()=>{sidebar.classList.remove('show');overlay.classList.remove('show')});}

        function showDetail(id, name) {
            $('#detailName').text(name);
            $('#detailContent').html('<tr><td colspan="4" class="text-center py-3">Memuat...</td></tr>');
            new bootstrap.Modal(document.getElementById('detailModal')).show();
            $.post('', {get_detail: true, id: id}, function(res){ $('#detailContent').html(res); });
        }

        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            Swal.fire({ title: 'Keluar?', text: "Anda harus login kembali nanti.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Keluar', cancelButtonText: 'Batal', reverseButtons: true }).then((result) => { if (result.isConfirmed) window.location.href = '../logout.php'; });
        });

        function confirmDelete(url) {
            Swal.fire({ title: 'Hapus Transaksi?', text: "Data hilang & stok dikembalikan.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal', reverseButtons: true }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }
    </script>
</body>
</html>