<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = clean_input($_POST['nama_produk']);
    $satuan = clean_input($_POST['satuan']);
    $harga_jual = clean_input($_POST['harga_jual']);
    $stok = clean_input($_POST['stok']);
    
    $query = "INSERT INTO produk (nama_produk, satuan, harga_jual, stok) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $nama_produk, $satuan, $harga_jual, $stok);
    
    if ($stmt->execute()) {
        $success = "Produk berhasil ditambahkan!";
        header("refresh:2;url=index.php");
    } else {
        $error = "Gagal menambahkan produk!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Dewi Cookies</title>
    
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
        
        .page-title h4 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        
        /* Content Area */
        .content-area {
            padding: 32px;
        }
        
        .alert-custom {
            border-radius: 10px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        
        .alert-custom i {
            font-size: 20px;
        }
        
        .alert-custom.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .alert-custom.danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }
        
        .form-text {
            font-size: 12px;
            color: var(--secondary-color);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .form-text i {
            font-size: 12px;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-cancel {
            background: rgba(100, 116, 139, 0.1);
            color: var(--secondary-color);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: rgba(100, 116, 139, 0.2);
            transform: translateY(-2px);
        }
        
        @media (max-width: 992px) {
            .content-area { padding: 24px; }
            .form-card { padding: 24px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 0; transform: translateX(-100%); }
            .sidebar.show { width: var(--sidebar-width); transform: translateX(0); }
            .main-content { margin-left: 0; }
            .topbar { padding: 16px 20px; }
            .content-area { padding: 20px; }
            .form-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">Cookie</div>
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
                    <a href="../bahan-baku/index.php" class="nav-link">
                        <i class="bi bi-box-seam"></i>
                        <span>Bahan Baku</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="index.php" class="nav-link active">
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
            <div class="page-title">
                <h4>
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Produk</span>
                </h4>
                <p>Tambahkan produk baru ke inventory</p>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert-custom success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-custom danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="nama_produk" class="form-label">Nama Produk *</label>
                        <input type="text" class="form-control" id="nama_produk" name="nama_produk" 
                               placeholder="Contoh: Nastar Keju" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label for="satuan" class="form-label">Satuan *</label>
                        <select class="form-select" id="satuan" name="satuan" required>
                            <option value="">-- Pilih Satuan --</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="gram">Gram (g)</option>
                            <option value="liter">Liter (L)</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="pack">Pack</option>
                            <option value="toples">Toples</option>
                            <option value="box">Box</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="harga_jual" class="form-label">Harga Jual (Rp) *</label>
                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                               value="0" min="0" step="100" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="stok" class="form-label">Stok Awal *</label>
                        <input type="number" class="form-control" id="stok" name="stok" 
                               value="0" min="0" required>
                        <div class="form-text">Masukkan jumlah stok awal produk jadi</div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-check-circle"></i>
                            <span>Simpan</span>
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-x-circle"></i>
                            <span>Batal</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>