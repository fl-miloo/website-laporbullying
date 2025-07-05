<?php
require_once '../../config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_type'])) {
    redirect(SITE_URL . ($_SESSION['user_type'] == 'guru_bk' ? 'teacher/dashboard.php' : 'student/dashboard.php'));
}

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Bersihkan input
    $nip = isset($_POST['nip']) ? clean_input($_POST['nip']) : '';
    $nama_guru = isset($_POST['nama_guru']) ? clean_input($_POST['nama_guru']) : '';
    $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? clean_input($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? clean_input($_POST['confirm_password']) : '';
    
    // Validasi input
    $errors = [];
    
    // Validasi NIP
    if (empty($nip)) {
        $errors[] = 'NIP harus diisi';
    } elseif (!validate_nip($nip)) {
        $errors[] = 'Format NIP tidak valid (harus 18 digit angka)';
    } else {
        // Cek apakah NIP sudah terdaftar
        $stmt = $conn->prepare("SELECT nip FROM guru_bk WHERE nip = ?");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'NIP sudah terdaftar';
        }
    }
    
    // Validasi Nama
    if (empty($nama_guru)) {
        $errors[] = 'Nama guru harus diisi';
    } elseif (strlen($nama_guru) < 3) {
        $errors[] = 'Nama guru terlalu pendek';
    }
    
    // Validasi Username
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username hanya boleh mengandung huruf, angka, dan underscore';
    } else {
        // Cek apakah username sudah terdaftar
        $stmt = $conn->prepare("SELECT username FROM guru_bk WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username sudah digunakan';
        }
    }
    
    // Validasi Password
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter';
    } elseif ($password != $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    // Handle upload foto
    $foto_name = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['foto'], 'foto_guru', ['jpg', 'jpeg', 'png']);
        
        if ($upload_result['success']) {
            $foto_name = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $password_hash = generate_password_hash($password);
        
        $stmt = $conn->prepare("INSERT INTO guru_bk (nip, nama_guru, username, password, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nip, $nama_guru, $username, $password_hash, $foto_name);
        
        if ($stmt->execute()) {
            set_alert('success', 'Registrasi berhasil! Silakan login dengan akun Anda');
            redirect(SITE_URL . 'auth/login/login_gurubk.php');
        } else {
            $errors[] = 'Gagal menyimpan data ke database: ' . $conn->error;
        }
        $stmt->close();
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
    <title>Registrasi Guru BK - Sistem Pelaporan Bullying</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .register-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .register-body {
            padding: 2rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6B73FF;
            box-shadow: 0 0 0 0.2rem rgba(107, 115, 255, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .strength-0 { width: 0%; background: #dc3545; }
        .strength-1 { width: 25%; background: #dc3545; }
        .strength-2 { width: 50%; background: #ffc107; }
        .strength-3 { width: 75%; background: #28a745; }
        .strength-4 { width: 100%; background: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <i class="fas fa-user-shield fa-3x mb-3"></i>
                <h3>Registrasi Guru BK</h3>
                <p class="mb-0">Daftar akun untuk mengakses sistem</p>
            </div>
            
            <div class="register-body">
                <?php show_alert(); ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nip" class="form-label">
                                <i class="fas fa-id-card me-2"></i>NIP *
                            </label>
                            <input type="text" class="form-control" id="nip" name="nip" 
                                   placeholder="18 digit NIP" required
                                   value="<?php echo isset($_POST['nip']) ? htmlspecialchars($_POST['nip']) : ''; ?>"
                                   maxlength="18" pattern="[0-9]{18}" title="Masukkan 18 digit NIP">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Username *
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username unik" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_guru" class="form-label">
                            <i class="fas fa-user-tie me-2"></i>Nama Lengkap *
                        </label>
                        <input type="text" class="form-control" id="nama_guru" name="nama_guru" 
                               placeholder="Nama lengkap guru" required
                               value="<?php echo isset($_POST['nama_guru']) ? htmlspecialchars($_POST['nama_guru']) : ''; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password *
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Minimal 8 karakter" required
                                   onkeyup="checkPasswordStrength(this.value)">
                            <div id="password-strength-bar" class="password-strength strength-0"></div>
                            <small id="password-strength-text" class="text-muted"></small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Konfirmasi Password *
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Ulangi password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="foto" class="form-label">
                            <i class="fas fa-camera me-2"></i>Foto Profil (Opsional)
                        </label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG. Maksimal 5MB</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                        <label class="form-check-label" for="agree">Saya menyetujui syarat dan ketentuan</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-2">Sudah punya akun?</p>
                        <a href="<?php echo SITE_URL; ?>auth/login/login_gurubk.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            // Check length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Check for mixed case
            if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength++;
            
            // Check for numbers
            if (password.match(/([0-9])/)) strength++;
            
            // Check for special chars
            if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength++;
            
            // Update UI
            strengthBar.className = 'password-strength strength-' + strength;
            
            const strengthMessages = [
                'Sangat Lemah',
                'Lemah',
                'Sedang',
                'Kuat',
                'Sangat Kuat'
            ];
            
            strengthText.textContent = 'Kekuatan: ' + strengthMessages[strength];
            strengthText.className = strength < 2 ? 'text-danger' : 
                                   strength < 4 ? 'text-warning' : 'text-success';
        }
    </script>
</body>
</html>