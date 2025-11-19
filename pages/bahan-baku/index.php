<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $delete_query = "DELETE FROM bahan_baku WHERE id_bahan = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Bahan baku berhasil dihapus!";
    } else {
        $error = "Gagal menghapus bahan baku!";
    }
}

// Ambil semua data bahan baku
$query = "SELECT * FROM bahan_baku ORDER BY nama_bahan ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahan Baku - Dewi Cookies</title>
    
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        
        .btn-add {
            background: var(--primary-color);
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
            text-decoration: none;
        }
        
        .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        /* Content Area */
        .content-area {
            padding: 32px;
        }
        
        /* Alert */
        .alert-custom {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }
        
        .alert-custom.success {
            background: #d1fae5;
            border-left-color: var(--success-color);
            color: #065f46;
        }
        
        .alert-custom.danger {
            background: #fee2e2;
            border-left-color: var(--danger-color);
            color: #991b1b;
        }
        
        .alert-custom i {
            font-size: 20px;
        }
        
        /* Table Card */
        .table-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: #f8fafc;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 16px 20px;
            vertical-align: middle;
            color: var(--secondary-color);
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Badge */
        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .badge-custom.danger {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .badge-custom.warning {
            background: #fef3c7;
            color: var(--warning-color);
        }
        
        .badge-custom.success {
            background: #d1fae5;
            color: var(--success-color);
        }
        
        /* Action Buttons */
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-action.edit {
            background: #fef3c7;
            color: var(--warning-color);
        }
        
        .btn-action.edit:hover {
            background: #fde68a;
            transform: translateY(-1px);
        }
        
        .btn-action.delete {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .btn-action.delete:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: var(--secondary-color);
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        .empty-state h5 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: var(--secondary-color);
            font-size: 14px;
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
            
            .topbar-content {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
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
                    <a href="../dashboard.php" class="nav-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Master Data</div>
                <div class="nav-item">
                    <a href="../supplier/index.php" class="nav-link">
                        <i class="bi bi-building"></i>
                        <span>Supplier</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../customer/index.php" class="nav-link">
                        <i class="bi bi-people"></i>
                        <span>Customer</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Inventory</div>
                <div class="nav-item">
                    <a href="index.php" class="nav-link active">
                        <i class="bi bi-box-seam"></i>
                        <span>Bahan Baku</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../produk/index.php" class="nav-link">
                        <i class="bi bi-grid"></i>
                        <span>Produk</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../resep/index.php" class="nav-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Resep</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Transaksi</div>
                <div class="nav-item">
                    <a href="../pembelian/index.php" class="nav-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Pembelian</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../penjualan/index.php" class="nav-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Penjualan</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Reports</div>
                <div class="nav-item">
                    <a href="../laporan/index.php" class="nav-link">
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
                    <h4>
                        <i class="bi bi-box-seam"></i>
                        <span>Bahan Baku</span>
                    </h4>
                    <p>Kelola stok bahan baku untuk produksi</p>
                </div>
                <a href="tambah.php" class="btn-add">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Bahan Baku</span>
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <?php if (isset($success)): ?>
                <div class="alert-custom success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert-custom danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Bahan</th>
                                <th>Satuan</th>
                                <th>Stok</th>
                                <th>Stok Minimum</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                                    // Tentukan status stok
                                    if ($row['stok'] == 0) {
                                        $status = '<span class="badge-custom danger"><i class="bi bi-circle-fill"></i> Habis</span>';
                                    } elseif ($row['stok'] <= $row['stok_min']) {
                                        $status = '<span class="badge-custom warning"><i class="bi bi-circle-fill"></i> Perlu Restok</span>';
                                    } else {
                                        $status = '<span class="badge-custom success"><i class="bi bi-check-circle-fill"></i> Aman</span>';
                                    }
                            ?>
                                <tr>
                                    <td><strong><?php echo $no++; ?></strong></td>
                                    <td><strong style="color: var(--dark-color);"><?php echo $row['nama_bahan']; ?></strong></td>
                                    <td><?php echo $row['satuan']; ?></td>
                                    <td><strong style="color: var(--dark-color);"><?php echo $row['stok']; ?></strong></td>
                                    <td><?php echo $row['stok_min']; ?></td>
                                    <td><?php echo $status; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id_bahan']; ?>" 
                                           class="btn-action edit">
                                            <i class="bi bi-pencil-square"></i>
                                            Edit
                                        </a>
                                        <a href="?delete=<?php echo $row['id_bahan']; ?>" 
                                           class="btn-action delete"
                                           onclick="return confirm('Yakin ingin menghapus bahan ini?')">
                                            <i class="bi bi-trash"></i>
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h5>Belum Ada Data Bahan Baku</h5>
                                            <p>Klik tombol "Tambah Bahan Baku" untuk menambahkan data pertama</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>