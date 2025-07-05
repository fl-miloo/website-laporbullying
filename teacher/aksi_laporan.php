<?php
require_once '../config/koneksi.php';
cek_login_guru();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
    
    if ($action == 'tambah') {
        // Validasi input
        $errors = [];
        
        $id_siswa_pelapor = (int)$_POST['id_siswa_pelapor'];
        $id_siswa_pelaku = !empty($_POST['id_siswa_pelaku']) ? (int)$_POST['id_siswa_pelaku'] : null;
        $nama_pelaku = clean_input($_POST['nama_pelaku']);
        $kelas_pelaku = clean_input($_POST['kelas_pelaku']);
        $jenis_bullying = clean_input($_POST['jenis_bullying']);
        $tingkat_bullying = clean_input($_POST['tingkat_bullying']);
        $deskripsi_kejadian = clean_input($_POST['deskripsi_kejadian']);
        $lokasi_kejadian = clean_input($_POST['lokasi_kejadian']);
        $tanggal_kejadian = clean_input($_POST['tanggal_kejadian']);
        $waktu_kejadian = !empty($_POST['waktu_kejadian']) ? clean_input($_POST['waktu_kejadian']) : null;
        $saksi = !empty($_POST['saksi']) ? clean_input($_POST['saksi']) : null;
        
        if (empty($id_siswa_pelapor)) $errors['id_siswa_pelapor'] = "Pelapor harus dipilih";
        if (empty($nama_pelaku)) $errors['nama_pelaku'] = "Nama pelaku harus diisi";
        if (empty($kelas_pelaku)) $errors['kelas_pelaku'] = "Kelas pelaku harus diisi";
        if (empty($deskripsi_kejadian)) $errors['deskripsi_kejadian'] = "Deskripsi kejadian harus diisi";
        if (empty($lokasi_kejadian)) $errors['lokasi_kejadian'] = "Lokasi kejadian harus diisi";
        if (empty($tanggal_kejadian)) $errors['tanggal_kejadian'] = "Tanggal kejadian harus diisi";
        
        // Jika tidak ada error, simpan data
        if (empty($errors)) {
            $id_guru_penangani = $_SESSION['user_id'];
            
            $query = "INSERT INTO laporan_bullying (
                id_siswa_pelapor, id_siswa_pelaku, nama_pelaku, kelas_pelaku, 
                jenis_bullying, tingkat_bullying, deskripsi_kejadian, 
                lokasi_kejadian, tanggal_kejadian, waktu_kejadian, saksi,
                id_guru_penangani
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "iisssssssssi", 
                $id_siswa_pelapor, $id_siswa_pelaku, $nama_pelaku, $kelas_pelaku,
                $jenis_bullying, $tingkat_bullying, $deskripsi_kejadian,
                $lokasi_kejadian, $tanggal_kejadian, $waktu_kejadian, $saksi,
                $id_guru_penangani
            );
            
            if ($stmt->execute()) {
                $id_laporan = $stmt->insert_id;
                
                // Update pelaku frequency melalui trigger
                set_alert('success', 'Laporan bullying berhasil ditambahkan');
                header("Location: detail_laporan.php?id=$id_laporan");
                exit();
            } else {
                set_alert('error', 'Gagal menambahkan laporan: ' . $conn->error);
            }
        } else {
            // Simpan error dalam session untuk ditampilkan kembali
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
        }
        
        header("Location: kelola_laporan.php");
        exit();
        
    } elseif ($action == 'edit') {
        $id_laporan = (int)$_POST['id_laporan'];
        $nama_pelaku = clean_input($_POST['nama_pelaku']);
        $kelas_pelaku = clean_input($_POST['kelas_pelaku']);
        $jenis_bullying = clean_input($_POST['jenis_bullying']);
        $tingkat_bullying = clean_input($_POST['tingkat_bullying']);
        $deskripsi_kejadian = clean_input($_POST['deskripsi_kejadian']);
        $lokasi_kejadian = clean_input($_POST['lokasi_kejadian']);
        $tanggal_kejadian = clean_input($_POST['tanggal_kejadian']);
        $waktu_kejadian = !empty($_POST['waktu_kejadian']) ? clean_input($_POST['waktu_kejadian']) : null;
        $status_laporan = clean_input($_POST['status_laporan']);
        $catatan_guru = !empty($_POST['catatan_guru']) ? clean_input($_POST['catatan_guru']) : null;
        
        // Update data
        $query = "UPDATE laporan_bullying SET 
                 nama_pelaku = ?, kelas_pelaku = ?, jenis_bullying = ?, 
                 tingkat_bullying = ?, deskripsi_kejadian = ?, lokasi_kejadian = ?, 
                 tanggal_kejadian = ?, waktu_kejadian = ?, status_laporan = ?, 
                 catatan_guru = ?
                 WHERE id_laporan = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssi", 
            $nama_pelaku, $kelas_pelaku, $jenis_bullying, 
            $tingkat_bullying, $deskripsi_kejadian, $lokasi_kejadian,
            $tanggal_kejadian, $waktu_kejadian, $status_laporan,
            $catatan_guru, $id_laporan
        );
        
        if ($stmt->execute()) {
            set_alert('success', 'Laporan bullying berhasil diperbarui');
        } else {
            set_alert('error', 'Gagal memperbarui laporan: ' . $conn->error);
        }
        
        header("Location: detail_laporan.php?id=$id_laporan");
        exit();
        
    } elseif ($action == 'hapus') {
        $id_laporan = (int)$_POST['id_laporan'];
        
        // Hapus bukti laporan terlebih dahulu
        $query_bukti = "DELETE FROM bukti_laporan WHERE id_laporan = ?";
        $stmt_bukti = $conn->prepare($query_bukti);
        $stmt_bukti->bind_param("i", $id_laporan);
        $stmt_bukti->execute();
        
        // Hapus tindak lanjut
        $query_tindak = "DELETE FROM tindak_lanjut WHERE id_laporan = ?";
        $stmt_tindak = $conn->prepare($query_tindak);
        $stmt_tindak->bind_param("i", $id_laporan);
        $stmt_tindak->execute();
        
        // Hapus laporan
        $query = "DELETE FROM laporan_bullying WHERE id_laporan = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_laporan);
        
        if ($stmt->execute()) {
            set_alert('success', 'Laporan bullying berhasil dihapus');
        } else {
            set_alert('error', 'Gagal menghapus laporan: ' . $conn->error);
        }
        
        header("Location: kelola_laporan.php");
        exit();
    }
}

header("Location: kelola_laporan.php");
exit();