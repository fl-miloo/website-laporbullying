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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tindak Lanjut - Sistem Pelaporan Bullying</title>
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
                <h4><i class="fas fa-tasks me-2"></i>Detail Tindak Lanjut</h4>
                <a href="tindak_lanjut.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
            
            <!-- Informasi Laporan -->
            <div class="card detail-card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Laporan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-label">Pelapor</div>
                            <div class="info-value">
                                <?= htmlspecialchars($tindak['nama_pelapor']) ?> 
                                (<?= htmlspecialchars($tindak['kelas_pelapor']) ?> | NISN: <?= htmlspecialchars($tindak['nisn_pelapor']) ?>)
                            </div>
                            
                            <div class="info-label">Pelaku</div>
                            <div class="info-value">
                                <?= htmlspecialchars($tindak['nama_pelaku']) ?> 
                                (<?= htmlspecialchars($tindak['kelas_pelaku']) ?>)
                            </div>
                            
                            <div class="info-label">Jenis Bullying</div>
                            <div class="info-value">
                                <span class="badge-jenis jenis-<?= strtolower($tindak['jenis_bullying']) ?>">
                                    <?= htmlspecialchars($tindak['jenis_bullying']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Tanggal Kejadian</div>
                            <div class="info-value">
                                <?= date('d F Y', strtotime($tindak['tanggal_kejadian'])) ?>
                                <?= $tindak['waktu_kejadian'] ? 'pukul ' . date('H:i', strtotime($tindak['waktu_kejadian'])) : '' ?>
                            </div>
                            
                            <div class="info-label">Lokasi Kejadian</div>
                            <div class="info-value"><?= htmlspecialchars($tindak['lokasi_kejadian']) ?></div>
                            
                            <div class="info-label">Status Laporan</div>
                            <div class="info-value">
                                <span class="badge bg-<?= 
                                    $tindak['status_laporan'] == 'Menunggu' ? 'warning' : 
                                    ($tindak['status_laporan'] == 'Diproses' ? 'info' : 
                                    ($tindak['status_laporan'] == 'Selesai' ? 'success' : 'danger')) 
                                ?>">
                                    <?= htmlspecialchars($tindak['status_laporan']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-label">Deskripsi Kejadian</div>
                    <div class="info-value p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($tindak['deskripsi_kejadian'])) ?>
                    </div>
                </div>
            </div>
            
            <!-- Detail Tindak Lanjut -->
            <div class="card detail-card">
                <div class="card-header">
                    <h5 class="mb-0">Detail Tindak Lanjut</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-label">Jenis Tindakan</div>
                            <div class="info-value"><?= htmlspecialchars($tindak['jenis_tindakan']) ?></div>
                            
                            <div class="info-label">Tanggal Tindakan</div>
                            <div class="info-value"><?= date('d F Y', strtotime($tindak['tanggal_tindakan'])) ?></div>
                            
                            <div class="info-label">Guru Penanggung Jawab</div>
                            <div class="info-value">
                                <?= !empty($tindak['nama_guru_pelaksana']) ? htmlspecialchars($tindak['nama_guru_pelaksana']) : 'Belum ditentukan' ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Status Tindakan</div>
                            <div class="info-value">
                                <span class="status-badge status-<?= strtolower($tindak['status_tindakan']) ?>">
                                    <?= htmlspecialchars($tindak['status_tindakan']) ?>
                                </span>
                            </div>
                            
                            <div class="info-label">Dibuat Pada</div>
                            <div class="info-value"><?= date('d F Y H:i', strtotime($tindak['created_at'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-label">Deskripsi Tindakan</div>
                    <div class="info-value p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($tindak['deskripsi_tindakan'])) ?>
                    </div>
                    
                    <?php if (!empty($tindak['hasil_tindakan'])): ?>
                        <div class="info-label">Hasil Tindakan</div>
                        <div class="info-value p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($tindak['hasil_tindakan'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="edit_tindak_lanjut.php?id=<?= $tindak['id_tindak_lanjut'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Edit
                </a>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#hapusModal">
                    <i class="fas fa-trash me-2"></i>Hapus
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="hapusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="aksi_tindak_lanjut.php" method="POST">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id_tindak_lanjut" value="<?= $tindak['id_tindak_lanjut'] ?>">
                    <input type="hidden" name="id_laporan" value="<?= $tindak['id_laporan'] ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Hapus Tindak Lanjut</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus tindak lanjut ini?</p>
                        <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
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