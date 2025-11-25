<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- 1. LOGIKA DELETE ---
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    
    // Cek apakah bahan baku dipakai di resep (Foreign Key Check)
    $check_query = "SELECT COUNT(*) as count FROM resep WHERE id_bahan = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();
    
    if ($check['count'] > 0) {
        $error = "Gagal: Bahan baku tidak bisa dihapus karena sedang digunakan dalam Resep Produk!";
    } else {
        $delete_query = "DELETE FROM bahan_baku WHERE id_bahan = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Data bahan baku berhasil dihapus!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menghapus data.";
        }
    }
}

// --- 2. LOGIKA CREATE (TAMBAH DATA) ---
if (isset($_POST['create_bahan'])) {
    $nama_bahan = clean_input($_POST['nama_bahan']);
    $satuan     = clean_input($_POST['satuan']);
    $stok       = clean_input($_POST['stok']);
    $stok_min   = clean_input($_POST['stok_min']);

    if (empty($nama_bahan)) {
        $error = "Nama bahan wajib diisi!";
    } else {
        $insert_query = "INSERT INTO bahan_baku (nama_bahan, satuan, stok, stok_min) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssii", $nama_bahan, $satuan, $stok, $stok_min);
        
        if ($stmt->execute()) {
            $success = "Bahan baku berhasil ditambahkan!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menambah data: " . $conn->error;
        }
    }
}

// --- 3. LOGIKA UPDATE (EDIT DATA) ---
if (isset($_POST['update_bahan'])) {
    $id_bahan   = clean_input($_POST['id_bahan']);
    $nama_bahan = clean_input($_POST['nama_bahan']);
    $satuan     = clean_input($_POST['satuan']);
    $stok       = clean_input($_POST['stok']);
    $stok_min   = clean_input($_POST['stok_min']);

    if (empty($nama_bahan)) {
        $error = "Nama bahan wajib diisi!";
    } else {
        $update_query = "UPDATE bahan_baku SET nama_bahan=?, satuan=?, stok=?, stok_min=? WHERE id_bahan=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssiii", $nama_bahan, $satuan, $stok, $stok_min, $id_bahan);
        
        if ($stmt->execute()) {
            $success = "Data bahan baku berhasil diperbarui!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal update data: " . $conn->error;
        }
    }
}

// Ambil Data Bahan Baku
$query = "SELECT * FROM bahan_baku ORDER BY nama_bahan ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahan Baku - Dewi Cookies</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/custom.css">

    <style>
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-aman { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .badge-warning { background: #FFF3E0; color: #EF6C00; border: 1px solid #FFE0B2; }
        .badge-danger { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
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
            <a href="index.php" class="nav-link active"><i class="bi bi-box-seam"></i> <span>Bahan Baku</span></a>
            <a href="../produk/index.php" class="nav-link"><i class="bi bi-grid"></i> <span>Produk</span></a>
            <a href="../resep/index.php" class="nav-link"><i class="bi bi-journal-text"></i> <span>Resep</span></a>

            <div class="nav-section-title">Transaksi</div>
            <a href="../pembelian/index.php" class="nav-link"><i class="bi bi-cart-plus"></i> <span>Pembelian</span></a>
            <a href="../penjualan/index.php" class="nav-link"><i class="bi bi-cash-coin"></i> <span>Penjualan</span></a>
            
            <div class="nav-section-title">Reports</div>
            <a href="../laporan/index.php" class="nav-link"><i class="bi bi-graph-up"></i> <span>Laporan</span></a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                
                <div class="page-title">
                    <h5 class="fw-bold mb-0 text-dark">Bahan Baku</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">Manage stok bahan</small>
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
                    <a href="../../logout.php" class="dropdown-item-custom logout text-danger" onclick="return confirm('Yakin ingin keluar?')">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 shadow-sm border-0 mb-4">
                    <i class="bi bi-check-circle-fill"></i> <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 shadow-sm border-0 mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i> <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Daftar Stok Bahan</h5>
                        <p class="text-muted small mb-0">Pantau ketersediaan bahan baku produksi</p>
                    </div>
                    <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i>Tambah
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 rounded-start">No</th>
                                <th class="px-3 py-3">Nama Bahan</th>
                                <th class="px-3 py-3">Satuan</th>
                                <th class="px-3 py-3">Stok</th>
                                <th class="px-3 py-3">Min. Stok</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 rounded-end text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                                    if ($row['stok'] == 0) {
                                        $status = '<span class="badge-status badge-danger">Habis</span>';
                                    } elseif ($row['stok'] <= $row['stok_min']) {
                                        $status = '<span class="badge-status badge-warning">Restok</span>';
                                    } else {
                                        $status = '<span class="badge-status badge-aman">Aman</span>';
                                    }
                            ?>
                                <tr>
                                    <td class="px-3"><?php echo $no++; ?></td>
                                    <td class="px-3 fw-bold text-dark"><?php echo htmlspecialchars($row['nama_bahan']); ?></td>
                                    <td class="px-3"><?php echo htmlspecialchars($row['satuan']); ?></td>
                                    <td class="px-3 font-monospace fs-6"><?php echo $row['stok']; ?></td>
                                    <td class="px-3 text-muted"><?php echo $row['stok_min']; ?></td>
                                    <td class="px-3"><?php echo $status; ?></td>
                                    <td class="px-3 text-end">
                                        <button type="button" class="btn btn-sm btn-warning text-white rounded-2 me-1 btn-action" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $row['id_bahan']; ?>"
                                                data-nama="<?php echo $row['nama_bahan']; ?>"
                                                data-satuan="<?php echo $row['satuan']; ?>"
                                                data-stok="<?php echo $row['stok']; ?>"
                                                data-min="<?php echo $row['stok_min']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="?delete=<?php echo $row['id_bahan']; ?>" class="btn btn-sm btn-danger rounded-2 btn-action" onclick="return confirm('Yakin ingin menghapus?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                        Belum ada data bahan baku
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Bahan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Bahan</label>
                            <input type="text" class="form-control rounded-3" name="nama_bahan" required placeholder="Contoh: Tepung Terigu">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Satuan</label>
                            <select class="form-select rounded-3" name="satuan" required>
                                <option value="">-- Pilih --</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="gram">Gram (g)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="pack">Pack</option>
                                <option value="butir">Butir</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Stok Awal</label>
                                <input type="number" class="form-control rounded-3" name="stok" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-danger">Min. Stok (Alert)</label>
                                <input type="number" class="form-control rounded-3" name="stok_min" value="5">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_bahan" class="btn btn-brown rounded-3 px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_bahan" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Bahan</label>
                            <input type="text" class="form-control rounded-3" name="nama_bahan" id="edit_nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Satuan</label>
                            <select class="form-select rounded-3" name="satuan" id="edit_satuan" required>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="gram">Gram (g)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="pack">Pack</option>
                                <option value="butir">Butir</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Stok Saat Ini</label>
                                <input type="number" class="form-control rounded-3" name="stok" id="edit_stok">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-danger">Min. Stok</label>
                                <input type="number" class="form-control rounded-3" name="stok_min" id="edit_min">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_bahan" class="btn btn-brown rounded-3 px-4">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
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

        // Modal Edit Populate
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_satuan').value = button.getAttribute('data-satuan');
            document.getElementById('edit_stok').value = button.getAttribute('data-stok');
            document.getElementById('edit_min').value = button.getAttribute('data-min');
        });
    </script>
</body>
</html>