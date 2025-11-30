<?php
require_once '../config/database.php';
session_start();

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}

$role = $_SESSION['role']; // Simpan role biar gampang ngeceknya

// --- QUERY DATA STOK (Untuk Semua Role) ---
$bahan_alert = $conn->query("SELECT COUNT(*) as jumlah FROM bahan_baku WHERE stok <= stok_min")->fetch_assoc()['jumlah'];
$produk_alert = $conn->query("SELECT COUNT(*) as jumlah FROM produk WHERE stok < 10")->fetch_assoc()['jumlah'];

$result_detail_bahan = $conn->query("SELECT * FROM bahan_baku WHERE stok <= stok_min ORDER BY stok ASC LIMIT 5");
$result_detail_produk = $conn->query("SELECT * FROM produk WHERE stok < 10 ORDER BY stok ASC LIMIT 5");

// --- QUERY DATA KEUANGAN (Hanya Owner) ---
$total_penjualan = 0;
$total_pembelian = 0;

if ($role == 'owner') {
    $total_penjualan = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM penjualan WHERE MONTH(tgl_penjualan) = MONTH(CURRENT_DATE) AND YEAR(tgl_penjualan) = YEAR(CURRENT_DATE)")->fetch_assoc()['total'];
    $total_pembelian = $conn->query("SELECT COALESCE(SUM(total_beli), 0) as total FROM pembelian WHERE MONTH(tgl) = MONTH(CURRENT_DATE) AND YEAR(tgl) = YEAR(CURRENT_DATE)")->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dewi Cookies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .hover-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: default;
        }
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(139, 69, 19, 0.15) !important;
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle">
                    <i class="bi bi-list"></i>
                </button>

                <div class="page-title">
                    <h5 class="fw-bold mb-0 text-dark">Dashboard</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">
                        Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!
                    </small>
                </div>
            </div>

            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info d-none d-md-block text-end">
                        <span class="name d-block text-dark fw-bold" style="font-size: 13px;">
                            <?php echo $_SESSION['nama_lengkap']; ?>
                        </span>
                        <span class="role d-block text-muted" style="font-size: 10px;">
                            <?php echo ucfirst($_SESSION['role']); ?>
                        </span>
                    </div>
                    <div class="user-avatar bg-primary text-white shadow-sm">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                    </div>
                </div>
                
                <div class="dropdown-menu-custom">
                    <a href="logout.php" class="dropdown-item-custom logout text-danger" id="btnLogout">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm h-100 border border-light position-relative overflow-hidden hover-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-secondary" style="font-size: 0.8rem;">BAHAN BAKU</div>
                            <span class="badge bg-warning text-dark rounded-pill">Restok</span>
                        </div>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $bahan_alert; ?></h2>
                        <small class="text-muted">Item perlu dibeli</small>
                        <div class="position-absolute end-0 bottom-0 p-3 opacity-10">
                            <i class="bi bi-box-seam text-warning" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm h-100 border border-light position-relative overflow-hidden hover-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-secondary" style="font-size: 0.8rem;">PRODUK</div>
                            <span class="badge bg-danger rounded-pill">Kritis</span>
                        </div>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $produk_alert; ?></h2>
                        <small class="text-muted">Stok menipis</small>
                        <div class="position-absolute end-0 bottom-0 p-3 opacity-10">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>

                <?php if ($role == 'owner'): ?>
                <div class="col-6 col-xl-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm h-100 border border-light position-relative overflow-hidden hover-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-secondary" style="font-size: 0.8rem;">OMZET (BLN)</div>
                            <i class="bi bi-graph-up-arrow text-success"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-0"><?php echo number_format($total_penjualan/1000, 0); ?>k</h2>
                        <small class="text-success fw-bold">+IDR</small>
                        <div class="position-absolute end-0 bottom-0 p-3 opacity-10">
                            <i class="bi bi-wallet2 text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm h-100 border border-light position-relative overflow-hidden hover-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-secondary" style="font-size: 0.8rem;">PENGELUARAN</div>
                            <i class="bi bi-cart text-primary"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-0"><?php echo number_format($total_pembelian/1000, 0); ?>k</h2>
                        <small class="text-primary fw-bold">-IDR</small>
                        <div class="position-absolute end-0 bottom-0 p-3 opacity-10">
                            <i class="bi bi-bag text-primary" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="bg-white p-4 rounded-4 shadow-sm h-100 border border-light hover-card">
                        <h6 class="fw-bold mb-3">⚠️ Bahan Baku Menipis</h6>
                        <?php if ($result_detail_bahan->num_rows > 0): ?>
                            <?php while($row = $result_detail_bahan->fetch_assoc()): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded bg-light">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-circle-fill text-warning" style="font-size: 8px;"></i>
                                        <span class="fw-medium small"><?php echo $row['nama_bahan']; ?></span>
                                    </div>
                                    <span class="badge bg-white text-dark border"><?php echo $row['stok'].' '.$row['satuan']; ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted small py-3">Semua stok aman!</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6">
                     <div class="bg-white p-4 rounded-4 shadow-sm h-100 border border-light hover-card">
                        <h6 class="fw-bold mb-3">📉 Produk Perlu Produksi</h6>
                        <?php if ($result_detail_produk->num_rows > 0): ?>
                            <?php while($row = $result_detail_produk->fetch_assoc()): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded bg-light">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-circle-fill text-danger" style="font-size: 8px;"></i>
                                        <span class="fw-medium small"><?php echo $row['nama_produk']; ?></span>
                                    </div>
                                    <span class="badge bg-white text-dark border"><?php echo $row['stok'].' '.$row['satuan']; ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted small py-3">Stok produk aman!</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ... script toggle sidebar (jika ada) ...

        // Script SweetAlert Logout
        const btnLogout = document.getElementById('btnLogout');
        if(btnLogout) {
            btnLogout.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah link langsung jalan
                const href = this.getAttribute('href'); // Ambil alamat logout

                Swal.fire({
                    title: 'Yakin ingin keluar?',
                    text: "Sesi Anda akan berakhir.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Keluar!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href; // Redirect manual jika user klik Ya
                    }
                });
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>