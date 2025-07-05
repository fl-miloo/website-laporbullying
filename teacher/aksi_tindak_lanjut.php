<?php
require_once '../config/koneksi.php';
cek_login_guru();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
    
    if ($action == 'tambah') {
        $id_laporan = (int)$_POST['id_laporan'];
        $jenis_tindakan = clean_input($_POST['jenis_tindakan']);
        $deskripsi_tindakan = clean_input($_POST['deskripsi_tindakan']);
        $tanggal_tindakan = clean_input($_POST['tanggal_tindakan']);
        $status_tindakan = clean_input($_POST['status_tindakan']);
        $hasil_tindakan = !empty($_POST['hasil_tindakan']) ? clean_input($_POST['hasil_tindakan']) : null;
        $id_guru_pelaksana = $_SESSION['user_id'];
        
        // Validasi input
        $errors = [];
        
        if (empty($id_laporan)) $errors['id_laporan'] = "Laporan harus dipilih";
        if (empty($jenis_tindakan)) $errors['jenis_tindakan'] = "Jenis tindakan harus diisi";
        if (empty($deskripsi_tindakan)) $errors['deskripsi_tindakan'] = "Deskripsi tindakan harus diisi";
        if (empty($tanggal_tindakan)) $errors['tanggal_tindakan'] = "Tanggal tindakan harus diisi";
        
        // Jika tidak ada error, simpan data
        if (empty($errors)) {
            $query = "INSERT INTO tindak_lanjut (
                id_laporan, jenis_tindakan, deskripsi_tindakan, 
                tanggal_tindakan, id_guru_pelaksana, status_tindakan, 
                hasil_tindakan
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "isssiss", 
                $id_laporan, $jenis_tindakan, $deskripsi_tindakan,
                $tanggal_tindakan, $id_guru_pelaksana, $status_tindakan,
                $hasil_tindakan
            );
            
            if ($stmt->execute()) {
                $id_tindak_lanjut = $stmt->insert_id;
                
                // Update status laporan jika tindakan selesai
                if ($status_tindakan == 'Selesai') {
                    $query_update = "UPDATE laporan_bullying SET status_laporan = 'Selesai' WHERE id_laporan = ?";
                    $stmt_update = $conn->prepare($query_update);
                    $stmt_update->bind_param("i", $id_laporan);
                    $stmt_update->execute();
                }
                
                set_alert('success', 'Tindak lanjut berhasil ditambahkan');
                
                // Trigger akan membuat notifikasi untuk siswa
            } else {
                set_alert('error', 'Gagal menambahkan tindak lanjut: ' . $conn->error);
            }
        } else {
            // Simpan error dalam session untuk ditampilkan kembali
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
        }
        
        header("Location: tindak_lanjut.php");
        exit();
        
    } elseif ($action == 'edit') {
        $id_tindak_lanjut = (int)$_POST['id_tindak_lanjut'];
        $id_laporan = (int)$_POST['id_laporan'];
        $jenis_tindakan = clean_input($_POST['jenis_tindakan']);
        $deskripsi_tindakan = clean_input($_POST['deskripsi_tindakan']);
        $tanggal_tindakan = clean_input($_POST['tanggal_tindakan']);
        $status_tindakan = clean_input($_POST['status_tindakan']);
        $hasil_tindakan = !empty($_POST['hasil_tindakan']) ? clean_input($_POST['hasil_tindakan']) : null;
        
        // Update data
        $query = "UPDATE tindak_lanjut SET 
                 jenis_tindakan = ?, deskripsi_tindakan = ?, 
                 tanggal_tindakan = ?, status_tindakan = ?, 
                 hasil_tindakan = ?
                 WHERE id_tindak_lanjut = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssi", 
            $jenis_tindakan, $deskripsi_tindakan,
            $tanggal_tindakan, $status_tindakan,
            $hasil_tindakan, $id_tindak_lanjut
        );
        
        if ($stmt->execute()) {
            // Update status laporan jika tindakan selesai
            if ($status_tindakan == 'Selesai') {
                $query_update = "UPDATE laporan_bullying SET status_laporan = 'Selesai' WHERE id_laporan = ?";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bind_param("i", $id_laporan);
                $stmt_update->execute();
            }
            
            set_alert('success', 'Tindak lanjut berhasil diperbarui');
        } else {
            set_alert('error', 'Gagal memperbarui tindak lanjut: ' . $conn->error);
        }
        
        header("Location: tindak_lanjut.php");
        exit();
        
    } elseif ($action == 'hapus') {
        $id_tindak_lanjut = (int)$_POST['id_tindak_lanjut'];
        $id_laporan = (int)$_POST['id_laporan'];
        
        // Hapus data
        $query = "DELETE FROM tindak_lanjut WHERE id_tindak_lanjut = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_tindak_lanjut);
        
        if ($stmt->execute()) {
            set_alert('success', 'Tindak lanjut berhasil dihapus');
        } else {
            set_alert('error', 'Gagal menghapus tindak lanjut: ' . $conn->error);
        }
        
        header("Location: tindak_lanjut.php");
        exit();
    }
}

header("Location: tindak_lanjut.php");
exit();