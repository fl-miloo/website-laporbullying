<?php
require_once '../../config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'siswa') {
    redirect('../../student/dashboard.php');
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_siswa = clean_input($_POST['nama_siswa']);
    $nisn = clean_input($_POST['nisn']);
    
    // Validasi input
    $errors = [];
    
    if (empty($nama_siswa)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (empty($nisn)) {
        $errors[] = 'NISN harus diisi';
    } elseif (!validate_nisn($nisn)) {
        $errors[] = 'NISN harus berupa 10 digit angka';
    }
    
    // Jika tidak ada error, cek login
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM siswa WHERE nama_siswa = ? AND nisn = ? AND status = 'Aktif'");
        $stmt->bind_param("ss", $nama_siswa, $nisn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $siswa = $result->fetch_assoc();
            
            // Set session
            $_SESSION['user_id'] = $siswa['id_siswa'];
            $_SESSION['user_name'] = $siswa['nama_siswa'];
            $_SESSION['user_type'] = 'siswa';
            $_SESSION['user_foto'] = $siswa['foto'];
            $_SESSION['user_nisn'] = $siswa['nisn'];
            $_SESSION['user_kelas'] = $siswa['kelas'];
            
            // Log aktivitas
            log_activity($siswa['id_siswa'], 'siswa', 'login', 'Berhasil login ke sistem');
            
            // Redirect ke dashboard
            redirect('../../student/dashboard.php');
        } else {
            $errors[] = 'Nama lengkap dan NISN tidak ditemukan atau akun tidak aktif';
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
    <title>Login Siswa - Sistem Pelaporan Bullying</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .info-box {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                <h3>Login Siswa</h3>
                <p class="mb-0">Sistem Pelaporan Bullying</p>
            </div>
            
            <div class="login-body">
                <?php show_alert(); ?>
                
                <div class="info-box">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    <strong>Petunjuk Login:</strong><br>
                    Masukkan nama lengkap sesuai yang terdaftar di sekolah dan NISN (10 digit) untuk masuk ke sistem.
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nama_siswa" class="form-label">
                            <i class="fas fa-user me-2"></i>Nama Lengkap *
                        </label>
                        <input type="text" class="form-control" id="nama_siswa" name="nama_siswa" 
                               placeholder="Masukkan nama lengkap sesuai data sekolah" required
                               value="<?php echo isset($_POST['nama_siswa']) ? htmlspecialchars($_POST['nama_siswa']) : ''; ?>">
                        <div class="form-text">Gunakan nama lengkap sesuai yang terdaftar di sekolah</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nisn" class="form-label">
                            <i class="fas fa-id-card me-2"></i>NISN *
                        </label>
                        <input type="text" class="form-control" id="nisn" name="nisn" 
                               placeholder="Masukkan 10 digit NISN" required maxlength="10"
                               value="<?php echo isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : ''; ?>">
                        <div class="form-text">NISN terdiri dari 10 digit angka</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Masuk
                    </button>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-2">Belum terdaftar?</p>
                    <a href="../registrasi/registrasi_siswa.php" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fas fa-user-plus me-2"></i>Daftar Siswa
                    </a>
                </div>
                
                <div class="text-center mt-3">
                    <a href="../../index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-home me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi NISN input hanya angka
        document.getElementById('nisn').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        // Auto format nama (capitalize first letter)
        document.getElementById('nama_siswa').addEventListener('blur', function(e) {
            let nama = this.value.toLowerCase().split(' ');
            for (let i = 0; i < nama.length; i++) {
                nama[i] = nama[i].charAt(0).toUpperCase() + nama[i].slice(1);
            }
            this.value = nama.join(' ');
        });
    </script>
</body>
</html>