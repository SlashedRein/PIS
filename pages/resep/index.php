<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$success = '';
$error = '';

// --- 1. LOGIKA HAPUS ---
if (isset($_GET['delete_item'])) {
    $id = clean_input($_GET['delete_item']);
    $conn->query("DELETE FROM resep WHERE id_resep = $id");
    header("Location: index.php");
}
if (isset($_GET['delete_product_resep'])) {
    $id = clean_input($_GET['delete_product_resep']);
    $conn->query("DELETE FROM resep WHERE id_produk = $id");
    header("Location: index.php");
}

// --- 2. LOGIKA SIMPAN RESEP ---
if (isset($_POST['save_resep'])) {
    $id_produk = clean_input($_POST['id_produk']);
    $bahans    = $_POST['id_bahan'];
    $takarans  = $_POST['takaran'];
    $satuans   = $_POST['satuan'];
    
    if (empty($id_produk) || empty($bahans)) {
        $error = "Produk dan bahan tidak boleh kosong!";
    } else {
        $stmt = $conn->prepare("INSERT INTO resep (id_produk, id_bahan, takaran, satuan) VALUES (?, ?, ?, ?)");
        $count = 0;
        for ($i = 0; $i < count($bahans); $i++) {
            if (!empty($bahans[$i]) && !empty($takarans[$i])) {
                // Cek duplikat bahan di produk yang sama
                $check = $conn->query("SELECT id_resep FROM resep WHERE id_produk = $id_produk AND id_bahan = " . $bahans[$i]);
                if ($check->num_rows == 0) {
                    $stmt->bind_param("iids", $id_produk, $bahans[$i], $takarans[$i], $satuans[$i]);
                    $stmt->execute();
                    $count++;
                }
            }
        }
        if ($count > 0) {
            $success = "Berhasil menyimpan resep!";
            header("refresh:1;url=index.php");
        } else {
            $error = "Gagal: Bahan mungkin sudah ada di resep ini.";
        }
    }
}

// --- 3. AMBIL DATA & HITUNG ESTIMASI ---
$query_resep = "SELECT r.*, p.nama_produk, p.satuan as satuan_produk, b.nama_bahan, b.stok as stok_gudang, b.satuan as satuan_bahan 
                FROM resep r
                JOIN produk p ON r.id_produk = p.id_produk
                JOIN bahan_baku b ON r.id_bahan = b.id_bahan
                ORDER BY p.nama_produk ASC";
$result_resep = $conn->query($query_resep);

$resep_group = [];
// Grouping data
while ($row = $result_resep->fetch_assoc()) {
    $pid = $row['id_produk'];
    $resep_group[$pid]['nama_produk'] = $row['nama_produk'];
    $resep_group[$pid]['satuan_produk'] = $row['satuan_produk'];
    $resep_group[$pid]['items'][] = $row;
}

// Hitung Estimasi Produksi (Max Yield)
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

// Data untuk Modal
$produk_list = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
$bahan_list = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");

// JSON Data untuk JS
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
        /* Card Style */
        .resep-card { 
            border-left: 5px solid var(--primary-color); 
            transition: transform 0.2s; 
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .resep-card:hover { transform: translateY(-5px); }
        
        .ingredient-item { 
            display: flex; justify-content: space-between; 
            padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem;
        }
        
        /* Modal Repeater */
        .repeater-item { 
            background: #f8f9fa; border: 1px solid #eee; 
            padding: 15px; border-radius: 10px; margin-bottom: 10px; 
            position: relative; animation: fadeIn 0.3s; 
        }
        .btn-remove-row { 
            position: absolute; top: -10px; right: -10px; 
            width: 25px; height: 25px; border-radius: 50%; padding: 0; 
            display: flex; align-items: center; justify-content: center; 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Badge Estimasi */
        .estimasi-box {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
            border-radius: 8px; padding: 10px; margin-top: 15px;
            border: 1px solid #FFD54F; text-align: center;
        }
        .estimasi-number { font-size: 1.2rem; font-weight: 800; color: #BF360C; }
        .estimasi-label { font-size: 0.75rem; color: #8D6E63; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        
        .btn-brown { background-color: var(--primary-color); color: white; }
        .btn-brown:hover { background-color: #6F3410; color: white; }
        .text-brown { color: var(--primary-color) !important; }
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
            <a href="../bahan-baku/index.php" class="nav-link"><i class="bi bi-box-seam"></i> <span>Bahan Baku</span></a>
            <a href="../produk/index.php" class="nav-link"><i class="bi bi-grid"></i> <span>Produk</span></a>
            <a href="index.php" class="nav-link active"><i class="bi bi-journal-text"></i> <span>Resep</span></a>

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
                    <h5 class="fw-bold mb-0 text-dark">Manajemen Resep</h5>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11px;">Komposisi & Estimasi Produksi</small>
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
                    <a href="#" class="dropdown-item-custom logout text-danger" id="btnLogout">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area p-4">
            
            <?php if ($success): ?><div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div><?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-brown m-0">Katalog Resep</h5>
                    <p class="text-muted small mb-0">Lihat resep dan potensi stok jadi berdasarkan gudang</p>
                </div>
                <button type="button" class="btn btn-brown rounded-3 px-4" data-bs-toggle="modal" data-bs-target="#addResepModal">
                    <i class="bi bi-plus-lg me-2"></i> Buat / Edit Resep
                </button>
            </div>

            <div class="row g-4">
                <?php if (empty($resep_group)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-journal-x" style="font-size: 3rem;"></i>
                        <p class="mt-3">Belum ada resep yang dibuat.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($resep_group as $id_prod => $group): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="content-box resep-card h-100 d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-cookie text-warning me-2"></i><?php echo $group['nama_produk']; ?></h5>
                                    <a href="?delete_product_resep=<?php echo $id_prod; ?>" class="text-danger" onclick="return confirm('Hapus SEMUA resep produk ini?')"><i class="bi bi-trash"></i></a>
                                </div>
                                
                                <ul class="list-unstyled flex-grow-1 mb-0">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li class="ingredient-item">
                                            <span><?php echo $item['nama_bahan']; ?></span>
                                            <div class="d-flex align-items-center">
                                                <strong class="text-brown me-2"><?php echo $item['takaran'] . ' ' . $item['satuan']; ?></strong>
                                                <a href="?delete_item=<?php echo $item['id_resep']; ?>" class="text-muted small" onclick="return confirm('Hapus bahan ini dari resep?')"><i class="bi bi-x-circle"></i></a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="estimasi-box">
                                    <div class="estimasi-label">Estimasi Produksi (Maksimal)</div>
                                    <div class="estimasi-number">
                                        <?php echo $group['estimasi_stok']; ?> <?php echo ucfirst($group['satuan_produk']); ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 10px;">*Berdasarkan stok bahan baku saat ini</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addResepModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Konfigurasi Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4 pt-0">
                        
                        <div class="row mb-3 g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Untuk Produk:</label>
                                <select class="form-select rounded-3" name="id_produk" id="selectProduk" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php 
                                    $produk_list->data_seek(0);
                                    while($p = $produk_list->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id_produk']; ?>"><?php echo $p['nama_produk']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-primary"><i class="bi bi-files"></i> Salin Resep Dari:</label>
                                <select class="form-select rounded-3 border-primary" id="copyFromSource" onchange="copyRecipe()">
                                    <option value="">-- Buat Kosong --</option>
                                    <?php foreach ($resep_group as $pid => $g): ?>
                                        <option value="<?php echo $pid; ?>"><?php echo $g['nama_produk']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr class="opacity-25">

                        <label class="form-label fw-bold small mb-2">Komposisi Bahan</label>
                        <div id="bahan-container">
                            </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2 dashed-border" onclick="addIngredientRow()">
                            <i class="bi bi-plus-circle"></i> Tambah Baris Bahan
                        </button>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_resep" class="btn btn-brown rounded-3 px-4">Simpan Resep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Data PHP to JS
        const bahanData = <?php echo json_encode($bahan_options_js); ?>;
        const existingRecipes = <?php echo json_encode($existing_recipes_js); ?>;
        
        // Sidebar Mobile
        const btnMobile = document.getElementById('btnMobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if(btnMobile) { btnMobile.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); }
        if(overlay) { overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }

        // SweetAlert Logout
        document.getElementById('btnLogout').addEventListener('click', function(e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Keluar?',
                text: "Anda harus login kembali nanti.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php'; 
                }
            });
        });

        // Add Row Function
        function addIngredientRow(selectedId = '', selectedTakaran = '', selectedSatuan = 'gram') {
            let optionsHtml = '<option value="">-- Bahan --</option>';
            bahanData.forEach(b => { 
                let sel = (b.id_bahan == selectedId) ? 'selected' : '';
                optionsHtml += `<option value="${b.id_bahan}" ${sel}>${b.nama_bahan} (${b.satuan})</option>`; 
            });

            const div = document.createElement('div');
            div.className = 'repeater-item';
            div.innerHTML = `
                <button type="button" class="btn-remove-row btn-danger text-white shadow-sm" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
                <div class="row g-2">
                    <div class="col-5">
                        <select name="id_bahan[]" class="form-select form-select-sm" required>${optionsHtml}</select>
                    </div>
                    <div class="col-3">
                        <input type="number" name="takaran[]" class="form-control form-control-sm" value="${selectedTakaran}" step="0.01" placeholder="Jml" required>
                    </div>
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
            document.getElementById('bahan-container').appendChild(div);
        }

        // Copy Recipe Logic
        function copyRecipe() {
            const sourceId = document.getElementById('copyFromSource').value;
            const container = document.getElementById('bahan-container');
            
            if(sourceId && existingRecipes[sourceId]) {
                container.innerHTML = '';
                const items = existingRecipes[sourceId];
                if(items.length > 0) {
                    items.forEach(item => { addIngredientRow(item.id_bahan, item.takaran, item.satuan); });
                } else {
                    addIngredientRow();
                }
            } else {
                container.innerHTML = '';
                addIngredientRow();
            }
        }

        // Init Row
        document.addEventListener('DOMContentLoaded', function() {
            addIngredientRow();
        });
    </script>
</body>
</html>