<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// --- [LOGIC 1] AJAX HANDLER: AMBIL DETAIL NOTA ---
// Bagian ini menangani request dari Javascript saat tombol "Mata" diklik
if (isset($_POST['get_detail_nota'])) {
    $id_penjualan = clean_input($_POST['id_penjualan']);
    
    // Ambil Detail Barang
    $query = "SELECT dp.*, p.nama_produk, p.satuan 
              FROM detail_penjualan dp 
              JOIN produk p ON dp.id_produk = p.id_produk 
              WHERE dp.id_penjualan = '$id_penjualan'";
    $result = $conn->query($query);
    
    // Ambil Info Header (Total & Tanggal)
    $header = $conn->query("SELECT total FROM penjualan WHERE id_penjualan = '$id_penjualan'")->fetch_assoc();

    // Generate HTML Baris Tabel untuk dimasukkan ke Modal
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
    exit(); // Stop script disini agar tidak me-load halaman web utuh
}

// --- FILTER TANGGAL (Default: Bulan Ini) ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- LOGIKA QUERY PEMISAH ---
// Kita asumsikan Pelanggan Tetap punya nama mengandung "PT", "CV", atau "UD"
$keyword_tetap = " (c.nama LIKE '%PT%' OR c.nama LIKE '%CV%' OR c.nama LIKE '%UD%') ";

// 1. QUERY PELANGGAN BIASA (Harian)
// Logic: Ambil semua KECUALI yang namanya ada PT/CV/UD
$query_biasa = "SELECT p.*, c.nama 
                FROM penjualan p 
                JOIN customer c ON p.id_cust = c.id_cust
                WHERE (p.tgl_penjualan BETWEEN '$start_date' AND '$end_date')
                AND NOT ($keyword_tetap)
                ORDER BY p.tgl_penjualan DESC";
$res_biasa = $conn->query($query_biasa);

// 2. QUERY PELANGGAN TETAP (Rekap Mingguan/Invoice)
// Logic: Hanya ambil yang PT/CV/UD, lalu GROUP BY Minggu & Customer
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

// Hitung Total Keseluruhan untuk Summary
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    
    <style>
        /* Custom Tabs Style */
        .nav-tabs .nav-link { color: var(--primary-color); border: none; font-weight: 500; }
        .nav-tabs .nav-link.active { 
            background-color: #FFF8E1; 
            color: var(--primary-color); 
            border-bottom: 3px solid var(--primary-color);
            font-weight: 700;
        }
        .nav-tabs { border-bottom: 2px solid #eee; margin-bottom: 20px; }
        
        /* Sidebar Toggle */
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
                <button class="btn-mobile-toggle" id="mobile-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
                <div class="page-title"><h4 class="m-0">Laporan Keuangan</h4></div>
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
                    <a href="../../logout.php" class="dropdown-item-custom logout text-danger"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <div class="bg-white rounded-4 shadow-sm border border-light p-4 mb-4">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-brown w-100"><i class="bi bi-filter"></i> Tampilkan Laporan</button>
                        </div>
                    </div>
                </form>
            </div>

            <ul class="nav nav-tabs" id="laporanTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="biasa-tab" data-bs-toggle="tab" data-bs-target="#biasa" type="button" role="tab">
                        <i class="bi bi-people"></i> Pelanggan Umum (Harian)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tetap-tab" data-bs-toggle="tab" data-bs-target="#tetap" type="button" role="tab">
                        <i class="bi bi-building"></i> Pelanggan Tetap / PT (Mingguan)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="laporanTabContent">
                
                <div class="tab-pane fade show active" id="biasa" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                        <h6 class="fw-bold mb-3 text-dark">Detail Transaksi Harian (Umum)</h6>
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
                                                <button class="btn btn-sm btn-info text-white rounded-2" 
                                                        onclick="showDetail(<?php echo $row['id_penjualan']; ?>, '<?php echo $row['nama']; ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi pelanggan umum.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">TOTAL PENDAPATAN (UMUM) :</td>
                                        <td colspan="2" class="text-end fw-bold py-3 text-success fs-5 px-3"><?php echo format_rupiah($grand_total_biasa); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tetap" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                        <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <small>Data ini menampilkan rekap tagihan mingguan untuk Customer dengan nama mengandung <b>"PT", "CV", atau "UD"</b>.</small>
                        </div>

                        <h6 class="fw-bold mb-3 text-dark">Rekap Tagihan Mingguan (Invoice)</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer (PT/CV)</th>
                                        <th>Periode Minggu</th>
                                        <th class="text-center">Jml Transaksi</th>
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
                                                <span class="badge bg-light text-dark border">
                                                    Minggu ke-<?php echo $row['minggu_ke']; ?>
                                                </span>
                                                <br><small class="text-muted"><?php echo $periode; ?></small>
                                            </td>
                                            <td class="text-center"><?php echo $row['jumlah_transaksi']; ?>x Order</td>
                                            <td class="text-end fw-bold text-danger"><?php echo format_rupiah($row['total_tagihan']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-secondary" title="Cetak Invoice Mingguan"><i class="bi bi-printer"></i></button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi pelanggan tetap (PT/CV).</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">TOTAL TAGIHAN (PT/CV) :</td>
                                        <td colspan="2" class="text-end fw-bold py-3 text-danger fs-5 px-3"><?php echo format_rupiah($grand_total_tetap); ?></td>
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
                    <h5 class="modal-title fw-bold">Detail Penjualan</h5>
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
                                <tr><td colspan="4" class="text-center py-3">Memuat data...</td></tr>
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
    
    <script>
        function toggleSidebar() { document.body.classList.toggle('show-sidebar'); }

        // FUNGSI AJAX TAMPILKAN DETAIL
        function showDetail(idPenjualan, customerName) {
            // 1. Set Nama Customer di Modal
            $('#detailCustomerName').text(customerName);
            
            // 2. Reset isi tabel ke loading
            $('#detailContent').html('<tr><td colspan="4" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat data...</td></tr>');
            
            // 3. Buka Modal
            var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();

            // 4. Panggil Data via AJAX
            $.ajax({
                url: '', // Post ke halaman ini sendiri
                type: 'POST',
                data: { get_detail_nota: true, id_penjualan: idPenjualan },
                success: function(response) {
                    $('#detailContent').html(response);
                },
                error: function() {
                    $('#detailContent').html('<tr><td colspan="4" class="text-center text-danger">Gagal mengambil data.</td></tr>');
                }
            });
        }
    </script>
</body>
</html>