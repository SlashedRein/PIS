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
    
    // Cek apakah customer dipakai di penjualan
    $check_query = "SELECT COUNT(*) as count FROM penjualan WHERE id_cust = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();
    
    if ($check['count'] > 0) {
        $error = "Customer tidak bisa dihapus karena masih terdapat transaksi penjualan terkait!";
    } else {
        $delete_query = "DELETE FROM customer WHERE id_cust = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Customer berhasil dihapus!";
        } else {
            $error = "Gagal menghapus customer!";
        }
    }
}

// Ambil semua data customer
$query = "SELECT * FROM customer ORDER BY nama ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Dewi Cookies</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {margin: 0;padding: 0;box-sizing: border-box;}
        body {font-family: 'Poppins', sans-serif;background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);min-height: 100vh;}
        .sidebar {position: fixed;left: 0;top: 0;width: 80px;height: 100vh;background: linear-gradient(180deg, #6F3410 0%, #8B4513 100%);color: #FFF8DC;padding: 20px 10px;overflow: hidden;box-shadow: 4px 0 20px rgba(0,0,0,0.1);z-index: 1000;transition: width 0.3s ease;}
        .sidebar:hover {width: 270px;overflow-y: auto;}
        .logo {text-align: center;padding-bottom: 20px;border-bottom: 2px solid rgba(255,248,220,0.2);margin-bottom: 20px;white-space: nowrap;}
        .logo-icon {font-size: 2.5rem;display: inline-block;animation: bounce 2s ease-in-out infinite;}
        @keyframes bounce {0%, 100% {transform: translateY(0);}50% {transform: translateY(-8px);}}
        .logo h2 {font-family: 'Playfair Display', serif;font-size: 1.5rem;margin-top: 10px;text-shadow: 2px 2px 4px rgba(0,0,0,0.3);opacity: 0;transition: opacity 0.3s ease;}
        .sidebar:hover .logo h2 {opacity: 1;}
        .menu-section {margin-bottom: 20px;}
        .menu-section-title {font-size: 0.7rem;text-transform: uppercase;letter-spacing: 1px;color: rgba(255,248,220,0.6);margin-bottom: 8px;padding-left: 15px;font-weight: 600;white-space: nowrap;opacity: 0;transition: opacity 0.3s ease;}
        .sidebar:hover .menu-section-title {opacity: 1;}
        .menu-item {padding: 12px 15px;margin: 5px 0;border-radius: 12px;cursor: pointer;transition: all 0.3s;color: #FFF8DC;text-decoration: none;display: flex;align-items: center;gap: 12px;font-weight: 500;white-space: nowrap;}
        .menu-item:hover {background: rgba(255,248,220,0.15);}
        .sidebar:hover .menu-item:hover {transform: translateX(5px);}
        .menu-item.active {background: rgba(255,248,220,0.25);box-shadow: 0 4px 12px rgba(0,0,0,0.2);}
        .menu-icon {font-size: 1.3rem;min-width: 1.3rem;text-align: center;}
        .menu-text {opacity: 0;transition: opacity 0.3s ease;}
        .sidebar:hover .menu-text {opacity: 1;}
        .user-info {position: absolute;bottom: 20px;left: 10px;right: 10px;padding: 15px;background: rgba(255,248,220,0.15);border-radius: 15px;text-align: center;}
        .user-avatar {width: 40px;height: 40px;background: linear-gradient(135deg, #FFF8DC, #F5DEB3);border-radius: 50%;display: flex;align-items: center;justify-content: center;font-size: 1.3rem;margin: 0 auto 10px;}
        .user-details {opacity: 0;transition: opacity 0.3s ease;white-space: nowrap;}
        .sidebar:hover .user-details {opacity: 1;}
        .user-name {font-weight: 600;font-size: 0.9rem;}
        .user-role {font-size: 0.75rem;opacity: 0.8;margin-top: 3px;}
        .main-content {margin-left: 80px;padding: 25px;min-height: 100vh;}
        .header {background: white;padding: 25px 30px;border-radius: 20px;margin-bottom: 25px;box-shadow: 0 4px 20px rgba(111,52,16,0.08);display: flex;justify-content: space-between;align-items: center;}
        .header h1 {font-family: 'Playfair Display', serif;color: #6F3410;font-size: 2rem;display: flex;align-items: center;gap: 10px;}
        .header p {color: #8B4513;margin-top: 5px;font-size: 0.95rem;}
        .btn {padding: 10px 20px;border: none;border-radius: 12px;cursor: pointer;text-decoration: none;display: inline-block;font-size: 0.9rem;font-weight: 600;transition: all 0.3s;}
        .btn-primary {background: linear-gradient(135deg, #8B4513, #6F3410);color: #FFF8DC;box-shadow: 0 4px 15px rgba(139,69,19,0.3);}
        .btn-primary:hover {transform: translateY(-2px);box-shadow: 0 6px 20px rgba(139,69,19,0.4);}
        .btn-warning {background: linear-gradient(135deg, #ffbb33, #ff9800);color: white;}
        .btn-danger {background: linear-gradient(135deg, #ff4444, #dc3545);color: white;}
        .btn-sm {padding: 6px 14px;font-size: 0.85rem;}
        .content-box {background: white;padding: 25px;border-radius: 18px;box-shadow: 0 4px 20px rgba(111,52,16,0.08);}
        table {width: 100%;border-collapse: collapse;margin-top: 20px;}
        th, td {padding: 14px 12px;text-align: left;border-bottom: 1px solid #f0f0f0;}
        th {background: linear-gradient(135deg, #FFF8DC, #F5DEB3);font-weight: 600;color: #6F3410;font-family: 'Playfair Display', serif;font-size: 1rem;}
        tbody tr {transition: all 0.3s;}
        tbody tr:hover {background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 30%);transform: translateX(5px);}
        td {color: #6F3410;}
        .alert {padding: 15px 20px;border-radius: 12px;margin-bottom: 20px;display: flex;align-items: center;gap: 10px;}
        .alert-success {background: linear-gradient(135deg, #d4edda, #c3e6cb);color: #155724;border-left: 4px solid #28a745;}
        .alert-error {background: linear-gradient(135deg, #f8d7da, #f5c6cb);color: #721c24;border-left: 4px solid #dc3545;}
        .no-data {text-align: center;padding: 50px 20px;color: #8B4513;}
        .no-data-icon {font-size: 4rem;margin-bottom: 15px;}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><div class="logo-icon">🍪</div><h2>Dewi Cookies</h2></div>
        <div class="menu-section">
            <div class="menu-section-title">Main Menu</div>
            <a href="../dashboard.php" class="menu-item"><span class="menu-icon">📊</span><span class="menu-text">Dashboard</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Master Data</div>
            <a href="../supplier/index.php" class="menu-item"><span class="menu-icon">🏭</span><span class="menu-text">Supplier</span></a>
            <a href="index.php" class="menu-item active"><span class="menu-icon">👥</span><span class="menu-text">Customer</span></a>
        </div>
        <div class="user-info">
            <div class="user-avatar">👤</div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div><h1><span>👥</span> Customer</h1><p>Kelola data customer/pelanggan</p></div>
            <a href="tambah.php" class="btn btn-primary">➕ Tambah Customer</a>
        </div>
        
        <div class="content-box">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><span style="font-size: 1.5rem;">✅</span><span><?php echo $success; ?></span></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><span style="font-size: 1.5rem;">❌</span><span><?php echo $error; ?></span></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr><th>No</th><th>Nama Customer</th><th>Alamat</th><th>No Telp</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo $row['nama']; ?></strong></td>
                            <td><?php echo $row['alamat'] ?? '-'; ?></td>
                            <td><?php echo $row['no_telp'] ?? '-'; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id_cust']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <a href="?delete=<?php echo $row['id_cust']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <div class="no-data-icon">👥</div>
                                <strong>Belum ada data customer</strong>
                                <p style="margin-top: 10px; font-size: 0.9rem;">Klik tombol "Tambah Customer" untuk menambahkan data</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>