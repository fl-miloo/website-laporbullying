<?php
require_once 'config/koneksi.php';

// Hapus semua data session
session_unset();
session_destroy();

// Hapus cookie remember me jika ada
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect ke halaman login
header('Location: index.php');
exit();
?>