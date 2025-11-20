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
        .modal-header {
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .modal-title { font-weight: 700; font-family: 'Playfair Display', serif; }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .form-label { font-weight: 600; color: var(--primary-color); }
        
        /* Badge Status */
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
            <a href="index.php" class="nav-link active">
                <i class="bi bi-box-seam"></i> <span>Bahan Baku</span>
            </a>
            <a href="../produk/index.php" class="nav-link">
                <i class="bi bi-grid"></i> <span>Produk</span>
            </a>
             <a href="../resep/index.php" class="nav-link">
                <i class="bi bi-journal-text"></i> <span>Resep</span>
            </a>
        </div>

         <div class="nav-section">
                <div class="nav-section-title">Transaksi</div>
                <div class="nav-item">
                    <a href="pembelian/index.php" class="nav-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Pembelian</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="penjualan/index.php" class="nav-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Penjualan</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Reports</div>
                <div class="nav-item">
                    <a href="laporan/index.php" class="nav-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Laporan</span>
                    </a>
                </div>
            </div>
    </div>

    <div class="main-content">
        
        <div class="topbar">
            <div class="page-title">
                <h4>Data Bahan Baku</h4>
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
                    <h5 style="margin:0; font-weight:700; color:var(--primary-color);">Stok Bahan Baku</h5>
                    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg"></i> Tambah Bahan
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Bahan</th>
                                <th>Satuan</th>
                                <th>Stok</th>
                                <th>Stok Min</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                                    // Tentukan Status
                                    if ($row['stok'] == 0) {
                                        $status = '<span class="badge-status badge-danger">Habis</span>';
                                    } elseif ($row['stok'] <= $row['stok_min']) {
                                        $status = '<span class="badge-status badge-warning">Perlu Restok</span>';
                                    } else {
                                        $status = '<span class="badge-status badge-aman">Aman</span>';
                                    }
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama_bahan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['satuan']); ?></td>
                                    <td><strong><?php echo $row['stok']; ?></strong></td>
                                    <td><?php echo $row['stok_min']; ?></td>
                                    <td><?php echo $status; ?></td>
                                    <td>
                                        <button type="button" class="btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $row['id_bahan']; ?>"
                                                data-nama="<?php echo $row['nama_bahan']; ?>"
                                                data-satuan="<?php echo $row['satuan']; ?>"
                                                data-stok="<?php echo $row['stok']; ?>"
                                                data-min="<?php echo $row['stok_min']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="?delete=<?php echo $row['id_bahan']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus bahan baku ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-box-seam" style="font-size: 2rem;"></i><br>
                                            Belum ada data bahan baku.
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

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam"></i> Tambah Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Bahan *</label>
                            <input type="text" class="form-control" name="nama_bahan" placeholder="Contoh: Tepung Terigu" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Satuan *</label>
                            <select class="form-select" name="satuan" required>
                                <option value="">-- Pilih Satuan --</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="gram">Gram (g)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="pack">Pack</option>
                                <option value="butir">Butir</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Awal</label>
                                <input type="number" class="form-control" name="stok" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Minimum</label>
                                <input type="number" class="form-control" name="stok_min" value="5" min="0">
                                <small class="text-muted" style="font-size: 10px;">Batas alert stok</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_bahan" class="btn btn-primary">
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_bahan" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Bahan *</label>
                            <input type="text" class="form-control" name="nama_bahan" id="edit_nama" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Satuan *</label>
                            <select class="form-select" name="satuan" id="edit_satuan" required>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="gram">Gram (g)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="pack">Pack</option>
                                <option value="butir">Butir</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Saat Ini</label>
                                <input type="number" class="form-control" name="stok" id="edit_stok" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Minimum</label>
                                <input type="number" class="form-control" name="stok_min" id="edit_min" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_bahan" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script untuk mengisi form EDIT modal secara otomatis
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            // Ambil data dari tombol edit
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const satuan = button.getAttribute('data-satuan');
            const stok = button.getAttribute('data-stok');
            const min = button.getAttribute('data-min');
            
            // Isi form
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_satuan').value = satuan;
            document.getElementById('edit_stok').value = stok;
            document.getElementById('edit_min').value = min;
        });
    </script>

</body>
</html>