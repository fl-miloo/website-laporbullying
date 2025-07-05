<?php
require_once '../config/koneksi.php';
cek_login_guru();

$id_guru = $_SESSION['user_id'];

// Fungsi untuk mendapatkan path foto guru
function getFotoGuruPath($foto) {
    if (empty($foto)) {
        return false;
    }
    
    $foto_path = "../assets/uploads/foto_guru/" . $foto;
    
    // Cek apakah file foto ada
    if (file_exists($foto_path)) {
        return $foto_path;
    }
    
    return false;
}


// Ambil data guru yang login
$query_guru = "SELECT * FROM guru_bk WHERE id_guru = ?";
$stmt = $conn->prepare($query_guru);
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result_guru = $stmt->get_result();
$data_guru = $result_guru->fetch_assoc();

// Statistik laporan
$query_stats = "SELECT 
    COUNT(*) as total_laporan,
    SUM(CASE WHEN status_laporan = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status_laporan = 'Diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status_laporan = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_laporan = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM laporan_bullying";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();

// Data untuk grafik bulanan (6 bulan terakhir)
$query_monthly = "SELECT 
    MONTH(created_at) as bulan,
    YEAR(created_at) as tahun,
    COUNT(*) as total_laporan,
    COUNT(CASE WHEN status_laporan = 'Selesai' THEN 1 END) as selesai
    FROM laporan_bullying 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY tahun, bulan";
$result_monthly = $conn->query($query_monthly);
$monthly_data = [];
while ($row = $result_monthly->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Jenis bullying terbanyak
$query_jenis = "SELECT jenis_bullying, COUNT(*) as jumlah 
               FROM laporan_bullying 
               GROUP BY jenis_bullying 
               ORDER BY jumlah DESC 
               LIMIT 5";
$result_jenis = $conn->query($query_jenis);
$jenis_bullying = [];
while ($row = $result_jenis->fetch_assoc()) {
    $jenis_bullying[] = $row;
}

// Laporan terbaru
$query_recent = "SELECT lb.*, s.nama_siswa as nama_pelapor, s.kelas 
                FROM laporan_bullying lb
                JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
                ORDER BY lb.created_at DESC 
                LIMIT 5";
$result_recent = $conn->query($query_recent);

// Pelaku sering
$query_pelaku = "SELECT * FROM v_pelaku_sering LIMIT 5";
$result_pelaku = $conn->query($query_pelaku);

// Bulan dalam Bahasa Indonesia
$bulan_indo = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru BK - Sistem Pelaporan Bullying</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin: 0;
            padding: 0;
        }
        
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--secondary), var(--dark));
            color: white;
            position: fixed;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        .sidebar-header p {
            margin: 8px 0 0 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 0.95rem;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary);
            transform: translateX(5px);
        }
        
        .sidebar-menu li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .navbar-title h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--secondary);
            font-weight: 600;
        }
        
        .navbar-title h1 i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            overflow: hidden;
            border: 3px solid #e9ecef;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--secondary);
            font-size: 1rem;
        }
        
        .user-role {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: #c0392b;
            color: white;
            transform: translateY(-2px);
        }
        
        .content-area {
            padding: 30px;
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 25px;
            color: white;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card i {
            font-size: 45px;
            opacity: 0.8;
        }
        
        .stat-card .count {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        
        .stat-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .card-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-custom .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 20px 25px;
            font-size: 1.1rem;
        }
        
        .card-custom .card-body {
            padding: 25px;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-menunggu { background-color: var(--warning); color: white; }
        .status-diproses { background-color: var(--info); color: white; }
        .status-selesai { background-color: var(--success); color: white; }
        .status-ditolak { background-color: var(--danger); color: white; }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .list-group-item {
            border: none;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .top-navbar {
                padding: 15px 20px;
            }
            .navbar-title h1 {
                font-size: 1.4rem;
            }
            .content-area {
                padding: 20px;
            }
        }
        
        .mobile-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
            .navbar-user {
                gap: 10px;
            }
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> GURU BK</h2>
            <p><?php echo format_tanggal(date('Y-m-d')); ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="kelola_laporan.php"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a></li>
            <li><a href="kelola_siswa.php"><i class="fas fa-users"></i> Data Siswa</a></li>
            <li><a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a></li>
            <li><a href="tindak_lanjut.php"><i class="fas fa-tasks"></i> Tindak Lanjut</a></li>
            <li><a href="cetak_laporan.php"><i class="fas fa-file-pdf"></i> Cetak Laporan</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-title">
                <h1><i class="fas fa-chart-line"></i> Dashboard Guru BK</h1>
            </div>
            <div class="navbar-user">
                <div class="user-avatar">
                    <?php 
                    $foto_path = getFotoGuruPath($data_guru['foto']);
                    if ($foto_path): 
                    ?>
                        <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Profil">
                    <?php else: ?>
                        <?php echo strtoupper(substr($data_guru['nama_guru'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($data_guru['nama_guru']); ?></div>
                    <div class="user-role">Guru Bimbingan Konseling</div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if (function_exists('show_alert')) show_alert(); ?>
            
            <!-- Statistik Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= $stats['total_laporan'] ?></div>
                                <div class="label">Total Laporan</div>
                            </div>
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= $stats['menunggu'] ?></div>
                                <div class="label">Menunggu</div>
                            </div>
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= $stats['diproses'] ?></div>
                                <div class="label">Diproses</div>
                            </div>
                            <i class="fas fa-cog"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= $stats['selesai'] ?></div>
                                <div class="label">Selesai</div>
                            </div>
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Data -->
            <div class="row">
                <!-- Grafik Bulanan -->
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>Statistik Laporan Bulanan
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Jenis Bullying -->
                <div class="col-lg-4">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle me-2"></i>Jenis Bullying Terbanyak
                        </div>
                        <div class="card-body">
                            <?php if (!empty($jenis_bullying)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($jenis_bullying as $jenis): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($jenis['jenis_bullying']) ?>
                                            <span class="badge bg-primary rounded-pill"><?= $jenis['jumlah'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>Belum ada data jenis bullying</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Laporan Terbaru -->
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Laporan Terbaru
                        </div>
                        <div class="card-body">
                            <?php if ($result_recent->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($laporan = $result_recent->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($laporan['nama_pelapor']) ?> (<?= htmlspecialchars($laporan['kelas']) ?>)</h6>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($laporan['jenis_bullying']) ?> • 
                                                        <?= date('d M Y', strtotime($laporan['tanggal_kejadian'])) ?>
                                                    </small>
                                                </div>
                                                <span class="badge-status status-<?= strtolower($laporan['status_laporan']) ?>">
                                                    <?= htmlspecialchars($laporan['status_laporan']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Belum ada laporan terbaru</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Pelaku Sering -->
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-user-times me-2"></i>Pelaku Sering
                        </div>
                        <div class="card-body">
                            <?php if ($result_pelaku->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama Pelaku</th>
                                                <th>Kelas</th>
                                                <th>Frekuensi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($pelaku = $result_pelaku->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($pelaku['nama_pelaku']) ?></td>
                                                    <td><?= !empty($pelaku['kelas']) ? htmlspecialchars($pelaku['kelas']) : '-' ?></td>
                                                    <td><span class="badge bg-danger"><?= $pelaku['frekuensi'] ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-user-shield fa-2x mb-2"></i>
                                    <p>Belum ada data pelaku sering</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle for Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Grafik Bulanan
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($monthly_data as $data): ?>
                        '<?= $bulan_indo[$data['bulan']] ?> <?= $data['tahun'] ?>',
                    <?php endforeach; ?>
                ],
                datasets: [
                    {
                        label: 'Total Laporan',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['total_laporan'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Selesai',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['selesai'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: 'rgba(39, 174, 96, 0.8)',
                        borderColor: 'rgba(39, 174, 96, 1)',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>