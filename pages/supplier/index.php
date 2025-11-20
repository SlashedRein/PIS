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
    $check_query = "SELECT COUNT(*) as count FROM pembelian WHERE id_supp = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();
    
    if ($check['count'] > 0) {
        $error = "Gagal: Supplier sedang digunakan di data pembelian.";
    } else {
        $delete_query = "DELETE FROM supplier WHERE id_supp = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Data berhasil dihapus.";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menghapus data.";
        }
    }
}

// --- 2. LOGIKA CREATE (TAMBAH DATA) ---
if (isset($_POST['create_supplier'])) {
    $nama    = clean_input($_POST['nama']);
    $alamat  = clean_input($_POST['alamat']);
    $no_telp = clean_input($_POST['no_telp']);
    $no_rek  = clean_input($_POST['no_rek']);

    if (empty($nama)) {
        $error = "Nama supplier wajib diisi!";
    } else {
        $insert_query = "INSERT INTO supplier (nama, alamat, no_telp, no_rek) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $nama, $alamat, $no_telp, $no_rek);
        
        if ($stmt->execute()) {
            $success = "Supplier berhasil ditambahkan!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal menambah data: " . $conn->error;
        }
    }
}

// --- 3. LOGIKA UPDATE (EDIT DATA) ---
if (isset($_POST['update_supplier'])) {
    $id_supp = clean_input($_POST['id_supp']);
    $nama    = clean_input($_POST['nama']);
    $alamat  = clean_input($_POST['alamat']);
    $no_telp = clean_input($_POST['no_telp']);
    $no_rek  = clean_input($_POST['no_rek']);

    if (empty($nama)) {
        $error = "Nama supplier tidak boleh kosong.";
    } else {
        $update_query = "UPDATE supplier SET nama=?, alamat=?, no_telp=?, no_rek=? WHERE id_supp=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssi", $nama, $alamat, $no_telp, $no_rek, $id_supp);
        
        if ($stmt->execute()) {
            $success = "Data supplier berhasil diperbarui!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal update data: " . $conn->error;
        }
    }
}

// Ambil Data Tabel
$query = "SELECT * FROM supplier ORDER BY nama ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Dewi Cookies</title>
    
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
            <a href="index.php" class="nav-link active">
                <i class="bi bi-building"></i> <span>Supplier</span>
            </a>
            <a href="../customer/index.php" class="nav-link">
                <i class="bi bi-people"></i> <span>Customer</span>
            </a>

            <div class="nav-section-title">Inventory</div>
            <a href="../bahan-baku/index.php" class="nav-link">
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

    <div class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h4>Data Supplier</h4>
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
                    <h5 style="margin:0; font-weight:700; color:var(--primary-color);">Daftar Supplier</h5>
                    
                    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg"></i> Tambah Supplier
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Supplier</th>
                                <th>Alamat</th>
                                <th>Kontak</th>
                                <th>Rekening</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_telp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_rek']); ?></td>
                                    <td>
                                        <button type="button" class="btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $row['id_supp']; ?>"
                                                data-nama="<?php echo $row['nama']; ?>"
                                                data-alamat="<?php echo $row['alamat']; ?>"
                                                data-telp="<?php echo $row['no_telp']; ?>"
                                                data-rek="<?php echo $row['no_rek']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="?delete=<?php echo $row['id_supp']; ?>" class="btn-danger" onclick="return confirm('Hapus data ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">Belum ada data.</td>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Supplier *</label>
                            <input type="text" class="form-control" name="nama" placeholder="Contoh: Toko Sejahtera" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Telepon</label>
                                <input type="text" class="form-control" name="no_telp" placeholder="0812xxxx">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Rekening</label>
                                <input type="text" class="form-control" name="no_rek" placeholder="Bank - No Rek">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_supplier" class="btn btn-primary">
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_supp" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Supplier *</label>
                            <input type="text" class="form-control" name="nama" id="edit_nama" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" id="edit_alamat" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Telepon</label>
                                <input type="text" class="form-control" name="no_telp" id="edit_telp">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Rekening</label>
                                <input type="text" class="form-control" name="no_rek" id="edit_rek">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_supplier" class="btn btn-primary">
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
            
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const alamat = button.getAttribute('data-alamat');
            const telp = button.getAttribute('data-telp');
            const rek = button.getAttribute('data-rek');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_telp').value = telp;
            document.getElementById('edit_rek').value = rek;
        });
    </script>

</body>
</html>