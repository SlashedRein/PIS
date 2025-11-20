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
        // Hapus resep lama jika ada (agar tidak duplikat saat update/copy)
        // Opsional: aktifkan baris bawah jika ingin sistem replace total
        // $conn->query("DELETE FROM resep WHERE id_produk = $id_produk");

        $stmt = $conn->prepare("INSERT INTO resep (id_produk, id_bahan, takaran, satuan) VALUES (?, ?, ?, ?)");
        $count = 0;
        for ($i = 0; $i < count($bahans); $i++) {
            if (!empty($bahans[$i]) && !empty($takarans[$i])) {
                // Cek duplikat bahan
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
            $error = "Gagal atau bahan sudah ada.";
        }
    }
}

// --- 3. AMBIL DATA UNTUK TAMPILAN & KALKULASI ---
// Ambil data resep lengkap dengan stok bahan baku saat ini
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

// --- 4. LOGIKA HITUNG ESTIMASI PRODUKSI (MAX YIELD) ---
foreach ($resep_group as $pid => $data) {
    $max_production = 999999; // Angka awal sangat besar
    
    foreach ($data['items'] as $item) {
        $butuh = $item['takaran'];
        $punya = $item['stok_gudang'];
        
        // Hindari pembagian nol
        if ($butuh > 0) {
            $bisa_buat = floor($punya / $butuh);
        } else {
            $bisa_buat = 0;
        }
        
        // Cari angka terkecil (limiting factor)
        if ($bisa_buat < $max_production) {
            $max_production = $bisa_buat;
        }
    }
    // Simpan hasil hitungan ke array
    $resep_group[$pid]['estimasi_stok'] = $max_production;
}

// --- 5. DATA PENDUKUNG UTK MODAL ---
$produk_list = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
$bahan_list = $conn->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");

// Siapkan Data JSON untuk Javascript (Fitur Copy Resep & Auto Row)
$bahan_options_js = [];
while($b = $bahan_list->fetch_assoc()) { $bahan_options_js[] = $b; }

// Siapkan Data JSON Resep yang sudah ada (untuk fitur Copy)
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
    <title>Resep & Estimasi - Dewi Cookies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .resep-card { border-left: 5px solid var(--primary-color); transition: transform 0.2s; }
        .resep-card:hover { transform: translateY(-5px); }
        .ingredient-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; }
        .repeater-item { background: #f8f9fa; border: 1px solid #eee; padding: 15px; border-radius: 10px; margin-bottom: 10px; position: relative; animation: fadeIn 0.3s; }
        .btn-remove-row { position: absolute; top: -10px; right: -10px; width: 25px; height: 25px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Badge Estimasi */
        .estimasi-box {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
            border-radius: 8px; padding: 10px; margin-top: 15px;
            border: 1px solid #FFD54F; text-align: center;
        }
        .estimasi-number { font-size: 1.2rem; font-weight: 800; color: #BF360C; }
        .estimasi-label { font-size: 0.8rem; color: #8D6E63; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
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
        <div class="topbar">
            <div class="page-title"><h4>Manajemen Resep</h4></div>
            <div class="user-dropdown-container">
                <div class="user-profile">
                    <div class="user-info"><span class="name"><?php echo $_SESSION['nama_lengkap']; ?></span><span class="role">Owner</span></div>
                    <div class="user-avatar">AD</div>
                </div>
                <div class="dropdown-menu-custom">
                    <a href="../../logout.php" class="dropdown-item-custom logout" onclick="return confirm('Keluar?')">Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div><?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 style="font-weight:700; color:var(--primary-color); margin:0;">Katalog & Estimasi Produksi</h5>
                    <p class="text-muted small mb-0">Lihat resep dan potensi stok jadi berdasarkan gudang.</p>
                </div>
                <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addResepModal">
                    <i class="bi bi-plus-lg"></i> Buat / Edit Resep
                </button>
            </div>

            <div class="row g-4">
                <?php if (empty($resep_group)): ?>
                    <div class="col-12 text-center py-5 text-muted"><i class="bi bi-journal-x" style="font-size: 3rem;"></i><p>Belum ada resep.</p></div>
                <?php else: ?>
                    <?php foreach ($resep_group as $id_prod => $group): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="content-box resep-card h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <h5 class="fw-bold text-dark">🍪 <?php echo $group['nama_produk']; ?></h5>
                                    <a href="?delete_product_resep=<?php echo $id_prod; ?>" class="text-danger" onclick="return confirm('Hapus SEMUA resep produk ini?')"><i class="bi bi-trash"></i></a>
                                </div>
                                
                                <ul class="ingredient-list flex-grow-1">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li class="ingredient-item">
                                            <span><?php echo $item['nama_bahan']; ?></span>
                                            <div>
                                                <strong style="color:var(--primary-color);"><?php echo $item['takaran'] . ' ' . $item['satuan']; ?></strong>
                                                <a href="?delete_item=<?php echo $item['id_resep']; ?>" class="text-muted small ms-2" onclick="return confirm('Hapus item ini?')">x</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="estimasi-box">
                                    <div class="estimasi-label">Estimasi Stok Jadi</div>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfigurasi Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Untuk Produk:</label>
                                <select class="form-select" name="id_produk" id="selectProduk" required>
                                    <option value="">-- Pilih Produk Baru --</option>
                                    <?php 
                                    $produk_list->data_seek(0);
                                    while($p = $produk_list->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id_produk']; ?>"><?php echo $p['nama_produk']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary"><i class="bi bi-files"></i> Salin Resep Dari:</label>
                                <select class="form-select border-primary" id="copyFromSource" onchange="copyRecipe()">
                                    <option value="">-- Jangan Salin (Buat Kosong) --</option>
                                    <?php foreach ($resep_group as $pid => $g): ?>
                                        <option value="<?php echo $pid; ?>"><?php echo $g['nama_produk']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih ini jika ingin menjiplak resep lain.</small>
                            </div>
                        </div>

                        <hr>

                        <label class="form-label fw-bold">Komposisi Bahan</label>
                        <div id="bahan-container">
                            <div class="repeater-item">
                                <div class="row g-2">
                                    <div class="col-5">
                                        <select name="id_bahan[]" class="form-select ingredient-select" required>
                                            <option value="">-- Bahan --</option>
                                        </select>
                                    </div>
                                    <div class="col-3"><input type="number" name="takaran[]" class="form-control" step="0.01" placeholder="Jml" required></div>
                                    <div class="col-4">
                                        <select name="satuan[]" class="form-select" required>
                                            <option value="gram">Gram</option><option value="kg">Kg</option><option value="ml">Ml</option><option value="liter">Liter</option><option value="pcs">Pcs</option><option value="sendok">Sdm</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="addIngredientRow()"><i class="bi bi-plus-circle"></i> Tambah Baris Bahan</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_resep" class="btn btn-primary">Simpan Resep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data dari PHP ke JS
        const bahanData = <?php echo json_encode($bahan_options_js); ?>;
        const existingRecipes = <?php echo json_encode($existing_recipes_js); ?>;
        
        // Fungsi Tambah Baris Kosong
        function addIngredientRow(selectedId = '', selectedTakaran = '', selectedSatuan = 'gram') {
            let optionsHtml = '<option value="">-- Bahan --</option>';
            bahanData.forEach(b => { 
                let sel = (b.id_bahan == selectedId) ? 'selected' : '';
                optionsHtml += `<option value="${b.id_bahan}" ${sel}>${b.nama_bahan} (${b.satuan})</option>`; 
            });

            const div = document.createElement('div');
            div.className = 'repeater-item';
            div.innerHTML = `
                <button type="button" class="btn-remove-row btn-danger text-white" onclick="this.parentElement.remove()">x</button>
                <div class="row g-2">
                    <div class="col-5"><select name="id_bahan[]" class="form-select" required>${optionsHtml}</select></div>
                    <div class="col-3"><input type="number" name="takaran[]" class="form-control" value="${selectedTakaran}" step="0.01" placeholder="Jml" required></div>
                    <div class="col-4">
                        <select name="satuan[]" class="form-select" required>
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

        // Initial Load Dropdown Baris Pertama (yang statis di HTML)
        document.addEventListener('DOMContentLoaded', function() {
            const firstSelect = document.querySelector('.ingredient-select');
            if(firstSelect && firstSelect.options.length <= 1) {
                let html = '<option value="">-- Bahan --</option>';
                bahanData.forEach(b => { html += `<option value="${b.id_bahan}">${b.nama_bahan} (${b.satuan})</option>`; });
                firstSelect.innerHTML = html;
            }
            
            // Logic Auto Open dari Halaman Produk
            const urlParams = new URLSearchParams(window.location.search);
            const newResepId = urlParams.get('new_resep_id');
            if(newResepId) {
                var myModal = new bootstrap.Modal(document.getElementById('addResepModal'));
                myModal.show();
                document.getElementById('selectProduk').value = newResepId;
            }
        });

        // FUNGSI COPY RESEP OTOMATIS
        function copyRecipe() {
            const sourceId = document.getElementById('copyFromSource').value;
            const container = document.getElementById('bahan-container');
            
            if(sourceId && existingRecipes[sourceId]) {
                // Kosongkan container
                container.innerHTML = '';
                
                // Loop resep sumber dan masukkan ke form
                const items = existingRecipes[sourceId];
                if(items.length > 0) {
                    items.forEach(item => {
                        addIngredientRow(item.id_bahan, item.takaran, item.satuan);
                    });
                } else {
                    // Jika kosong, tambah 1 baris default
                    addIngredientRow();
                }
            } else {
                // Reset ke 1 baris kosong jika pilih "-- Jangan Salin --"
                container.innerHTML = '';
                addIngredientRow();
            }
        }
    </script>
</body>
</html>