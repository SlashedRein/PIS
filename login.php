<?php
require_once 'config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil input dari form login
    $username_input = trim($_POST['username']);
    $password_input = $_POST['password'];
    
    $query = "SELECT * FROM t_user WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Gunakan password_verify untuk mencocokkan hash
        if (password_verify($password_input, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            header("Location: pages/dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dewi Cookies Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">    
</head>
<body class="login-page">
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🍪</div>
            <h1>Dewi Cookies</h1>
            <p>Sistem Inventory Management</p>
        </div>
        
        <div class="divider"></div>
        
        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" 
                           placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" 
                           placeholder="Masukkan password" required>
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                🔐 Masuk ke Dashboard
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Kembali ke Beranda</a>
        </div>
        
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>