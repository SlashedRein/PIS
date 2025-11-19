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
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --dark-color: #0f172a;
            --light-bg: #f8fafc;
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-collapsed);
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow: hidden;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
        }
        
        .sidebar:hover {
            width: var(--sidebar-width);
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .logo-text {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar:hover .logo-text {
            opacity: 1;
        }
        
        .logo-text h5 {
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            margin: 0;
        }
        
        .logo-text p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            margin: 0;
        }
        
        .sidebar-nav {
            padding: 20px 12px;
            overflow-y: auto;
            height: calc(100vh - 180px);
        }
        
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 16px;
            margin-bottom: 8px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar:hover .nav-section-title {
            opacity: 1;
        }
        
        .nav-item {
            margin-bottom: 4px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            white-space: nowrap;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .nav-link i {
            font-size: 20px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .nav-link span {
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar:hover .nav-link span {
            opacity: 1;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .user-info {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar:hover .user-info {
            opacity: 1;
        }
        
        .user-info .name {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }
        
        .user-info .role {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            margin: 0;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-collapsed);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }
        
        /* Topbar */
        .topbar {
            background: #fff;
            padding: 20px 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .topbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title h4 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }
        
        .page-title p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        
        .btn-logout {
            background: var(--dark-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-logout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Content Area */
        .content-area {
            padding: 32px;
        }
        
        /* Stats Cards */
        .stats-row {
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: var(--warning-color);
        }
        
        .stat-icon.danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: var(--success-color);
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, #cffafe, #a5f3fc);
            color: var(--info-color);
        }
        
        .stat-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .stat-badge.warning {
            background: #fef3c7;
            color: var(--warning-color);
        }
        
        .stat-badge.danger {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .stat-badge.success {
            background: #d1fae5;
            color: var(--success-color);
        }
        
        .stat-badge.info {
            background: #cffafe;
            color: var(--info-color);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: var(--secondary-color);
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Alert Section */
        .alert-box {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .alert-box-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }
        
        .alert-box-header i {
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .alert-box-header h5 {
            margin: 0;
            font-weight: 700;
            color: var(--dark-color);
            font-size: 18px;
        }
        
        .alert-item {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 3px solid;
            transition: all 0.2s;
        }
        
        .alert-item:last-child {
            margin-bottom: 0;
        }
        
        .alert-item:hover {
            transform: translateX(4px);
        }
        
        .alert-item.warning {
            background: #fef3c7;
            border-left-color: var(--warning-color);
        }
        
        .alert-item.danger {
            background: #fee2e2;
            border-left-color: var(--danger-color);
        }
        
        .alert-item-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .alert-item-header i {
            font-size: 16px;
        }
        
        .alert-item-header span {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
        }
        
        .alert-item-detail {
            color: var(--secondary-color);
            font-size: 13px;
            padding-left: 24px;
        }
        
        .alert-item-detail strong {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .no-alert {
            text-align: center;
            padding: 48px 24px;
        }
        
        .no-alert i {
            font-size: 48px;
            color: var(--success-color);
            margin-bottom: 16px;
        }
        
        .no-alert h6 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .no-alert p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .content-area {
                padding: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                width: var(--sidebar-width);
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .topbar {
                padding: 16px 20px;
            }
            
            .content-area {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 24px;
            }
        }
    </style>
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
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                </div>
                <div class="user-info">
                    <p class="name"><?php echo $_SESSION['nama_lengkap']; ?></p>
                    <p class="role"><?php echo ucfirst($_SESSION['role']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-content">
                <div class="page-title">
                    <h4>Dashboard</h4>
                    <p>Selamat datang kembali, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
                </div>
                <a href="../logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
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