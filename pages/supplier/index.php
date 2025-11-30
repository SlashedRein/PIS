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
$role = $_SESSION['role']; // Simpan role

// --- 1. LOGIKA DELETE (HANYA OWNER) ---
if (isset($_GET['delete'])) {
    if ($role !== 'owner') {
        echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
        exit();
    }

    $id = clean_input($_GET['delete']);
    // Cek relasi
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

// --- 2. LOGIKA CREATE (HANYA OWNER) ---
if (isset($_POST['create_supplier'])) {
    if ($role !== 'owner') {
        $error = "Anda tidak berhak menambah data.";
    } else {
        $nama    = clean_input($_POST['nama']);
        $alamat  = clean_input($_POST['alamat']);
        $no_telp = clean_input($_POST['no_telp']);
        $no_rek  = clean_input($_POST['no_rek']);

        if (empty($nama)) {
            $error = "Nama supplier wajib diisi!";
        } else {
            $stmt = $conn->prepare("INSERT INTO supplier (nama, alamat, no_telp, no_rek) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $alamat, $no_telp, $no_rek);
            
            if ($stmt->execute()) {
                $success = "Supplier berhasil ditambahkan!";
                header("refresh:1;url=index.php");
            } else {
                $error = "Gagal menambah data: " . $conn->error;
            }
        }
    }
}

// --- 3. LOGIKA UPDATE (HANYA OWNER) ---
if (isset($_POST['update_supplier'])) {
    if ($role !== 'owner') {
        $error = "Anda tidak berhak mengubah data.";
    } else {
        $id_supp = clean_input($_POST['id_supp']);
        $nama    = clean_input($_POST['nama']);
        $alamat  = clean_input($_POST['alamat']);
        $no_telp = clean_input($_POST['no_telp']);
        $no_rek  = clean_input($_POST['no_rek']);

        if (empty($nama)) {
            $error = "Nama supplier tidak boleh kosong.";
        } else {
            $stmt = $conn->prepare("UPDATE supplier SET nama=?, alamat=?, no_telp=?, no_rek=? WHERE id_supp=?");
            $stmt->bind_param("ssssi", $nama, $alamat, $no_telp, $no_rek, $id_supp);
            
            if ($stmt->execute()) {
                $success = "Data supplier berhasil diperbarui!";
                header("refresh:1;url=index.php");
            } else {
                $error = "Gagal update data: " . $conn->error;
            }
        }
    }
}

$result = $conn->query("SELECT * FROM supplier ORDER BY nama ASC");
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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title">
                    <h5 class="fw-bold mb-0 text-dark">Supplier</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">Rekanan Bisnis</small>
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
                    <a href="logout.php" class="dropdown-item-custom logout text-danger" id="btnLogout">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Data Supplier</h5>
                        <p class="text-muted small mb-0">Kelola kontak supplier bahan baku</p>
                    </div>
                    <?php if ($role == 'owner'): ?>
                        <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-2"></i>Tambah Supplier
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 rounded-start">No</th>
                                <th class="px-3 py-3">Nama Supplier</th>
                                <th class="px-3 py-3">Alamat</th>
                                <th class="px-3 py-3">Kontak</th>
                                <th class="px-3 py-3">Rekening</th>
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
                                    <td class="px-3 text-muted small"><?php echo htmlspecialchars($row['alamat']); ?></td>
                                    <td class="px-3"><?php echo htmlspecialchars($row['no_telp']); ?></td>
                                    <td class="px-3 font-monospace small"><?php echo htmlspecialchars($row['no_rek']); ?></td>
                                    <td class="px-3 text-end">
                                        <?php if ($role == 'owner'): ?>
                                            <button type="button" class="btn btn-sm btn-warning text-white rounded-2 me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="<?php echo $row['id_supp']; ?>"
                                                    data-nama="<?php echo $row['nama']; ?>"
                                                    data-alamat="<?php echo $row['alamat']; ?>"
                                                    data-telp="<?php echo $row['no_telp']; ?>"
                                                    data-rek="<?php echo $row['no_rek']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="?delete=<?php echo $row['id_supp']; ?>" class="btn btn-sm btn-danger rounded-2" onclick="return confirm('Hapus data ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Read Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role == 'owner'): ?>
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label fw-bold small">Nama Supplier *</label><input type="text" class="form-control rounded-3" name="nama" required></div>
                        <div class="mb-3"><label class="form-label fw-bold small">Alamat</label><textarea class="form-control rounded-3" name="alamat" rows="2"></textarea></div>
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label fw-bold small">No Telepon</label><input type="text" class="form-control rounded-3" name="no_telp"></div>
                            <div class="col-6"><label class="form-label fw-bold small">No Rekening</label><input type="text" class="form-control rounded-3" name="no_rek"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="create_supplier" class="btn btn-brown rounded-3 px-4">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role == 'owner'): ?>
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">Edit Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_supp" id="edit_id">
                        <div class="mb-3"><label class="form-label fw-bold small">Nama Supplier *</label><input type="text" class="form-control rounded-3" name="nama" id="edit_nama" required></div>
                        <div class="mb-3"><label class="form-label fw-bold small">Alamat</label><textarea class="form-control rounded-3" name="alamat" id="edit_alamat" rows="2"></textarea></div>
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label fw-bold small">No Telepon</label><input type="text" class="form-control rounded-3" name="no_telp" id="edit_telp"></div>
                            <div class="col-6"><label class="form-label fw-bold small">No Rekening</label><input type="text" class="form-control rounded-3" name="no_rek" id="edit_rek"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="update_supplier" class="btn btn-brown rounded-3 px-4">Update Data</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script Populate Edit Modal
        const editModal = document.getElementById('editModal');
        if (editModal) { // Cek exists (karena modal gak dirender kalau bukan owner)
            editModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('edit_id').value = button.getAttribute('data-id');
                document.getElementById('edit_nama').value = button.getAttribute('data-nama');
                document.getElementById('edit_alamat').value = button.getAttribute('data-alamat');
                document.getElementById('edit_telp').value = button.getAttribute('data-telp');
                document.getElementById('edit_rek').value = button.getAttribute('data-rek');
            });
        }
    </script>
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