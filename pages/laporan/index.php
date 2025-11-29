<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// --- [LOGIC 1] AJAX HANDLER: DETAIL NOTA ---
if (isset($_POST['get_detail_nota'])) {
    $id_penjualan = clean_input($_POST['id_penjualan']);
    
    // Ambil Detail Barang
    $query = "SELECT dp.*, p.nama_produk, p.satuan 
              FROM detail_penjualan dp 
              JOIN produk p ON dp.id_produk = p.id_produk 
              WHERE dp.id_penjualan = '$id_penjualan'";
    $result = $conn->query($query);
    
    // Ambil Header (Total Belanja)
    $header = $conn->query("SELECT total FROM penjualan WHERE id_penjualan = '$id_penjualan'")->fetch_assoc();

    $html = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $row['nama_produk'] . '</td>';
            $html .= '<td class="text-center">' . $row['jumlah'] . ' ' . $row['satuan'] . '</td>';
            $html .= '<td class="text-end">' . format_rupiah($row['harga_satuan']) . '</td>';
            $html .= '<td class="text-end fw-bold">' . format_rupiah($row['sub_total']) . '</td>';
            $html .= '</tr>';
        }
        // Baris Total
        $html .= '<tr class="table-light fw-bold">';
        $html .= '<td colspan="3" class="text-end">GRAND TOTAL :</td>';
        $html .= '<td class="text-end text-success">' . format_rupiah($header['total']) . '</td>';
        $html .= '</tr>';
    } else {
        $html .= '<tr><td colspan="4" class="text-center text-muted">Detail tidak ditemukan.</td></tr>';
    }
    
    echo $html;
    exit(); 
}

// --- FILTER TANGGAL ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- LOGIKA QUERY PEMISAH ---
$keyword_tetap = " (c.nama LIKE '%PT%' OR c.nama LIKE '%CV%' OR c.nama LIKE '%UD%') ";

// 1. Pelanggan Biasa (Harian)
$query_biasa = "SELECT p.*, c.nama 
                FROM penjualan p 
                JOIN customer c ON p.id_cust = c.id_cust
                WHERE (p.tgl_penjualan BETWEEN '$start_date' AND '$end_date')
                AND NOT ($keyword_tetap)
                ORDER BY p.tgl_penjualan DESC";
$res_biasa = $conn->query($query_biasa);

// 2. Pelanggan Tetap (Rekap Mingguan)
$query_tetap = "SELECT 
                    c.nama,
                    YEAR(p.tgl_penjualan) as tahun,
                    WEEK(p.tgl_penjualan) as minggu_ke,
                    MIN(p.tgl_penjualan) as tgl_awal_minggu,
                    MAX(p.tgl_penjualan) as tgl_akhir_minggu,
                    COUNT(p.id_penjualan) as jumlah_transaksi,
                    SUM(p.total) as total_tagihan
                FROM penjualan p 
                JOIN customer c ON p.id_cust = c.id_cust
                WHERE (p.tgl_penjualan BETWEEN '$start_date' AND '$end_date')
                AND ($keyword_tetap)
                GROUP BY c.id_cust, YEAR(p.tgl_penjualan), WEEK(p.tgl_penjualan)
                ORDER BY tgl_akhir_minggu DESC"; 
$res_tetap = $conn->query($query_tetap);

$grand_total_biasa = 0;
$grand_total_tetap = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Dewi Cookies</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        .nav-tabs .nav-link { color: #8B4513; border: none; font-weight: 500; padding: 10px 20px; }
        .nav-tabs .nav-link.active { 
            background-color: #FFF8E1; 
            color: #8B4513; 
            border-bottom: 3px solid #8B4513;
            font-weight: 700;
        }
        .btn-brown { background-color: #8B4513; color: white; border: none; }
        .btn-brown:hover { background-color: #6F3410; color: white; }
    </style>
</head>
<body>

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
            <a href="../penjualan/index.php" class="nav-link"><i class="bi bi-cash-coin"></i> <span>Penjualan</span></a>
            
            <div class="nav-section-title">Reports</div>
            <a href="index.php" class="nav-link active"><i class="bi bi-graph-up"></i> <span>Laporan</span></a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title">
                    <h5 class="fw-bold mb-0 text-dark">Laporan Keuangan</h5>
                </div>
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
                    <a href="#" class="dropdown-item-custom logout text-danger" id="btnLogout"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <div class="bg-white rounded-4 shadow-sm border border-light p-4 mb-4">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Pencarian</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="No Nota / Nama..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-brown px-4 fw-bold shadow-sm">Filter Data</button>
                            <?php if(isset($_GET['q']) || isset($_GET['start_date'])): ?>
                                <a href="index.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <ul class="nav nav-tabs" id="laporanTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="biasa-tab" data-bs-toggle="tab" data-bs-target="#biasa" type="button">
                        <i class="bi bi-people"></i> Pelanggan Umum (Harian)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tetap-tab" data-bs-toggle="tab" data-bs-target="#tetap" type="button">
                        <i class="bi bi-building"></i> Pelanggan Tetap / PT (Mingguan)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="laporanTabContent">
                
                <div class="tab-pane fade show active" id="biasa" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark m-0">Detail Transaksi Harian</h6>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>No Nota</th>
                                        <th>Tanggal</th>
                                        <th>Customer</th>
                                        <th class="text-end">Total Belanja</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($res_biasa->num_rows > 0): ?>
                                        <?php while($row = $res_biasa->fetch_assoc()): 
                                            $grand_total_biasa += $row['total'];
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-secondary font-monospace">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                            <td><?php echo format_tanggal($row['tgl_penjualan']); ?></td>
                                            <td><?php echo $row['nama']; ?></td>
                                            <td class="text-end fw-bold"><?php echo format_rupiah($row['total']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-info text-white shadow-sm" 
                                                        onclick="showDetail(<?php echo $row['id_penjualan']; ?>, '<?php echo $row['nama']; ?>')" title="Lihat Detail Barang">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="../penjualan/nota.php?id=<?php echo $row['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="Cetak Nota"><i class="bi bi-printer"></i></a>
                                                
                                                <button onclick="confirmDelete('../penjualan/index.php?delete=<?php echo $row['id_penjualan']; ?>')" class="btn btn-sm btn-outline-danger ms-1" title="Hapus"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">TOTAL PENDAPATAN :</td>
                                        <td colspan="2" class="text-start fw-bold py-3 text-success fs-5 px-3"><?php echo format_rupiah($grand_total_biasa); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tetap" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <small>Rekapitulasi mingguan untuk Customer <b>PT / CV / UD</b>.</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer (PT/CV)</th>
                                        <th>Periode Minggu</th>
                                        <th class="text-center">Jml Order</th>
                                        <th class="text-end">Total Tagihan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($res_tetap->num_rows > 0): ?>
                                        <?php while($row = $res_tetap->fetch_assoc()): 
                                            $grand_total_tetap += $row['total_tagihan'];
                                            $periode = date('d M', strtotime($row['tgl_awal_minggu'])) . " - " . date('d M Y', strtotime($row['tgl_akhir_minggu']));
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?php echo $row['nama']; ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border">Minggu ke-<?php echo $row['minggu_ke']; ?></span>
                                                <br><small class="text-muted"><?php echo $periode; ?></small>
                                            </td>
                                            <td class="text-center"><?php echo $row['jumlah_transaksi']; ?>x</td>
                                            <td class="text-end fw-bold text-danger"><?php echo format_rupiah($row['total_tagihan']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Invoice</button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data pelanggan tetap.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">TOTAL TAGIHAN :</td>
                                        <td colspan="2" class="text-start fw-bold py-3 text-danger fs-5 px-3"><?php echo format_rupiah($grand_total_tetap); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div> 
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Detail Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="px-3 pb-2 text-muted small">Customer: <strong id="detailCustomerName" class="text-dark">...</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end pe-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detailContent">
                                </tbody>
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
        // Logic Sidebar Mobile
        const btnMobile = document.getElementById('btnMobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if(btnMobile) {
            btnMobile.addEventListener('click', () => {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            });
        }
        if(overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        // Logic Pop-up Detail via AJAX
        function showDetail(idPenjualan, customerName) {
            $('#detailCustomerName').text(customerName);
            $('#detailContent').html('<tr><td colspan="4" class="text-center py-3 text-muted">Memuat data...</td></tr>');
            
            var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();

            $.ajax({
                url: '', 
                type: 'POST',
                data: { get_detail_nota: true, id_penjualan: idPenjualan },
                success: function(response) {
                    $('#detailContent').html(response);
                },
                error: function() {
                    $('#detailContent').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        // --- SWEETALERT LOGOUT (SERAGAM DENGAN CUSTOMER/SUPPLIER) ---
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Keluar?',
                text: "Anda harus login kembali nanti.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php'; // Naik 1 level (karena file ini di pages/laporan/)
                }
            });
        });

        // --- SWEETALERT DELETE (SERAGAM) ---
        function confirmDelete(url) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data akan hilang permanen & stok barang akan dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>