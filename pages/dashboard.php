<?php
require_once '../config/database.php';
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data untuk dashboard
$query_bahan_alert = "SELECT COUNT(*) as jumlah FROM bahan_baku WHERE stok <= stok_min";
$result_bahan = $conn->query($query_bahan_alert);
$bahan_alert = $result_bahan ? $result_bahan->fetch_assoc()['jumlah'] : 0;

$query_produk_alert = "SELECT COUNT(*) as jumlah FROM produk WHERE stok < 10";
$result_produk = $conn->query($query_produk_alert);
$produk_alert = $result_produk ? $result_produk->fetch_assoc()['jumlah'] : 0;

$query_penjualan = "SELECT COALESCE(SUM(total), 0) as total FROM penjualan 
                    WHERE MONTH(tgl_penjualan) = MONTH(CURRENT_DATE) 
                    AND YEAR(tgl_penjualan) = YEAR(CURRENT_DATE)";
$result_penjualan = $conn->query($query_penjualan);
$total_penjualan = $result_penjualan ? $result_penjualan->fetch_assoc()['total'] : 0;

$query_pembelian = "SELECT COALESCE(SUM(total_beli), 0) as total FROM pembelian 
                    WHERE MONTH(tgl) = MONTH(CURRENT_DATE) 
                    AND YEAR(tgl) = YEAR(CURRENT_DATE)";
$result_pembelian_stat = $conn->query($query_pembelian);
$total_pembelian = $result_pembelian_stat ? $result_pembelian_stat->fetch_assoc()['total'] : 0;

$query_detail_bahan = "SELECT * FROM bahan_baku WHERE stok <= stok_min ORDER BY stok ASC LIMIT 5";
$result_detail_bahan = $conn->query($query_detail_bahan);

$query_detail_produk = "SELECT * FROM produk WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$result_detail_produk = $conn->query($query_detail_produk);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dewi Cookies</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🍪</div>
            <div class="logo-text">
                <h5>Dewi Cookies</h5>
                <p>Management System</p>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main Menu</div>
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Master Data</div>
                <div class="nav-item">
                    <a href="supplier/index.php" class="nav-link">
                        <i class="bi bi-building"></i>
                        <span>Supplier</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="customer/index.php" class="nav-link">
                        <i class="bi bi-people"></i>
                        <span>Customer</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Inventory</div>
                <div class="nav-item">
                    <a href="bahan-baku/index.php" class="nav-link">
                        <i class="bi bi-box-seam"></i>
                        <span>Bahan Baku</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="produk/index.php" class="nav-link">
                        <i class="bi bi-grid"></i>
                        <span>Produk</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="resep/index.php" class="nav-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Resep</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Transaksi</div>
                <div class="nav-item">
                    <a href="pembelian/index.php" class="nav-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Pembelian</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="penjualan/index.php" class="nav-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Penjualan</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Reports</div>
                <div class="nav-item">
                    <a href="laporan/index.php" class="nav-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Laporan</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                </div>
                <div class="user-info">
                    <p class="name"><?php echo $_SESSION['nama_lengkap']; ?></p>
                    <p class="role"><?php echo ucfirst($_SESSION['role']); ?></p>
                </div>
            </div>
        </div> -->
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
                <div class="topbar">
            <div class="page-title">
                <h4>Dashboard Overview</h4>
                <p>Selamat datang kembali, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
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
                    <i class="bi bi-chevron-down profile-arrow"></i>
                </div>
                
                <div class="dropdown-menu-custom">
                    <div style="padding: 10px 15px; font-size: 11px; color: #aaa; font-weight: 600;">
                        ACCOUNT SETTINGS
                    </div>
                    <a href="#" class="dropdown-item-custom">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                    <a href="#" class="dropdown-item-custom">
                        <i class="bi bi-gear"></i> Pengaturan
                    </a>
                    
                    <a href="../logout.php" class="dropdown-item-custom logout" onclick="return confirm('Yakin ingin keluar dari sistem?')">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Stats Cards -->
            <div class="row g-4 stats-row">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon warning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <span class="stat-badge warning">Alert</span>
                        </div>
                        <div class="stat-value"><?php echo $bahan_alert; ?></div>
                        <div class="stat-label">Bahan Baku Perlu Restok</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon danger">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </div>
                            <span class="stat-badge danger">Low Stock</span>
                        </div>
                        <div class="stat-value"><?php echo $produk_alert; ?></div>
                        <div class="stat-label">Produk Stok Rendah</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon success">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <span class="stat-badge success">Revenue</span>
                        </div>
                        <div class="stat-value"><?php echo format_rupiah($total_penjualan); ?></div>
                        <div class="stat-label">Penjualan Bulan Ini</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon info">
                                <i class="bi bi-cart-fill"></i>
                            </div>
                            <span class="stat-badge info">Purchase</span>
                        </div>
                        <div class="stat-value"><?php echo format_rupiah($total_pembelian); ?></div>
                        <div class="stat-label">Pembelian Bulan Ini</div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Section -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="alert-box">
                        <div class="alert-box-header">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <h5>Bahan Baku Perlu Restok</h5>
                        </div>
                        
                        <?php if ($result_detail_bahan && $result_detail_bahan->num_rows > 0): ?>
                            <?php while ($bahan = $result_detail_bahan->fetch_assoc()): ?>
                                <div class="alert-item <?php echo $bahan['stok'] == 0 ? 'danger' : 'warning'; ?>">
                                    <div class="alert-item-header">
                                        <i class="bi bi-circle-fill <?php echo $bahan['stok'] == 0 ? 'text-danger' : 'text-warning'; ?>"></i>
                                        <span><?php echo $bahan['nama_bahan']; ?></span>
                                    </div>
                                    <div class="alert-item-detail">
                                        Stok: <strong><?php echo $bahan['stok']; ?> <?php echo $bahan['satuan']; ?></strong> 
                                        (Min: <?php echo $bahan['stok_min']; ?> <?php echo $bahan['satuan']; ?>)
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-alert">
                                <i class="bi bi-check-circle-fill"></i>
                                <h6>Semua Bahan Baku Aman!</h6>
                                <p>Tidak ada stok yang perlu di-restok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="alert-box">
                        <div class="alert-box-header">
                            <i class="bi bi-arrow-down-circle-fill"></i>
                            <h5>Produk Stok Rendah</h5>
                        </div>
                        
                        <?php if ($result_detail_produk && $result_detail_produk->num_rows > 0): ?>
                            <?php while ($produk = $result_detail_produk->fetch_assoc()): ?>
                                <div class="alert-item <?php echo $produk['stok'] == 0 ? 'danger' : 'warning'; ?>">
                                    <div class="alert-item-header">
                                        <i class="bi bi-circle-fill <?php echo $produk['stok'] == 0 ? 'text-danger' : 'text-warning'; ?>"></i>
                                        <span><?php echo $produk['nama_produk']; ?></span>
                                    </div>
                                    <div class="alert-item-detail">
                                        Stok: <strong><?php echo $produk['stok']; ?> <?php echo $produk['satuan']; ?></strong>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-alert">
                                <i class="bi bi-check-circle-fill"></i>
                                <h6>Semua Produk Stok Aman!</h6>
                                <p>Tidak ada produk dengan stok rendah</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>