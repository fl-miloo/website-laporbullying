<?php
require_once '../config/koneksi.php';
cek_login_guru();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
    
    if ($action == 'tambah') {
        $id_laporan = (int)$_POST['id_laporan'];
        $jenis_bukti = clean_input($_POST['jenis_bukti']);
        $keterangan = !empty($_POST['keterangan']) ? clean_input($_POST['keterangan']) : null;
        
        // Validasi file
        if (empty($_FILES['file_bukti']['name'])) {
            set_alert('error', 'File bukti harus diupload');
            header("Location: detail_laporan.php?id=$id_laporan");
            exit();
        }
        
        // Tentukan folder upload berdasarkan jenis bukti
        $folder = '';
        switch ($jenis_bukti) {
            case 'Foto': $folder = 'foto'; break;
            case 'Video': $folder = 'video'; break;
            case 'Audio': $folder = 'audio'; break;
            case 'Dokumen': $folder = 'dokumen'; break;
            default: $folder = 'lainnya';
        }
        
        $upload = upload_file($_FILES['file_bukti'], "bukti/$folder", ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'mp3', 'wav', 'pdf', 'doc', 'docx']);
        
        if ($upload['success']) {
            $nama_file = $upload['filename'];
            
            $query = "INSERT INTO bukti_laporan (id_laporan, jenis_bukti, nama_file, keterangan) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $id_laporan, $jenis_bukti, $nama_file, $keterangan);
            
            if ($stmt->execute()) {
                set_alert('success', 'Bukti laporan berhasil ditambahkan');
            } else {
                // Hapus file yang sudah diupload jika gagal menyimpan ke database
                $file_path = UPLOAD_PATH . "bukti/$folder/" . $nama_file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                set_alert('error', 'Gagal menambahkan bukti laporan: ' . $conn->error);
            }
        } else {
            set_alert('error', $upload['message']);
        }
        
        header("Location: detail_laporan.php?id=$id_laporan");
        exit();
        
    } elseif ($action == 'hapus') {
        $id_bukti = (int)$_POST['id_bukti'];
        $id_laporan = (int)$_POST['id_laporan'];
        
        // Ambil data bukti untuk menghapus file
        $query_bukti = "SELECT jenis_bukti, nama_file FROM bukti_laporan WHERE id_bukti = ?";
        $stmt_bukti = $conn->prepare($query_bukti);
        $stmt_bukti->bind_param("i", $id_bukti);
        $stmt_bukti->execute();
        $result_bukti = $stmt_bukti->get_result();
        $bukti = $result_bukti->fetch_assoc();
        
        // Hapus file
        $folder = '';
        switch ($bukti['jenis_bukti']) {
            case 'Foto': $folder = 'foto'; break;
            case 'Video': $folder = 'video'; break;
            case 'Audio': $folder = 'audio'; break;
            case 'Dokumen': $folder = 'dokumen'; break;
            default: $folder = 'lainnya';
        }
        
        $file_path = UPLOAD_PATH . "bukti/$folder/" . $bukti['nama_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Hapus data dari database
        $query = "DELETE FROM bukti_laporan WHERE id_bukti = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_bukti);
        
        if ($stmt->execute()) {
            set_alert('success', 'Bukti laporan berhasil dihapus');
        } else {
            set_alert('error', 'Gagal menghapus bukti laporan: ' . $conn->error);
        }
        
        header("Location: detail_laporan.php?id=$id_laporan");
        exit();
    }
}

header("Location: kelola_laporan.php");
exit();