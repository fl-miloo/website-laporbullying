<?php
require_once '../config/koneksi.php';
cek_login_siswa();

$siswa = get_logged_in_user();
$id_siswa = $siswa['id_siswa'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_pelaku = clean_input($_POST['nama_pelaku']);
    $kelas_pelaku = clean_input($_POST['kelas_pelaku']);
    $jenis_bullying = clean_input($_POST['jenis_bullying']);
    $tingkat_bullying = clean_input($_POST['tingkat_bullying']);
    $deskripsi_kejadian = clean_input($_POST['deskripsi_kejadian']);
    $lokasi_kejadian = clean_input($_POST['lokasi_kejadian']);
    $tanggal_kejadian = clean_input($_POST['tanggal_kejadian']);
    $waktu_kejadian = clean_input($_POST['waktu_kejadian']);
    $saksi = clean_input($_POST['saksi']);
    
    // Validasi input
    if (empty($nama_pelaku)) {
        $errors[] = "Nama pelaku harus diisi";
    }
    
    if (empty($kelas_pelaku)) {
        $errors[] = "Kelas pelaku harus diisi";
    }
    
    if (empty($deskripsi_kejadian)) {
        $errors[] = "Deskripsi kejadian harus diisi";
    }
    
    if (empty($lokasi_kejadian)) {
        $errors[] = "Lokasi kejadian harus diisi";
    }
    
    if (empty($tanggal_kejadian)) {
        $errors[] = "Tanggal kejadian harus diisi";
    }
    
    // Handle upload bukti
    $bukti_files = [];
    if (!empty($_FILES['bukti']['name'][0])) {
        foreach ($_FILES['bukti']['name'] as $key => $name) {
            if ($_FILES['bukti']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $name,
                    'type' => $_FILES['bukti']['type'][$key],
                    'tmp_name' => $_FILES['bukti']['tmp_name'][$key],
                    'error' => $_FILES['bukti']['error'][$key],
                    'size' => $_FILES['bukti']['size'][$key]
                ];
                
                // Tentukan jenis bukti berdasarkan tipe file
                $file_type = explode('/', $file['type'])[0];
                $folder = '';
                $allowed_types = [];
                
                switch ($file_type) {
                    case 'image':
                        $folder = 'foto';
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        break;
                    case 'video':
                        $folder = 'video';
                        $allowed_types = ['mp4', 'mov', 'avi'];
                        break;
                    case 'audio':
                        $folder = 'audio';
                        $allowed_types = ['mp3', 'wav'];
                        break;
                    default:
                        $folder = 'dokumen';
                        $allowed_types = ['pdf', 'doc', 'docx'];
                }
                
                $upload = upload_file($file, 'bukti/' . $folder, $allowed_types);
                
                if ($upload['success']) {
                    $bukti_files[] = [
                        'jenis' => ucfirst($folder),
                        'nama_file' => $upload['filename']
                    ];
                } else {
                    $errors[] = "Gagal mengupload file " . $name . ": " . $upload['message'];
                }
            }
        }
    }
    
    // Insert data jika tidak ada error
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Insert laporan bullying
            $query = "INSERT INTO laporan_bullying (
                id_siswa_pelapor, 
                nama_pelaku, 
                kelas_pelaku, 
                jenis_bullying, 
                tingkat_bullying, 
                deskripsi_kejadian, 
                lokasi_kejadian, 
                tanggal_kejadian, 
                waktu_kejadian, 
                saksi,
                status_laporan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu')";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "isssssssss", 
                $id_siswa, 
                $nama_pelaku, 
                $kelas_pelaku, 
                $jenis_bullying, 
                $tingkat_bullying, 
                $deskripsi_kejadian, 
                $lokasi_kejadian, 
                $tanggal_kejadian, 
                $waktu_kejadian, 
                $saksi
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan laporan: " . $stmt->error);
            }
            
            $id_laporan = $stmt->insert_id;
            
            // Insert bukti jika ada
            if (!empty($bukti_files)) {
                foreach ($bukti_files as $bukti) {
                    $query = "INSERT INTO bukti_laporan (
                        id_laporan, 
                        jenis_bukti, 
                        nama_file
                    ) VALUES (?, ?, ?)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param(
                        "iss", 
                        $id_laporan, 
                        $bukti['jenis'], 
                        $bukti['nama_file']
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Gagal menyimpan bukti: " . $stmt->error);
                    }
                }
            }
            
            $conn->commit();
            $success = true;
            set_alert('success', 'Laporan bullying berhasil dikirim!');
            
            // Reset form
            $_POST = [];
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        set_alert('error', implode('<br>', $errors));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan Bullying - <?php echo htmlspecialchars($siswa['nama_siswa']); ?></title>
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
                <a class="nav-link active" href="buat_laporan.php">
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
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="report-card">
                    <h2 class="mb-4"><i class="fas fa-exclamation-triangle me-2"></i>Buat Laporan Bullying</h2>
                    
                    <?php show_alert(); ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Pelaku <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_pelaku" value="<?php echo $_POST['nama_pelaku'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Kelas Pelaku <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="kelas_pelaku" value="<?php echo $_POST['kelas_pelaku'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Bullying <span class="text-danger">*</span></label>
                                        <select class="form-select" name="jenis_bullying" required>
                                            <option value="">Pilih Jenis</option>
                                            <option value="Fisik" <?php echo ($_POST['jenis_bullying'] ?? '') == 'Fisik' ? 'selected' : ''; ?>>Fisik</option>
                                            <option value="Verbal" <?php echo ($_POST['jenis_bullying'] ?? '') == 'Verbal' ? 'selected' : ''; ?>>Verbal</option>
                                            <option value="Sosial" <?php echo ($_POST['jenis_bullying'] ?? '') == 'Sosial' ? 'selected' : ''; ?>>Sosial</option>
                                            <option value="Cyber" <?php echo ($_POST['jenis_bullying'] ?? '') == 'Cyber' ? 'selected' : ''; ?>>Cyber</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tingkat Bullying <span class="text-danger">*</span></label>
                                        <select class="form-select" name="tingkat_bullying" required>
                                            <option value="">Pilih Tingkat</option>
                                            <option value="Ringan" <?php echo ($_POST['tingkat_bullying'] ?? '') == 'Ringan' ? 'selected' : ''; ?>>Ringan</option>
                                            <option value="Sedang" <?php echo ($_POST['tingkat_bullying'] ?? '') == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                            <option value="Berat" <?php echo ($_POST['tingkat_bullying'] ?? '') == 'Berat' ? 'selected' : ''; ?>>Berat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Kejadian <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_kejadian" value="<?php echo $_POST['tanggal_kejadian'] ?? date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Waktu Kejadian</label>
                                        <input type="time" class="form-control" name="waktu_kejadian" value="<?php echo $_POST['waktu_kejadian'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Lokasi Kejadian <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="lokasi_kejadian" value="<?php echo $_POST['lokasi_kejadian'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Saksi (jika ada)</label>
                                    <input type="text" class="form-control" name="saksi" value="<?php echo $_POST['saksi'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Deskripsi Kejadian <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="deskripsi_kejadian" rows="5" required><?php echo $_POST['deskripsi_kejadian'] ?? ''; ?></textarea>
                            <small class="text-muted">Jelaskan kejadian bullying secara detail dan jelas</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Upload Bukti (Opsional)</label>
                            <div class="file-upload" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                                <h5>Seret file ke sini atau klik untuk memilih</h5>
                                <p class="text-muted">Format: JPG, PNG, MP4, MP3, PDF (maks 5MB per file)</p>
                                <input type="file" name="bukti[]" id="bukti" multiple style="display: none;">
                            </div>
                            
                            <div class="file-preview" id="filePreview">
                                <!-- File preview akan muncul di sini -->
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle file upload area
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('bukti');
        const filePreview = document.getElementById('filePreview');
        
        fileUploadArea.addEventListener('click', () => fileInput.click());
        
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = '#667eea';
            fileUploadArea.style.backgroundColor = '#f8f9ff';
        });
        
        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.style.borderColor = '#dee2e6';
            fileUploadArea.style.backgroundColor = 'transparent';
        });
        
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = '#dee2e6';
            fileUploadArea.style.backgroundColor = 'transparent';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFilePreview();
            }
        });
        
        fileInput.addEventListener('change', updateFilePreview);
        
        function updateFilePreview() {
            filePreview.innerHTML = '';
            
            if (fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach((file, index) => {
                    const fileType = file.type.split('/')[0];
                    let previewContent = '';
                    
                    if (fileType === 'image') {
                        previewContent = `<img src="${URL.createObjectURL(file)}" alt="${file.name}">`;
                    } else if (fileType === 'video') {
                        previewContent = `<video><source src="${URL.createObjectURL(file)}" type="${file.type}"></video>`;
                    } else {
                        previewContent = `<div class="d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="fas fa-file-alt fa-3x mb-2"></i>
                            <small class="text-center">${file.name}</small>
                        </div>`;
                    }
                    
                    const previewItem = document.createElement('div');
                    previewItem.className = 'file-preview-item';
                    previewItem.innerHTML = `
                        ${previewContent}
                        <div class="remove-btn" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </div>
                    `;
                    
                    filePreview.appendChild(previewItem);
                });
                
                // Add remove button functionality
                document.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const index = parseInt(btn.getAttribute('data-index'));
                        removeFile(index);
                    });
                });
            }
        }
        
        function removeFile(index) {
            const dt = new DataTransfer();
            const files = Array.from(fileInput.files);
            
            files.splice(index, 1);
            
            files.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            
            updateFilePreview();
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