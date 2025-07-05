<?php
require_once '../config/koneksi.php';
cek_login_guru();
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];

// Ambil semua tindak lanjut dengan informasi laporan dan guru
$query = "SELECT tl.*, lb.jenis_bullying, lb.status_laporan, 
          s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor,
          gb.nama_guru as nama_guru_pelaksana
          FROM tindak_lanjut tl
          JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
          JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
          LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
          ORDER BY tl.tanggal_tindakan DESC, tl.created_at DESC";
$result = $conn->query($query);

// Jika ada pencarian
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

if (!empty($search) || !empty($status_filter)) {
    if (!empty($search) && !empty($status_filter)) {
        $query = "SELECT tl.*, lb.jenis_bullying, lb.status_laporan, 
                 s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor,
                 gb.nama_guru as nama_guru_pelaksana
                 FROM tindak_lanjut tl
                 JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
                 WHERE (s.nama_siswa LIKE ? OR tl.jenis_tindakan LIKE ?) AND tl.status_tindakan = ?
                 ORDER BY tl.tanggal_tindakan DESC, tl.created_at DESC";
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param("sss", $search_param, $search_param, $status_filter);
    } elseif (!empty($search)) {
        $query = "SELECT tl.*, lb.jenis_bullying, lb.status_laporan, 
                 s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor,
                 gb.nama_guru as nama_guru_pelaksana
                 FROM tindak_lanjut tl
                 JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
                 WHERE s.nama_siswa LIKE ? OR tl.jenis_tindakan LIKE ?
                 ORDER BY tl.tanggal_tindakan DESC, tl.created_at DESC";
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param("ss", $search_param, $search_param);
    } else {
        $query = "SELECT tl.*, lb.jenis_bullying, lb.status_laporan, 
                 s.nama_siswa as nama_pelapor, s.kelas as kelas_pelapor,
                 gb.nama_guru as nama_guru_pelaksana
                 FROM tindak_lanjut tl
                 JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                 LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
                 WHERE tl.status_tindakan = ?
                 ORDER BY tl.tanggal_tindakan DESC, tl.created_at DESC";
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
    <title>Tindak Lanjut - Sistem Pelaporan Bullying</title>
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
            <?php show_alert(); ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-tasks me-2"></i>Tindak Lanjut Kasus Bullying</h4>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahTindakLanjutModal">
                    <i class="fas fa-plus me-2"></i>Tambah Tindak Lanjut
                </a>
            </div>
            
            <!-- Filter dan Pencarian -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" placeholder="Cari nama pelapor atau jenis tindakan..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="Direncanakan" <?= $status_filter == 'Direncanakan' ? 'selected' : '' ?>>Direncanakan</option>
                                <option value="Dilaksanakan" <?= $status_filter == 'Dilaksanakan' ? 'selected' : '' ?>>Dilaksanakan</option>
                                <option value="Selesai" <?= $status_filter == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
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
            
            <!-- Tabel Tindak Lanjut -->
            <div class="card card-custom">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    <th>Pelapor</th>
                                    <th>Jenis Bullying</th>
                                    <th>Jenis Tindakan</th>
                                    <th>Tanggal Tindakan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($tindak = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($tindak['nama_pelapor']) ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($tindak['kelas_pelapor']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-jenis jenis-<?= strtolower($tindak['jenis_bullying']) ?>">
                                                    <?= htmlspecialchars($tindak['jenis_bullying']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($tindak['jenis_tindakan']) ?></td>
                                            <td>
                                                <?= date('d M Y', strtotime($tindak['tanggal_tindakan'])) ?>
                                                <div class="small text-muted">
                                                    <?= !empty($tindak['nama_guru_pelaksana']) ? 'Oleh: ' . htmlspecialchars($tindak['nama_guru_pelaksana']) : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower($tindak['status_tindakan']) ?>">
                                                    <?= htmlspecialchars($tindak['status_tindakan']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="detail_tindak_lanjut.php?id=<?= $tindak['id_tindak_lanjut'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                            data-bs-target="#editTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                            data-bs-target="#hapusTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit Tindak Lanjut -->
                                        <div class="modal fade" id="editTindakLanjutModal<?= $tindak['id_tindak_lanjut'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="aksi_tindak_lanjut.php" method="POST">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id_tindak_lanjut" value="<?= $tindak['id_tindak_lanjut'] ?>">
                                                        <input type="hidden" name="id_laporan" value="<?= $tindak['id_laporan'] ?>">
                                                        
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
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-tasks fa-2x mb-3 text-muted"></i>
                                            <p class="text-muted">Tidak ada data tindak lanjut ditemukan</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Tindak Lanjut -->
    <div class="modal fade" id="tambahTindakLanjutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="aksi_tindak_lanjut.php" method="POST">
                    <input type="hidden" name="action" value="tambah">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Tindak Lanjut</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Laporan Bullying</label>
                            <select class="form-select" name="id_laporan" required>
                                <option value="">Pilih Laporan</option>
                                <?php
                                $query_laporan = "SELECT lb.id_laporan, lb.jenis_bullying, s.nama_siswa, s.kelas 
                                                 FROM laporan_bullying lb
                                                 JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                                                 WHERE lb.status_laporan != 'Selesai' AND lb.status_laporan != 'Ditolak'
                                                 ORDER BY lb.created_at DESC";
                                $result_laporan = $conn->query($query_laporan);
                                while ($laporan = $result_laporan->fetch_assoc()):
                                ?>
                                    <option value="<?= $laporan['id_laporan'] ?>">
                                        <?= htmlspecialchars($laporan['nama_siswa']) ?> (<?= htmlspecialchars($laporan['kelas']) ?>) - <?= htmlspecialchars($laporan['jenis_bullying']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
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