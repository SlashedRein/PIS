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
$role = $_SESSION['role']; 
$swal_script = '';

// --- 1. LOGIKA DELETE (HANYA OWNER) ---
if (isset($_GET['delete'])) {
    if ($role !== 'owner') {
        $swal_script = "Swal.fire('Akses Ditolak', 'Hanya Owner yang boleh menghapus data.', 'error');";
    } else {
        $id = clean_input($_GET['delete']);
        
        // Cek Relasi
        $check_resep = $conn->query("SELECT COUNT(*) as count FROM resep WHERE id_produk = '$id'")->fetch_assoc();
        $check_jual  = $conn->query("SELECT COUNT(*) as count FROM detail_penjualan WHERE id_produk = '$id'")->fetch_assoc();
        
        if ($check_resep['count'] > 0) {
            $swal_script = "Swal.fire('Gagal', 'Produk ini digunakan dalam Resep. Hapus resepnya dulu.', 'error');";
        } elseif ($check_jual['count'] > 0) {
            $swal_script = "Swal.fire('Gagal', 'Produk ini punya riwayat penjualan. Tidak bisa dihapus.', 'error');";
        } else {
            $stmt = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: index.php?status=deleted");
                exit();
            } else {
                $swal_script = "Swal.fire('Error', 'Gagal hapus: " . $conn->error . "', 'error');";
            }
        }
    }
}

// --- 2. LOGIKA CREATE (HANYA OWNER) ---
if (isset($_POST['create_produk'])) {
    if ($role !== 'owner') {
        $swal_script = "Swal.fire('Gagal', 'Anda tidak berhak menambah produk.', 'error');";
    } else {
        $nama   = clean_input($_POST['nama_produk']);
        $satuan = clean_input($_POST['satuan']);
        $harga  = clean_input($_POST['harga_jual']);
        $stok   = clean_input($_POST['stok']);

        if (empty($nama)) {
            $swal_script = "Swal.fire('Gagal', 'Nama produk wajib diisi!', 'warning');";
        } else {
            $stmt = $conn->prepare("INSERT INTO produk (nama_produk, satuan, harga_jual, stok) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nama, $satuan, $harga, $stok);
            if ($stmt->execute()) {
                header("Location: index.php?status=created");
                exit();
            } else {
                $swal_script = "Swal.fire('Error', 'Gagal simpan: " . $conn->error . "', 'error');";
            }
        }
    }
}

// --- 3. LOGIKA UPDATE (BISA SEMUA, TAPI DIBATASI) ---
if (isset($_POST['update_produk'])) {
    $id     = clean_input($_POST['id_produk']);
    $stok   = clean_input($_POST['stok']); 
    
    $old_data = $conn->query("SELECT * FROM produk WHERE id_produk = '$id'")->fetch_assoc();

    if ($role == 'owner') {
        $nama   = clean_input($_POST['nama_produk']);
        $satuan = clean_input($_POST['satuan']);
        $harga  = clean_input($_POST['harga_jual']);
    } else {
        $nama   = $old_data['nama_produk'];
        $satuan = $old_data['satuan'];
        $harga  = $old_data['harga_jual'];
    }

    $stmt = $conn->prepare("UPDATE produk SET nama_produk=?, satuan=?, harga_jual=?, stok=? WHERE id_produk=?");
    $stmt->bind_param("ssiii", $nama, $satuan, $harga, $stok, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?status=updated");
        exit();
    } else {
        $swal_script = "Swal.fire('Error', 'Gagal update: " . $conn->error . "', 'error');";
    }
}

// --- 4. CEK STATUS NOTIFIKASI ---
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') $swal_script = "Swal.fire('Berhasil', 'Produk dihapus.', 'success');";
    if ($_GET['status'] == 'created') $swal_script = "Swal.fire('Berhasil', 'Produk baru ditambahkan.', 'success');";
    if ($_GET['status'] == 'updated') $swal_script = "Swal.fire('Berhasil', 'Data produk diperbarui.', 'success');";
}

// --- 5. PAGINATION & SEARCH LOGIC ---
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$q = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$where_sql = !empty($q) ? "WHERE nama_produk LIKE '%$q%'" : "";

// Hitung Total Data
$total_result = $conn->query("SELECT COUNT(*) as total FROM produk $where_sql")->fetch_assoc();
$total_pages = ceil($total_result['total'] / $limit);

// Ambil Data Limit
$query = "SELECT * FROM produk $where_sql ORDER BY nama_produk ASC LIMIT $start, $limit";
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
        
        /* Tombol Aksi Responsif */
        .btn-action-group {
            display: flex; gap: 4px; justify-content: flex-end; flex-wrap: wrap;
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
                    <h5 class="fw-bold mb-0 text-dark">Data Produk</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">Katalog Kue & Cookies</small>
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
                        <h5 class="fw-bold text-brown mb-1">Daftar Produk</h5>
                        <p class="text-muted small mb-0">Manajemen stok barang jadi</p>
                    </div>
                    
                    <?php if ($role == 'owner'): ?>
                    <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i>Produk Baru
                    </button>
                    <?php endif; ?>
                </div>

                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama produk..." value="<?php echo htmlspecialchars($q); ?>">
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
                                <th class="px-3 py-3">Nama Produk</th>
                                <th class="px-3 py-3">Satuan</th>
                                <th class="px-3 py-3">Harga Jual</th>
                                <th class="px-3 py-3">Stok</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 rounded-end text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $start + 1;
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): 
                                    if ($row['stok'] == 0) $status = '<span class="badge-status badge-danger">Habis</span>';
                                    elseif ($row['stok'] < 10) $status = '<span class="badge-status badge-warning">Menipis</span>';
                                    else $status = '<span class="badge-status badge-aman">Aman</span>';
                            ?>
                                <tr>
                                    <td class="px-3"><?php echo $no++; ?></td>
                                    <td class="px-3 fw-bold text-dark"><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                    <td class="px-3"><?php echo htmlspecialchars($row['satuan']); ?></td>
                                    <td class="px-3">Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                                    <td class="px-3 fw-bold bg-light text-center border"><?php echo $row['stok']; ?></td>
                                    <td class="px-3"><?php echo $status; ?></td>
                                    <td class="px-3 text-end">
                                        <div class="btn-action-group">
                                            <button type="button" class="btn btn-sm btn-warning text-white rounded-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="<?php echo $row['id_produk']; ?>"
                                                    data-nama="<?php echo $row['nama_produk']; ?>"
                                                    data-satuan="<?php echo $row['satuan']; ?>"
                                                    data-harga="<?php echo $row['harga_jual']; ?>"
                                                    data-stok="<?php echo $row['stok']; ?>"
                                                    title="<?php echo ($role == 'owner') ? 'Edit Produk' : 'Update Stok'; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <?php if ($role == 'owner'): ?>
                                            <button type="button" class="btn btn-sm btn-danger rounded-2" 
                                                    onclick="confirmDelete('?delete=<?php echo $row['id_produk']; ?>')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&q=<?php echo $q; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&q=<?php echo $q; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&q=<?php echo $q; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php if ($role == 'owner'): ?>
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">Tambah Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold">Nama Produk</label><input type="text" class="form-control rounded-3" name="nama_produk" required></div>
                        <div class="row">
                            <div class="col-6 mb-3"><label class="form-label small fw-bold">Satuan</label><select class="form-select rounded-3" name="satuan"><option value="toples">Toples</option><option value="pcs">Pcs</option><option value="box">Box</option><option value="pack">Pack</option></select></div>
                            <div class="col-6 mb-3"><label class="form-label small fw-bold">Stok Awal</label><input type="number" class="form-control rounded-3" name="stok" value="0"></div>
                        </div>
                        <div class="mb-3"><label class="form-label small fw-bold">Harga Jual</label><input type="number" class="form-control rounded-3" name="harga_jual" required></div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="create_produk" class="btn btn-brown rounded-3 px-4">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><?php echo ($role == 'owner') ? 'Edit Produk' : 'Update Stok Produksi'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_produk" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Produk</label>
                            <input type="text" class="form-control rounded-3 <?php echo ($role !== 'owner') ? 'bg-light' : ''; ?>" name="nama_produk" id="edit_nama" <?php echo ($role !== 'owner') ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Satuan</label>
                                <?php if ($role == 'owner'): ?>
                                    <select class="form-select rounded-3" name="satuan" id="edit_satuan"><option value="toples">Toples</option><option value="pcs">Pcs</option><option value="box">Box</option><option value="pack">Pack</option></select>
                                <?php else: ?>
                                    <input type="text" class="form-control rounded-3 bg-light" name="satuan" id="edit_satuan_text" readonly>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold text-success">Stok (Update)</label>
                                <input type="number" class="form-control rounded-3 border-success" name="stok" id="edit_stok" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Harga Jual</label>
                            <input type="number" class="form-control rounded-3 <?php echo ($role !== 'owner') ? 'bg-light' : ''; ?>" name="harga_jual" id="edit_harga" <?php echo ($role !== 'owner') ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="update_produk" class="btn btn-brown rounded-3 px-4">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const editModal = document.getElementById('editModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                document.getElementById('edit_id').value = btn.getAttribute('data-id');
                document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_stok').value = btn.getAttribute('data-stok');
                document.getElementById('edit_harga').value = btn.getAttribute('data-harga');
                
                const role = "<?php echo $role; ?>";
                if(role === 'owner') document.getElementById('edit_satuan').value = btn.getAttribute('data-satuan');
                else document.getElementById('edit_satuan_text').value = btn.getAttribute('data-satuan');
            });
        }

        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); const href = this.getAttribute('href');
            Swal.fire({ title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Keluar' }).then((res) => { if (res.isConfirmed) window.location.href = href; });
        });

        function confirmDelete(url) {
            Swal.fire({ title: 'Hapus?', text: "Data hilang permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!' }).then((res) => { if (res.isConfirmed) window.location.href = url; });
        }

        // Tampilkan Pesan Sukses/Gagal dari PHP
        <?php if(!empty($swal_script)) echo $swal_script; ?>
    </script>
</body>
</html>