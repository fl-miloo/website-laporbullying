<?php
require_once '../config/koneksi.php';
cek_login_guru();

$id_guru = $_SESSION['user_id'];
$errors = [];
$success = false;

// Ambil data guru
$query = "SELECT * FROM guru_bk WHERE id_guru = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();
$guru = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nama)) {
        $errors['nama'] = "Nama tidak boleh kosong";
    }
    
    if (empty($username)) {
        $errors['username'] = "Username tidak boleh kosong";
    } else {
        // Cek username sudah digunakan atau tidak (kecuali oleh user ini)
        $check = $conn->prepare("SELECT id_guru FROM guru_bk WHERE username = ? AND id_guru != ?");
        $check->bind_param("si", $username, $id_guru);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors['username'] = "Username sudah digunakan";
        }
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors['password'] = "Password minimal 6 karakter";
        } elseif ($password != $confirm_password) {
            $errors['confirm_password'] = "Konfirmasi password tidak sesuai";
        }
    }
    
    // Upload foto jika ada
    $foto = $guru['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $upload = upload_file($_FILES['foto'], 'foto_gurubk', ['jpg', 'jpeg', 'png']);
        
        if ($upload['success']) {
            // Hapus foto lama jika ada
            if (!empty($foto)) {
                $old_file = UPLOAD_PATH . 'foto_gurubk/' . $foto;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $foto = $upload['filename'];
        } else {
            $errors['foto'] = $upload['message'];
        }
    }

    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        if (!empty($password)) {
            $password_hash = generate_password_hash($password);
            $query = "UPDATE guru_bk SET nama_guru = ?, username = ?, password = ?, foto = ? WHERE id_guru = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $nama, $username, $password_hash, $foto, $id_guru);
        } else {
            $query = "UPDATE guru_bk SET nama_guru = ?, username = ?, foto = ? WHERE id_guru = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nama, $username, $foto, $id_guru);
        }
        
        if ($stmt->execute()) {
            $success = true;
            set_alert('success', 'Profil berhasil diperbarui');
            
            // Update data session jika username berubah
            if ($_SESSION['username'] != $username) {
                $_SESSION['username'] = $username;
            }
            
            // Refresh data guru
            $query = "SELECT * FROM guru_bk WHERE id_guru = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_guru);
            $stmt->execute();
            $result = $stmt->get_result();
            $guru = $result->fetch_assoc();
        } else {
            set_alert('error', 'Gagal memperbarui profil: ' . $conn->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Guru BK - Sistem Pelaporan Bullying</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #9b59b6;
            --light: #ecf0f1;
            --dark: #34495e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--secondary), var(--dark));
            color: white;
            position: fixed;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .profile-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            background-color: #eee;
            overflow: hidden;
            margin: -75px auto 20px;
            position: relative;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            border: 2px solid white;
        }
        
        .profile-avatar-edit input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-role {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header text-center">
            <h4><i class="fas fa-shield-alt"></i> Guru BK</h4>
            <p class="mb-0 small"><?= date('d F Y') ?></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="kelola_laporan.php"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a>
            <a href="kelola_siswa.php"><i class="fas fa-users"></i> Data Siswa</a>
            <a href="tindak_lanjut.php"><i class="fas fa-tasks"></i> Tindak Lanjut</a>
            <a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a>
            <a href="cetak_laporan.php"><i class="fas fa-file-pdf"></i> Cetak Laporan</a>
            <a href="profile.php" class="active"><i class="fas fa-user"></i> Profil</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
            <div class="container-fluid">
                <button class="btn btn-link d-lg-none" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center ms-auto">
                    <div class="me-3 text-end">
                        <div class="fw-bold"><?= htmlspecialchars($guru['nama_guru']) ?></div>
                        <div class="small text-muted">Guru BK</div>
                    </div>
                    <div class="user-avatar">
                        <?php if (!empty($guru['foto'])): ?>
                            <img src="../assets/uploads/foto_guru/<?= htmlspecialchars($guru['foto']) ?>" alt="Foto Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($guru['nama_guru'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <?php show_alert(); ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card profile-card">
                        <div class="card-header profile-header">
                            <h4 class="mb-0"><i class="fas fa-user-cog me-2"></i>Profil Saya</h4>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="profile-avatar">
                                    <?php if (!empty($guru['foto'])): ?>
                                        <img src="../assets/uploads/foto_guru/<?= htmlspecialchars($guru['foto']) ?>" alt="Foto Profil">
                                    <?php else: ?>
                                        <div style="font-size: 60px; line-height: 140px;">
                                            <?= strtoupper(substr($guru['nama_guru'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="profile-avatar-edit">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" id="fotoInput" name="foto" form="profileForm" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            
                            <form id="profileForm" method="POST" enctype="multipart/form-data" class="mt-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nama" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>" 
                                               id="nama" name="nama" value="<?= htmlspecialchars($guru['nama_guru']) ?>">
                                        <?php if (isset($errors['nama'])): ?>
                                            <div class="invalid-feedback"><?= $errors['nama'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                               id="username" name="username" value="<?= htmlspecialchars($guru['username']) ?>">
                                        <?php if (isset($errors['username'])): ?>
                                            <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="nip" class="form-label">NIP</label>
                                        <input type="text" class="form-control" id="nip" value="<?= htmlspecialchars($guru['nip']) ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <input type="text" class="form-control" id="status" value="<?= htmlspecialchars($guru['status']) ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password Baru (Opsional)</label>
                                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                               id="password" name="password">
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                               id="confirm_password" name="confirm_password">
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Preview foto sebelum upload
        document.getElementById('fotoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.querySelector('.profile-avatar img') || 
                                document.createElement('img');
                    img.src = event.target.result;
                    img.alt = "Foto Profil";
                    
                    const avatarDiv = document.querySelector('.profile-avatar');
                    const existingContent = avatarDiv.querySelector('div');
                    if (existingContent) {
                        avatarDiv.removeChild(existingContent);
                    }
                    
                    if (!avatarDiv.querySelector('img')) {
                        avatarDiv.insertBefore(img, avatarDiv.firstChild);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>