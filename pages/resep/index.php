<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $delete_query = "DELETE FROM resep WHERE id_resep = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Resep berhasil dihapus!";
    } else {
        $error = "Gagal menghapus resep!";
    }
}

// Ambil data resep dengan join ke produk dan bahan baku
$query = "SELECT r.*, p.nama_produk, b.nama_bahan, b.satuan as satuan_bahan 
          FROM resep r
          JOIN produk p ON r.id_produk = p.id_produk
          JOIN bahan_baku b ON r.id_bahan = b.id_bahan
          ORDER BY p.nama_produk, b.nama_bahan ASC";
$result = $conn->query($query);

// Group by produk
$resep_by_produk = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $resep_by_produk[$row['nama_produk']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep - Dewi Cookies</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: #ff6b9d;
            color: white;
        }
        
        .btn-warning {
            background: #ffbb33;
            color: white;
        }
        
        .btn-danger {
            background: #ff4444;
            color: white;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
        }
        
        .content-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
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
        
        .resep-card {
            border-left: 4px solid #ff6b9d;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .resep-card h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ingredient-list {
            list-style: none;
            padding-left: 0;
        }
        
        .ingredient-item {
            padding: 10px;
            background: white;
            margin-bottom: 8px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ingredient-name {
            font-weight: 500;
            color: #333;
        }
        
        .ingredient-amount {
            color: #666;
            font-size: 0.95rem;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
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
            <div>
                <h1>📝 Resep Produk</h1>
                <p style="color: #666; margin-top: 5px;">Kelola resep untuk setiap produk</p>
            </div>
            <a href="tambah.php" class="btn btn-primary">➕ Tambah Resep</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($resep_by_produk)): ?>
            <?php foreach ($resep_by_produk as $nama_produk => $resep_items): ?>
                <div class="resep-card">
                    <h3>
                        <span>🍪 <?php echo $nama_produk; ?></span>
                        <span style="font-size: 0.9rem; font-weight: normal; color: #666;">
                            (<?php echo count($resep_items); ?> bahan)
                        </span>
                    </h3>
                    
                    <ul class="ingredient-list">
                        <?php foreach ($resep_items as $item): ?>
                            <li class="ingredient-item">
                                <span class="ingredient-name">
                                    📦 <?php echo $item['nama_bahan']; ?>
                                </span>
                                <div>
                                    <span class="ingredient-amount">
                                        <?php echo $item['takaran']; ?> <?php echo $item['satuan']; ?>
                                    </span>
                                    <a href="edit.php?id=<?php echo $item['id_resep']; ?>" 
                                       class="btn btn-warning btn-sm" style="margin-left: 10px;">✏️</a>
                                    <a href="?delete=<?php echo $item['id_resep']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus?')">🗑️</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="content-box">
                <div class="no-data">
                    <div style="font-size: 4rem; margin-bottom: 20px;">📝</div>
                    <h3>Belum ada resep</h3>
                    <p style="color: #999; margin-top: 10px;">
                        Tambahkan resep untuk produk Anda agar sistem bisa menghitung stok otomatis
                    </p>
                    <a href="tambah.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Tambah Resep Pertama
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>