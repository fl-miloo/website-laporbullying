<?php
require_once '../config/koneksi.php';
cek_login_guru();
$guru = get_logged_in_user();
$id_guru = $guru['id_guru'];

// Set default bulan dan tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
// Ambil data laporan bulanan
$conn->query("SET lc_time_names = 'id_ID'");
$query = "CALL GetLaporanBulanan(?, ?)";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

// Proses hasil pertama
$laporan_data = [];
while ($row = $result->fetch_assoc()) {
    $laporan_data[] = $row;
}

// Bersihkan hasil sebelum menjalankan query berikutnya
$stmt->close();
$conn->next_result(); // Penting untuk stored procedure

// Ambil statistik bulanan
$query_stats = "CALL GetStatistikBulanan(?, ?)";
$stmt_stats = $conn->prepare($query_stats);

if ($stmt_stats === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt_stats->bind_param("ii", $bulan, $tahun);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_assoc();

// Bersihkan hasil statistik
$stmt_stats->close();
$conn->next_result();

// Bulan dalam Bahasa Indonesia
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Jika ada aksi cetak
if (isset($_GET['action']) && $_GET['action'] == 'cetak') {
    // Header untuk generate PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Laporan_Bullying_' . $bulan_indo[$bulan] . '_' . $tahun . '.pdf"');
    
    // HTML untuk PDF
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Bulanan Bullying</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #333; }
            h2 { text-align: center; color: #555; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #2c3e50; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border: 1px solid #ddd; }
            .stats-table { margin-bottom: 30px; }
            .stats-table th { background-color: #3498db; }
            .footer { text-align: center; margin-top: 50px; font-style: italic; color: #777; }
            .page-break { page-break-after: always; }
        </style>
    </head>
    <body>
        <h1>LAPORAN BULANAN BULLYING</h1>
        <h2>Bulan: ' . $bulan_indo[$bulan] . ' ' . $tahun . '</h2>
        
        <h3>Statistik Ringkas</h3>
        <table class="stats-table">
            <tr>
                <th width="25%">Total Laporan</th>
                <th width="25%">Menunggu</th>
                <th width="25%">Diproses</th>
                <th width="25%">Selesai</th>
            </tr>
            <tr>
                <td align="center">' . $stats['total_laporan'] . '</td>
                <td align="center">' . $stats['menunggu'] . '</td>
                <td align="center">' . $stats['diproses'] . '</td>
                <td align="center">' . $stats['selesai'] . '</td>
            </tr>
            <tr>
                <th width="25%">Fisik</th>
                <th width="25%">Verbal</th>
                <th width="25%">Sosial</th>
                <th width="25%">Cyber</th>
            </tr>
            <tr>
                <td align="center">' . $stats['fisik'] . '</td>
                <td align="center">' . $stats['verbal'] . '</td>
                <td align="center">' . $stats['sosial'] . '</td>
                <td align="center">' . $stats['cyber'] . '</td>
            </tr>
            <tr>
                <th width="25%">Ringan</th>
                <th width="25%">Sedang</th>
                <th width="25%">Berat</th>
                <th width="25%"></th>
            </tr>
            <tr>
                <td align="center">' . $stats['ringan'] . '</td>
                <td align="center">' . $stats['sedang'] . '</td>
                <td align="center">' . $stats['berat'] . '</td>
                <td align="center"></td>
            </tr>
        </table>
        
        <h3>Detail Laporan</h3>';
        
    if (count($laporan_data) > 0) {
        $html .= '<table>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Pelapor</th>
                <th width="15%">Pelaku</th>
                <th width="10%">Jenis</th>
                <th width="10%">Tingkat</th>
                <th width="15%">Tanggal Kejadian</th>
                <th width="10%">Status</th>
                <th width="20%">Tindakan</th>
            </tr>';
        
        $no = 1;
        foreach ($laporan_data as $laporan) {
            $html .= '<tr>
                <td>' . $no++ . '</td>
                <td>' . htmlspecialchars($laporan['nama_pelapor']) . ' (' . htmlspecialchars($laporan['kelas_pelapor']) . ')</td>
                <td>' . htmlspecialchars($laporan['nama_pelaku']) . ' (' . htmlspecialchars($laporan['kelas_pelaku']) . ')</td>
                <td>' . htmlspecialchars($laporan['jenis_bullying']) . '</td>
                <td>' . htmlspecialchars($laporan['tingkat_bullying']) . '</td>
                <td>' . date('d M Y', strtotime($laporan['tanggal_kejadian'])) . '</td>
                <td>' . htmlspecialchars($laporan['status_laporan']) . '</td>
                <td>' . (!empty($laporan['jenis_tindakan']) ? htmlspecialchars($laporan['jenis_tindakan']) : '-') . '</td>
            </tr>';
        }
        
        $html .= '</table>';
    } else {
        $html .= '<p>Tidak ada data laporan untuk bulan ini</p>';
    }
    
    $html .= '
        <div class="footer">
            <p>Dicetak pada: ' . date('d F Y H:i:s') . '</p>
            <p>Oleh: ' . htmlspecialchars($guru['nama_guru']) . ' (Guru BK)</p>
        </div>
    </body>
    </html>';
    
    // Menggunakan library dompdf sebagai alternatif
    // Jika ingin menggunakan dompdf, uncomment kode berikut dan install library dompdf
    /*
    require_once '../assets/libs/dompdf/autoload.inc.php';
    use Dompdf\Dompdf;
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('Laporan_Bullying_' . $bulan_indo[$bulan] . '_' . $tahun . '.pdf', array('Attachment' => 0));
    */
    
    // Jika tidak ingin menggunakan library apapun, cukup output HTML dengan header PDF
    // Browser akan menangani konversi ke PDF saat pengguna memilih print as PDF
    echo $html;
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan - Sistem Pelaporan Bullying</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS yang sama seperti sebelumnya */
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
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--info));
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--info));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
        }
        
        .badge {
            border-radius: 20px;
            padding: 5px 12px;
        }
        
        .badge-warning {
            background-color: var(--warning);
        }
        
        .badge-info {
            background-color: var(--info);
        }
        
        .badge-success {
            background-color: var(--success);
        }
        
        .badge-danger {
            background-color: var(--danger);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--secondary);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .main-content {
                margin-left: 0;
            }
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
        
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .print-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        /* CSS untuk print */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            .table {
                width: 100% !important;
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
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="kelola_laporan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kelola_laporan.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Kelola Laporan</a></li>
            <li><a href="kelola_siswa.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kelola_siswa.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Data Siswa</a></li>
            <li><a href="statistik.php" class="<?= basename($_SERVER['PHP_SELF']) == 'statistik.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Statistik</a></li>
            <li><a href="tindak_lanjut.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tindak_lanjut.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Tindak Lanjut</a></li>
            <li><a href="cetak_laporan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cetak_laporan.php' ? 'active' : '' ?>"><i class="fas fa-file-pdf"></i> Cetak Laporan</a></li>
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profil</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <nav class="navbar navbar-expand-lg navbar-custom mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Cetak Laporan Bulanan</h4>
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

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-4"><i class="fas fa-filter"></i> Filter Laporan</h5>
            <form method="GET" class="row">
                <div class="col-md-4">
                    <label for="bulan" class="form-label">Bulan</label>
                    <select name="bulan" id="bulan" class="form-select">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($bulan == $i) ? 'selected' : ''; ?>>
                                <?php echo $bulan_indo[$i]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="tahun" class="form-label">Tahun</label>
                    <select name="tahun" id="tahun" class="form-select">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= ($currentYear - 5); $i--): 
                        ?>
                            <option value="<?php echo $i; ?>" <?php echo ($tahun == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistik Section -->
        <div class="row mb-4 no-print">
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="stats-number"><?php echo $stats['total_laporan']; ?></div>
                    <div class="stats-label">Total Laporan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number"><?php echo $stats['menunggu']; ?></div>
                    <div class="stats-label">Menunggu</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stats-number"><?php echo $stats['diproses']; ?></div>
                    <div class="stats-label">Diproses</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="stats-number"><?php echo $stats['selesai']; ?></div>
                    <div class="stats-label">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Detail Statistics -->
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <div class="card card-custom">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Jenis Bullying</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-danger"><?php echo $stats['fisik']; ?></h4>
                                    <small>Fisik</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-warning"><?php echo $stats['verbal']; ?></h4>
                                    <small>Verbal</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-info"><?php echo $stats['sosial']; ?></h4>
                                    <small>Sosial</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-primary"><?php echo $stats['cyber']; ?></h4>
                                    <small>Cyber</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Tingkat Bullying</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <div class="text-center">
                                    <h4 class="text-success"><?php echo $stats['ringan']; ?></h4>
                                    <small>Ringan</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h4 class="text-warning"><?php echo $stats['sedang']; ?></h4>
                                    <small>Sedang</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h4 class="text-danger"><?php echo $stats['berat']; ?></h4>
                                    <small>Berat</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laporan Detail -->
        <div class="print-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>LAPORAN BULANAN BULLYING</h1>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Bulan: <?php echo $bulan_indo[$bulan] . ' ' . $tahun; ?></h3>
                <div class="text-end">
                    <small class="text-muted">Total: <?php echo count($laporan_data); ?> laporan</small>
                </div>
            </div>

            <h4>Statistik Ringkas</h4>
            <table class="table table-bordered mb-4">
                <tr>
                    <th width="25%">Total Laporan</th>
                    <th width="25%">Menunggu</th>
                    <th width="25%">Diproses</th>
                    <th width="25%">Selesai</th>
                </tr>
                <tr>
                    <td align="center"><?php echo $stats['total_laporan']; ?></td>
                    <td align="center"><?php echo $stats['menunggu']; ?></td>
                    <td align="center"><?php echo $stats['diproses']; ?></td>
                    <td align="center"><?php echo $stats['selesai']; ?></td>
                </tr>
                <tr>
                    <th width="25%">Fisik</th>
                    <th width="25%">Verbal</th>
                    <th width="25%">Sosial</th>
                    <th width="25%">Cyber</th>
                </tr>
                <tr>
                    <td align="center"><?php echo $stats['fisik']; ?></td>
                    <td align="center"><?php echo $stats['verbal']; ?></td>
                    <td align="center"><?php echo $stats['sosial']; ?></td>
                    <td align="center"><?php echo $stats['cyber']; ?></td>
                </tr>
                <tr>
                    <th width="25%">Ringan</th>
                    <th width="25%">Sedang</th>
                    <th width="25%">Berat</th>
                    <th width="25%"></th>
                </tr>
                <tr>
                    <td align="center"><?php echo $stats['ringan']; ?></td>
                    <td align="center"><?php echo $stats['sedang']; ?></td>
                    <td align="center"><?php echo $stats['berat']; ?></td>
                    <td align="center"></td>
                </tr>
            </table>

            <h4>Detail Laporan</h4>
            <?php if (count($laporan_data) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Pelapor</th>
                                <th style="width: 15%;">Pelaku</th>
                                <th style="width: 10%;">Jenis</th>
                                <th style="width: 10%;">Tingkat</th>
                                <th style="width: 15%;">Lokasi</th>
                                <th style="width: 12%;">Tanggal Kejadian</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 8%;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($laporan_data as $laporan): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($laporan['nama_pelapor']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($laporan['kelas_pelapor']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($laporan['nama_pelaku']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($laporan['kelas_pelaku']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo $laporan['jenis_bullying']; ?>
                                    </td>
                                    <td>
                                        <?php echo $laporan['tingkat_bullying']; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($laporan['lokasi_kejadian']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($laporan['tanggal_kejadian'])); ?>
                                    </td>
                                    <td>
                                        <?php echo $laporan['status_laporan']; ?>
                                    </td>
                                   <td>
                                        <?php echo !empty($laporan['jenis_tindakan']) ? htmlspecialchars($laporan['jenis_tindakan']) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada laporan untuk bulan ini</h5>
                    <p class="text-muted">Belum ada laporan bullying yang masuk pada <?php echo $bulan_indo[$bulan] . ' ' . $tahun; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-5 text-right">
                <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?></p>
                <p>Oleh: <?php echo htmlspecialchars($guru['nama_guru']); ?> (Guru BK)</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDetailModal(idLaporan) {
            // Show loading
            document.getElementById('modalContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail laporan...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
            
            // Fetch detail data
            fetch(`../laporan/get_detail_laporan.php?id=${idLaporan}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Gagal memuat detail laporan. Silakan coba lagi.
                        </div>
                    `;
                });
        }

        // Auto refresh data every 30 seconds
        setInterval(function() {
            // Only refresh if no modal is open
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);

        // Statistics animation
        function animateNumbers() {
            const statsNumbers = document.querySelectorAll('.stats-number');
            statsNumbers.forEach(function(stat) {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 20;
                const timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current);
                }, 50);
            });
        }

        // Run animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            animateNumbers();
        });
    </script>
</body>
</html>

<?php
// Clean up
$conn->close();
?>