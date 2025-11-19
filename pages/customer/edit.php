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

$query = "SELECT * FROM customer WHERE id_cust = ?";
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
    $nama = clean_input($_POST['nama']);
    $alamat = clean_input($_POST['alamat']);
    $no_telp = clean_input($_POST['no_telp']);
    
    $update_query = "UPDATE customer SET nama = ?, alamat = ?, no_telp = ? WHERE id_cust = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $nama, $alamat, $no_telp, $id);
    
    if ($stmt->execute()) {
        $success = "Customer berhasil diupdate!";
        header("refresh:2;url=index.php");
    } else {
        $error = "Gagal mengupdate customer!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Dewi Cookies</title>
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
        .header {background: white;padding: 25px 30px;border-radius: 20px;margin-bottom: 25px;box-shadow: 0 4px 20px rgba(111,52,16,0.08);}
        .header h1 {font-family: 'Playfair Display', serif;color: #6F3410;font-size: 2rem;display: flex;align-items: center;gap: 10px;}
        .header p {color: #8B4513;margin-top: 5px;font-size: 0.95rem;}
        .content-box {background: white;padding: 30px;border-radius: 18px;box-shadow: 0 4px 20px rgba(111,52,16,0.08);max-width: 700px;}
        .info-box {background: linear-gradient(135deg, #FFF8DC, #F5DEB3);padding: 18px;border-radius: 12px;margin-bottom: 25px;border-left: 4px solid #8B4513;}
        .info-box strong {color: #6F3410;font-family: 'Playfair Display', serif;}
        .form-group {margin-bottom: 22px;}
        label {display: block;margin-bottom: 10px;color: #6F3410;font-weight: 600;font-family: 'Playfair Display', serif;font-size: 1.05rem;}
        input[type="text"],textarea {width: 100%;padding: 14px 16px;border: 2px solid #F5DEB3;border-radius: 12px;font-size: 1rem;transition: all 0.3s;font-family: 'Poppins', sans-serif;color: #6F3410;}
        textarea {min-height: 100px;resize: vertical;}
        input:focus, textarea:focus {outline: none;border-color: #8B4513;box-shadow: 0 0 0 3px rgba(139,69,19,0.1);}
        .btn {padding: 12px 28px;border: none;border-radius: 12px;cursor: pointer;font-size: 1rem;text-decoration: none;display: inline-block;margin-right: 12px;font-weight: 600;transition: all 0.3s;}
        .btn-primary {background: linear-gradient(135deg, #8B4513, #6F3410);color: #FFF8DC;box-shadow: 0 4px 15px rgba(139,69,19,0.3);}
        .btn-primary:hover {transform: translateY(-2px);box-shadow: 0 6px 20px rgba(139,69,19,0.4);}
        .btn-secondary {background: linear-gradient(135deg, #6c757d, #5a6268);color: white;box-shadow: 0 4px 15px rgba(108,117,125,0.3);}
        .btn-secondary:hover {transform: translateY(-2px);box-shadow: 0 6px 20px rgba(108,117,125,0.4);}
        .alert {padding: 16px 20px;border-radius: 12px;margin-bottom: 25px;display: flex;align-items: center;gap: 12px;animation: slideIn 0.3s ease-out;}
        @keyframes slideIn {from {opacity: 0;transform: translateY(-10px);}to {opacity: 1;transform: translateY(0);}}
        .alert-success {background: linear-gradient(135deg, #d4edda, #c3e6cb);color: #155724;border-left: 4px solid #28a745;}
        .alert-error {background: linear-gradient(135deg, #f8d7da, #f5c6cb);color: #721c24;border-left: 4px solid #dc3545;}
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
            <div><h1><span>✏️</span> Edit Customer</h1><p>Update data customer</p></div>
        </div>
        
        <div class="content-box">
            <div class="info-box">
                <strong>📝 Mengedit:</strong> <?php echo htmlspecialchars($data['nama']); ?>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><span style="font-size: 1.5rem;">✅</span><span><?php echo $success; ?></span></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><span style="font-size: 1.5rem;">❌</span><span><?php echo $error; ?></span></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama">Nama Customer *</label>
                    <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($data['nama']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="no_telp">No Telepon</label>
                    <input type="text" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($data['no_telp']); ?>">
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