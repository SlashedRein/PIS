<?php
session_start();
require_once '../../config/database.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$role = $_SESSION['role']; 
$swal_script = ''; // Variabel untuk menampung script SweetAlert

// --- 1. LOGIKA DELETE (HANYA OWNER) ---
if (isset($_GET['delete'])) {
    if ($role !== 'owner') {
        $swal_script = "Swal.fire('Akses Ditolak', 'Hanya Owner yang boleh menghapus data.', 'error');";
    } else {
        $id = clean_input($_GET['delete']);
        
        // Cek Relasi: Apakah bahan dipakai di RESEP?
        $check_resep = $conn->query("SELECT COUNT(*) as count FROM resep WHERE id_bahan = '$id'")->fetch_assoc();
        
        // Cek Relasi: Apakah bahan ada di RIWAYAT PEMBELIAN?
        $check_beli = $conn->query("SELECT COUNT(*) as count FROM detail_pembelian WHERE id_bahan = '$id'")->fetch_assoc();

        if ($check_resep['count'] > 0) {
            $swal_script = "Swal.fire('Gagal Menghapus!', 'Bahan ini sedang digunakan dalam Resep Produk. Hapus resepnya terlebih dahulu.', 'error');";
        } elseif ($check_beli['count'] > 0) {
            $swal_script = "Swal.fire('Gagal Menghapus!', 'Bahan ini memiliki riwayat transaksi pembelian. Data tidak bisa dihapus demi integritas laporan.', 'error');";
        } else {
            // Aman dihapus
            $stmt = $conn->prepare("DELETE FROM bahan_baku WHERE id_bahan = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Redirect agar parameter GET hilang (bersih)
                header("Location: index.php?status=deleted");
                exit();
            } else {
                $swal_script = "Swal.fire('Error', 'Gagal menghapus data: " . $conn->error . "', 'error');";
            }
        }
    }
}

// --- 2. LOGIKA CREATE (HANYA OWNER) ---
if (isset($_POST['create_bahan'])) {
    if ($role !== 'owner') {
        $swal_script = "Swal.fire('Gagal', 'Anda tidak berhak menambah data.', 'error');";
    } else {
        $nama_bahan = clean_input($_POST['nama_bahan']);
        $satuan     = clean_input($_POST['satuan']);
        $stok       = clean_input($_POST['stok']);
        $stok_min   = clean_input($_POST['stok_min']);

        if (empty($nama_bahan)) {
            $swal_script = "Swal.fire('Gagal', 'Nama bahan wajib diisi!', 'warning');";
        } else {
            $stmt = $conn->prepare("INSERT INTO bahan_baku (nama_bahan, satuan, stok, stok_min) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nama_bahan, $satuan, $stok, $stok_min);
            
            if ($stmt->execute()) {
                header("Location: index.php?status=created");
                exit();
            } else {
                $swal_script = "Swal.fire('Error', 'Gagal menyimpan: " . $conn->error . "', 'error');";
            }
        }
    }
}

// --- 3. LOGIKA UPDATE (STOK BISA SEMUA, SISANYA OWNER) ---
if (isset($_POST['update_bahan'])) {
    $id_bahan = clean_input($_POST['id_bahan']);
    $stok     = clean_input($_POST['stok']); // Semua bisa edit stok (opname)

    // Ambil data lama
    $old_data = $conn->query("SELECT * FROM bahan_baku WHERE id_bahan = '$id_bahan'")->fetch_assoc();

    if ($role == 'owner') {
        $nama_bahan = clean_input($_POST['nama_bahan']);
        $satuan     = clean_input($_POST['satuan']);
        $stok_min   = clean_input($_POST['stok_min']);
    } else {
        // Karyawan pakai data lama
        $nama_bahan = $old_data['nama_bahan'];
        $satuan     = $old_data['satuan'];
        $stok_min   = $old_data['stok_min'];
    }

    $stmt = $conn->prepare("UPDATE bahan_baku SET nama_bahan=?, satuan=?, stok=?, stok_min=? WHERE id_bahan=?");
    $stmt->bind_param("ssiii", $nama_bahan, $satuan, $stok, $stok_min, $id_bahan);
    
    if ($stmt->execute()) {
        header("Location: index.php?status=updated");
        exit();
    } else {
        $swal_script = "Swal.fire('Error', 'Gagal update: " . $conn->error . "', 'error');";
    }
}

// --- 4. CEK NOTIFIKASI DARI URL ---
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') $swal_script = "Swal.fire('Berhasil', 'Data bahan baku telah dihapus.', 'success');";
    if ($_GET['status'] == 'created') $swal_script = "Swal.fire('Berhasil', 'Bahan baku baru ditambahkan.', 'success');";
    if ($_GET['status'] == 'updated') $swal_script = "Swal.fire('Berhasil', 'Data bahan baku diperbarui.', 'success');";
}

// --- 5. SEARCH & QUERY DATA ---
$q = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$sql_search = !empty($q) ? "WHERE nama_bahan LIKE '%$q%'" : "";
$query = "SELECT * FROM bahan_baku $sql_search ORDER BY nama_bahan ASC";
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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-aman { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .badge-warning { background: #FFF3E0; color: #EF6C00; border: 1px solid #FFE0B2; }
        .badge-danger { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
        .text-brown { color: var(--primary-color) !important; }
        .btn-brown { background-color: var(--primary-color); color: white; }
        
        /* Tombol Rapi di Mobile */
        .btn-action-group {
            display: flex;
            gap: 4px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

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
                    <a href="../logout.php" class="dropdown-item-custom logout text-danger" id="btnLogout">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <div class="bg-white rounded-4 shadow-sm border border-light p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-brown mb-1">Daftar Stok Bahan</h5>
                        <p class="text-muted small mb-0">Pantau ketersediaan bahan baku produksi</p>
                    </div>
                    
                    <?php if ($role == 'owner'): ?>
                    <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i> Tambah
                    </button>
                    <?php endif; ?>
                </div>

                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama bahan..." value="<?php echo htmlspecialchars($q); ?>">
                        <button type="submit" class="btn btn-secondary">Cari</button>
                        <?php if(!empty($q)): ?>
                            <a href="index.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>

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
                                    // Label Status
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
                                        <div class="btn-action-group">
                                            <button type="button" class="btn btn-sm btn-warning text-white rounded-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="<?php echo $row['id_bahan']; ?>"
                                                    data-nama="<?php echo $row['nama_bahan']; ?>"
                                                    data-satuan="<?php echo $row['satuan']; ?>"
                                                    data-stok="<?php echo $row['stok']; ?>"
                                                    data-min="<?php echo $row['stok_min']; ?>"
                                                    title="<?php echo ($role == 'owner') ? 'Edit Data' : 'Update Stok'; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <?php if ($role == 'owner'): ?>
                                            <button type="button" class="btn btn-sm btn-danger rounded-2" 
                                                    onclick="confirmDelete('?delete=<?php echo $row['id_bahan']; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Bahan baku tidak ditemukan.</td></tr>
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
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">Tambah Bahan Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold">Nama Bahan</label><input type="text" class="form-control rounded-3" name="nama_bahan" required placeholder="Contoh: Tepung Terigu"></div>
                        <div class="mb-3"><label class="form-label small fw-bold">Satuan</label>
                            <select class="form-select rounded-3" name="satuan" required>
                                <option value="">-- Pilih --</option><option value="kg">Kilogram (kg)</option><option value="gram">Gram (g)</option><option value="liter">Liter (L)</option><option value="ml">Mililiter (ml)</option><option value="pcs">Pieces (pcs)</option><option value="pack">Pack</option><option value="butir">Butir</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label small fw-bold">Stok Awal</label><input type="number" class="form-control rounded-3" name="stok" value="0"></div>
                            <div class="col-6"><label class="form-label small fw-bold text-danger">Min. Stok (Alert)</label><input type="number" class="form-control rounded-3" name="stok_min" value="5"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="create_bahan" class="btn btn-brown rounded-3 px-4">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><?php echo ($role == 'owner') ? 'Edit Bahan Baku' : 'Koreksi Stok Bahan'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_bahan" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Bahan</label>
                            <input type="text" class="form-control rounded-3 <?php echo ($role !== 'owner') ? 'bg-light' : ''; ?>" 
                                   name="nama_bahan" id="edit_nama" 
                                   <?php echo ($role !== 'owner') ? 'readonly' : 'required'; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Satuan</label>
                            <?php if ($role == 'owner'): ?>
                                <select class="form-select rounded-3" name="satuan" id="edit_satuan" required>
                                    <option value="kg">Kilogram (kg)</option><option value="gram">Gram (g)</option><option value="liter">Liter (L)</option><option value="ml">Mililiter (ml)</option><option value="pcs">Pieces (pcs)</option><option value="pack">Pack</option><option value="butir">Butir</option>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control rounded-3 bg-light" name="satuan" id="edit_satuan_text" readonly>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-success">Stok Saat Ini</label>
                                <input type="number" class="form-control rounded-3 border-success" name="stok" id="edit_stok" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-danger">Min. Stok</label>
                                <input type="number" class="form-control rounded-3 <?php echo ($role !== 'owner') ? 'bg-light' : ''; ?>" 
                                       name="stok_min" id="edit_min" 
                                       <?php echo ($role !== 'owner') ? 'readonly' : 'required'; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="submit" name="update_bahan" class="btn btn-brown rounded-3 px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Modal Edit Script
        const editModal = document.getElementById('editModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('edit_id').value = button.getAttribute('data-id');
                document.getElementById('edit_nama').value = button.getAttribute('data-nama');
                document.getElementById('edit_stok').value = button.getAttribute('data-stok');
                document.getElementById('edit_min').value = button.getAttribute('data-min');

                // Handle Dropdown vs Text
                const role = "<?php echo $role; ?>";
                if(role === 'owner') {
                    document.getElementById('edit_satuan').value = button.getAttribute('data-satuan');
                } else {
                    document.getElementById('edit_satuan_text').value = button.getAttribute('data-satuan');
                }
            });
        }

        // SWEETALERT LOGOUT
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar', cancelButtonText: 'Batal', reverseButtons: true
            }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });

        // SWEETALERT DELETE KONFIRMASI
        function confirmDelete(url) {
            Swal.fire({
                title: 'Hapus Data?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

        // TAMPILKAN SWEETALERT DARI PHP
        <?php if(!empty($swal_script)) echo $swal_script; ?>
    </script>
</body>
</html>