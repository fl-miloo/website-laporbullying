<?php
require_once '../config/koneksi.php';
cek_login_guru();
// Ambil data guru yang login
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('kelola_laporan.php');
}

$id_laporan = (int)$_GET['id'];

// Ambil data laporan
$query = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor, 
          COALESCE(gb.nama_guru, ?) as nama_guru_penangani
          FROM laporan_bullying lb
          JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
          LEFT JOIN guru_bk gb ON lb.id_guru_penangani = gb.id_guru
          WHERE lb.id_laporan = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $guru['nama_guru'], $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    set_alert('error', 'Laporan tidak ditemukan');
    redirect('kelola_laporan.php');
}

$laporan = $result->fetch_assoc();

// Ambil bukti laporan
$query_bukti = "SELECT * FROM bukti_laporan WHERE id_laporan = ?";
$stmt_bukti = $conn->prepare($query_bukti);
$stmt_bukti->bind_param("i", $id_laporan);
$stmt_bukti->execute();
$result_bukti = $stmt_bukti->get_result();

// Ambil tindak lanjut
$query_tindak_lanjut = "SELECT tl.*, gb.nama_guru 
                       FROM tindak_lanjut tl
                       LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
                       WHERE tl.id_laporan = ?
                       ORDER BY tl.tanggal_tindakan DESC";
$stmt_tindak_lanjut = $conn->prepare($query_tindak_lanjut);
$stmt_tindak_lanjut->bind_param("i", $id_laporan);
$stmt_tindak_lanjut->execute();
$result_tindak_lanjut = $stmt_tindak_lanjut->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Sistem Pelaporan Bullying</title>
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
        
        .status-menunggu { background-color: var(--warning); color: white; }
        .status-diproses { background-color: var(--info); color: white; }
        .status-selesai { background-color: var(--success); color: white; }
        .status-ditolak { background-color: var(--danger); color: white; }
        
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
        
        .bukti-item {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
        }
        
        .bukti-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            border: 4px solid white;
        }
        
        .timeline-date {
            font-size: 12px;
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
            <a href="kelola_laporan.php" class="active"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a>
            <a href="kelola_siswa.php"><i class="fas fa-users"></i> Data Siswa</a>
            <a href="tindak_lanjut.php"><i class="fas fa-tasks"></i> Tindak Lanjut</a>
            <a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a>
            <a href="cetak_laporan.php"><i class="fas fa-file-pdf"></i> Cetak Laporan</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <?php show_alert(); ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-clipboard-list me-2"></i>Detail Laporan Bullying</h4>
                <a href="kelola_laporan.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
            
            <!-- Informasi Laporan -->
            <div class="card card-custom mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Laporan</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Pelapor</label>
                                <p class="fw-bold"><?= htmlspecialchars($laporan['nama_pelapor']) ?> (<?= htmlspecialchars($laporan['kelas_pelapor']) ?>)</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Pelaku</label>
                                <p class="fw-bold"><?= htmlspecialchars($laporan['nama_pelaku']) ?> (<?= htmlspecialchars($laporan['kelas_pelaku']) ?>)</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Jenis Bullying</label>
                                <p>
                                    <span class="badge-jenis jenis-<?= strtolower($laporan['jenis_bullying']) ?>">
                                        <?= htmlspecialchars($laporan['jenis_bullying']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Status Laporan</label>
                                <p>
                                    <span class="status-badge status-<?= strtolower($laporan['status_laporan']) ?>">
                                        <?= htmlspecialchars($laporan['status_laporan']) ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Tanggal Kejadian</label>
                                <p class="fw-bold">
                                    <?= date('d F Y', strtotime($laporan['tanggal_kejadian'])) ?>
                                    <?= !empty($laporan['waktu_kejadian']) ? ' - ' . date('H:i', strtotime($laporan['waktu_kejadian'])) : '' ?>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Lokasi Kejadian</label>
                                <p class="fw-bold"><?= htmlspecialchars($laporan['lokasi_kejadian']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Deskripsi Kejadian</label>
                        <div class="border p-3 rounded bg-light">
                            <?= nl2br(htmlspecialchars($laporan['deskripsi_kejadian'])) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($laporan['saksi'])): ?>
                        <div class="mb-3">
                            <label class="form-label text-muted">Saksi</label>
                            <p class="fw-bold"><?= htmlspecialchars($laporan['saksi']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($laporan['catatan_guru'])): ?>
                        <div class="mb-3">
                            <label class="form-label text-muted">Catatan Guru</label>
                            <div class="border p-3 rounded bg-light">
                                <?= nl2br(htmlspecialchars($laporan['catatan_guru'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($laporan['nama_guru_penangani'])): ?>
                        <div class="mb-3">
                            <label class="form-label text-muted">Guru Penangani</label>
                            <p class="fw-bold"><?= htmlspecialchars($laporan['nama_guru_penangani']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bukti Laporan -->
            <div class="card card-custom mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bukti Laporan</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBuktiModal">
                        <i class="fas fa-plus me-2"></i>Tambah Bukti
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($result_bukti->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($bukti = $result_bukti->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="bukti-item">
                                        <?php if ($bukti['jenis_bukti'] == 'Foto'): ?>
                                            <img src="../assets/uploads/bukti/foto/<?= htmlspecialchars($bukti['nama_file']) ?>" 
                                                 class="bukti-image img-fluid mb-2" alt="Bukti Foto">
                                        <?php elseif ($bukti['jenis_bukti'] == 'Video'): ?>
                                            <video controls class="img-fluid mb-2">
                                                <source src="../assets/uploads/bukti/video/<?= htmlspecialchars($bukti['nama_file']) ?>" type="video/mp4">
                                                Browser Anda tidak mendukung tag video.
                                            </video>
                                        <?php elseif ($bukti['jenis_bukti'] == 'Audio'): ?>
                                            <audio controls class="w-100 mb-2">
                                                <source src="../assets/uploads/bukti/audio/<?= htmlspecialchars($bukti['nama_file']) ?>" type="audio/mpeg">
                                                Browser Anda tidak mendukung tag audio.
                                            </audio>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-file fa-3x text-muted mb-2"></i>
                                                <p><?= htmlspecialchars($bukti['nama_file']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="mb-1"><strong><?= htmlspecialchars($bukti['jenis_bukti']) ?></strong></p>
                                        <?php if (!empty($bukti['keterangan'])): ?>
                                            <p class="small"><?= htmlspecialchars($bukti['keterangan']) ?></p>
                                        <?php endif; ?>
                                        
                                        
                                        <div class="d-flex justify-content-end gap-2 mt-2">
                                           <a href="../assets/uploads/bukti/<?= strtolower($bukti['jenis_bukti']) ?>/<?= htmlspecialchars($bukti['nama_file']) ?>" 
                                                class="btn btn-sm btn-info" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                    data-bs-target="#hapusBuktiModal<?= $bukti['id_bukti'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Hapus Bukti -->
                                    <div class="modal fade" id="hapusBuktiModal<?= $bukti['id_bukti'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="aksi_bukti.php" method="POST">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="id_bukti" value="<?= $bukti['id_bukti'] ?>">
                                                    <input type="hidden" name="id_laporan" value="<?= $id_laporan ?>">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Konfirmasi Hapus Bukti</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus bukti ini?</p>
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
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-image fa-2x mb-2"></i>
                            <p>Tidak ada bukti laporan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tindak Lanjut -->
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tindak Lanjut</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahTindakLanjutModal">
                        <i class="fas fa-plus me-2"></i>Tambah Tindak Lanjut
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($result_tindak_lanjut->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($tindak = $result_tindak_lanjut->fetch_assoc()): ?>
                                <div class="timeline-item mb-4">
                                    <div class="card card-custom">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($tindak['jenis_tindakan']) ?></h6>
                                                <span class="badge bg-<?= $tindak['status_tindakan'] == 'Selesai' ? 'success' : ($tindak['status_tindakan'] == 'Dilaksanakan' ? 'info' : 'warning') ?>">
                                                    <?= htmlspecialchars($tindak['status_tindakan']) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="timeline-date mb-2">
                                                <i class="far fa-calendar-alt me-2"></i>
                                                <?= date('d F Y', strtotime($tindak['tanggal_tindakan'])) ?>
                                                <?= !empty($tindak['nama_guru']) ? ' - Oleh: ' . htmlspecialchars($tindak['nama_guru']) : '' ?>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <?= nl2br(htmlspecialchars($tindak['deskripsi_tindakan'])) ?>
                                            </div>
                                            
                                            <?php if (!empty($tindak['hasil_tindakan'])): ?>
                                                <div class="border-start border-3 ps-3 py-1 bg-light">
                                                    <p class="mb-0"><strong>Hasil:</strong> <?= htmlspecialchars($tindak['hasil_tindakan']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-end gap-2 mt-3">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                        data-bs-target="#editTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                        data-bs-target="#hapusTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Edit Tindak Lanjut -->
                                <div class="modal fade" id="editTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="aksi_tindak_lanjut.php" method="POST">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id_tindak_lanjut" value="<?= $tindak['id_tindak_lanjut'] ?>">
                                                <input type="hidden" name="id_laporan" value="<?= $id_laporan ?>">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Tindak Lanjut</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Jenis Tindakan</label>
                                                        <input type="text" class="form-control" name="jenis_tindakan" 
                                                               value="<?= htmlspecialchars($tindak['jenis_tindakan']) ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Deskripsi Tindakan</label>
                                                        <textarea class="form-control" name="deskripsi_tindakan" rows="3" required><?= htmlspecialchars($tindak['deskripsi_tindakan']) ?></textarea>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Tanggal Tindakan</label>
                                                            <input type="date" class="form-control" name="tanggal_tindakan" 
                                                                   value="<?= htmlspecialchars($tindak['tanggal_tindakan']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Status Tindakan</label>
                                                            <select class="form-select" name="status_tindakan" required>
                                                                <option value="Direncanakan" <?= $tindak['status_tindakan'] == 'Direncanakan' ? 'selected' : '' ?>>Direncanakan</option>
                                                                <option value="Dilaksanakan" <?= $tindak['status_tindakan'] == 'Dilaksanakan' ? 'selected' : '' ?>>Dilaksanakan</option>
                                                                <option value="Selesai" <?= $tindak['status_tindakan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Hasil Tindakan</label>
                                                        <textarea class="form-control" name="hasil_tindakan" rows="2"><?= htmlspecialchars($tindak['hasil_tindakan']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Hapus Tindak Lanjut -->
                                <div class="modal fade" id="hapusTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="aksi_tindak_lanjut.php" method="POST">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id_tindak_lanjut" value="<?= $tindak['id_tindak_lanjut'] ?>">
                                                <input type="hidden" name="id_laporan" value="<?= $id_laporan ?>">
                                                
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
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-tasks fa-2x mb-2"></i>
                            <p>Belum ada tindak lanjut</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Bukti -->
    <div class="modal fade" id="tambahBuktiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="aksi_bukti.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tambah">
                    <input type="hidden" name="id_laporan" value="<?= $id_laporan ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Bukti Laporan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Jenis Bukti</label>
                            <select class="form-select" name="jenis_bukti" required>
                                <option value="Foto">Foto</option>
                                <option value="Video">Video</option>
                                <option value="Audio">Audio</option>
                                <option value="Dokumen">Dokumen</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">File Bukti</label>
                            <input type="file" class="form-control" name="file_bukti" required>
                            <small class="text-muted">Ukuran maksimal 5MB</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" name="keterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Tindak Lanjut -->
    <div class="modal fade" id="tambahTindakLanjutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="aksi_tindak_lanjut.php" method="POST">
                    <input type="hidden" name="action" value="tambah">
                    <input type="hidden" name="id_laporan" value="<?= $id_laporan ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Tindak Lanjut</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Jenis Tindakan</label>
                            <input type="text" class="form-control" name="jenis_tindakan" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Tindakan</label>
                            <textarea class="form-control" name="deskripsi_tindakan" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Tindakan</label>
                                <input type="date" class="form-control" name="tanggal_tindakan" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status Tindakan</label>
                                <select class="form-select" name="status_tindakan" required>
                                    <option value="Direncanakan">Direncanakan</option>
                                    <option value="Dilaksanakan">Dilaksanakan</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hasil Tindakan (Opsional)</label>
                            <textarea class="form-control" name="hasil_tindakan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>