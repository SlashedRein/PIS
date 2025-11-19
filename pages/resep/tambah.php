<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Ambil data produk
$query_produk = "SELECT * FROM produk ORDER BY nama_produk ASC";
$result_produk = $conn->query($query_produk);

// Ambil data bahan baku
$query_bahan = "SELECT * FROM bahan_baku ORDER BY nama_bahan ASC";
$result_bahan = $conn->query($query_bahan);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_produk = clean_input($_POST['id_produk']);
    $id_bahan = clean_input($_POST['id_bahan']);
    $takaran = clean_input($_POST['takaran']);
    $satuan = clean_input($_POST['satuan']);
    
    // Cek apakah kombinasi produk + bahan sudah ada
    $check_query = "SELECT * FROM resep WHERE id_produk = ? AND id_bahan = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $id_produk, $id_bahan);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Bahan baku ini sudah ada dalam resep produk tersebut! Silakan edit jika ingin mengubah takaran.";
    } else {
        $query = "INSERT INTO resep (id_produk, id_bahan, takaran, satuan) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iids", $id_produk, $id_bahan, $takaran, $satuan);
        
        if ($stmt->execute()) {
            $success = "Resep berhasil ditambahkan!";
            header("refresh:2;url=index.php");
        } else {
            $error = "Gagal menambahkan resep!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resep - Dewi Cookies</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #ff6b9d 0%, #c06c84 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
        }
        
        .logo {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 3rem;
        }
        
        .menu-item {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.2);
        }
        
        .user-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .content-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #ff6b9d;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #ff6b9d;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-info {
            background: #33b5e5;
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .info-box h4 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        
        .calculator-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .calculator-result {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .calculator-result.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">🍪</div>
            <h2>Dewi Cookies</h2>
        </div>
        
        <a href="../dashboard.php" class="menu-item">📊 Dashboard</a>
        <a href="../bahan-baku/index.php" class="menu-item">📦 Bahan Baku</a>
        <a href="../produk/index.php" class="menu-item">🍪 Produk</a>
        <a href="index.php" class="menu-item active">📝 Resep</a>
        <a href="../pembelian/index.php" class="menu-item">🛒 Pembelian</a>
        <a href="../penjualan/index.php" class="menu-item">💰 Penjualan</a>
        <a href="../supplier/index.php" class="menu-item">🏭 Supplier</a>
        <a href="../customer/index.php" class="menu-item">👥 Customer</a>
        <a href="../laporan/index.php" class="menu-item">📈 Laporan</a>
        
        <div class="user-info">
            <div>👤 <?php echo $_SESSION['nama_lengkap']; ?></div>
            <div style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">
                <?php echo ucfirst($_SESSION['role']); ?>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>➕ Tambah Resep</h1>
            <p style="color: #666; margin-top: 5px;">Tambahkan bahan baku untuk resep produk</p>
        </div>
        
        <div class="content-box">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="id_produk">Produk *</label>
                    <select id="id_produk" name="id_produk" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php while ($produk = $result_produk->fetch_assoc()): ?>
                            <option value="<?php echo $produk['id_produk']; ?>">
                                <?php echo $produk['nama_produk']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_bahan">Bahan Baku *</label>
                    <select id="id_bahan" name="id_bahan" required>
                        <option value="">-- Pilih Bahan Baku --</option>
                        <?php while ($bahan = $result_bahan->fetch_assoc()): ?>
                            <option value="<?php echo $bahan['id_bahan']; ?>" 
                                    data-satuan="<?php echo $bahan['satuan']; ?>"
                                    data-stok="<?php echo $bahan['stok']; ?>">
                                <?php echo $bahan['nama_bahan']; ?> 
                                (Stok: <?php echo $bahan['stok']; ?> <?php echo $bahan['satuan']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="takaran">Takaran/Jumlah *</label>
                    <input type="number" id="takaran" name="takaran" step="0.01" min="0" required>
                    <small style="color: #666;">Jumlah bahan yang dibutuhkan untuk 1 unit produk</small>
                </div>
                
                <div class="form-group">
                    <label for="satuan">Satuan *</label>
                    <select id="satuan" name="satuan" required>
                        <option value="">-- Pilih Satuan --</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="gram">Gram (g)</option>
                        <option value="liter">Liter (L)</option>
                        <option value="ml">Mililiter (ml)</option>
                        <option value="pcs">Pieces (pcs)</option>
                        <option value="sendok">Sendok (sdm)</option>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" class="btn btn-info" onclick="calculateStock()">🧮 Hitung Stok</button>
                    <a href="index.php" class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
            
            <div class="calculator-box">
                <h4 style="color: #856404; margin-bottom: 10px;">🧮 Kalkulator Stok Produksi</h4>
                <p style="color: #856404; font-size: 0.9rem;">
                    Klik tombol "Hitung Stok" untuk melihat berapa produk yang bisa dibuat dari stok bahan baku yang tersedia
                </p>
                
                <div id="calculatorResult" class="calculator-result">
                    <!-- Hasil akan muncul di sini -->
                </div>
            </div>
            
            <div class="info-box">
                <h4>💡 Tips Penggunaan Resep:</h4>
                <ul style="margin-left: 20px; color: #666;">
                    <li>Tambahkan semua bahan yang diperlukan untuk 1 unit produk</li>
                    <li>Pastikan satuan sesuai dengan satuan bahan baku di stok</li>
                    <li>Gunakan kalkulator untuk melihat berapa produk yang bisa dibuat</li>
                    <li>Sistem akan otomatis mengurangi stok bahan saat ada penjualan</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-fill satuan ketika memilih bahan
        document.getElementById('id_bahan').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const satuan = selected.getAttribute('data-satuan');
            
            if (satuan) {
                document.getElementById('satuan').value = satuan;
            }
        });
        
        // Fungsi untuk menghitung stok yang bisa diproduksi
        function calculateStock() {
            const idProduk = document.getElementById('id_produk').value;
            const idBahan = document.getElementById('id_bahan').value;
            const takaran = parseFloat(document.getElementById('takaran').value);
            
            if (!idProduk || !idBahan || !takaran) {
                alert('Mohon lengkapi semua field terlebih dahulu!');
                return;
            }
            
            const selected = document.getElementById('id_bahan').options[document.getElementById('id_bahan').selectedIndex];
            const stokBahan = parseFloat(selected.getAttribute('data-stok'));
            const namaBahan = selected.text;
            const namaProduk = document.getElementById('id_produk').options[document.getElementById('id_produk').selectedIndex].text;
            
            // Hitung berapa produk yang bisa dibuat
            const jumlahProdukBisa = Math.floor(stokBahan / takaran);
            
            // Tampilkan hasil
            const resultDiv = document.getElementById('calculatorResult');
            resultDiv.classList.add('show');
            
            if (jumlahProdukBisa > 0) {
                resultDiv.innerHTML = `
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                        <h3 style="color: #28a745; margin-bottom: 10px;">Stok Cukup!</h3>
                        <p style="color: #666; margin-bottom: 15px;">
                            Dengan stok <strong>${stokBahan}</strong> yang tersedia,<br>
                            Anda bisa membuat <strong style="color: #ff6b9d; font-size: 1.5rem;">${jumlahProdukBisa} unit</strong> ${namaProduk}
                        </p>
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
                            <small style="color: #666;">
                                📊 Perhitungan: ${stokBahan} ÷ ${takaran} = ${jumlahProdukBisa} unit
                            </small>
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">⚠️</div>
                        <h3 style="color: #dc3545; margin-bottom: 10px;">Stok Tidak Cukup!</h3>
                        <p style="color: #666;">
                            Stok ${namaBahan} saat ini: <strong>${stokBahan}</strong><br>
                            Dibutuhkan minimal: <strong>${takaran}</strong> untuk 1 unit produk
                        </p>
                        <p style="color: #dc3545; margin-top: 10px;">
                            <strong>Silakan restok bahan baku terlebih dahulu!</strong>
                        </p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>