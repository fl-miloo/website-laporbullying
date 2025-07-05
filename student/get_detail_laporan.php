<?php
require_once '../config/koneksi.php';
cek_login_siswa();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">ID laporan tidak valid</div>');
}

$id_laporan = (int)$_GET['id'];
$siswa = get_logged_in_user();
$id_siswa = $siswa['id_siswa'];

// Ambil detail laporan
$query = "SELECT 
            lb.*,
            gb.nama_guru as guru_penangani,
            s.nama_siswa as nama_pelapor,
            s.kelas as kelas_pelapor
          FROM laporan_bullying lb
          LEFT JOIN guru_bk gb ON lb.id_guru_penangani = gb.id_guru
          LEFT JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
          WHERE lb.id_laporan = ? AND lb.id_siswa_pelapor = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_laporan, $id_siswa);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die('<div class="alert alert-danger">Laporan tidak ditemukan atau Anda tidak memiliki akses</div>');
}

$laporan = $result->fetch_assoc();

// Ambil bukti laporan
$query_bukti = "SELECT * FROM bukti_laporan WHERE id_laporan = ?";
$stmt_bukti = $conn->prepare($query_bukti);
$stmt_bukti->bind_param("i", $id_laporan);
$stmt_bukti->execute();
$bukti = $stmt_bukti->get_result();

// Ambil tindak lanjut jika status laporan bukan "Menunggu"
$tindak_lanjut = null;
if ($laporan['status_laporan'] !== 'Menunggu') {
    $query_tl = "SELECT 
                    tl.*,
                    gb.nama_guru as nama_pelaksana
                 FROM tindak_lanjut tl
                 LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
                 WHERE tl.id_laporan = ?
                 ORDER BY tl.tanggal_tindakan DESC";

    if ($stmt_tl = $conn->prepare($query_tl)) {
        $stmt_tl->bind_param("i", $id_laporan);
        if ($stmt_tl->execute()) {
            $result_tl = $stmt_tl->get_result();
            if ($result_tl && $result_tl->num_rows > 0) {
                $tindak_lanjut = $result_tl;
            }
        }
    }
}
?>

<!-- TAMPILAN HTML -->
<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Informasi Laporan</h5>
        <!-- Isi data laporan seperti status, pelapor, pelaku, dsb -->
        <div class="mb-3">
            <label class="form-label text-muted">Status Laporan</label>
            <p>
                <span class="status-badge status-<?php echo strtolower($laporan['status_laporan']); ?>">
                    <?php echo $laporan['status_laporan']; ?>
                </span>
            </p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Pelapor</label>
            <p><?php echo htmlspecialchars($laporan['nama_pelapor']); ?> (<?php echo htmlspecialchars($laporan['kelas_pelapor']); ?>)</p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Pelaku</label>
            <p><?php echo htmlspecialchars($laporan['nama_pelaku']); ?> (<?php echo htmlspecialchars($laporan['kelas_pelaku']); ?>)</p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Jenis Bullying</label>
            <p><span class="badge bg-secondary"><?php echo $laporan['jenis_bullying']; ?></span></p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Tingkat Bullying</label>
            <p>
                <span class="badge <?php 
                    echo $laporan['tingkat_bullying'] == 'Ringan' ? 'bg-success' : 
                        ($laporan['tingkat_bullying'] == 'Sedang' ? 'bg-warning' : 'bg-danger'); 
                ?>">
                    <?php echo $laporan['tingkat_bullying']; ?>
                </span>
            </p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Tanggal Kejadian</label>
            <p>
                <?php echo format_tanggal($laporan['tanggal_kejadian']); ?>
                <?php if ($laporan['waktu_kejadian']): ?>
                    pukul <?php echo format_waktu($laporan['waktu_kejadian']); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Lokasi Kejadian</label>
            <p><?php echo htmlspecialchars($laporan['lokasi_kejadian']); ?></p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Saksi (jika ada)</label>
            <p><?php echo $laporan['saksi'] ? htmlspecialchars($laporan['saksi']) : '-'; ?></p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Guru Penangani</label>
            <p><?php echo $laporan['guru_penangani'] ? htmlspecialchars($laporan['guru_penangani']) : '-'; ?></p>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted">Catatan Guru</label>
            <p><?php echo $laporan['catatan_guru'] ? htmlspecialchars($laporan['catatan_guru']) : '-'; ?></p>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Deskripsi Kejadian</h5>
        <div class="border p-3 rounded bg-light mb-4">
            <?php echo nl2br(htmlspecialchars($laporan['deskripsi_kejadian'])); ?>
        </div>

        <!-- Bukti Laporan -->
        <?php if ($bukti && $bukti->num_rows > 0): ?>
        <h5 class="mb-3">Bukti Laporan</h5>
        <div class="row g-2 mb-4">
            <?php while ($item = $bukti->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <?php if ($item['jenis_bukti'] == 'Foto'): ?>
                        <img src="../assets/uploads/bukti/foto/<?php echo $item['nama_file']; ?>" 
                             class="img-fluid detail-modal-img" 
                             alt="Bukti Foto">
                    <?php elseif ($item['jenis_bukti'] == 'Video'): ?>
                        <video controls class="w-100">
                            <source src="../assets/uploads/bukti/video/<?php echo $item['nama_file']; ?>">
                        </video>
                    <?php elseif ($item['jenis_bukti'] == 'Audio'): ?>
                        <audio controls class="w-100">
                            <source src="../assets/uploads/bukti/audio/<?php echo $item['nama_file']; ?>">
                        </audio>
                    <?php else: ?>
                        <div class="text-center py-2">
                            <i class="fas fa-file-alt fa-3x mb-2"></i>
                            <p class="mb-0"><?php echo $item['nama_file']; ?></p>
                            <a href="../assets/uploads/bukti/dokumen/<?php echo $item['nama_file']; ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-download me-1"></i>Unduh
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($item['keterangan']): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($item['keterangan']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- Tindak Lanjut -->
        <?php if ($tindak_lanjut && $tindak_lanjut->num_rows > 0): ?>
        <h5 class="mb-3">Tindak Lanjut</h5>
        <div class="timeline">
            <?php while ($tl = $tindak_lanjut->fetch_assoc()): ?>
            <div class="timeline-item mb-4">
                <div class="timeline-marker bg-<?php 
                    echo $tl['status_tindakan'] == 'Selesai' ? 'success' : 
                        ($tl['status_tindakan'] == 'Dilaksanakan' ? 'primary' : 'warning'); 
                ?>"></div>
                <div class="timeline-content">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($tl['jenis_tindakan']); ?></h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($tl['deskripsi_tindakan'])); ?></p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?php echo format_tanggal($tl['tanggal_tindakan']); ?></small>
                                <span class="badge bg-<?php 
                                    echo $tl['status_tindakan'] == 'Selesai' ? 'success' : 
                                        ($tl['status_tindakan'] == 'Dilaksanakan' ? 'primary' : 'warning'); 
                                ?>"><?php echo $tl['status_tindakan']; ?></span>
                            </div>
                            <?php if ($tl['nama_pelaksana']): ?>
                                <small class="text-muted d-block mt-1"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($tl['nama_pelaksana']); ?></small>
                            <?php endif; ?>
                            <?php if ($tl['hasil_tindakan']): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted d-block">Hasil:</small>
                                    <?php echo nl2br(htmlspecialchars($tl['hasil_tindakan'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
