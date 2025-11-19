<?php
require_once '../config/database.php';
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

            header("Location: dashboard.php");
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 50%, #DEB887 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background */
        body::before {
            content: '🍪';
            position: absolute;
            font-size: 15rem;
            opacity: 0.03;
            top: -50px;
            left: -100px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '🥠';
            position: absolute;
            font-size: 12rem;
            opacity: 0.03;
            bottom: -50px;
            right: -80px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        .login-container {
            background: white;
            padding: 50px 45px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(111, 52, 16, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo-icon {
            font-size: 5rem;
            display: inline-block;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            color: #6F3410;
            font-size: 2.2rem;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #8B4513;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #8B4513, transparent);
            margin: 25px 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #6F3410;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #F5DEB3;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #FEFEFE;
            font-family: 'Poppins', sans-serif;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
            background: white;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #8B4513 0%, #6F3410 100%);
            color: #FFF8DC;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(139, 69, 19, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: linear-gradient(135deg, #ffe0e0 0%, #ffcccb 100%);
            color: #c00;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            border-left: 4px solid #d00;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            color: #6F3410;
            gap: 8px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);
            border-left: 4px solid #8B4513;
            padding: 20px;
            margin-top: 25px;
            border-radius: 12px;
        }
        
        .info-box h4 {
            color: #6F3410;
            margin-bottom: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
        }
        
        .info-box p {
            color: #6F3410;
            font-size: 0.9rem;
            margin: 8px 0;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #8B4513;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2rem;
            user-select: none;
        }
        
        /* Responsive */
        @media (max-width: 500px) {
            .login-container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
            
            .logo-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
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