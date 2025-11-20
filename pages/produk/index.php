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
    
    // Cek relasi: Apakah produk ada di Resep atau Detail Penjualan?
    $check_resep = $conn->query("SELECT COUNT(*) as count FROM resep WHERE id_produk = $id")->fetch_assoc();
    $check_jual  = $conn->query("SELECT COUNT(*) as count FROM detail_penjualan WHERE id_produk = $id")->fetch_assoc();
    
    if ($check_resep['count'] > 0) {
        $error = "Gagal: Produk tidak bisa dihapus karena digunakan dalam Resep!";
    } elseif ($check_jual['count'] > 0) {
        $error = "Gagal: Produk tidak bisa dihapus karena ada riwayat penjualan!";
    } else {
        $delete_query = "DELETE FROM produk WHERE id_produk = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Data produk berhasil dihapus!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menghapus data.";
        }
    }
}

// --- 2. LOGIKA CREATE (TAMBAH) ---
if (isset($_POST['create_produk'])) {
    $nama   = clean_input($_POST['nama_produk']);
    $satuan = clean_input($_POST['satuan']);
    $harga  = clean_input($_POST['harga_jual']);
    $stok   = clean_input($_POST['stok']);

    if (empty($nama)) {
        $error = "Nama produk wajib diisi!";
    } else {
        $query = "INSERT INTO produk (nama_produk, satuan, harga_jual, stok) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $nama, $satuan, $harga, $stok);
        
        if ($stmt->execute()) {
            $success = "Produk berhasil ditambahkan!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal: " . $conn->error;
        }
    }
}

// --- 3. LOGIKA UPDATE (EDIT) ---
if (isset($_POST['update_produk'])) {
    $id     = clean_input($_POST['id_produk']);
    $nama   = clean_input($_POST['nama_produk']);
    $satuan = clean_input($_POST['satuan']);
    $harga  = clean_input($_POST['harga_jual']);
    $stok   = clean_input($_POST['stok']);

    if (empty($nama)) {
        $error = "Nama produk wajib diisi!";
    } else {
        $query = "UPDATE produk SET nama_produk=?, satuan=?, harga_jual=?, stok=? WHERE id_produk=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssiii", $nama, $satuan, $harga, $stok, $id);
        
        if ($stmt->execute()) {
            $success = "Data produk berhasil diperbarui!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal update: " . $conn->error;
        }
    }
}

// Ambil Data Produk
$query = "SELECT * FROM produk ORDER BY nama_produk ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Dewi Cookies</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/custom.css">

    <style>
        .modal-header {
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .form-label { font-weight: 600; color: var(--primary-color); }
        
        /* Status Badge */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-aman { background: #E8F5E9; color: #2E7D32; }
        .badge-warning { background: #FFF3E0; color: #EF6C00; }
        .badge-danger { background: #FFEBEE; color: #C62828; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🍪</div>
            <div class="logo-text" style="margin-left: 10px;">
                <h5 style="margin:0; font-size:16px; font-weight:700;">Dewi Cookies</h5>
                <small style="opacity:0.7; font-size:11px;">Management System</small>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section-title">Main Menu</div>
            <a href="../dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
            
            <div class="nav-section-title">Master Data</div>
            <a href="../supplier/index.php" class="nav-link">
                <i class="bi bi-building"></i> <span>Supplier</span>
            </a>
            <a href="../customer/index.php" class="nav-link">
                <i class="bi bi-people"></i> <span>Customer</span>
            </a>

            <div class="nav-section-title">Inventory</div>
            <a href="../bahan-baku/index.php" class="nav-link">
                <i class="bi bi-box-seam"></i> <span>Bahan Baku</span>
            </a>
            <a href="index.php" class="nav-link active">
                <i class="bi bi-grid"></i> <span>Produk</span>
            </a>
             <a href="../resep/index.php" class="nav-link">
                <i class="bi bi-journal-text"></i> <span>Resep</span>
            </a>

            <div class="nav-section-title">Transaksi</div>
            <a href="../pembelian/index.php" class="nav-link">
                <i class="bi bi-cart-plus"></i> <span>Pembelian</span>
            </a>
            <a href="../penjualan/index.php" class="nav-link">
                <i class="bi bi-cash-coin"></i> <span>Penjualan</span>
            </a>

            <div class="nav-section-title">Reports</div>
            <a href="../laporan/index.php" class="nav-link">
                <i class="bi bi-graph-up"></i> <span>Laporan</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="topbar">
            <div class="page-title">
                <h4>Data Produk</h4>
            </div>
            
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info">
                        <span class="name"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        <span class="role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                    </div>
                    <i class="bi bi-chevron-down profile-arrow"></i>
                </div>
                
                <div class="dropdown-menu-custom">
                    <a href="#" class="dropdown-item-custom">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                    <a href="../../logout.php" class="dropdown-item-custom logout" onclick="return confirm('Yakin ingin keluar?')">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area">
            
            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i> <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 style="margin:0; font-weight:700; color:var(--primary-color);">Daftar Produk Cookies</h5>
                    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg"></i> Tambah Produk
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Produk</th>
                                <th>Satuan</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                                    // Logic Status
                                    if ($row['stok'] == 0) {
                                        $status = '<span class="badge-status badge-danger">Habis</span>';
                                    } elseif ($row['stok'] < 10) {
                                        $status = '<span class="badge-status badge-warning">Perlu Restok</span>';
                                    } else {
                                        $status = '<span class="badge-status badge-aman">Aman</span>';
                                    }
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['satuan']); ?></td>
                                    <td><strong>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></strong></td>
                                    <td><strong><?php echo $row['stok']; ?></strong></td>
                                    <td><?php echo $status; ?></td>
                                    <td>
                                        <button type="button" class="btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $row['id_produk']; ?>"
                                                data-nama="<?php echo $row['nama_produk']; ?>"
                                                data-satuan="<?php echo $row['satuan']; ?>"
                                                data-harga="<?php echo $row['harga_jual']; ?>"
                                                data-stok="<?php echo $row['stok']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="?delete=<?php echo $row['id_produk']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">Belum ada data produk.</td>
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
            <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid"></i> Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Produk *</label>
                            <input type="text" class="form-control" name="nama_produk" placeholder="Contoh: Nastar Keju" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satuan</label>
                                <select class="form-select" name="satuan" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="toples">Toples</option>
                                    <option value="pcs">Pcs</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Awal</label>
                                <input type="number" class="form-control" name="stok" value="0" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" class="form-control" name="harga_jual" placeholder="0" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_produk" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_produk" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Produk *</label>
                            <input type="text" class="form-control" name="nama_produk" id="edit_nama" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satuan</label>
                                <select class="form-select" name="satuan" id="edit_satuan" required>
                                    <option value="toples">Toples</option>
                                    <option value="pcs">Pcs</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" class="form-control" name="stok" id="edit_stok" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" class="form-control" name="harga_jual" id="edit_harga" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_produk" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_satuan').value = button.getAttribute('data-satuan');
            document.getElementById('edit_harga').value = button.getAttribute('data-harga');
            document.getElementById('edit_stok').value = button.getAttribute('data-stok');
        });
    </script>

</body>
</html>