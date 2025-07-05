<?php
// config/koneksi.php
session_start();

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bully_management');

define('SITE_URL', 'http://localhost/bully_management/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Membuat koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset untuk menghindari masalah encoding
$conn->set_charset("utf8");

// Fungsi untuk membersihkan input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi untuk cek login siswa
function cek_login_siswa() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'siswa') {
        redirect('../../auth/login/login_siswa.php');
    }
}

// Fungsi untuk cek login guru BK
function cek_login_guru() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'guru_bk') {
        redirect('../../auth/login/login_gurubk.php');
    }
}
// Fungsi untuk logout
function logout() {
    session_unset();
    session_destroy();
    redirect(SITE_URL . 'index.php');
}

// Fungsi untuk menampilkan alert
function set_alert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function show_alert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $alertClass = '';
        $alertIcon = '';
        
        switch ($alert['type']) {
            case 'success':
                $alertClass = 'alert-success';
                $alertIcon = 'fas fa-check-circle';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                $alertIcon = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $alertIcon = 'fas fa-exclamation-triangle';
                break;
            case 'info':
                $alertClass = 'alert-info';
                $alertIcon = 'fas fa-info-circle';
                break;
        }
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo '<i class="' . $alertIcon . ' me-2"></i>';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        unset($_SESSION['alert']);
    }
}

// Fungsi untuk format tanggal Indonesia
function format_tanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari = date('j', $timestamp);
    $bulan_idx = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $hari . ' ' . $bulan[$bulan_idx] . ' ' . $tahun;
}

// Fungsi untuk format waktu
function format_waktu($time) {
    return date('H:i', strtotime($time));
}

// Fungsi untuk upload file
function upload_file($file, $folder, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    $target_dir = UPLOAD_PATH . $folder . '/';
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // Cek apakah file sudah dipilih
    if ($file['size'] == 0) {
        return ['success' => false, 'message' => 'Tidak ada file yang dipilih'];
    }
    
    // Cek ukuran file (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File terlalu besar (maksimal 5MB)'];
    }
    
    // Cek ekstensi file
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak diizinkan'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Fungsi untuk generate password hash
function generate_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fungsi untuk verifikasi password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Fungsi untuk generate NISN (untuk testing/demo)
function generate_nisn() {
    return str_pad(rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
}

// Fungsi untuk generate NIP (untuk testing/demo)
function generate_nip() {
    return date('Ymd') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Fungsi untuk validasi NISN
function validate_nisn($nisn) {
    return preg_match('/^[0-9]{10}$/', $nisn);
}

// Fungsi untuk validasi NIP
function validate_nip($nip) {
    return preg_match('/^[0-9]{18}$/', $nip);
}

// Fungsi untuk mendapatkan informasi user yang sedang login
function get_logged_in_user() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    if ($user_type == 'siswa') {
        $stmt = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM guru_bk WHERE id_guru = ?");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Fungsi untuk sanitize filename
function sanitize_filename($filename) {
    // Hapus karakter yang tidak diinginkan
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    // Batasi panjang filename
    if (strlen($filename) > 50) {
        $filename = substr($filename, 0, 50);
    }
    return $filename;
}

// Fungsi untuk cek hak akses
function check_permission($required_role) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $required_role) {
        set_alert('error', 'Anda tidak memiliki hak akses untuk halaman ini');
        redirect(SITE_URL . 'index.php');
    }
}

// Fungsi untuk log aktivitas (opsional)
function log_activity($user_id, $user_type, $activity, $description = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (user_id, user_type, aktivitas, deskripsi, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $user_type, $activity, $description);
        $stmt->execute();
        $stmt->close();
    }
}

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');
?>