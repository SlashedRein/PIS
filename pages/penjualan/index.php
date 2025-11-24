<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- LOGIKA HAPUS TRANSAKSI ---
if (isset($_GET['delete'])) {
    $id_penjualan = clean_input($_GET['delete']);
    
    // Ambil item untuk restore stok
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

$query = "SELECT p.*, c.nama as nama_customer, 
          (SELECT COUNT(*) FROM detail_penjualan WHERE id_penjualan = p.id_penjualan) as jumlah_item
          FROM penjualan p 
          LEFT JOIN customer c ON p.id_cust = c.id_cust 
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
        /* SETUP SIDEBAR & OVERLAY */
        .sidebar {
            width: 260px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            transition: transform 0.3s ease-in-out;
            z-index: 1050;
        }
        .main-content {
            margin-left: 260px;
            transition: margin-left 0.3s ease-in-out;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            cursor: pointer;
        }
        #mobile-toggle { display: none; }

        /* RESPONSIVE: HP & TABLET */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            #mobile-toggle {
                display: block;
                font-size: 1.5rem;
                cursor: pointer;
                margin-right: 15px;
                color: var(--primary-color);
            }
            
            body.show-sidebar .sidebar {
                transform: translateX(0);
            }
            body.show-sidebar .overlay {
                display: block;
            }
        }

        /* CUSTOM BUTTON COLOR (Brown Outline) */
        .btn-outline-brown {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-brown:hover {
            background-color: var(--primary-color);
            color: #fff;
        }
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
            <a href="../dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
            
            <div class="nav-section-title">Master Data</div>
            <a href="../supplier/index.php" class="nav-link">
                <i class="bi bi-building"></i> <span>Supplier</span>
            </a>
            <a href="../customer/index.php" class="nav-link">
                <i class="bi bi-people"></i> <span>Customer</span>
            </a>

            <div class="nav-section-title">Inventory</div>
            <a href="../bahan-baku/index.php" class="nav-link">
                <i class="bi bi-box-seam"></i> <span>Bahan Baku</span>
            </a>
            <a href="../produk/index.php" class="nav-link">
                <i class="bi bi-grid"></i> <span>Produk</span>
            </a>
             <a href="../resep/index.php" class="nav-link">
                <i class="bi bi-journal-text"></i> <span>Resep</span>
            </a>

            <div class="nav-section-title">Transaksi</div>
            <a href="../pembelian/index.php" class="nav-link">
                <i class="bi bi-cart-plus"></i> <span>Pembelian</span>
            </a>
            <a href="index.php" class="nav-link active">
                <i class="bi bi-cash-coin"></i> <span>Penjualan</span>
            </a>

            <div class="nav-section-title">Reports</div>
            <a href="../laporan/index.php" class="nav-link">
                <i class="bi bi-graph-up"></i> <span>Laporan</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list" id="mobile-toggle" onclick="toggleSidebar()"></i>
                <div class="page-title">
                    <h4 style="margin:0;">Riwayat Penjualan</h4>
                </div>
            </div>
            
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info">
                        <span class="name"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        <span class="role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                    </div>
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

            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 style="margin:0; font-weight:700; color:var(--primary-color);">Daftar Transaksi</h5>
                    <a href="create.php" class="btn-add text-decoration-none">
                        <i class="bi bi-plus-lg"></i> Transaksi Baru
                    </a>
                </div>

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
                                        <a href="nota.php?id=<?php echo $row['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-outline-brown"><i class="bi bi-printer"></i></a>
                                        <a href="?delete=<?php echo $row['id_penjualan']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus transaksi ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5">Belum ada transaksi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.body.classList.toggle('show-sidebar');
        }
    </script>
</body>
</html>