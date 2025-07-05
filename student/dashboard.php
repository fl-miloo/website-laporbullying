<?php
require_once '../config/koneksi.php';
cek_login_siswa();

// Ambil data siswa yang sedang login
$siswa = get_logged_in_user();
$id_siswa = $siswa['id_siswa'];

// Query statistik laporan
$stats = [
    'total_laporan' => 0,
    'laporan_proses' => 0,
    'laporan_selesai' => 0,
    'sebagai_pelaku' => 0
];

// Total laporan yang dibuat siswa
$query = "SELECT COUNT(*) as total FROM laporan_bullying WHERE id_siswa_pelapor = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}
$stmt->bind_param("i", $id_siswa);
if (!$stmt->execute()) {
    die('Error executing statement: ' . $stmt->error);
}
$result = $stmt->get_result();
$stats['total_laporan'] = $result->fetch_assoc()['total'];
$stmt->close();

// Laporan yang sedang diproses
$query = "SELECT COUNT(*) as total FROM laporan_bullying WHERE id_siswa_pelapor = ? AND status_laporan IN ('Menunggu', 'Diproses')";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}
$stmt->bind_param("i", $id_siswa);
if (!$stmt->execute()) {
    die('Error executing statement: ' . $stmt->error);
}
$result = $stmt->get_result();
$stats['laporan_proses'] = $result->fetch_assoc()['total'];
$stmt->close();

// Laporan yang selesai
$query = "SELECT COUNT(*) as total FROM laporan_bullying WHERE id_siswa_pelapor = ? AND status_laporan = 'Selesai'";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}
$stmt->bind_param("i", $id_siswa);
if (!$stmt->execute()) {
    die('Error executing statement: ' . $stmt->error);
}
$result = $stmt->get_result();
$stats['laporan_selesai'] = $result->fetch_assoc()['total'];
$stmt->close();

// Cek apakah siswa ini pernah dilaporkan (sebagai pelaku)
$query = "SELECT COUNT(*) as total FROM pelaku WHERE id_siswa = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}
$stmt->bind_param("i", $id_siswa);
if (!$stmt->execute()) {
    die('Error executing statement: ' . $stmt->error);
}
$result = $stmt->get_result();
$stats['sebagai_pelaku'] = $result->fetch_assoc()['total'];
$stmt->close();

// Ambil notifikasi menggunakan stored procedure
$notifikasi = [];
$stmt = $conn->prepare("CALL GetNotifikasiSiswa(?)");
if ($stmt === false) {
    // Jika stored procedure tidak ada, gunakan query biasa
    $stmt = $conn->prepare("SELECT * FROM notifikasi WHERE id_siswa = ? ORDER BY created_at DESC LIMIT 5");
    if ($stmt === false) {
        die('Error preparing notification statement: ' . $conn->error);
    }
    $stmt->bind_param("i", $id_siswa);
} else {
    $stmt->bind_param("i", $id_siswa);
}

if (!$stmt->execute()) {
    die('Error executing notification statement: ' . $stmt->error);
}
$notifikasi = $stmt->get_result();
$stmt->close();

// Clear any remaining results if stored procedure was used
if ($conn->more_results()) {
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
}

// Ambil laporan terbaru (5 terakhir)
$query = "SELECT * FROM laporan_bullying WHERE id_siswa_pelapor = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Error preparing recent reports statement: ' . $conn->error);
}
$stmt->bind_param("i", $id_siswa);
if (!$stmt->execute()) {
    die('Error executing recent reports statement: ' . $stmt->error);
}
$laporan_terbaru = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - <?php echo htmlspecialchars($siswa['nama_siswa']); ?></title>
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
            transition: all 0.3s;
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

        .header-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-menunggu { background: #fff3cd; color: #856404; }
        .status-diproses { background: #d1ecf1; color: #0c5460; }
        .status-selesai { background: #d4edda; color: #155724; }
        .status-ditolak { background: #f8d7da; color: #721c24; }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .quick-action-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .alert-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
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
                <a class="nav-link active" href="dashboard.php">
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
        <!-- Header -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">Selamat Datang, <?php echo htmlspecialchars($siswa['nama_siswa']); ?>!</h1>
                    <p class="mb-0 mt-2">Sistem Pelaporan Bullying - Dashboard Siswa</p>
                    <small>Kelas <?php echo htmlspecialchars($siswa['kelas']); ?> • Login terakhir: <?php echo date('d M Y, H:i'); ?></small>
                </div>
                <div class="col-md-4 text-end">
                    <a href="buat_laporan.php" class="quick-action-btn">
                        <i class="fas fa-plus me-2"></i>Buat Laporan
                    </a>
                </div>
            </div>
        </div>

        <?php show_alert(); ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_laporan']; ?></h3>
                            <p class="text-muted mb-0">Total Laporan</p>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['laporan_proses']; ?></h3>
                            <p class="text-muted mb-0">Sedang Diproses</p>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['laporan_selesai']; ?></h3>
                            <p class="text-muted mb-0">Laporan Selesai</p>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['sebagai_pelaku']; ?></h3>
                            <p class="text-muted mb-0">Sebagai Pelaku</p>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-user-slash fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peringatan (jika pernah dilaporkan sebagai pelaku) -->
        <?php if ($stats['sebagai_pelaku'] > 0): ?>
        <div class="alert alert-warning alert-custom">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Perhatian: Anda Pernah Dilaporkan Sebagai Pelaku</h5>
                    <p class="mb-0">
                        Anda telah dilaporkan sebanyak <strong><?php echo $stats['sebagai_pelaku']; ?> kali</strong> sebagai pelaku bullying.
                        Silakan berkonsultasi dengan Guru BK untuk penanganan lebih lanjut.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Laporan Terbaru -->
            <div class="col-md-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Laporan Terbaru</h5>
                        <a href="riwayat_laporan.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Lihat Semua
                        </a>
                    </div>
                    
                    <?php if ($laporan_terbaru->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis Bullying</th>
                                    <th>Tingkat</th>
                                    <th>Status</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($laporan = $laporan_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo format_tanggal($laporan['tanggal_kejadian']); ?></td>
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
                                        <a href="riwayat_laporan.php?id=<?php echo $laporan['id_laporan']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada laporan yang dibuat</p>
                        <a href="buat_laporan.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Buat Laporan Pertama
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifikasi dan Quick Actions -->
            <div class="col-md-4">
                <!-- Notifikasi -->
                <div class="table-card">
                    <h5 class="mb-3"><i class="fas fa-bell me-2"></i>Notifikasi Terbaru</h5>
                    
                    <?php if ($notifikasi->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($notif = $notifikasi->fetch_assoc()): ?>
                            <a href="#" class="list-group-item list-group-item-action border-0 mb-2 rounded">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notif['judul_notifikasi']); ?></h6>
                                    <small><?php echo format_tanggal($notif['created_at']); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars(substr($notif['pesan_notifikasi'], 0, 50)); ?>...</p>
                                <?php if ($notif['tanggal_panggilan']): ?>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo format_tanggal($notif['tanggal_panggilan']); ?> 
                                    <?php if ($notif['waktu_panggilan']): ?>
                                        pukul <?php echo format_waktu($notif['waktu_panggilan']); ?>
                                    <?php endif; ?>
                                </small>
                                <?php endif; ?>
                            </a>
                            <?php endwhile; ?>
                        </div>
                        <div class="text-center mt-2">
                            <a href="#" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Lihat Semua Notifikasi
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada notifikasi baru</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="table-card">
                    <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="buat_laporan.php" class="btn btn-primary">
                            <i class="fas fa-exclamation-triangle me-2"></i>Buat Laporan
                        </a>
                        <a href="riwayat_laporan.php" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>Riwayat Laporan
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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