<?php
require_once 'config/koneksi.php';

// Jika sudah login, redirect ke dashboard masing-masing
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'siswa') {
        header('Location: student/dashboard.php');
        exit;
    } elseif ($_SESSION['user_type'] == 'guru_bk') {
        header('Location: teacher/dashboard.php');
        exit;
    }
}

// Fungsi untuk mendapatkan statistik dengan error handling
function getStatisticSafely($conn, $query, $default = 0) {
    try {
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return $row['total'];
        }
        return $default;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return $default;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sistem Pelaporan Bullying - SMA Negeri 1</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Education Section */
.education-section {
    padding: 80px 0;
    background: #fff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
}

.education-section .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.education-section h1 {
    font-size: 2.8rem;
    color: #1e3c72;
    text-align: center;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.6rem;
}

.education-section h1 i {
    color: #2196f3;
    font-size: 2.8rem;
}

.education-section p {
    font-size: 1.25rem;
    text-align: center;
    color: #555;
    margin-bottom: 3rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

/* Education Content Wrapper */
.education-content {
    display: flex;
    flex-direction: column;
    gap: 3.5rem;
}

/* Card Base Style */
.card {
    background: #f8f9fa;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    padding: 2rem;
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 12px 28px rgba(0,0,0,0.15);
}

/* Card Header */
.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.card-icon {
    background: #2196f3;
    color: white;
    padding: 0.8rem 1rem;
    border-radius: 50%;
    font-size: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.4);
}

.card-header h2 {
    font-size: 1.8rem;
    color: #1e3c72;
    margin: 0;
}

/* Definition Card */
.definition p {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.definition > div {
    background: #fff5f5;
    padding: 1rem 1.2rem;
    border-radius: 8px;
    border-left: 5px solid #e53e3e;
}

.definition strong {
    color: #c53030;
}

.definition ul {
    margin-top: 0.5rem;
    padding-left: 1.6rem;
    color: #555;
    line-height: 1.5;
}

/* Types of Bullying */
.bullying-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.8rem;
}

.type-item {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transition: transform 0.3s ease;
    cursor: default;
}

.type-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

.type-item h3 {
    font-size: 1.25rem;
    margin-bottom: 0.7rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #1e3c72;
}

.type-item h3 i {
    color: #2196f3;
    font-size: 1.3rem;
}

.type-item p {
    font-size: 1rem;
    color: #444;
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.type-item > div {
    font-size: 0.9rem;
    color: #666;
}

/* Levels Grid */
.levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.level-item {
    background: white;
    border-radius: 10px;
    padding: 1.8rem 1.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.07);
    text-align: center;
    transition: transform 0.3s ease;
}

.level-item:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 30px rgba(0,0,0,0.12);
}

.level-number {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 1.5rem;
    line-height: 45px;
    color: white;
    margin: 0 auto 1rem auto;
    user-select: none;
}

.level-light .level-number {
    background-color: #68d391; /* hijau muda */
}

.level-medium .level-number {
    background-color: #f6ad55; /* oranye */
}

.level-heavy .level-number {
    background-color: #e53e3e; /* merah */
}

.level-item h3 {
    font-size: 1.3rem;
    margin-bottom: 0.7rem;
    color: #1e3c72;
}

.level-item p {
    font-size: 1rem;
    color: #555;
    line-height: 1.5;
}

/* Signs List */
.signs-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.3rem;
}

.sign-item {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    background: white;
    border-radius: 10px;
    padding: 1rem 1.2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    font-size: 1rem;
    color: #444;
    transition: background-color 0.3s ease;
    cursor: default;
}

.sign-item i {
    color: #2196f3;
    font-size: 1.5rem;
}

/* Help Steps */
.help-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.7rem;
}

.step-item {
    background: white;
    border-radius: 10px;
    padding: 1.7rem 1.5rem;
    box-shadow: 0 5px 16px rgba(0,0,0,0.08);
    text-align: center;
    transition: transform 0.3s ease;
}

.step-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 28px rgba(0,0,0,0.12);
}

.step-number {
    width: 42px;
    height: 42px;
    background: #2196f3;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    line-height: 42px;
    border-radius: 50%;
    margin: 0 auto 1rem auto;
    user-select: none;
}

.step-item h3 {
    font-size: 1.3rem;
    color: #1e3c72;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
}

.step-item h3 i {
    font-size: 1.2rem;
    color: #2196f3;
}

.step-item p {
    font-size: 1rem;
    color: #555;
    line-height: 1.5;
}

/* Reminder Box */
.education-section > .container > .education-content > .help > div:last-child {
    background: #e8f5e8;
    padding: 1.5rem 1.8rem;
    border-radius: 10px;
    margin-top: 2.5rem;
}

.education-section > .container > .education-content > .help > div:last-child h3 {
    color: #2d5e2d;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.5rem;
}

.education-section > .container > .education-content > .help > div:last-child h3 i {
    color: #2d5e2d;
    font-size: 1.5rem;
}

.education-section > .container > .education-content > .help > div:last-child ul {
    padding-left: 1.6rem;
    color: #2d5e2d;
    line-height: 1.5;
    font-weight: 600;
}

.education-section > .container > .education-content > .help > div:last-child ul li {
    margin-bottom: 0.5rem;
}

/* Responsive Adjustments */
@media (max-width: 1024px) {
    .bullying-types, .levels-grid, .signs-list, .help-steps {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
}

@media (max-width: 600px) {
    .education-section h1 {
        font-size: 2rem;
    }
    .education-section h1 i {
        font-size: 2rem;
    }
    .education-section p {
        font-size: 1.1rem;
        max-width: 100%;
    }
}

    </style>
</head>
<body>
    <div class="landing-page">
        <!-- Header -->
        <header class="header">
            <nav class="navbar">
                <div class="nav-brand">
                    <i class="fas fa-shield-alt"></i>
                    <span>Anti Bullying System</span>
                </div>
                <div class="nav-menu">
                    <a href="#home" class="nav-link">Beranda</a>
                    <a href="#about" class="nav-link">Tentang</a>
                    <a href="#education" class="nav-link">Edukasi</a>
                    <a href="#contact" class="nav-link">Kontak</a>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section id="home" class="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Sistem Pelaporan Bullying</h1>
                    <p>Platform digital untuk melaporkan dan menangani kasus bullying di sekolah dengan aman dan terpercaya</p>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <h3>
                                <?php 
                                    echo getStatisticSafely($conn, "SELECT COUNT(*) AS total FROM siswa WHERE status = 'Aktif'", 0);
                                ?>
                            </h3>
                            <p>Siswa Terdaftar</p>
                        </div>
                        <div class="stat-item">
                            <h3>
                                <?php 
                                    echo getStatisticSafely($conn, "SELECT COUNT(*) AS total FROM laporan_bullying", 0);
                                ?>
                            </h3>
                            <p>Laporan Ditangani</p>
                        </div>
                        <div class="stat-item">
                            <h3>
                                <?php 
                                    echo getStatisticSafely($conn, "SELECT COUNT(*) AS total FROM guru_bk", 0);
                                ?>
                            </h3>
                            <p>Konselor BK</p>
                        </div>
                    </div>
                </div>
                <div class="hero-login">
                    <div class="login-card">
                        <h2>Masuk ke Sistem</h2>
                        <div class="login-options">
                            <a href="auth/login/login_siswa.php" class="login-btn student">
                                <i class="fas fa-user-graduate"></i>
                                <span>Login Siswa</span>
                            </a>
                            <a href="auth/login/login_gurubk.php" class="login-btn teacher">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Login Guru BK</span>
                            </a>
                        </div>
                        <div class="register-links">
                            <p>Belum punya akun?</p>
                            <a href="auth/registrasi/registrasi_siswa.php">Daftar Siswa</a> | 
                            <a href="auth/registrasi/registrasi_gurubk.php">Daftar Guru BK</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="about">
            <div class="container">
                <h2>Tentang Sistem Anti Bullying</h2>
                <div class="about-grid">
                    <div class="about-item">
                        <i class="fas fa-bullhorn"></i>
                        <h3>Pelaporan Mudah</h3>
                        <p>Siswa dapat melaporkan kasus bullying dengan mudah dan aman melalui platform digital</p>
                    </div>
                    <div class="about-item">
                        <i class="fas fa-user-tie"></i>
                        <h3>Penanganan Profesional</h3>
                        <p>Guru BK menangani setiap laporan dengan profesional dan tindak lanjut yang tepat</p>
                    </div>
                    <div class="about-item">
                        <i class="fas fa-chart-line"></i>
                        <h3>Statistik Real-time</h3>
                        <p>Dashboard dengan statistik dan grafik untuk monitoring kasus bullying</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Education Section -->
        <section id="education" class="education-section">
            <div class="container">
                <h1><i class="fas fa-graduation-cap"></i> Edukasi Bullying</h1>
                <p>Pahami, Kenali, dan Lawan Bullying Bersama-sama</p>

                <div class="education-content">
                    <!-- Definisi Bullying -->
                    <div class="card definition">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h2>Apa itu Bullying?</h2>
                        </div>
                        <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                            <strong>Bullying</strong> adalah perilaku agresif yang dilakukan secara berulang-ulang oleh seseorang atau sekelompok orang terhadap orang lain yang dianggap lebih lemah. Bullying melibatkan ketidakseimbangan kekuatan, baik fisik, psikologis, maupun sosial.
                        </p>
                        <div style="background: #fff5f5; padding: 1rem; border-radius: 8px; border-left: 4px solid #e53e3e;">
                            <strong>Ciri-ciri Bullying:</strong>
                            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                <li>Dilakukan secara berulang-ulang</li>
                                <li>Ada ketidakseimbangan kekuatan</li>
                                <li>Bersifat merugikan korban</li>
                                <li>Dilakukan secara sengaja</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Jenis-jenis Bullying -->
                    <div class="card types">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-list-ul"></i>
                            </div>
                            <h2>Jenis-jenis Bullying</h2>
                        </div>
                        <div class="bullying-types">
                            <div class="type-item physical">
                                <h3><i class="fas fa-fist-raised"></i> Bullying Fisik</h3>
                                <p>Kekerasan yang melibatkan kontak fisik seperti memukul, menendang, mendorong, merusak barang milik orang lain.</p>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                    <strong>Contoh:</strong> Memukul, menendang, mendorong, merusak tas/buku
                                </div>
                            </div>
                            
                            <div class="type-item verbal">
                                <h3><i class="fas fa-comment-slash"></i> Bullying Verbal</h3>
                                <p>Penggunaan kata-kata untuk menyakiti, mengancam, atau mempermalukan orang lain.</p>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                    <strong>Contoh:</strong> Mengejek, menghina, mengancam, menyebar rumor
                                </div>
                            </div>
                            
                            <div class="type-item social">
                                <h3><i class="fas fa-user-slash"></i> Bullying Sosial/Relasional</h3>
                                <p>Merusak hubungan sosial dan reputasi seseorang di lingkungan sosialnya.</p>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                    <strong>Contoh:</strong> Mengucilkan, menyebarkan gosip, mempermalukan di depan umum
                                    </div>
                                </div>
                                
                                <div class="type-item cyber">
                                    <h3><i class="fas fa-wifi"></i> Cyberbullying</h3>
                                    <p>Bullying yang dilakukan melalui media digital seperti media sosial, pesan teks, atau platform online.</p>
                                    <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                        <strong>Contoh:</strong> Mengirim pesan jahat, menyebarkan foto/video memalukan online
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tingkatan Bullying -->
                        <div class="card levels">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <h2>Tingkatan Bullying</h2>
                            </div>
                            <div class="levels-grid">
                                <div class="level-item level-light">
                                    <div class="level-number">1</div>
                                    <h3>Ringan</h3>
                                    <p>Korban merasa tersinggung, malu, atau tidak dihargai karena candaan kasar, ejekan ringan, atau pengabaian yang terjadi sesekali.</p>
                                </div>

                                <div class="level-item level-medium">
                                    <div class="level-number">2</div>
                                    <h3>Sedang</h3>
                                    <p>Korban mulai merasa tertekan secara emosional akibat ejekan berulang, pengucilan dari teman, atau ancaman verbal yang membuatnya takut.</p>
                                </div>

                                <div class="level-item level-heavy">
                                    <div class="level-number">3</div>
                                    <h3>Berat</h3>
                                    <p>Korban mengalami ketakutan mendalam, trauma, bahkan gangguan psikologis akibat kekerasan fisik, ancaman serius, atau serangan di media sosial.</p>
                                </div>
                            </div>
                        </div>


                        <!-- Tanda-tanda Korban Bullying -->
                        <div class="card signs">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h2>Tanda-tanda Seseorang Menjadi Korban Bullying</h2>
                            </div>
                            <div class="signs-list">
                                <div class="sign-item">
                                    <i class="fas fa-sad-tear"></i>
                                    <span>Perubahan perilaku mendadak</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-bed"></i>
                                    <span>Tidak mau pergi ke sekolah</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-utensils"></i>
                                    <span>Kehilangan nafsu makan</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-moon"></i>
                                    <span>Sulit tidur atau mimpi buruk</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-band-aid"></i>
                                    <span>Luka atau memar yang tidak jelas</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-backpack"></i>
                                    <span>Barang yang hilang atau rusak</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-user-friends"></i>
                                    <span>Kehilangan teman atau menarik diri</span>
                                </div>
                                <div class="sign-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Penurunan prestasi akademik</span>
                                </div>
                            </div>
                        </div>

                        <!-- Langkah Bantuan -->
                        <div class="card help">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <h2>Apa yang Harus Dilakukan?</h2>
                            </div>
                            <div class="help-steps">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <h3><i class="fas fa-eye"></i> Kenali</h3>
                                    <p>Kenali tanda-tanda bullying pada diri sendiri atau orang lain di sekitar kita</p>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <h3><i class="fas fa-file-alt"></i> Dokumentasi</h3>
                                    <p>Catat atau dokumentasikan kejadian bullying yang terjadi (waktu, tempat, pelaku)</p>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <h3><i class="fas fa-bullhorn"></i> Laporkan</h3>
                                    <p>Laporkan kejadian ke guru, orang tua, atau sistem pelaporan sekolah</p>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <h3><i class="fas fa-heart"></i> Dukung</h3>
                                    <p>Berikan dukungan moral kepada korban dan jangan menjadi penonton pasif</p>
                                </div>
                            </div>
                            
                            <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; margin-top: 2rem;">
                                <h3 style="color: #2d5e2d; margin-bottom: 1rem;">
                                    <i class="fas fa-lightbulb"></i> Ingat!
                                </h3>
                                <ul style="color: #2d5e2d; padding-left: 1.5rem;">
                                    <li><strong>Bullying bukan salah korban!</strong> Tidak ada alasan yang membenarkan bullying</li>
                                    <li><strong>Kamu tidak sendirian.</strong> Selalu ada orang yang siap membantu</li>
                                    <li><strong>Berani melaporkan bukan pengaduan.</strong> Ini adalah langkah berani untuk keselamatan</li>
                                    <li><strong>Setiap orang berhak merasa aman</strong> di lingkungan sekolah</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Contact Section -->
        <section id="contact" class="contact">
            <div class="container">
                <h2>Kontak Sekolah</h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>JL. TANJUNG PESONA LINGKUNGAN RAMBAK</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <p>(0717) 9104986</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <p>infosmpn6sungailiat@gmail.com</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>&copy; 2025 fatma lestari - Sistem Pelaporan Bullying. All rights reserved.</p>
            </div>
        </footer>
    </div>

  <script>
        // Animasi scroll
        window.addEventListener('scroll', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const cardVisible = 150;
                
                if (cardTop < window.innerHeight - cardVisible) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                } else {
                    card.style.opacity = '0.5';
                    card.style.transform = 'translateY(50px)';
                }
            });
        });

        // Efek hover interaktif
        document.querySelectorAll('.type-item, .level-item, .step-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
