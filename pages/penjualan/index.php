<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- 1. LOGIKA HAPUS TRANSAKSI ---
if (isset($_GET['delete'])) {
    $id_penjualan = clean_input($_GET['delete']);
    
    $query_items = "SELECT id_produk, jumlah FROM detail_penjualan WHERE id_penjualan = ?";
    $stmt_items = $conn->prepare($query_items);
    $stmt_items->bind_param("i", $id_penjualan);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    
    $conn->begin_transaction();
    try {
        $stmt_restock = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        while ($item = $result_items->fetch_assoc()) {
            $stmt_restock->bind_param("ii", $item['jumlah'], $item['id_produk']);
            $stmt_restock->execute();
        }
        $conn->query("DELETE FROM detail_penjualan WHERE id_penjualan = $id_penjualan");
        $conn->query("DELETE FROM penjualan WHERE id_penjualan = $id_penjualan");
        
        $conn->commit();
        $success = "Transaksi dihapus & stok dikembalikan.";
        header("refresh:2;url=index.php");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal: " . $e->getMessage();
    }
}

// --- 2. LOGIKA SEARCH & FILTER ---
$where_clauses = ["1=1"];

// Filter Pencarian
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = clean_input($_GET['q']);
    if (is_numeric($q)) {
        $where_clauses[] = "(p.id_penjualan = '$q' OR c.nama LIKE '%$q%')";
    } else {
        $where_clauses[] = "c.nama LIKE '%$q%'";
    }
}

// Filter Tanggal
if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $start = clean_input($_GET['start_date']);
    $end   = clean_input($_GET['end_date']);
    $where_clauses[] = "p.tgl_penjualan BETWEEN '$start' AND '$end'";
}

$where_sql = implode(' AND ', $where_clauses);

// Query Utama
$query = "SELECT p.*, c.nama as nama_customer, 
          (SELECT COUNT(*) FROM detail_penjualan WHERE id_penjualan = p.id_penjualan) as jumlah_item
          FROM penjualan p 
          LEFT JOIN customer c ON p.id_cust = c.id_cust 
          WHERE $where_sql
          ORDER BY p.tgl_penjualan DESC, p.id_penjualan DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - Dewi Cookies</title>
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
        
        .text-brown { color: var(--primary-color) !important; }
        .btn-brown { background-color: var(--primary-color); color: white; border: none; }
        .btn-brown:hover { background-color: #6F3410; color: white; }
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
                <div class="page-title"><h4 class="m-0">Riwayat Penjualan</h4></div>
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
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <?php if (isset($_GET['new_nota'])): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-circle-fill"></i> Transaksi Berhasil Disimpan!</span>
                    <div>
                        <a href="nota.php?id=<?php echo $_GET['new_nota']; ?>" target="_blank" class="btn btn-sm btn-brown me-2"><i class="bi bi-printer"></i> Cetak Nota</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-bold text-brown m-0">Daftar Transaksi</h5>
                    <a href="create.php" class="btn-add text-decoration-none">
                        <i class="bi bi-plus-lg"></i> Transaksi Baru
                    </a>
                </div>

                <form method="GET" action="" class="mb-4">
                    <div class="row g-3"> <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari Nota / Nama..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
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
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No Nota</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                    <td><?php echo format_tanggal($row['tgl_penjualan']); ?></td>
                                    <td><?php echo $row['nama_customer'] ? $row['nama_customer'] : '<em class="text-muted">Umum</em>'; ?></td>
                                    <td><?php echo $row['jumlah_item']; ?> jenis</td>
                                    <td><strong class="text-success"><?php echo format_rupiah($row['total']); ?></strong></td>
                                    <td>
                                        <a href="nota.php?id=<?php echo $row['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-outline-brown" title="Cetak Nota"><i class="bi bi-printer"></i></a>
                                        <a href="?delete=<?php echo $row['id_penjualan']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus transaksi ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5">Tidak ada data ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() { document.body.classList.toggle('show-sidebar'); }
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('new_nota')) {
            const notaId = urlParams.get('new_nota');
            if(confirm("Transaksi Berhasil Disimpan!\nApakah Anda ingin mencetak nota sekarang?")) {
                window.open('nota.php?id=' + notaId, '_blank');
            }
        }
    </script>
</body>
</html>