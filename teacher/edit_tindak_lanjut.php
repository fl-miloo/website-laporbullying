<?php
require_once '../config/koneksi.php';
cek_login_guru();
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];

// Ambil ID dari parameter URL
$id_tindak_lanjut = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID
if ($id_tindak_lanjut <= 0) {
    header('Location: tindak_lanjut.php');
    exit();
}

// Query untuk mendapatkan detail tindak lanjut
$query = "SELECT tl.*, lb.*, 
          s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor, s.nisn as nisn_pelapor,
          gb.nama_guru as nama_guru_pelaksana,
          lb.nama_pelaku, lb.kelas_pelaku
          FROM tindak_lanjut tl
          JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
          JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
          LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
          WHERE tl.id_tindak_lanjut = ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $id_tindak_lanjut);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: tindak_lanjut.php');
    exit();
}

$tindak = $result->fetch_assoc();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_tindakan = clean_input($_POST['jenis_tindakan']);
    $deskripsi_tindakan = clean_input($_POST['deskripsi_tindakan']);
    $tanggal_tindakan = clean_input($_POST['tanggal_tindakan']);
    $status_tindakan = clean_input($_POST['status_tindakan']);
    $hasil_tindakan = clean_input($_POST['hasil_tindakan']);
    
    $update_query = "UPDATE tindak_lanjut SET 
                    jenis_tindakan = ?,
                    deskripsi_tindakan = ?,
                    tanggal_tindakan = ?,
                    status_tindakan = ?,
                    hasil_tindakan = ?,
                    id_guru_pelaksana = ?
                    WHERE id_tindak_lanjut = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if ($update_stmt === false) {
        die("Error preparing update statement: " . $conn->error);
    }
    
    $update_stmt->bind_param("sssssii", 
        $jenis_tindakan,
        $deskripsi_tindakan,
        $tanggal_tindakan,
        $status_tindakan,
        $hasil_tindakan,
        $id_guru,
        $id_tindak_lanjut
    );
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Tindak lanjut berhasil diperbarui";
        header("Location: detail_tindak_lanjut.php?id=" . $id_tindak_lanjut);
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui tindak lanjut: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tindak Lanjut - Sistem Pelaporan Bullying</title>
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
        
        .card-custom {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .table-custom th {
            background-color: var(--light);
            border-bottom: 2px solid #dee2e6;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-direncanakan { background-color: var(--warning); color: white; }
        .status-dilaksanakan { background-color: var(--info); color: white; }
        .status-selesai { background-color: var(--success); color: white; }
        
        .badge-jenis {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .jenis-fisik { background-color: #e74c3c; color: white; }
        .jenis-verbal { background-color: #3498db; color: white; }
        .jenis-sosial { background-color: #9b59b6; color: white; }
        .jenis-cyber { background-color: #2ecc71; color: white; }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
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
   <div class="sidebar">
        <div class="sidebar-header text-center">
            <h4><i class="fas fa-shield-alt"></i> Guru BK</h4>
            <p class="mb-0 small"><?= date('d F Y') ?></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="kelola_laporan.php"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a>
            <a href="kelola_siswa.php"><i class="fas fa-users"></i> Data Siswa</a>
            <a href="tindak_lanjut.php" class="active"><i class="fas fa-tasks"></i> Tindak Lanjut</a>
            <a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a>
            <a href="cetak_laporan.php"><i class="fas fa-file-pdf"></i> Cetak Laporan</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
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
                            <img src="../assets/uploads/foto_guru/<?= htmlspecialchars($guru['foto']) ?>" alt="Foto Profil" class="foto-siswa">
                        <?php else: ?>
                            <?= strtoupper(substr($guru['nama_guru'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </nav>
        <!-- Content -->
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-edit me-2"></i>Edit Tindak Lanjut</h4>
                <a href="detail_tindak_lanjut.php?id=<?= $id_tindak_lanjut ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
            
            <?php show_alert(); ?>
            
            <div class="form-container">
                <!-- Informasi Laporan -->
                <div class="form-section">
                    <h5 class="mb-4">Informasi Laporan</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pelapor</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($tindak['nama_pelapor']) ?> (<?= htmlspecialchars($tindak['kelas_pelapor']) ?>)" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pelaku</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($tindak['nama_pelaku']) ?> (<?= htmlspecialchars($tindak['kelas_pelaku']) ?>)" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jenis Bullying</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($tindak['jenis_bullying']) ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tanggal Kejadian</label>
                                <input type="text" class="form-control" value="<?= date('d F Y', strtotime($tindak['tanggal_kejadian'])) ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Edit Tindak Lanjut -->
                <div class="form-section">
                    <h5 class="mb-4">Edit Tindak Lanjut</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="jenis_tindakan" class="form-label">Jenis Tindakan</label>
                            <input type="text" class="form-control" id="jenis_tindakan" name="jenis_tindakan" 
                                   value="<?= htmlspecialchars($tindak['jenis_tindakan']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi_tindakan" class="form-label">Deskripsi Tindakan</label>
                            <textarea class="form-control" id="deskripsi_tindakan" name="deskripsi_tindakan" 
                                      rows="3" required><?= htmlspecialchars($tindak['deskripsi_tindakan']) ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_tindakan" class="form-label">Tanggal Tindakan</label>
                                <input type="date" class="form-control" id="tanggal_tindakan" name="tanggal_tindakan" 
                                       value="<?= htmlspecialchars($tindak['tanggal_tindakan']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status_tindakan" class="form-label">Status Tindakan</label>
                                <select class="form-select" id="status_tindakan" name="status_tindakan" required>
                                    <option value="Direncanakan" <?= $tindak['status_tindakan'] == 'Direncanakan' ? 'selected' : '' ?>>Direncanakan</option>
                                    <option value="Dilaksanakan" <?= $tindak['status_tindakan'] == 'Dilaksanakan' ? 'selected' : '' ?>>Dilaksanakan</option>
                                    <option value="Selesai" <?= $tindak['status_tindakan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hasil_tindakan" class="form-label">Hasil Tindakan</label>
                            <textarea class="form-control" id="hasil_tindakan" name="hasil_tindakan" 
                                      rows="3"><?= htmlspecialchars($tindak['hasil_tindakan']) ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle (sama seperti sebelumnya)
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>