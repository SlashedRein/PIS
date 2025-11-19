<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = clean_input($_GET['id']);

// Ambil data resep
$query = "SELECT r.*, p.nama_produk, b.nama_bahan 
          FROM resep r
          JOIN produk p ON r.id_produk = p.id_produk
          JOIN bahan_baku b ON r.id_bahan = b.id_bahan
          WHERE r.id_resep = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$data = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $takaran = clean_input($_POST['takaran']);
    $satuan = clean_input($_POST['satuan']);
    
    $update_query = "UPDATE resep SET takaran = ?, satuan = ? WHERE id_resep = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("dsi", $takaran, $satuan, $id);
    
    if ($stmt->execute()) {
        $success = "Resep berhasil diupdate!";
        header("refresh:2;url=index.php");
    } else {
        $error = "Gagal mengupdate resep!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resep - Dewi Cookies</title>
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
            max-width: 600px;
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
        
        input:disabled, select:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
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
            margin-bottom: 20px;
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
            <h1>✏️ Edit Resep</h1>
            <p style="color: #666; margin-top: 5px;">Update takaran resep</p>
        </div>
        
        <div class="content-box">
            <div class="info-box">
                <strong>🍪 Produk:</strong> <?php echo $data['nama_produk']; ?><br>
                <strong>📦 Bahan:</strong> <?php echo $data['nama_bahan']; ?>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Produk</label>
                    <input type="text" value="<?php echo $data['nama_produk']; ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Bahan Baku</label>
                    <input type="text" value="<?php echo $data['nama_bahan']; ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="takaran">Takaran/Jumlah *</label>
                    <input type="number" id="takaran" name="takaran" step="0.01" min="0" 
                           value="<?php echo $data['takaran']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="satuan">Satuan *</label>
                    <select id="satuan" name="satuan" required>
                        <option value="">-- Pilih Satuan --</option>
                        <option value="kg" <?php echo $data['satuan'] == 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="gram" <?php echo $data['satuan'] == 'gram' ? 'selected' : ''; ?>>Gram (g)</option>
                        <option value="liter" <?php echo $data['satuan'] == 'liter' ? 'selected' : ''; ?>>Liter (L)</option>
                        <option value="ml" <?php echo $data['satuan'] == 'ml' ? 'selected' : ''; ?>>Mililiter (ml)</option>
                        <option value="pcs" <?php echo $data['satuan'] == 'pcs' ? 'selected' : ''; ?>>Pieces (pcs)</option>
                        <option value="sendok" <?php echo $data['satuan'] == 'sendok' ? 'selected' : ''; ?>>Sendok (sdm)</option>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">💾 Update</button>
                    <a href="index.php" class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>