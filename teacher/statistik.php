<?php
require_once '../config/koneksi.php';
cek_login_guru();

// Ambil data guru yang login
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];
// Ambil statistik umum
$query_stats = "SELECT 
    COUNT(*) as total_laporan,
    SUM(CASE WHEN status_laporan = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status_laporan = 'Diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status_laporan = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_laporan = 'Ditolak' THEN 1 ELSE 0 END) as ditolak,
    SUM(CASE WHEN jenis_bullying = 'Fisik' THEN 1 ELSE 0 END) as fisik,
    SUM(CASE WHEN jenis_bullying = 'Verbal' THEN 1 ELSE 0 END) as verbal,
    SUM(CASE WHEN jenis_bullying = 'Sosial' THEN 1 ELSE 0 END) as sosial,
    SUM(CASE WHEN jenis_bullying = 'Cyber' THEN 1 ELSE 0 END) as cyber,
    SUM(CASE WHEN tingkat_bullying = 'Ringan' THEN 1 ELSE 0 END) as ringan,
    SUM(CASE WHEN tingkat_bullying = 'Sedang' THEN 1 ELSE 0 END) as sedang,
    SUM(CASE WHEN tingkat_bullying = 'Berat' THEN 1 ELSE 0 END) as berat
    FROM laporan_bullying";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();

// Statistik bulanan (12 bulan terakhir)
$query_monthly = "SELECT 
    MONTH(created_at) as bulan,
    YEAR(created_at) as tahun,
    COUNT(*) as total_laporan,
    SUM(CASE WHEN jenis_bullying = 'Fisik' THEN 1 ELSE 0 END) as fisik,
    SUM(CASE WHEN jenis_bullying = 'Verbal' THEN 1 ELSE 0 END) as verbal,
    SUM(CASE WHEN jenis_bullying = 'Sosial' THEN 1 ELSE 0 END) as sosial,
    SUM(CASE WHEN jenis_bullying = 'Cyber' THEN 1 ELSE 0 END) as cyber
    FROM laporan_bullying 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY tahun, bulan";
$result_monthly = $conn->query($query_monthly);
$monthly_data = [];
while ($row = $result_monthly->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Statistik kelas
$query_kelas = "SELECT 
    kelas_pelaku as kelas,
    COUNT(*) as jumlah_laporan,
    SUM(CASE WHEN jenis_bullying = 'Fisik' THEN 1 ELSE 0 END) as fisik,
    SUM(CASE WHEN jenis_bullying = 'Verbal' THEN 1 ELSE 0 END) as verbal,
    SUM(CASE WHEN jenis_bullying = 'Sosial' THEN 1 ELSE 0 END) as sosial,
    SUM(CASE WHEN jenis_bullying = 'Cyber' THEN 1 ELSE 0 END) as cyber
    FROM laporan_bullying
    GROUP BY kelas_pelaku
    ORDER BY kelas_pelaku";
$result_kelas = $conn->query($query_kelas);

// Pelaku sering
$query_pelaku = "SELECT * FROM v_pelaku_sering LIMIT 10";
$result_pelaku = $conn->query($query_pelaku);

// Bulan dalam Bahasa Indonesia
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Bullying - Sistem Pelaporan Bullying</title>
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
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .stat-card .count {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 14px;
        }
        
        .table-custom th {
            background-color: var(--light);
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
            <a href="statistik.php" class="active"><i class="fas fa-chart-bar"></i> Statistik</a>
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
                <h4 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Statistik Bullying</h4>
            
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
            
            
            <!-- Statistik Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-clipboard-list"></i>
                        <div class="count"><?= $stats['total_laporan'] ?></div>
                        <div class="label">Total Laporan</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-user-times"></i>
                        <div class="count"><?= $stats['fisik'] + $stats['verbal'] + $stats['sosial'] + $stats['cyber'] ?></div>
                        <div class="label">Total Kasus Bullying</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                        <i class="fas fa-check-circle"></i>
                        <div class="count"><?= $stats['selesai'] ?></div>
                        <div class="label">Kasus Selesai</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="fas fa-users"></i>
                        <div class="count">
                            <?php 
                            $query_pelaku_count = "SELECT COUNT(*) as total FROM pelaku";
                            $result_pelaku_count = $conn->query($query_pelaku_count);
                            $pelaku_count = $result_pelaku_count->fetch_assoc();
                            echo $pelaku_count['total'];
                            ?>
                        </div>
                        <div class="label">Total Pelaku</div>
                    </div>
                </div>
            </div>
            
            <!-- Grafik Bulanan -->
            <div class="card card-custom mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistik Bulanan</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Grafik Jenis Bullying -->
                <div class="col-lg-6">
                    <div class="card card-custom mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-pie-chart me-2"></i>Distribusi Jenis Bullying</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="jenisChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Grafik Tingkat Bullying -->
                <div class="col-lg-6">
                    <div class="card card-custom mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Tingkat Bullying</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="tingkatChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistik per Kelas -->
            <div class="card card-custom mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-school me-2"></i>Statistik per Kelas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-custom">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Total Laporan</th>
                                    <th>Fisik</th>
                                    <th>Verbal</th>
                                    <th>Sosial</th>
                                    <th>Cyber</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_kelas->num_rows > 0): ?>
                                    <?php while ($kelas = $result_kelas->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($kelas['kelas']) ?></td>
                                            <td><?= $kelas['jumlah_laporan'] ?></td>
                                            <td><?= $kelas['fisik'] ?></td>
                                            <td><?= $kelas['verbal'] ?></td>
                                            <td><?= $kelas['sosial'] ?></td>
                                            <td><?= $kelas['cyber'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2 text-muted"></i>
                                            <p class="text-muted">Tidak ada data statistik per kelas</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pelaku Sering -->
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>10 Pelaku Paling Sering</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Pelaku</th>
                                    <th>Kelas</th>
                                    <th>Frekuensi</th>
                                    <th>Terakhir Dilaporkan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_pelaku->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($pelaku = $result_pelaku->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($pelaku['nama_pelaku']) ?></td>
                                            <td><?= !empty($pelaku['kelas']) ? htmlspecialchars($pelaku['kelas']) : '-' ?></td>
                                            <td><?= $pelaku['frekuensi'] ?></td>
                                            <td><?= date('d M Y', strtotime($pelaku['last_reported'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                                            <p class="text-muted">Tidak ada data pelaku</p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Grafik Bulanan
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
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
                        borderColor: 'rgba(52, 152, 219, 1)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Fisik',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['fisik'] ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(231, 76, 60, 1)',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Verbal',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['verbal'] ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(52, 152, 219, 1)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Sosial',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['sosial'] ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(155, 89, 182, 1)',
                        backgroundColor: 'rgba(155, 89, 182, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Cyber',
                        data: [
                            <?php foreach ($monthly_data as $data): ?>
                                <?= $data['cyber'] ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(46, 204, 113, 1)',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderWidth: 2,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Grafik Jenis Bullying
        const jenisCtx = document.getElementById('jenisChart').getContext('2d');
        const jenisChart = new Chart(jenisCtx, {
            type: 'doughnut',
            data: {
                labels: ['Fisik', 'Verbal', 'Sosial', 'Cyber'],
                datasets: [{
                    data: [
                        <?= $stats['fisik'] ?>,
                        <?= $stats['verbal'] ?>,
                        <?= $stats['sosial'] ?>,
                        <?= $stats['cyber'] ?>
                    ],
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(46, 204, 113, 0.8)'
                    ],
                    borderColor: [
                        'rgba(231, 76, 60, 1)',
                        'rgba(52, 152, 219, 1)',
                        'rgba(155, 89, 182, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Grafik Tingkat Bullying
        const tingkatCtx = document.getElementById('tingkatChart').getContext('2d');
        const tingkatChart = new Chart(tingkatCtx, {
            type: 'pie',
            data: {
                labels: ['Ringan', 'Sedang', 'Berat'],
                datasets: [{
                    data: [
                        <?= $stats['ringan'] ?>,
                        <?= $stats['sedang'] ?>,
                        <?= $stats['berat'] ?>
                    ],
                    backgroundColor: [
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(243, 156, 18, 0.8)',
                        'rgba(230, 126, 34, 0.8)'
                    ],
                    borderColor: [
                        'rgba(241, 196, 15, 1)',
                        'rgba(243, 156, 18, 1)',
                        'rgba(230, 126, 34, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>