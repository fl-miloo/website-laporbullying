<?php
require_once '../config/koneksi.php';
cek_login_guru();

// Ambil data guru yang login
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];
// Ambil data laporan dengan informasi pelapor
$query = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor 
          FROM laporan_bullying lb
          JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
          ORDER BY lb.created_at DESC";
$result = $conn->query($query);

// Jika ada pencarian
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

if (!empty($search) || !empty($status_filter)) {
    if (!empty($search) && !empty($status_filter)) {
        $query = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor 
                 FROM laporan_bullying lb
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 WHERE (s.nama_siswa LIKE ? OR lb.nama_pelaku LIKE ?) AND lb.status_laporan = ?
                 ORDER BY lb.created_at DESC";
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param("sss", $search_param, $search_param, $status_filter);
    } elseif (!empty($search)) {
        $query = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor 
                 FROM laporan_bullying lb
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 WHERE s.nama_siswa LIKE ? OR lb.nama_pelaku LIKE ?
                 ORDER BY lb.created_at DESC";
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param("ss", $search_param, $search_param);
    } else {
        $query = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor 
                 FROM laporan_bullying lb
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 WHERE lb.status_laporan = ?
                 ORDER BY lb.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $status_filter);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - Sistem Pelaporan Bullying</title>
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
        
        .status-menunggu { background-color: var(--warning); color: white; }
        .status-diproses { background-color: var(--info); color: white; }
        .status-selesai { background-color: var(--success); color: white; }
        .status-ditolak { background-color: var(--danger); color: white; }
        
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
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-clipboard-list me-2"></i>Kelola Laporan Bullying</h4>
                </div>
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


            <!-- Filter dan Pencarian -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" placeholder="Cari nama pelapor atau pelaku..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="Menunggu" <?= $status_filter == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="Diproses" <?= $status_filter == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="Selesai" <?= $status_filter == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Ditolak" <?= $status_filter == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="container-fluid">
                <?php show_alert(); ?>
            
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahLaporanModal">
                    <i class="fas fa-plus me-2"></i>Tambah Laporan
                </a>
            </div>
            <!-- Tabel Laporan -->
            <div class="card card-custom">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    <th>Pelapor</th>
                                    <th>Pelaku</th>
                                    <th>Jenis</th>
                                    <th>Tanggal Kejadian</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($laporan = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($laporan['nama_pelapor']) ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($laporan['kelas_pelapor']) ?></div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($laporan['nama_pelaku']) ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($laporan['kelas_pelaku']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-jenis jenis-<?= strtolower($laporan['jenis_bullying']) ?>">
                                                    <?= htmlspecialchars($laporan['jenis_bullying']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('d M Y', strtotime($laporan['tanggal_kejadian'])) ?>
                                                <div class="small text-muted">
                                                    <?= !empty($laporan['waktu_kejadian']) ? date('H:i', strtotime($laporan['waktu_kejadian'])) : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower($laporan['status_laporan']) ?>">
                                                    <?= htmlspecialchars($laporan['status_laporan']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="detail_laporan.php?id=<?= $laporan['id_laporan'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                            data-bs-target="#editLaporanModal<?= $laporan['id_laporan'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                            data-bs-target="#hapusLaporanModal<?= $laporan['id_laporan'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit Laporan -->
                                        <div class="modal fade" id="editLaporanModal<?= $laporan['id_laporan'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form action="aksi_laporan.php" method="POST">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id_laporan" value="<?= $laporan['id_laporan'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Laporan Bullying</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Nama Pelaku</label>
                                                                    <input type="text" class="form-control" name="nama_pelaku" 
                                                                           value="<?= htmlspecialchars($laporan['nama_pelaku']) ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Kelas Pelaku</label>
                                                                    <input type="text" class="form-control" name="kelas_pelaku" 
                                                                           value="<?= htmlspecialchars($laporan['kelas_pelaku']) ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Jenis Bullying</label>
                                                                    <select class="form-select" name="jenis_bullying" required>
                                                                        <option value="Fisik" <?= $laporan['jenis_bullying'] == 'Fisik' ? 'selected' : '' ?>>Fisik</option>
                                                                        <option value="Verbal" <?= $laporan['jenis_bullying'] == 'Verbal' ? 'selected' : '' ?>>Verbal</option>
                                                                        <option value="Sosial" <?= $laporan['jenis_bullying'] == 'Sosial' ? 'selected' : '' ?>>Sosial</option>
                                                                        <option value="Cyber" <?= $laporan['jenis_bullying'] == 'Cyber' ? 'selected' : '' ?>>Cyber</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Tingkat Bullying</label>
                                                                    <select class="form-select" name="tingkat_bullying" required>
                                                                        <option value="Ringan" <?= $laporan['tingkat_bullying'] == 'Ringan' ? 'selected' : '' ?>>Ringan</option>
                                                                        <option value="Sedang" <?= $laporan['tingkat_bullying'] == 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                                                                        <option value="Berat" <?= $laporan['tingkat_bullying'] == 'Berat' ? 'selected' : '' ?>>Berat</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Deskripsi Kejadian</label>
                                                                <textarea class="form-control" name="deskripsi_kejadian" rows="3" required><?= htmlspecialchars($laporan['deskripsi_kejadian']) ?></textarea>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Lokasi Kejadian</label>
                                                                    <input type="text" class="form-control" name="lokasi_kejadian" 
                                                                           value="<?= htmlspecialchars($laporan['lokasi_kejadian']) ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Tanggal Kejadian</label>
                                                                    <input type="date" class="form-control" name="tanggal_kejadian" 
                                                                           value="<?= htmlspecialchars($laporan['tanggal_kejadian']) ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Waktu Kejadian</label>
                                                                    <input type="time" class="form-control" name="waktu_kejadian" 
                                                                           value="<?= htmlspecialchars($laporan['waktu_kejadian']) ?>">
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Status Laporan</label>
                                                                    <select class="form-select" name="status_laporan" required>
                                                                        <option value="Menunggu" <?= $laporan['status_laporan'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                                        <option value="Diproses" <?= $laporan['status_laporan'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                                                        <option value="Selesai" <?= $laporan['status_laporan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                                        <option value="Ditolak" <?= $laporan['status_laporan'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Catatan Guru</label>
                                                                <textarea class="form-control" name="catatan_guru" rows="2"><?= htmlspecialchars($laporan['catatan_guru']) ?></textarea>
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
                                        
                                        <!-- Modal Hapus Laporan -->
                                        <div class="modal fade" id="hapusLaporanModal<?= $laporan['id_laporan'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="aksi_laporan.php" method="POST">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id_laporan" value="<?= $laporan['id_laporan'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Konfirmasi Hapus Laporan</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus laporan dari <strong><?= htmlspecialchars($laporan['nama_pelapor']) ?></strong>?</p>
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
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                            <p class="text-muted">Tidak ada data laporan ditemukan</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>

    <!-- Modal Tambah Laporan -->
    <div class="modal fade" id="tambahLaporanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="aksi_laporan.php" method="POST">
                    <input type="hidden" name="action" value="tambah">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Laporan Bullying</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pelapor</label>
                                <select class="form-select" name="id_siswa_pelapor" required>
                                    <option value="">Pilih Pelapor</option>
                                    <?php
                                    $query_siswa = "SELECT id_siswa, nisn, nama_siswa, kelas FROM siswa WHERE status = 'Aktif' ORDER BY nama_siswa";
                                    $result_siswa = $conn->query($query_siswa);
                                    while ($siswa = $result_siswa->fetch_assoc()):
                                    ?>
                                        <option value="<?= $siswa['id_siswa'] ?>">
                                            <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= htmlspecialchars($siswa['kelas']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pelaku (Siswa)</label>
                                <select class="form-select" name="id_siswa_pelaku">
                                    <option value="">Pilih Pelaku (Jika Siswa)</option>
                                    <?php
                                    $query_siswa = "SELECT id_siswa, nisn, nama_siswa, kelas FROM siswa WHERE status = 'Aktif' ORDER BY nama_siswa";
                                    $result_siswa = $conn->query($query_siswa);
                                    while ($siswa = $result_siswa->fetch_assoc()):
                                    ?>
                                        <option value="<?= $siswa['id_siswa'] ?>">
                                            <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= htmlspecialchars($siswa['kelas']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Pelaku</label>
                                <input type="text" class="form-control" name="nama_pelaku" required>
                                <small class="text-muted">Diisi jika pelaku bukan siswa</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas Pelaku</label>
                                <input type="text" class="form-control" name="kelas_pelaku" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Bullying</label>
                                <select class="form-select" name="jenis_bullying" required>
                                    <option value="Fisik">Fisik</option>
                                    <option value="Verbal">Verbal</option>
                                    <option value="Sosial">Sosial</option>
                                    <option value="Cyber">Cyber</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tingkat Bullying</label>
                                <select class="form-select" name="tingkat_bullying" required>
                                    <option value="Ringan">Ringan</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Berat">Berat</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Kejadian</label>
                            <textarea class="form-control" name="deskripsi_kejadian" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi Kejadian</label>
                                <input type="text" class="form-control" name="lokasi_kejadian" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Kejadian</label>
                                <input type="date" class="form-control" name="tanggal_kejadian" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Waktu Kejadian</label>
                            <input type="time" class="form-control" name="waktu_kejadian">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Saksi (Jika Ada)</label>
                            <input type="text" class="form-control" name="saksi">
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