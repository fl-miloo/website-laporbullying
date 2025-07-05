<?php
require_once '../config/koneksi.php';
cek_login_siswa();

$siswa = get_logged_in_user();
$id_siswa = $siswa['id_siswa'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_siswa = clean_input($_POST['nama_siswa']);
    $kelas = clean_input($_POST['kelas']);
    $jenis_kelamin = clean_input($_POST['jenis_kelamin']);
    $alamat = clean_input($_POST['alamat']);
    $no_hp_ortu = clean_input($_POST['no_hp_ortu']);
    
    // Validasi input
    if (empty($nama_siswa)) {
        $errors[] = "Nama siswa harus diisi";
    }
    
    if (empty($kelas)) {
        $errors[] = "Kelas harus diisi";
    }
    
    if (empty($no_hp_ortu)) {
        $errors[] = "Nomor HP orang tua harus diisi";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $no_hp_ortu)) {
        $errors[] = "Format nomor HP tidak valid";
    }
    
    // Handle upload foto jika ada
    $foto = $siswa['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $upload = upload_file($_FILES['foto'], 'foto_siswa');
        
        if ($upload['success']) {
            $foto = $upload['filename'];
            
            // Hapus foto lama jika ada
            if ($siswa['foto'] && file_exists(UPLOAD_PATH . 'foto_siswa/' . $siswa['foto'])) {
                unlink(UPLOAD_PATH . 'foto_siswa/' . $siswa['foto']);
            }
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // Update data jika tidak ada error
    if (empty($errors)) {
        $query = "UPDATE siswa SET 
                  nama_siswa = ?, 
                  kelas = ?, 
                  jenis_kelamin = ?, 
                  alamat = ?, 
                  no_hp_ortu = ?, 
                  foto = ? 
                  WHERE id_siswa = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $nama_siswa, $kelas, $jenis_kelamin, $alamat, $no_hp_ortu, $foto, $id_siswa);
        
        if ($stmt->execute()) {
            $success = true;
            set_alert('success', 'Profil berhasil diperbarui');
            
            // Update session data
            $_SESSION['user_name'] = $nama_siswa;
            if ($foto) {
                $_SESSION['user_foto'] = $foto;
            }
            
            // Redirect untuk memastikan data terupdate
            redirect('profile.php');
        } else {
            $errors[] = "Gagal memperbarui profil: " . $stmt->error;
        }
    }
    
    if (!empty($errors)) {
        set_alert('error', implode('<br>', $errors));
    }
}

// Ambil data terbaru
$query = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Siswa - <?php echo htmlspecialchars($siswa['nama_siswa']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .btn-upload input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3 text-center border-bottom border-light">
            <img src="<?php echo $siswa['foto'] ? '../assets/uploads/foto_siswa/' . $siswa['foto'] : '../assets/img/default/avatar.png'; ?>" 
                 alt="Profile" class="profile-img mb-2">
            <h5 class="text-white mb-0"><?php echo htmlspecialchars($siswa['nama_siswa']); ?></h5>
            <small class="text-light">NISN: <?php echo htmlspecialchars($siswa['nisn']); ?></small><br>
            <small class="text-light">Kelas: <?php echo htmlspecialchars($siswa['kelas']); ?></small>
        </div>

        <ul class="nav flex-column p-3">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buat_laporan.php">
                    <i class="fas fa-exclamation-triangle me-2"></i>Buat Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="riwayat_laporan.php">
                    <i class="fas fa-history me-2"></i>Riwayat Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="profile.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="profile-card">
                    <h2 class="mb-4"><i class="fas fa-user me-2"></i>Profil Siswa</h2>
                    
                    <?php show_alert(); ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <img src="<?php echo $siswa['foto'] ? '../assets/uploads/foto_siswa/' . $siswa['foto'] : '../assets/img/default/avatar.png'; ?>" 
                                     alt="Profile" class="profile-img mb-3" id="previewFoto">
                                
                                <div class="btn-upload btn btn-primary mb-2">
                                    <i class="fas fa-camera me-2"></i>Ubah Foto
                                    <input type="file" name="foto" id="foto" accept="image/*">
                                </div>
                                <small class="text-muted d-block">Format: JPG/PNG, maks 2MB</small>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">NISN</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($siswa['nisn']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_siswa" value="<?php echo htmlspecialchars($siswa['nama_siswa']); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="kelas" value="<?php echo htmlspecialchars($siswa['kelas']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select class="form-select" name="jenis_kelamin">
                                            <option value="Laki-laki" <?php echo $siswa['jenis_kelamin'] == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="Perempuan" <?php echo $siswa['jenis_kelamin'] == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control" name="alamat" rows="3"><?php echo htmlspecialchars($siswa['alamat']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nomor HP Orang Tua <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="no_hp_ortu" value="<?php echo htmlspecialchars($siswa['no_hp_ortu']); ?>" required>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview foto sebelum upload
        document.getElementById('foto').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('previewFoto').src = e.target.result;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Auto close alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>