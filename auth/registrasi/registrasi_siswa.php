<?php
require_once '../../config/koneksi.php';

// Fungsi khusus untuk registrasi siswa (tidak ada di koneksi.php)
function cek_nisn_exist($nisn) {
    global $conn;
    $stmt = $conn->prepare("SELECT nisn FROM siswa WHERE nisn = ?");
    $stmt->bind_param("s", $nisn);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'siswa') {
        redirect(SITE_URL . 'student/dashboard.php');
    } elseif ($_SESSION['user_type'] == 'guru_bk') {
        redirect(SITE_URL . 'teacher/dashboard.php');
    }
}

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize variables - menggunakan fungsi clean_input dari koneksi.php
    $nisn = isset($_POST['nisn']) ? clean_input($_POST['nisn']) : '';
    $nama_siswa = isset($_POST['nama_siswa']) ? clean_input($_POST['nama_siswa']) : '';
    $kelas = isset($_POST['kelas']) ? clean_input($_POST['kelas']) : '';
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? clean_input($_POST['jenis_kelamin']) : '';
    $alamat = isset($_POST['alamat']) ? clean_input($_POST['alamat']) : '';
    $no_hp_ortu = isset($_POST['no_hp_ortu']) ? clean_input($_POST['no_hp_ortu']) : '';
    
    // Validasi input
    $errors = [];
    
    if (empty($nisn)) {
        $errors[] = 'NISN harus diisi';
    } elseif (strlen($nisn) != 10) {
        $errors[] = 'NISN harus 10 digit';
    } elseif (!validate_nisn($nisn)) {
        $errors[] = 'Format NISN tidak valid (harus 10 digit angka)';
    } elseif (cek_nisn_exist($nisn)) {
        $errors[] = 'NISN sudah terdaftar';
    }
    
    if (empty($nama_siswa)) {
        $errors[] = 'Nama siswa harus diisi';
    }
    
    if (empty($kelas)) {
        $errors[] = 'Kelas harus dipilih';
    }

    if (empty($jenis_kelamin)) {
        $errors[] = 'Jenis kelamin harus dipilih';
    }
    
    if (empty($alamat)) {
        $errors[] = 'Alamat harus diisi';
    }
    
    if (empty($no_hp_ortu)) {
        $errors[] = 'Nomor HP orang tua harus diisi';
    } elseif (!preg_match('/^[0-9+\-\s]+$/', $no_hp_ortu)) {
        $errors[] = 'Format nomor HP tidak valid';
    }
    
    // Handle upload foto
    $foto_name = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['foto'], 'foto_siswa', ['jpg', 'jpeg', 'png']);
        
        if ($upload_result['success']) {
            $foto_name = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        // Hash password default (NISN)
        $password_hash = generate_password_hash($nisn);
        
        $stmt = $conn->prepare("INSERT INTO siswa (nisn, nama_siswa, kelas, jenis_kelamin, alamat, no_hp_ortu, password, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nisn, $nama_siswa, $kelas, $jenis_kelamin, $alamat, $no_hp_ortu, $password_hash, $foto_name);
        
        if ($stmt->execute()) {
            set_alert('success', 'Registrasi berhasil! Silakan login dengan NISN Anda');
            redirect(SITE_URL . 'auth/login/login_siswa.php');
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
    <title>Registrasi Siswa - Sistem Pelaporan Bullying</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-container">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h3>Registrasi Siswa</h3>
                        <p class="mb-0">Daftar untuk menggunakan Sistem Pelaporan Bullying</p>
                    </div>
                    
                    <div class="register-body">
                        <?php show_alert(); ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nisn" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>NISN *
                                    </label>
                                    <input type="text" class="form-control" id="nisn" name="nisn" 
                                           placeholder="10 digit NISN" required
                                           value="<?php echo isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : ''; ?>"
                                           maxlength="10" pattern="[0-9]{10}" title="Masukkan 10 digit NISN">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="kelas" class="form-label">
                                        <i class="fas fa-school me-2"></i>Kelas *
                                    </label>
                                    <select class="form-select" id="kelas" name="kelas" required>
                                        <option value="">Pilih Kelas</option>
                                        <option value="VII A" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VII A') ? 'selected' : ''; ?>>VII A</option>
                                        <option value="VII B" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VII B') ? 'selected' : ''; ?>>VII B</option>
                                        <option value="VII C" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VII C') ? 'selected' : ''; ?>>VII C</option>
                                        <option value="VIII A" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VIII A') ? 'selected' : ''; ?>>VIII A</option>
                                        <option value="VIII B" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VIII B') ? 'selected' : ''; ?>>VIII B</option>
                                        <option value="VIII C" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'VIII C') ? 'selected' : ''; ?>>VIII C</option>
                                        <option value="IX A" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'IX A') ? 'selected' : ''; ?>>IX A</option>
                                        <option value="IX B" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'IX B') ? 'selected' : ''; ?>>IX B</option>
                                        <option value="IX C" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == 'IX C') ? 'selected' : ''; ?>>IX C</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama_siswa" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nama Lengkap *
                                </label>
                                <input type="text" class="form-control" id="nama_siswa" name="nama_siswa" 
                                       placeholder="Nama lengkap siswa" required
                                       value="<?php echo isset($_POST['nama_siswa']) ? htmlspecialchars($_POST['nama_siswa']) : ''; ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jenis_kelamin" class="form-label">
                                        <i class="fas fa-venus-mars me-2"></i>Jenis Kelamin *
                                    </label>
                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="Perempuan" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="alamat" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Alamat *
                                </label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                          placeholder="Alamat lengkap" required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="no_hp_ortu" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Nomor HP Orang Tua *
                                </label>
                                <input type="tel" class="form-control" id="no_hp_ortu" name="no_hp_ortu" 
                                       placeholder="08xxxxxxxxxx" required
                                       value="<?php echo isset($_POST['no_hp_ortu']) ? htmlspecialchars($_POST['no_hp_ortu']) : ''; ?>">
                                <small class="text-muted">Nomor ini akan digunakan untuk notifikasi WhatsApp</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="foto" class="form-label">
                                    <i class="fas fa-camera me-2"></i>Foto Profil (Opsional)
                                </label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG. Maksimal 5MB</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Informasi:</strong> Password default Anda adalah NISN. Harap ganti password setelah login pertama kali.
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-2">Sudah punya akun?</p>
                            <a href="<?php echo SITE_URL; ?>auth/login/login_siswa.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                            </a>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <a href="../../index.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-home me-2"></i>Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>