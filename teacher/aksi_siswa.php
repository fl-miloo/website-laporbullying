<?php
require_once '../config/koneksi.php';
cek_login_guru();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
    
    if ($action == 'tambah') {
        // Validasi input
        $errors = [];
        
        $nama_siswa = clean_input($_POST['nama_siswa']);
        $nisn = clean_input($_POST['nisn']);
        $kelas = clean_input($_POST['kelas']);
        $jenis_kelamin = clean_input($_POST['jenis_kelamin']);
        $alamat = clean_input($_POST['alamat']);
        $no_hp_ortu = clean_input($_POST['no_hp_ortu']);
        $password = $_POST['password'];
        $status = clean_input($_POST['status']);
        
        if (empty($nama_siswa)) $errors['nama_siswa'] = "Nama siswa harus diisi";
        if (empty($nisn)) $errors['nisn'] = "NISN harus diisi";
        if (empty($kelas)) $errors['kelas'] = "Kelas harus diisi";
        if (empty($no_hp_ortu)) $errors['no_hp_ortu'] = "No. HP orang tua harus diisi";
        if (empty($password)) $errors['password'] = "Password harus diisi";
        
        // Cek NISN sudah ada atau belum
        $check = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ?");
        $check->bind_param("s", $nisn);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors['nisn'] = "NISN sudah terdaftar";
        }
        
        // Upload foto jika ada
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_file($_FILES['foto'], 'foto_siswa', ['jpg', 'jpeg', 'png']);
            
            if ($upload['success']) {
                $foto = $upload['filename'];
            } else {
                $errors['foto'] = $upload['message'];
            }
        }
        
        // Jika tidak ada error, simpan data
        if (empty($errors)) {
            $password_hash = generate_password_hash($password);
            
            $query = "INSERT INTO siswa (nisn, nama_siswa, kelas, jenis_kelamin, alamat, no_hp_ortu, password, foto, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssss", $nisn, $nama_siswa, $kelas, $jenis_kelamin, $alamat, $no_hp_ortu, $password_hash, $foto, $status);
            
            if ($stmt->execute()) {
                set_alert('success', 'Data siswa berhasil ditambahkan');
            } else {
                set_alert('error', 'Gagal menambahkan data siswa: ' . $conn->error);
            }
        } else {
            // Simpan error dalam session untuk ditampilkan kembali
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
        }
        
        header("Location: kelola_siswa.php");
        exit();
        
    } elseif ($action == 'edit') {
        $id_siswa = (int)$_POST['id_siswa'];
        $nama_siswa = clean_input($_POST['nama_siswa']);
        $nisn = clean_input($_POST['nisn']);
        $kelas = clean_input($_POST['kelas']);
        $jenis_kelamin = clean_input($_POST['jenis_kelamin']);
        $alamat = clean_input($_POST['alamat']);
        $no_hp_ortu = clean_input($_POST['no_hp_ortu']);
        $password = $_POST['password'];
        $status = clean_input($_POST['status']);
        
        // Ambil data siswa untuk mendapatkan foto lama
        $query_siswa = "SELECT foto FROM siswa WHERE id_siswa = ?";
        $stmt_siswa = $conn->prepare($query_siswa);
        $stmt_siswa->bind_param("i", $id_siswa);
        $stmt_siswa->execute();
        $result_siswa = $stmt_siswa->get_result();
        $siswa = $result_siswa->fetch_assoc();
        $foto = $siswa['foto'];
        
        // Upload foto baru jika ada
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_file($_FILES['foto'], 'foto_siswa', ['jpg', 'jpeg', 'png']);
            
            if ($upload['success']) {
                // Hapus foto lama jika ada
                if (!empty($foto)) {
                    $old_file = UPLOAD_PATH . 'foto_siswa/' . $foto;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $foto = $upload['filename'];
            }
        }
        
        // Update data
        if (!empty($password)) {
            $password_hash = generate_password_hash($password);
            $query = "UPDATE siswa SET 
                     nisn = ?, nama_siswa = ?, kelas = ?, jenis_kelamin = ?, 
                     alamat = ?, no_hp_ortu = ?, password = ?, foto = ?, status = ?
                     WHERE id_siswa = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssssi", $nisn, $nama_siswa, $kelas, $jenis_kelamin, 
                             $alamat, $no_hp_ortu, $password_hash, $foto, $status, $id_siswa);
        } else {
            $query = "UPDATE siswa SET 
                     nisn = ?, nama_siswa = ?, kelas = ?, jenis_kelamin = ?, 
                     alamat = ?, no_hp_ortu = ?, foto = ?, status = ?
                     WHERE id_siswa = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssssi", $nisn, $nama_siswa, $kelas, $jenis_kelamin, 
                             $alamat, $no_hp_ortu, $foto, $status, $id_siswa);
        }
        
        if ($stmt->execute()) {
            set_alert('success', 'Data siswa berhasil diperbarui');
        } else {
            set_alert('error', 'Gagal memperbarui data siswa: ' . $conn->error);
        }
        
        header("Location: kelola_siswa.php");
        exit();
        
    } elseif ($action == 'hapus') {
        $id_siswa = (int)$_POST['id_siswa'];
        
        // Ambil data siswa untuk menghapus foto
        $query_siswa = "SELECT foto FROM siswa WHERE id_siswa = ?";
        $stmt_siswa = $conn->prepare($query_siswa);
        $stmt_siswa->bind_param("i", $id_siswa);
        $stmt_siswa->execute();
        $result_siswa = $stmt_siswa->get_result();
        $siswa = $result_siswa->fetch_assoc();
        
        // Hapus foto jika ada
        if (!empty($siswa['foto'])) {
            $file_path = UPLOAD_PATH . 'foto_siswa/' . $siswa['foto'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Hapus data siswa
        $query = "DELETE FROM siswa WHERE id_siswa = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_siswa);
        
        if ($stmt->execute()) {
            set_alert('success', 'Data siswa berhasil dihapus');
        } else {
            set_alert('error', 'Gagal menghapus data siswa: ' . $conn->error);
        }
        
        header("Location: kelola_siswa.php");
        exit();
    }
}

header("Location: kelola_siswa.php");
exit();