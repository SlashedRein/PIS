<?php
session_start();
require_once '../../config/database.php';

// 1. SET TIMEZONE
date_default_timezone_set('Asia/Jakarta');

// 2. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// 3. Proteksi Owner
$role = $_SESSION['role'];
if ($role !== 'owner') {
    echo "<script>alert('Akses Ditolak!'); window.location.href = '../dashboard.php';</script>";
    exit();
}

// 4. Inisialisasi Variabel
$grand_total_biasa = 0;
$grand_total_tetap = 0;

// --- [LOGIC 1] AJAX HANDLER: DETAIL NOTA ---
if (isset($_POST['get_detail_nota'])) {
    $id_penjualan = clean_input($_POST['id_penjualan']);
    
    $query = "SELECT dp.*, p.nama_produk, p.satuan 
              FROM detail_penjualan dp 
              LEFT JOIN produk p ON dp.id_produk = p.id_produk 
              WHERE dp.id_penjualan = '$id_penjualan'";
    $result = $conn->query($query);
    
    $header_q = $conn->query("SELECT total FROM penjualan WHERE id_penjualan = '$id_penjualan'");
    $header = ($header_q->num_rows > 0) ? $header_q->fetch_assoc() : ['total' => 0];

    $html = '';
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['nama_produk']) . '</td>
                <td class="text-center">' . $row['jumlah'] . ' ' . $row['satuan'] . '</td>
                <td class="text-end">' . format_rupiah($row['harga_satuan']) . '</td>
                <td class="text-end fw-bold">' . format_rupiah($row['sub_total']) . '</td>
            </tr>';
        }
        $html .= '<tr class="table-light fw-bold"><td colspan="3" class="text-end">GRAND TOTAL :</td><td class="text-end text-success">' . format_rupiah($header['total']) . '</td></tr>';
    } else {
        $html .= '<tr><td colspan="4" class="text-center text-muted">Detail tidak ditemukan.</td></tr>';
    }
    echo $html;
    exit(); 
}

// --- [LOGIC 2] FILTER TANGGAL (REVISI: DEFAULT SEMUA DATA) ---
$filter_mode = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Default: SEMUA DATA

switch ($filter_mode) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
        $label_filter = "Hari Ini (" . date('d M Y') . ")";
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date   = date('Y-m-d', strtotime('sunday this week'));
        $label_filter = "Minggu Ini (" . date('d M', strtotime($start_date)) . " - " . date('d M', strtotime($end_date)) . ")";
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date   = date('Y-m-t');
        $label_filter = "Bulan Ini (" . date('F Y') . ")";
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $label_filter = "Periode " . date('d M y', strtotime($start_date)) . " s/d " . date('d M y', strtotime($end_date));
        break;
    case 'all':
    default:
        // Tampilkan semua data dari tahun 2000 sampai tahun depan (agar aman)
        $start_date = '2000-01-01';
        $end_date   = date('Y-12-31', strtotime('+1 year')); 
        $label_filter = "Semua Riwayat Transaksi";
        $filter_mode = 'all'; // Paksa set ke 'all' jika masuk default
        break;
}

// --- [LOGIC 3] QUERY UTAMA ---
$keyword_tetap = " (IFNULL(c.nama, '') LIKE '%PT%' OR IFNULL(c.nama, '') LIKE '%CV%' OR IFNULL(c.nama, '') LIKE '%UD%') ";
$search_q = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$sql_search = !empty($search_q) ? " AND (p.id_penjualan LIKE '%$search_q%' OR IFNULL(c.nama, '') LIKE '%$search_q%') " : "";

// A. Pelanggan Umum
$query_biasa = "SELECT p.*, IFNULL(c.nama, 'Umum / Tanpa Nama') as nama_cust 
                FROM penjualan p 
                LEFT JOIN customer c ON p.id_cust = c.id_cust
                WHERE (p.tgl_penjualan BETWEEN '$start_date' AND '$end_date')
                AND NOT ($keyword_tetap)
                $sql_search
                ORDER BY p.tgl_penjualan DESC, p.id_penjualan DESC";
$res_biasa = $conn->query($query_biasa);

// B. Pelanggan Tetap
$query_tetap = "SELECT 
                    IFNULL(c.nama, 'Tanpa Nama') as nama_cust,
                    YEAR(p.tgl_penjualan) as tahun,
                    WEEK(p.tgl_penjualan, 1) as minggu_ke,
                    MIN(p.tgl_penjualan) as tgl_awal_minggu,
                    MAX(p.tgl_penjualan) as tgl_akhir_minggu,
                    COUNT(p.id_penjualan) as jumlah_transaksi,
                    SUM(p.total) as total_tagihan
                FROM penjualan p 
                LEFT JOIN customer c ON p.id_cust = c.id_cust
                WHERE (p.tgl_penjualan BETWEEN '$start_date' AND '$end_date')
                AND ($keyword_tetap)
                $sql_search
                GROUP BY c.id_cust, YEAR(p.tgl_penjualan), WEEK(p.tgl_penjualan, 1)
                ORDER BY tgl_akhir_minggu DESC"; 
$res_tetap = $conn->query($query_tetap);
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
        .nav-tabs .nav-link { color: #8B4513; font-weight: 500; padding: 10px 20px; }
        .nav-tabs .nav-link.active { background-color: #FFF8E1; color: #8B4513; border-bottom: 3px solid #8B4513; font-weight: 700; }
        .btn-filter-group .btn { border-radius: 20px; padding: 5px 15px; font-size: 0.85rem; }
        .btn-filter-group .btn.active { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
        .btn-brown { background-color: var(--primary-color); color: white; }
    </style>
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title"><h4 class="m-0">Laporan Keuangan</h4></div>
            </div>
            
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info d-none d-md-block text-end">
                        <span class="name d-block text-dark fw-bold" style="font-size: 13px;"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        <span class="role d-block text-muted" style="font-size: 10px;"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <div class="user-avatar bg-primary text-white shadow-sm"><?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?></div>
                </div>
                <div class="dropdown-menu-custom">
                    <a href="../logout.php" class="dropdown-item-custom logout text-danger" id="btnLogout"><i class="bi bi-power"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <div class="bg-white rounded-4 shadow-sm border border-light p-4 mb-4">
                <form method="GET" action="">
                    <div class="row g-2 align-items-end">
                        
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Cari Nota / Nama</label>
                            <input type="text" name="q" class="form-control form-control-sm" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Contoh: #0001">
                        </div>

                        <div class="col-md-5 text-center">
                            <label class="form-label small fw-bold text-muted d-block">Filter Periode</label>
                            <div class="btn-group btn-filter-group" role="group">
                                <a href="?filter=all&q=<?php echo $search_q; ?>" class="btn btn-outline-secondary <?php echo ($filter_mode == 'all') ? 'active' : ''; ?>">Semua</a>
                                <a href="?filter=today&q=<?php echo $search_q; ?>" class="btn btn-outline-secondary <?php echo ($filter_mode == 'today') ? 'active' : ''; ?>">Hari Ini</a>
                                <a href="?filter=month&q=<?php echo $search_q; ?>" class="btn btn-outline-secondary <?php echo ($filter_mode == 'month') ? 'active' : ''; ?>">Bulan Ini</a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Pilih Tanggal Manual</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="start_date" class="form-control" value="<?php echo ($filter_mode == 'all') ? '' : $start_date; ?>">
                                <span class="input-group-text bg-white">-</span>
                                <input type="date" name="end_date" class="form-control" value="<?php echo ($filter_mode == 'all') ? '' : $end_date; ?>">
                                <input type="hidden" name="filter" value="custom"> 
                                <button type="submit" class="btn btn-secondary"><i class="bi bi-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted m-0">Menampilkan: <strong class="text-brown"><?php echo $label_filter; ?></strong></h6>
                <a href="print_laporan.php?start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>&mode=<?php echo $filter_mode; ?>" target="_blank" class="btn btn-brown btn-sm px-3 rounded-pill shadow-sm"><i class="bi bi-printer-fill me-2"></i>Cetak Rekap</a>
            </div>

            <ul class="nav nav-tabs" id="laporanTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="biasa-tab" data-bs-toggle="tab" data-bs-target="#biasa"><i class="bi bi-people"></i> Umum (Harian)</button></li>
                <li class="nav-item"><button class="nav-link" id="tetap-tab" data-bs-toggle="tab" data-bs-target="#tetap"><i class="bi bi-building"></i> Pelanggan Tetap (Mingguan)</button></li>
            </ul>

            <div class="tab-content border border-top-0 bg-white p-4 rounded-bottom shadow-sm" id="laporanTabContent">
                
                <div class="tab-pane fade show active" id="biasa" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm">
                            <thead class="table-light">
                                <tr><th>No Nota</th><th>Tanggal</th><th>Customer</th><th class="text-end">Total</th><th class="text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if($res_biasa && $res_biasa->num_rows > 0): ?>
                                    <?php while($row = $res_biasa->fetch_assoc()): $grand_total_biasa += $row['total']; ?>
                                    <tr>
                                        <td><span class="badge bg-secondary font-monospace">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                        <td><?php echo format_tanggal($row['tgl_penjualan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_cust']); ?></td>
                                        <td class="text-end fw-bold"><?php echo format_rupiah($row['total']); ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info text-white py-0 px-2" onclick="showDetail(<?php echo $row['id_penjualan']; ?>, '<?php echo $row['nama_cust']; ?>')"><i class="bi bi-eye"></i></button>
                                            <a href="../penjualan/nota.php?id=<?php echo $row['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-printer"></i></a>
                                            <button onclick="confirmDelete('../penjualan/index.php?delete=<?php echo $row['id_penjualan']; ?>')" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr><td colspan="3" class="text-end fw-bold py-3">TOTAL :</td><td colspan="2" class="text-start fw-bold py-3 text-success fs-5 px-3"><?php echo format_rupiah($grand_total_biasa); ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tetap" role="tabpanel">
                    <div class="alert alert-warning py-2 mb-3"><small><i class="bi bi-info-circle-fill"></i> Data Rekapitulasi Mingguan untuk PT/CV/UD.</small></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm">
                            <thead class="table-light">
                                <tr><th>Customer</th><th>Periode</th><th class="text-center">Order</th><th class="text-end">Total</th><th class="text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if($res_tetap && $res_tetap->num_rows > 0): ?>
                                    <?php while($row = $res_tetap->fetch_assoc()): 
                                        $grand_total_tetap += $row['total_tagihan'];
                                        $periode = date('d/m', strtotime($row['tgl_awal_minggu'])) . " - " . date('d/m/y', strtotime($row['tgl_akhir_minggu']));
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo $row['nama_cust']; ?></td>
                                        <td><span class="badge bg-light text-dark border">Minggu <?php echo $row['minggu_ke']; ?></span><small class="text-muted d-block"><?php echo $periode; ?></small></td>
                                        <td class="text-center"><?php echo $row['jumlah_transaksi']; ?>x</td>
                                        <td class="text-end fw-bold text-danger"><?php echo format_rupiah($row['total_tagihan']); ?></td>
                                        <td class="text-center"><button class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-printer"></i> Invoice</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr><td colspan="3" class="text-end fw-bold py-3">TOTAL :</td><td colspan="2" class="text-start fw-bold py-3 text-danger fs-5 px-3"><?php echo format_rupiah($grand_total_tetap); ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div> 
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Detail Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><div class="px-3 pb-2 text-muted small">Customer: <strong id="detailCustomerName">...</strong></div><table class="table table-sm table-striped mb-0"><thead class="table-light"><tr><th class="ps-3">Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end pe-3">Subtotal</th></tr></thead><tbody id="detailContent"></tbody></table></div><div class="modal-footer border-top-0 py-2"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function showDetail(id, name) {
            $('#detailCustomerName').text(name);
            $('#detailContent').html('<tr><td colspan="4" class="text-center py-3 text-muted">Memuat...</td></tr>');
            new bootstrap.Modal(document.getElementById('detailModal')).show();
            $.post('', { get_detail_nota: true, id_penjualan: id }, function(res) { $('#detailContent').html(res); });
        }
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); const href = this.getAttribute('href');
            Swal.fire({ title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Keluar' }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });
        function confirmDelete(url) {
            Swal.fire({ title: 'Hapus Transaksi?', text: "Data akan hilang permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!' }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        }
    </script>
</body>
</html>