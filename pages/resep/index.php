<?php
session_start();
require_once '../../config/database.php';

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$role = $_SESSION['role']; 
$success = '';
$error = '';

// --- 1. LOGIKA HAPUS (HANYA OWNER) ---
if (isset($_GET['delete_item'])) {
    if ($role !== 'owner') {
        echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
        exit();
    }
    $id = clean_input($_GET['delete_item']);
    $conn->query("DELETE FROM resep WHERE id_resep = $id");
    header("Location: index.php");
}
if (isset($_GET['delete_product_resep'])) {
    if ($role !== 'owner') {
        echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
        exit();
    }
    $id = clean_input($_GET['delete_product_resep']);
    $conn->query("DELETE FROM resep WHERE id_produk = $id");
    header("Location: index.php");
}

// --- 2. LOGIKA SIMPAN/EDIT RESEP (HANYA OWNER) ---
if (isset($_POST['save_resep'])) {
    if ($role !== 'owner') {
        $error = "Anda tidak berhak mengubah resep.";
    } else {
        $id_produk = clean_input($_POST['id_produk']);
        $bahans    = $_POST['id_bahan'];
        $takarans  = $_POST['takaran'];
        $satuans   = $_POST['satuan'];
        
        if (empty($id_produk) || empty($bahans)) {
            $error = "Produk dan bahan tidak boleh kosong!";
        } else {
            // Hapus resep lama dulu (Reset) agar tidak duplikat/bingung editnya
            // Ini strategi "Edit = Replace All" yang lebih aman untuk resep
            $conn->query("DELETE FROM resep WHERE id_produk = '$id_produk'");

            $stmt = $conn->prepare("INSERT INTO resep (id_produk, id_bahan, takaran, satuan) VALUES (?, ?, ?, ?)");
            $count = 0;
            for ($i = 0; $i < count($bahans); $i++) {
                if (!empty($bahans[$i]) && !empty($takarans[$i])) {
                    $stmt->bind_param("iids", $id_produk, $bahans[$i], $takarans[$i], $satuans[$i]);
                    $stmt->execute();
                    $count++;
                }
            }
            if ($count > 0) {
                $success = "Resep berhasil diperbarui!";
                header("refresh:1;url=index.php");
            } else {
                $error = "Gagal menyimpan resep.";
            }
        }
    }
}

// --- 3. AMBIL DATA ---
$query_resep = "SELECT r.*, p.nama_produk, p.satuan as satuan_produk, b.nama_bahan, b.stok as stok_gudang, b.satuan as satuan_bahan 
                FROM resep r
                JOIN produk p ON r.id_produk = p.id_produk
                JOIN bahan_baku b ON r.id_bahan = b.id_bahan
                ORDER BY p.nama_produk ASC";
$result_resep = $conn->query($query_resep);

$resep_group = [];
while ($row = $result_resep->fetch_assoc()) {
    $pid = $row['id_produk'];
    $resep_group[$pid]['nama_produk'] = $row['nama_produk'];
    $resep_group[$pid]['satuan_produk'] = $row['satuan_produk'];
    $resep_group[$pid]['items'][] = $row;
}

foreach ($resep_group as $pid => $data) {
    $max_production = 999999;
    foreach ($data['items'] as $item) {
        $butuh = $item['takaran'];
        $punya = $item['stok_gudang'];
        $bisa_buat = ($butuh > 0) ? floor($punya / $butuh) : 0;
        if ($bisa_buat < $max_production) $max_production = $bisa_buat;
    }
    $resep_group[$pid]['estimasi_stok'] = $max_production;
}

$produk_list = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
$bahan_list = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");

$bahan_options_js = [];
while($b = $bahan_list->fetch_assoc()) { $bahan_options_js[] = $b; }

$existing_recipes_js = [];
foreach ($resep_group as $pid => $data) {
    $existing_recipes_js[$pid] = $data['items'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep - Dewi Cookies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .resep-card { border-left: 5px solid var(--primary-color); background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .resep-card:hover { transform: translateY(-5px); }
        .ingredient-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem; }
        .estimasi-box { background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%); border-radius: 8px; padding: 10px; margin-top: 15px; border: 1px solid #FFD54F; text-align: center; }
        .estimasi-number { font-size: 1.2rem; font-weight: 800; color: #BF360C; }
        .estimasi-label { font-size: 0.75rem; color: #8D6E63; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .text-brown { color: var(--primary-color) !important; }
        .btn-brown { background-color: var(--primary-color); color: white; }
    </style>
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-mobile-toggle" id="btnMobileToggle"><i class="bi bi-list"></i></button>
                <div class="page-title"><h5 class="fw-bold mb-0 text-dark">Manajemen Resep</h5></div>
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
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-brown m-0">Katalog Resep</h5>
                    <p class="text-muted small mb-0">Komposisi bahan baku untuk setiap produk</p>
                </div>
                
                <?php if ($role == 'owner'): ?>
                <button type="button" class="btn btn-brown rounded-3 px-4" onclick="openModalResep()">
                    <i class="bi bi-plus-lg me-2"></i> Buat Resep Baru
                </button>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <?php if (empty($resep_group)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-journal-x" style="font-size: 3rem;"></i><p class="mt-3">Belum ada resep.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($resep_group as $id_prod => $group): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="content-box resep-card h-100 d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-cookie text-warning me-2"></i><?php echo $group['nama_produk']; ?></h5>
                                    
                                    <div class="d-flex gap-2">
                                        <?php if ($role == 'owner'): ?>
                                        <button class="btn btn-sm btn-outline-warning border-0" onclick="editResep(<?php echo $id_prod; ?>)" title="Edit Resep">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>
                                        <a href="?delete_product_resep=<?php echo $id_prod; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus SEMUA resep ini?')" title="Hapus Resep">
                                            <i class="bi bi-trash fs-6"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <ul class="list-unstyled flex-grow-1 mb-0">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li class="ingredient-item">
                                            <span><?php echo $item['nama_bahan']; ?></span>
                                            <strong class="text-brown"><?php echo $item['takaran'] . ' ' . $item['satuan']; ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="estimasi-box">
                                    <div class="estimasi-label">Estimasi Produksi</div>
                                    <div class="estimasi-number"><?php echo $group['estimasi_stok']; ?> <?php echo ucfirst($group['satuan_produk']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($role == 'owner'): ?>
    <div class="modal fade" id="addResepModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Konfigurasi Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 pt-0">
                        <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Mengedit akan menimpa resep lama produk ini.</div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Pilih Produk:</label>
                            <select class="form-select rounded-3" name="id_produk" id="selectProduk" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php $produk_list->data_seek(0); while($p = $produk_list->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id_produk']; ?>"><?php echo $p['nama_produk']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <label class="form-label fw-bold small mb-2">Komposisi Bahan</label>
                        <div id="bahan-container"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2 dashed-border" onclick="addIngredientRow()"><i class="bi bi-plus-circle"></i> Tambah Bahan</button>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4"><button type="submit" name="save_resep" class="btn btn-brown rounded-3 px-4">Simpan Resep</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        const bahanData = <?php echo json_encode($bahan_options_js); ?>;
        const existingRecipes = <?php echo json_encode($existing_recipes_js); ?>;
        
        // Fungsi Buka Modal Baru
        function openModalResep() {
            document.getElementById('modalTitle').innerText = "Buat Resep Baru";
            document.getElementById('selectProduk').value = "";
            document.getElementById('bahan-container').innerHTML = "";
            addIngredientRow(); // Tambah 1 baris kosong
            new bootstrap.Modal(document.getElementById('addResepModal')).show();
        }

        // Fungsi Edit (Load Data ke Modal)
        function editResep(idProduk) {
            document.getElementById('modalTitle').innerText = "Edit Resep Produk";
            const select = document.getElementById('selectProduk');
            select.value = idProduk;
            
            const container = document.getElementById('bahan-container');
            container.innerHTML = "";

            if(existingRecipes[idProduk]) {
                existingRecipes[idProduk].forEach(item => {
                    addIngredientRow(item.id_bahan, item.takaran, item.satuan);
                });
            } else {
                addIngredientRow();
            }
            new bootstrap.Modal(document.getElementById('addResepModal')).show();
        }

        function addIngredientRow(selectedId = '', selectedTakaran = '', selectedSatuan = 'gram') {
            const container = document.getElementById('bahan-container');
            let optionsHtml = '<option value="">-- Bahan --</option>';
            bahanData.forEach(b => { 
                let sel = (b.id_bahan == selectedId) ? 'selected' : '';
                optionsHtml += `<option value="${b.id_bahan}" ${sel}>${b.nama_bahan} (${b.satuan})</option>`; 
            });

            const div = document.createElement('div');
            div.className = 'repeater-item mb-2 p-2 border rounded bg-light position-relative';
            div.innerHTML = `
                <button type="button" class="btn-close position-absolute top-0 end-0 m-1" onclick="this.parentElement.remove()" style="font-size:0.7rem"></button>
                <div class="row g-2">
                    <div class="col-5"><select name="id_bahan[]" class="form-select form-select-sm" required>${optionsHtml}</select></div>
                    <div class="col-3"><input type="number" name="takaran[]" class="form-control form-control-sm" value="${selectedTakaran}" step="0.01" placeholder="Jml" required></div>
                    <div class="col-4">
                        <select name="satuan[]" class="form-select form-select-sm" required>
                            <option value="gram" ${selectedSatuan=='gram'?'selected':''}>Gram</option>
                            <option value="kg" ${selectedSatuan=='kg'?'selected':''}>Kg</option>
                            <option value="ml" ${selectedSatuan=='ml'?'selected':''}>Ml</option>
                            <option value="liter" ${selectedSatuan=='liter'?'selected':''}>Liter</option>
                            <option value="pcs" ${selectedSatuan=='pcs'?'selected':''}>Pcs</option>
                            <option value="sendok" ${selectedSatuan=='sendok'?'selected':''}>Sdm</option>
                        </select>
                    </div>
                </div>`;
            container.appendChild(div);
        }

        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); const href = this.getAttribute('href');
            Swal.fire({ title: 'Keluar?', text: "Sesi Anda akan berakhir.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Keluar' }).then((res) => { if (res.isConfirmed) window.location.href = href; });
        });
    </script>
</body>
</html>