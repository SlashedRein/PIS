<?php
// Deteksi URL dasar otomatis
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url    = '';

if (strpos($script_name, '/pages/') !== false) {
    $parts = explode('/pages/', $script_name);
    $base_url = $parts[0];
} else {
    $base_url = dirname($script_name);
    if ($base_url == '/' || $base_url == '\\') $base_url = '';
}

$current_page  = basename($_SERVER['PHP_SELF']);
$parent_folder = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-center gap-2">
        <div class="logo-icon">🍪</div>
        <div class="logo-text text-start">
            <h5 class="mb-0 fw-bold" style="font-size: 16px;">Dewi Cookies</h5>
        </div>
    </div>
    
    <div class="sidebar-nav mt-3">
        
        <div class="nav-section-title">Main Menu</div>
        <a href="<?php echo $base_url; ?>/pages/dashboard.php" 
           class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-section-title">Master Data</div>
        
        <a href="<?php echo $base_url; ?>/pages/supplier/index.php" 
           class="nav-link <?php echo ($parent_folder == 'supplier') ? 'active' : ''; ?>">
            <i class="bi bi-building"></i> <span>Supplier</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/pages/customer/index.php" 
           class="nav-link <?php echo ($parent_folder == 'customer') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> <span>Customer</span>
        </a>

        <div class="nav-section-title">Inventory</div>
        
        <a href="<?php echo $base_url; ?>/pages/bahan-baku/index.php" 
           class="nav-link <?php echo ($parent_folder == 'bahan-baku') ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i> <span>Bahan Baku</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/pages/produk/index.php" 
           class="nav-link <?php echo ($parent_folder == 'produk') ? 'active' : ''; ?>">
            <i class="bi bi-grid"></i> <span>Produk</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/pages/resep/index.php" 
           class="nav-link <?php echo ($parent_folder == 'resep') ? 'active' : ''; ?>">
            <i class="bi bi-journal-text"></i> <span>Resep</span>
        </a>

        <div class="nav-section-title">Transaksi</div>
        
        <a href="<?php echo $base_url; ?>/pages/pembelian/index.php" 
           class="nav-link <?php echo ($parent_folder == 'pembelian') ? 'active' : ''; ?>">
            <i class="bi bi-cart-plus"></i> <span>Pembelian</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/pages/penjualan/index.php" 
           class="nav-link <?php echo ($parent_folder == 'penjualan') ? 'active' : ''; ?>">
            <i class="bi bi-cash-coin"></i> <span>Penjualan</span>
        </a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'owner'): ?>
            <div class="nav-section-title">Reports</div>
            <a href="<?php echo $base_url; ?>/pages/laporan/index.php" 
               class="nav-link <?php echo ($parent_folder == 'laporan') ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i> <span>Laporan</span>
            </a>
        <?php endif; ?>
        
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnMobile = document.getElementById('btnMobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if(btnMobile && sidebar && overlay) {
            btnMobile.addEventListener('click', () => {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    });
</script>