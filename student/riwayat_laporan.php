<?php
require_once '../config/koneksi.php';
cek_login_siswa();

$siswa = get_logged_in_user();
$id_siswa = $siswa['id_siswa'];

// Ambil parameter untuk filter
$filter_status = $_GET['status'] ?? '';
$filter_jenis = $_GET['jenis'] ?? '';
$filter_tanggal = $_GET['tanggal'] ?? '';

// Query dasar
$query = "SELECT 
            lb.id_laporan,
            lb.nama_pelaku,
            lb.kelas_pelaku,
            lb.jenis_bullying,
            lb.tingkat_bullying,
            lb.lokasi_kejadian,
            lb.tanggal_kejadian,
            lb.status_laporan,
            lb.created_at,
            gb.nama_guru as guru_penangani
          FROM laporan_bullying lb
          LEFT JOIN guru_bk gb ON lb.id_guru_penangani = gb.id_guru
          WHERE lb.id_siswa_pelapor = ?";

$params = [$id_siswa];
$types = "i";

// Tambahkan filter jika ada
if (!empty($filter_status)) {
    $query .= " AND lb.status_laporan = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_jenis)) {
    $query .= " AND lb.jenis_bullying = ?";
    $params[] = $filter_jenis;
    $types .= "s";
}

if (!empty($filter_tanggal)) {
    $query .= " AND DATE(lb.tanggal_kejadian) = ?";
    $params[] = $filter_tanggal;
    $types .= "s";
}

$query .= " ORDER BY lb.created_at DESC";

// Prepare statement
$stmt = $conn->prepare($query);

// Bind parameters
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - <?php echo htmlspecialchars($siswa['nama_siswa']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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

        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .alert-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            border-radius: 15px;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .required {
            color: var(--danger-color);
        }

        .form-help {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .preview-img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 10px;
            border: 2px solid #e9ecef;
        }

        .upload-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
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
                <a class="nav-link active" href="riwayat_laporan.php">
                    <i class="fas fa-history me-2"></i>Riwayat Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
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
        <div class="report-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Laporan</h2>
                <a href="buat_laporan.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Laporan Baru
                </a>
            </div>
            
            <?php show_alert(); ?>
            
            <!-- Filter -->
            <div class="filter-card mb-4">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="Menunggu" <?php echo $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="Diproses" <?php echo $filter_status == 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="Selesai" <?php echo $filter_status == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Jenis Bullying</label>
                        <select class="form-select" name="jenis">
                            <option value="">Semua Jenis</option>
                            <option value="Fisik" <?php echo $filter_jenis == 'Fisik' ? 'selected' : ''; ?>>Fisik</option>
                            <option value="Verbal" <?php echo $filter_jenis == 'Verbal' ? 'selected' : ''; ?>>Verbal</option>
                            <option value="Sosial" <?php echo $filter_jenis == 'Sosial' ? 'selected' : ''; ?>>Sosial</option>
                            <option value="Cyber" <?php echo $filter_jenis == 'Cyber' ? 'selected' : ''; ?>>Cyber</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Kejadian</label>
                        <input type="date" class="form-control" name="tanggal" value="<?php echo $filter_tanggal; ?>">
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <a href="riwayat_laporan.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Tabel Laporan -->
            <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Lapor</th>
                            <th>Pelaku</th>
                            <th>Jenis</th>
                            <th>Tingkat</th>
                            <th>Status</th>
                            <th>Penanganan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($laporan = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo format_tanggal($laporan['created_at']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($laporan['nama_pelaku']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($laporan['kelas_pelaku']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $laporan['jenis_bullying']; ?></span>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo $laporan['tingkat_bullying'] == 'Ringan' ? 'bg-success' : 
                                        ($laporan['tingkat_bullying'] == 'Sedang' ? 'bg-warning' : 'bg-danger'); 
                                ?>">
                                    <?php echo $laporan['tingkat_bullying']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($laporan['status_laporan']); ?>">
                                    <?php echo $laporan['status_laporan']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($laporan['guru_penangani']): ?>
                                    <?php echo htmlspecialchars($laporan['guru_penangani']); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-detail" 
                                        data-id="<?php echo $laporan['id_laporan']; ?>">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Tidak ada data laporan</h5>
                <p class="text-muted">Anda belum membuat laporan bullying</p>
                <a href="buat_laporan.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Laporan Pertama
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Laporan -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Laporan Bullying</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat detail laporan...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal detail laporan
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        
        document.querySelectorAll('.view-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                const idLaporan = this.getAttribute('data-id');
                loadDetailLaporan(idLaporan);
            });
        });
        
        function loadDetailLaporan(idLaporan) {
            const modalContent = document.getElementById('modalDetailContent');
            modalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat detail laporan...</p>
                </div>
            `;
            
            detailModal.show();
            
            fetch(`get_detail_laporan.php?id=${idLaporan}`)
                .then(response => response.text())
                .then(data => {
                    modalContent.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Gagal memuat detail laporan. Silakan coba lagi.
                        </div>
                    `;
                });
        }
        
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