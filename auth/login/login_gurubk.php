<?php
require_once '../../config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'guru_bk') {
    redirect('../../teacher/dashboard.php');
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    // Validasi input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    }
    
    // Jika tidak ada error, cek login
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM guru_bk WHERE username = ? AND status = 'Aktif'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $guru = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $guru['password'])) {
                // Set session
                $_SESSION['user_id'] = $guru['id_guru'];
                $_SESSION['user_name'] = $guru['nama_guru'];
                $_SESSION['user_type'] = 'guru_bk';
                $_SESSION['user_foto'] = $guru['foto'];
                
                // Redirect ke dashboard
                redirect('../../teacher/dashboard.php');
            } else {
                $errors[] = 'Password salah';
            }
        } else {
            $errors[] = 'Username tidak ditemukan atau akun tidak aktif';
        }
    }
    
    // Tampilkan error
    if (!empty($errors)) {
        set_alert('error', implode('<br>', $errors));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Guru BK - Sistem Pelaporan Bullying</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }
        .login-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #6B73FF;
            box-shadow: 0 0 0 0.2rem rgba(107, 115, 255, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-shield fa-3x mb-3"></i>
                <h3>Login Guru BK</h3>
                <p class="mb-0">Masuk ke Sistem Pelaporan Bullying</p>
            </div>
            
            <div class="login-body">
                <?php show_alert(); ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>Username *
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Masukkan username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Password *
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                    
                    <div class="text-center">
                        <a href="forgot_password.php" class="text-decoration-none">Lupa password?</a>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p>Belum punya akun? Hubungi admin untuk registrasi</p>
                    <a href="../../index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-home me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>