<?php
session_start();
require_once '../../config/database.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- 1. LOGIKA DELETE ---
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    
    // Cek apakah customer dipakai di penjualan (Foreign Key Check)
    $check_query = "SELECT COUNT(*) as count FROM penjualan WHERE id_cust = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();
    
    if ($check['count'] > 0) {
        $error = "Gagal: Customer tidak bisa dihapus karena memiliki riwayat transaksi penjualan!";
    } else {
        $delete_query = "DELETE FROM customer WHERE id_cust = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Data customer berhasil dihapus!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menghapus data.";
        }
    }
}

// --- 2. LOGIKA CREATE (TAMBAH DATA VIA POP-UP) ---
if (isset($_POST['create_customer'])) {
    $nama    = clean_input($_POST['nama']);
    $alamat  = clean_input($_POST['alamat']);
    $no_telp = clean_input($_POST['no_telp']);

    if (empty($nama)) {
        $error = "Nama customer wajib diisi!";
    } else {
        $insert_query = "INSERT INTO customer (nama, alamat, no_telp) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sss", $nama, $alamat, $no_telp);
        
        if ($stmt->execute()) {
            $success = "Customer berhasil ditambahkan!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menambah data: " . $conn->error;
        }
    }
}

// --- 3. LOGIKA UPDATE (EDIT DATA VIA POP-UP) ---
if (isset($_POST['update_customer'])) {
    $id_cust = clean_input($_POST['id_cust']);
    $nama    = clean_input($_POST['nama']);
    $alamat  = clean_input($_POST['alamat']);
    $no_telp = clean_input($_POST['no_telp']);

    if (empty($nama)) {
        $error = "Nama customer wajib diisi!";
    } else {
        $update_query = "UPDATE customer SET nama=?, alamat=?, no_telp=? WHERE id_cust=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $nama, $alamat, $no_telp, $id_cust);
        
        if ($stmt->execute()) {
            $success = "Data customer berhasil diperbarui!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal update data: " . $conn->error;
        }
    }
}

// Ambil Data Customer
$query = "SELECT * FROM customer ORDER BY nama ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Dewi Cookies</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/custom.css">

    <style>
        .modal-header {
            background: #fff;
            color: var(--dark-color);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .modal-title { font-weight: 700; color: var(--primary-color); }
        .form-label { font-weight: 600; color: var(--primary-color); }
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
            <a href="index.php" class="nav-link active"><i class="bi bi-people"></i> <span>Customer</span></a>

            <div class="nav-section-title">Inventory</div>
            <a href="../bahan-baku/index.php" class="nav-link"><i class="bi bi-box-seam"></i> <span>Bahan Baku</span></a>
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
                    <h5 class="fw-bold mb-0 text-dark">Data Customer</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">Kelola pelanggan setia</small>
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
                        <h5 class="fw-bold text-dark mb-1">Daftar Pelanggan</h5>
                        <p class="text-muted small mb-0">Database kontak customer</p>
                    </div>
                    <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Customer
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 rounded-start">No</th>
                                <th class="px-3 py-3">Nama Customer</th>
                                <th class="px-3 py-3">Alamat</th>
                                <th class="px-3 py-3">No Telp</th>
                                <th class="px-3 py-3 rounded-end text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="px-3"><?php echo $no++; ?></td>
                                    <td class="px-3 fw-bold text-dark"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="px-3 text-muted small"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                                    <td class="px-3"><?php echo htmlspecialchars($row['no_telp'] ?? '-'); ?></td>
                                    <td class="px-3 text-end">
                                        <button type="button" class="btn btn-sm btn-warning text-white rounded-2 me-1 btn-action" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $row['id_cust']; ?>"
                                                data-nama="<?php echo $row['nama']; ?>"
                                                data-alamat="<?php echo $row['alamat']; ?>"
                                                data-telp="<?php echo $row['no_telp']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="?delete=<?php echo $row['id_cust']; ?>" class="btn btn-sm btn-danger rounded-2 btn-action" onclick="return confirm('Yakin ingin menghapus customer ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                                        Belum ada data customer.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Customer *</label>
                            <input type="text" class="form-control rounded-3" name="nama" placeholder="Contoh: Ibu Sarah" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Alamat</label>
                            <textarea class="form-control rounded-3" name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">No Telepon</label>
                            <input type="text" class="form-control rounded-3" name="no_telp" placeholder="0812xxxx">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_customer" class="btn btn-brown rounded-3 px-4">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_cust" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Customer *</label>
                            <input type="text" class="form-control rounded-3" name="nama" id="edit_nama" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Alamat</label>
                            <textarea class="form-control rounded-3" name="alamat" id="edit_alamat" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">No Telepon</label>
                            <input type="text" class="form-control rounded-3" name="no_telp" id="edit_telp">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_customer" class="btn btn-brown rounded-3 px-4">
                            Update Data
                        </button>
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

        // Script untuk mengisi form modal edit
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            // Ambil data dari tombol
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const alamat = button.getAttribute('data-alamat');
            const telp = button.getAttribute('data-telp');
            
            // Isi form di dalam modal
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_telp').value = telp;
        });
    </script>

</body>
</html>