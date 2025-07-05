<?php
require_once '../config/koneksi.php';
cek_login_guru();

// Ambil data guru yang login
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];

// Proses pencarian dan filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$kelas_filter = isset($_GET['kelas']) ? clean_input($_GET['kelas']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Query dasar
$query = "SELECT * FROM siswa WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (nama_siswa LIKE ? OR nisn LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($kelas_filter)) {
    $query .= " AND kelas = ?";
    $params[] = $kelas_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY nama_siswa ASC";

// Eksekusi query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Ambil daftar kelas unik untuk filter
$kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - Sistem Pelaporan Bullying</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(145deg, #2c3e50 0%, #34495e 100%);
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #ecf0f1;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.8;
            color: #bdc3c7;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 2px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, rgba(52, 152, 219, 0.2) 0%, rgba(41, 128, 185, 0.1) 100%);
            border-left-color: #3498db;
            transform: translateX(5px);
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: white;
            padding: 20px 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-title h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .user-role {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .content-container {
            padding: 35px;
        }
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .btn-tambah {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        
        .btn-tambah:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
            color: white;
        }
        
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        
        .status-aktif { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }
        .status-tidak-aktif { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-edit { 
            background: #f39c12; 
            color: white; 
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete { 
            background: #e74c3c; 
            color: white;
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .foto-siswa {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .poin-badge {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
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
    <div class="sidebar">
        <div class="sidebar-header text-center">
            <h4><i class="fas fa-shield-alt"></i> Guru BK</h4>
            <p class="mb-0 small"><?= date('d F Y') ?></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="kelola_laporan.php"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a>
            <a href="kelola_siswa.php" class="active"><i class="fas fa-users"></i> Data Siswa</a>
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
        <nav class="navbar navbar-expand-lg navbar-light bg-white top-navbar mb-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-users me-2"></i>Data Siswa</h4>
            </div>
                <button class="btn btn-link d-lg-none" id="sidebarToggle">
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
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Cari nama atau NISN..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="kelas">
                                <option value="">Semua Kelas</option>
                                <?php while ($kelas = $kelas_result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($kelas['kelas']) ?>" 
                                        <?= $kelas_filter == $kelas['kelas'] ? 'selected' : '' ?>>
                                        Kelas <?= htmlspecialchars($kelas['kelas']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="Aktif" <?= $status_filter == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="Tidak Aktif" <?= $status_filter == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
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

                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahSiswaModal">
                        <i class="fas fa-plus me-2"></i>Tambah Siswa
                    </button>
                </div>
            
            
            <!-- Tabel Siswa -->
            <div class="card card-custom">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    <th>Foto</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Kelas</th>
                                    <th>Jenis Kelamin</th>
                                    <th>No. HP Ortu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($siswa = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <?php if (!empty($siswa['foto'])): ?>
                                                    <img src="../assets/uploads/foto_siswa/<?= htmlspecialchars($siswa['foto']) ?>" 
                                                         alt="Foto <?= htmlspecialchars($siswa['nama_siswa']) ?>" 
                                                         class="foto-siswa">
                                                <?php else: ?>
                                                    <div class="foto-siswa d-flex align-items-center justify-content-center bg-secondary text-white">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                            <td><?= htmlspecialchars($siswa['nisn']) ?></td>
                                            <td><?= htmlspecialchars($siswa['kelas']) ?></td>
                                            <td><?= htmlspecialchars($siswa['jenis_kelamin']) ?></td>
                                            <td><?= htmlspecialchars($siswa['no_hp_ortu']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $siswa['status'])) ?>">
                                                    <?= htmlspecialchars($siswa['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-info btn-action" data-bs-toggle="modal" 
                                                            data-bs-target="#editSiswaModal<?= $siswa['id_siswa'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" 
                                                            data-bs-target="#hapusSiswaModal<?= $siswa['id_siswa'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit Siswa -->
                                        <div class="modal fade" id="editSiswaModal<?= $siswa['id_siswa'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="aksi_siswa.php" method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Data Siswa</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3 text-center">
                                                                <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px;">
                                                                    <?php if (!empty($siswa['foto'])): ?>
                                                                        <img src="../assets/uploads/foto_siswa/<?= htmlspecialchars($siswa['foto']) ?>" 
                                                                             alt="Foto <?= htmlspecialchars($siswa['nama_siswa']) ?>"
                                                                             style="width: 100%; height: 100%; object-fit: cover;">
                                                                    <?php else: ?>
                                                                        <div style="font-size: 40px; line-height: 100px;">
                                                                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <input type="file" class="form-control" name="foto" accept="image/*">
                                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Nama Siswa</label>
                                                                <input type="text" class="form-control" name="nama_siswa" 
                                                                       value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" required>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">NISN</label>
                                                                    <input type="text" class="form-control" name="nisn" 
                                                                           value="<?= htmlspecialchars($siswa['nisn']) ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Kelas</label>
                                                                    <input type="text" class="form-control" name="kelas" 
                                                                           value="<?= htmlspecialchars($siswa['kelas']) ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Jenis Kelamin</label>
                                                                    <select class="form-select" name="jenis_kelamin" required>
                                                                        <option value="Laki-laki" <?= $siswa['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                                                        <option value="Perempuan" <?= $siswa['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select class="form-select" name="status" required>
                                                                        <option value="Aktif" <?= $siswa['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                                                        <option value="Tidak Aktif" <?= $siswa['status'] == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Alamat</label>
                                                                <textarea class="form-control" name="alamat" rows="2"><?= htmlspecialchars($siswa['alamat']) ?></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">No. HP Orang Tua</label>
                                                                <input type="text" class="form-control" name="no_hp_ortu" 
                                                                       value="<?= htmlspecialchars($siswa['no_hp_ortu']) ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Password Baru</label>
                                                                <input type="password" class="form-control" name="password">
                                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
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
                                        
                                        <!-- Modal Hapus Siswa -->
                                        <div class="modal fade" id="hapusSiswaModal<?= $siswa['id_siswa'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="aksi_siswa.php" method="POST">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Konfirmasi Hapus Siswa</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus data siswa <strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong>?</p>
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
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Tidak ada data siswa ditemukan</p>
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

    <!-- Modal Tambah Siswa -->
    <div class="modal fade" id="tambahSiswaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="aksi_siswa.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tambah">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Foto Siswa</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_siswa" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NISN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nisn" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kelas" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select" name="jenis_kelamin" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" required>
                                    <option value="Aktif" selected>Aktif</option>
                                    <option value="Tidak Aktif">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">No. HP Orang Tua <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="no_hp_ortu" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
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

        // Validasi input NISN (10 digit angka)
        document.querySelectorAll('input[name="nisn"]').forEach(function(input) {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 10);
            });
        });

        // Validasi input No HP (hanya angka)
        document.querySelectorAll('input[name="no_hp_ortu"]').forEach(function(input) {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 15);
            });
        });

        // Auto capitalize nama siswa
        document.querySelectorAll('input[name="nama_siswa"]').forEach(function(input) {
            input.addEventListener('blur', function(e) {
                let words = this.value.toLowerCase().split(' ');
                for (let i = 0; i < words.length; i++) {
                    if (words[i].length > 0) {
                        words[i] = words[i][0].toUpperCase() + words[i].substring(1);
                    }
                }
                this.value = words.join(' ');
            });
        });
    </script>
</body>
</html>