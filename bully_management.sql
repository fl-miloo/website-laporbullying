-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 08:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bully_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetLaporanBulanan` (IN `bulan` INT, IN `tahun` INT)   BEGIN
    SELECT 
        lb.id_laporan,
        s.nama_siswa as nama_pelapor,
        s.nisn as nisn_pelapor,
        s.kelas as kelas_pelapor,
        lb.nama_pelaku,
        lb.kelas_pelaku,
        lb.jenis_bullying,
        lb.tingkat_bullying,
        lb.deskripsi_kejadian,
        lb.lokasi_kejadian,
        lb.tanggal_kejadian,
        lb.waktu_kejadian,
        lb.status_laporan,
        lb.created_at,
        gb.nama_guru as guru_penangani,
        lb.catatan_guru,
        tl.jenis_tindakan,
        tl.status_tindakan
    FROM laporan_bullying lb
    JOIN siswa s ON lb.id_siswa_pelapor = s.id_siswa
    LEFT JOIN guru_bk gb ON lb.id_guru_penangani = gb.id_guru
    LEFT JOIN tindak_lanjut tl ON lb.id_laporan = tl.id_laporan
    WHERE MONTH(lb.created_at) = bulan AND YEAR(lb.created_at) = tahun
    ORDER BY lb.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetNotifikasiSiswa` (IN `siswa_id` INT)   BEGIN
    SELECT 
        n.id_notifikasi,
        n.judul_notifikasi,
        n.pesan_notifikasi,
        n.tanggal_panggilan,
        n.waktu_panggilan,
        n.lokasi_panggilan,
        n.status_notifikasi,
        n.dibaca_pada,
        n.created_at,
        tl.jenis_tindakan,
        tl.status_tindakan,
        lb.jenis_bullying,
        gb.nama_guru
    FROM notifikasi n
    LEFT JOIN tindak_lanjut tl ON n.id_tindak_lanjut = tl.id_tindak_lanjut
    LEFT JOIN laporan_bullying lb ON tl.id_laporan = lb.id_laporan
    LEFT JOIN guru_bk gb ON tl.id_guru_pelaksana = gb.id_guru
    WHERE n.id_siswa = siswa_id
    ORDER BY n.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStatistikBulanan` (IN `bulan` INT, IN `tahun` INT)   BEGIN
    SELECT 
        COUNT(*) as total_laporan,
        SUM(CASE WHEN status_laporan = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status_laporan = 'Diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status_laporan = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status_laporan = 'Ditolak' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN jenis_bullying = 'Fisik' THEN 1 ELSE 0 END) as fisik,
        SUM(CASE WHEN jenis_bullying = 'Verbal' THEN 1 ELSE 0 END) as verbal,
        SUM(CASE WHEN jenis_bullying = 'Sosial' THEN 1 ELSE 0 END) as sosial,
        SUM(CASE WHEN jenis_bullying = 'Cyber' THEN 1 ELSE 0 END) as cyber,
        SUM(CASE WHEN tingkat_bullying = 'Ringan' THEN 1 ELSE 0 END) as ringan,
        SUM(CASE WHEN tingkat_bullying = 'Sedang' THEN 1 ELSE 0 END) as sedang,
        SUM(CASE WHEN tingkat_bullying = 'Berat' THEN 1 ELSE 0 END) as berat
    FROM laporan_bullying
    WHERE MONTH(created_at) = bulan AND YEAR(created_at) = tahun;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `search_siswa` (IN `nama_search` VARCHAR(100), IN `kelas_filter` VARCHAR(10))   BEGIN
    DECLARE search_pattern VARCHAR(102);
    SET search_pattern = CONCAT('%', nama_search, '%');
    
    SELECT 
        s.id_siswa,
        s.nisn,
        s.nama_siswa,
        s.kelas,
        s.jenis_kelamin,
        s.alamat,
        s.no_hp_ortu,
        s.status,
        s.created_at,
        CASE 
            WHEN s.nama_siswa = nama_search THEN 100
            WHEN s.nama_siswa LIKE CONCAT(nama_search, '%') THEN 90
            WHEN s.nama_siswa LIKE CONCAT('%', nama_search) THEN 80
            WHEN s.nama_siswa LIKE search_pattern THEN 70
            ELSE 60
        END as relevance_score,
        IFNULL(p.frekuensi, 0) as frekuensi_pelaku
    FROM siswa s
    LEFT JOIN pelaku p ON s.id_siswa = p.id_siswa
    WHERE s.status = 'Aktif' 
        AND s.nama_siswa LIKE search_pattern
        AND (kelas_filter = '' OR kelas_filter IS NULL OR s.kelas = kelas_filter)
    ORDER BY relevance_score DESC, frekuensi_pelaku DESC, s.nama_siswa
    LIMIT 20;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bukti_laporan`
--

CREATE TABLE `bukti_laporan` (
  `id_bukti` int(11) NOT NULL,
  `id_laporan` int(11) NOT NULL,
  `jenis_bukti` enum('Foto','Video','Audio','Dokumen') NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bukti_laporan`
--

INSERT INTO `bukti_laporan` (`id_bukti`, `id_laporan`, `jenis_bukti`, `nama_file`, `keterangan`, `created_at`) VALUES
(2, 2, 'Foto', '6853f6558f871.jpg', NULL, '2025-06-19 11:36:53'),
(4, 4, 'Video', '6863fe456d942.mp4', NULL, '2025-07-01 15:27:01'),
(5, 5, 'Video', '6864277e3b202.mp4', 'itu buktinya buk', '2025-07-01 18:22:54'),
(6, 6, 'Audio', '686434bc403c2.mp3', NULL, '2025-07-01 19:19:24'),
(7, 7, 'Foto', '68649069e1091.jpg', NULL, '2025-07-02 01:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `guru_bk`
--

CREATE TABLE `guru_bk` (
  `id_guru` int(11) NOT NULL,
  `nip` varchar(18) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru_bk`
--

INSERT INTO `guru_bk` (`id_guru`, `nip`, `nama_guru`, `username`, `password`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, '111111111111111111', 'ibu ma', 'admin', '$2y$10$YKRr6x1ASr3E.NbxphdGrOL8C8XjL7eZvHSQRk4XpPSagHRZvQI6e', '68513495b62cc.jpg', 'Aktif', '2025-06-17 09:25:41', '2025-06-17 09:25:41'),
(2, '666666666666666666', 'ibu harum', 'admin2', '$2y$10$aIW1Dzs0fZ5.DfEJQ3cum.R.agQxtnBhmYmgEFB.bAM8oFgIc9gh6', '686490b23a950.jpg', 'Aktif', '2025-07-02 01:51:46', '2025-07-02 01:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_bullying`
--

CREATE TABLE `jenis_bullying` (
  `id_jenis` int(11) NOT NULL,
  `nama_jenis` enum('Fisik','Verbal','Sosial','Cyber') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_bullying`
--

INSERT INTO `jenis_bullying` (`id_jenis`, `nama_jenis`, `deskripsi`, `created_at`) VALUES
(1, 'Fisik', 'Bullying fisik seperti memukul, menendang, merusak barang', '2025-05-30 09:13:09'),
(2, 'Verbal', 'Bullying verbal seperti menghina, mengancam, memaki', '2025-05-30 09:13:09'),
(3, 'Sosial', 'Bullying sosial seperti mengucilkan, menyebarkan gosip', '2025-05-30 09:13:09'),
(4, 'Cyber', 'Cyberbullying seperti pelecehan melalui media sosial', '2025-05-30 09:13:09');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_bullying`
--

CREATE TABLE `laporan_bullying` (
  `id_laporan` int(11) NOT NULL,
  `id_siswa_pelapor` int(11) NOT NULL,
  `nama_pelaku` varchar(100) NOT NULL,
  `kelas_pelaku` varchar(10) NOT NULL,
  `jenis_bullying` enum('Fisik','Verbal','Sosial','Cyber') NOT NULL,
  `tingkat_bullying` enum('Ringan','Sedang','Berat') NOT NULL,
  `deskripsi_kejadian` text NOT NULL,
  `lokasi_kejadian` varchar(255) NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `waktu_kejadian` time DEFAULT NULL,
  `saksi` varchar(255) DEFAULT NULL,
  `status_laporan` enum('Menunggu','Diproses','Selesai','Ditolak') DEFAULT 'Menunggu',
  `id_guru_penangani` int(11) DEFAULT NULL,
  `catatan_guru` text DEFAULT NULL,
  `tanggal_penanganan` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_siswa_pelaku` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_bullying`
--

INSERT INTO `laporan_bullying` (`id_laporan`, `id_siswa_pelapor`, `nama_pelaku`, `kelas_pelaku`, `jenis_bullying`, `tingkat_bullying`, `deskripsi_kejadian`, `lokasi_kejadian`, `tanggal_kejadian`, `waktu_kejadian`, `saksi`, `status_laporan`, `id_guru_penangani`, `catatan_guru`, `tanggal_penanganan`, `created_at`, `updated_at`, `id_siswa_pelaku`) VALUES
(1, 1, 'toni', 'VII A', 'Verbal', 'Ringan', 'jadi gini bukk..........', 'kantin', '2025-06-18', '12:05:00', 'teman dekat', 'Ditolak', NULL, 'gk adsa penjelasan', NULL, '2025-06-18 01:55:01', '2025-06-20 06:29:08', NULL),
(2, 3, 'abi', 'VIII A', 'Verbal', 'Ringan', 'saya tadi di kelas ngobrol dengan teman lalu abi tiba tiba tarik jilbab', 'di kelas', '2025-06-19', '00:00:00', 'adelia', 'Selesai', NULL, 'keruang ibu', NULL, '2025-06-19 11:36:53', '2025-06-28 07:43:44', NULL),
(3, 1, 'abi', 'VIII A', 'Fisik', 'Sedang', 'gini bukkk', 'kantin', '2025-06-28', '15:49:00', 'teman', 'Selesai', NULL, 'oke', NULL, '2025-06-28 07:50:03', '2025-07-01 18:37:00', NULL),
(4, 5, 'abi', 'VIII A', 'Verbal', 'Ringan', 'cem tu buk', 'kantin', '2025-07-01', '22:25:00', 'teman', 'Diproses', NULL, 'sabar', NULL, '2025-07-01 15:27:01', '2025-07-01 19:13:05', NULL),
(5, 1, 'abi', 'VIII A', 'Verbal', 'Ringan', 'gituu', 'kantin', '2025-07-03', '01:20:00', 'tidak ada', 'Diproses', 1, 'sabar', NULL, '2025-07-01 18:20:13', '2025-07-01 18:20:58', 6),
(6, 5, 'abi', 'VIII A', 'Verbal', 'Ringan', 'hemmm', 'kantin', '2025-07-02', '03:17:00', 'tidak ada', 'Diproses', NULL, 'cube', NULL, '2025-07-01 19:19:24', '2025-07-01 19:25:32', NULL),
(7, 7, 'abi', 'IX B', 'Fisik', 'Sedang', 'saya di jakmbak buk', 'kantin', '2025-07-04', '09:49:00', 'teman', 'Selesai', NULL, 'menunggu', NULL, '2025-07-02 01:50:33', '2025-07-02 02:02:05', NULL);

--
-- Triggers `laporan_bullying`
--
DELIMITER $$
CREATE TRIGGER `update_pelaku_frequency` AFTER INSERT ON `laporan_bullying` FOR EACH ROW BEGIN
    -- Update frekuensi berdasarkan nama pelaku
    INSERT INTO pelaku (nama_pelaku, id_siswa, frekuensi)
    VALUES (NEW.nama_pelaku, NEW.id_siswa_pelaku, 1)
    ON DUPLICATE KEY UPDATE 
        frekuensi = frekuensi + 1, 
        last_reported = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_tindak_lanjut` int(11) DEFAULT NULL,
  `judul_notifikasi` varchar(255) NOT NULL,
  `pesan_notifikasi` text NOT NULL,
  `tanggal_panggilan` date DEFAULT NULL,
  `waktu_panggilan` time DEFAULT NULL,
  `lokasi_panggilan` varchar(255) DEFAULT NULL,
  `status_notifikasi` enum('Pending','Dibaca','Selesai') DEFAULT 'Pending',
  `dibaca_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_siswa`, `id_tindak_lanjut`, `judul_notifikasi`, `pesan_notifikasi`, `tanggal_panggilan`, `waktu_panggilan`, `lokasi_panggilan`, `status_notifikasi`, `dibaca_pada`, `created_at`) VALUES
(1, 3, 1, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Verbal yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individual untuk menyelesaikan kasus ini.', '2025-06-19', '08:00:00', 'Ruang Bimbingan Konseling', 'Selesai', NULL, '2025-06-19 13:58:09'),
(2, 1, 2, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Fisik yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individual untuk menyelesaikan kasus ini.', '2025-07-01', '08:00:00', 'Ruang Bimbingan Konseling', 'Pending', NULL, '2025-07-01 12:57:37'),
(3, 1, 3, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Fisik yang pernah Anda laporkan. Guru BK akan melakukan memanggil orang tua untuk menyelesaikan kasus ini.', '2025-07-01', '08:00:00', 'Ruang Bimbingan Konseling', 'Selesai', NULL, '2025-07-01 13:00:04'),
(4, 5, 4, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Verbal yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individual untuk menyelesaikan kasus ini.', '2025-07-02', '08:00:00', 'Ruang Bimbingan Konseling', 'Pending', NULL, '2025-07-01 17:41:09'),
(5, 1, 5, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Verbal yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individu untuk menyelesaikan kasus ini.', '2025-07-02', '08:00:00', 'Ruang Bimbingan Konseling', 'Pending', NULL, '2025-07-01 18:21:35'),
(6, 5, 6, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Verbal yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individual untuk menyelesaikan kasus ini.', '2025-07-02', '08:00:00', 'Ruang Bimbingan Konseling', 'Pending', NULL, '2025-07-01 19:20:43'),
(7, 7, 7, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Fisik yang pernah Anda laporkan. Guru BK akan melakukan Konseling Individual untuk menyelesaikan kasus ini.', '2025-07-05', '08:00:00', 'Ruang Bimbingan Konseling', 'Selesai', NULL, '2025-07-02 01:54:52'),
(8, 7, 8, 'Panggilan Tindak Lanjut Kasus Bullying', 'Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus Fisik yang pernah Anda laporkan. Guru BK akan melakukan memanggil orang tua untuk menyelesaikan kasus ini.', '2025-07-05', '08:00:00', 'Ruang Bimbingan Konseling', 'Pending', NULL, '2025-07-02 01:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `pelaku`
--

CREATE TABLE `pelaku` (
  `id_pelaku` int(11) NOT NULL,
  `nama_pelaku` varchar(100) NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `frekuensi` int(11) DEFAULT 1,
  `last_reported` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelaku`
--

INSERT INTO `pelaku` (`id_pelaku`, `nama_pelaku`, `id_siswa`, `frekuensi`, `last_reported`) VALUES
(1, 'toni', NULL, 1, '2025-06-18 01:55:01'),
(2, 'abi', NULL, 1, '2025-06-19 11:36:53'),
(3, 'abi', NULL, 1, '2025-06-28 07:50:03'),
(4, 'abi', NULL, 1, '2025-07-01 15:27:01'),
(5, 'abi', 6, 1, '2025-07-01 18:20:13'),
(6, 'abi', NULL, 1, '2025-07-01 19:19:24'),
(7, 'abi', NULL, 1, '2025-07-02 01:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nisn` varchar(10) NOT NULL COMMENT 'Nomor Induk Siswa Nasional',
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp_ortu` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nisn`, `nama_siswa`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp_ortu`, `password`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, '1234567891', 'fatma', 'IX A', 'Perempuan', 'jl maju', '083157907980', '$2y$10$Ok1K/r8ghwG2lRHW/Am7PurjeeMCZ/57i7bqR1JHSWSOesK3yTPE.', '6852229c0f6c3.jpg', 'Aktif', '2025-06-17 06:19:49', '2025-06-18 02:21:16'),
(2, '2222222222', 'toni', 'VII A', 'Laki-laki', 'jl mundur', '08311543123', '$2y$10$APLqzsC9A3naUGGkQ4dla.ZZpQX5Qm4ybsplNAhiMUPtdP/1.S8jq', '685f9d636c65f.png', 'Aktif', '2025-06-17 06:32:34', '2025-06-28 07:44:35'),
(3, '1212121212', 'azza', 'VIII A', 'Perempuan', 'jl.nelayan 2', '085240958392', '$2y$10$kg2gW9hkoyyjb574kTzDjuR3pvaL.A9DpYo/UmAPvVssCh5ow4yb6', '6853f3a78177b.jpg', 'Aktif', '2025-06-19 11:25:27', '2025-06-19 11:25:27'),
(4, '3333333333', 'awal', 'VII A', 'Perempuan', 'jalan nelayan 2', '085240958392', '$2y$10$ZUn.xfToTtrMpJS0a9mvRe21eOwjp6bHZEzpDsoOF2qbOWDPEqjqy', '6853f979a58bd.png', 'Aktif', '2025-06-19 11:50:17', '2025-06-19 11:50:17'),
(5, '2828282828', 'Rasti', 'VIII A', 'Perempuan', 'Jalan nelayan 2', '08311543123', '$2y$10$vHg9N5BWUh0h9cJIZZ/1eOV7.pkDSao.4gabo3TWGLGgAv8ADpTrG', '68540226054a6.png', 'Aktif', '2025-06-19 12:27:18', '2025-06-19 12:27:18'),
(6, '9999999999', 'Abi', 'VIII A', 'Laki-laki', 'jln nangnung', '0931555678998', '$2y$10$zKAaKMlhv6T6bzuQCcJ0zeTrEArNPcfAgNFbNIxgHkCzdU/3zWCbq', '685f9e18981e8.jpg', 'Aktif', '2025-06-28 07:47:36', '2025-06-28 07:47:36'),
(7, '5555555555', 'devia', 'IX A', 'Perempuan', 'jalan sri menanti', '083157907980', '$2y$10$4VMofkhEJKtU7L5DeezSlOTBz78yodgWwjr3aO4kCReE2pZl/GO.O', '686482cc8b5eb.png', 'Aktif', '2025-07-02 00:52:28', '2025-07-02 00:52:28'),
(8, '8888888888', 'rahma', 'IX C', 'Perempuan', 'nelayan', '087654321', '$2y$10$azpS6fwwnQFkV7kQqwv0feDGGBRXv/wIr4z6SVU5xJYlUvwDi5gKO', '68649e415541d.png', 'Aktif', '2025-07-02 02:49:37', '2025-07-02 02:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `tindak_lanjut`
--

CREATE TABLE `tindak_lanjut` (
  `id_tindak_lanjut` int(11) NOT NULL,
  `id_laporan` int(11) NOT NULL,
  `jenis_tindakan` varchar(100) NOT NULL,
  `deskripsi_tindakan` text NOT NULL,
  `tanggal_tindakan` date NOT NULL,
  `id_guru_pelaksana` int(11) NOT NULL,
  `status_tindakan` enum('Direncanakan','Dilaksanakan','Selesai') DEFAULT 'Direncanakan',
  `hasil_tindakan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tindak_lanjut`
--

INSERT INTO `tindak_lanjut` (`id_tindak_lanjut`, `id_laporan`, `jenis_tindakan`, `deskripsi_tindakan`, `tanggal_tindakan`, `id_guru_pelaksana`, `status_tindakan`, `hasil_tindakan`, `created_at`) VALUES
(1, 2, 'Konseling Individual', 'melakukan mendekatan ke siswa, menanyakan kenapa', '2025-06-19', 1, 'Selesai', 'sudah ditangani', '2025-06-19 13:58:09'),
(2, 3, 'Konseling Individual', 'memanggil korban', '2025-07-01', 1, 'Dilaksanakan', 'menunggu siswa', '2025-07-01 12:57:37'),
(3, 3, 'memanggil orang tua', 'meminta orang tua pelaku datang', '2025-07-01', 1, 'Selesai', 'sudah aman', '2025-07-01 13:00:04'),
(4, 4, 'Konseling Individual', 'akan di panggil ke ruangan', '2025-07-02', 1, 'Dilaksanakan', 'belum ada', '2025-07-01 17:41:09'),
(5, 5, 'Konseling Individu', 'keruangan', '2025-07-02', 1, 'Dilaksanakan', 'belum ada', '2025-07-01 18:21:35'),
(6, 6, 'Konseling Individual', 'tidur', '2025-07-02', 1, 'Dilaksanakan', 'belum ada', '2025-07-01 19:20:43'),
(7, 7, 'Konseling Individual', 'ke ruangan segera', '2025-07-05', 2, 'Selesai', 'selesai aman', '2025-07-02 01:54:52'),
(8, 7, 'memanggil orang tua', 'memanggil orang tua korban maupun pelaku', '2025-07-05', 2, 'Dilaksanakan', 'menunggu', '2025-07-02 01:59:17');

--
-- Triggers `tindak_lanjut`
--
DELIMITER $$
CREATE TRIGGER `create_notification` AFTER INSERT ON `tindak_lanjut` FOR EACH ROW BEGIN
    DECLARE korban_id INT;
    DECLARE jenis_bullying_text VARCHAR(50);
    
    -- Ambil data laporan terkait
    SELECT lb.id_siswa_pelapor, lb.jenis_bullying
    INTO korban_id, jenis_bullying_text
    FROM laporan_bullying lb 
    WHERE lb.id_laporan = NEW.id_laporan;
    
    -- Buat notifikasi untuk korban
    IF korban_id IS NOT NULL THEN
        INSERT INTO notifikasi (
            id_siswa, 
            id_tindak_lanjut, 
            judul_notifikasi, 
            pesan_notifikasi, 
            tanggal_panggilan, 
            waktu_panggilan, 
            lokasi_panggilan
        ) VALUES (
            korban_id,
            NEW.id_tindak_lanjut,
            'Panggilan Tindak Lanjut Kasus Bullying',
            CONCAT('Anda diminta untuk hadir dalam sesi tindak lanjut terkait kasus ', jenis_bullying_text, ' yang pernah Anda laporkan. Guru BK akan melakukan ', NEW.jenis_tindakan, ' untuk menyelesaikan kasus ini.'),
            NEW.tanggal_tindakan,
            '08:00:00',
            'Ruang Bimbingan Konseling'
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_notification_status` AFTER UPDATE ON `tindak_lanjut` FOR EACH ROW BEGIN
    IF NEW.status_tindakan = 'Selesai' AND OLD.status_tindakan != 'Selesai' THEN
        UPDATE notifikasi 
        SET status_notifikasi = 'Selesai' 
        WHERE id_tindak_lanjut = NEW.id_tindak_lanjut;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tingkat_bullying`
--

CREATE TABLE `tingkat_bullying` (
  `id_tingkat` int(11) NOT NULL,
  `nama_tingkat` enum('Ringan','Sedang','Berat') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tingkat_bullying`
--

INSERT INTO `tingkat_bullying` (`id_tingkat`, `nama_tingkat`, `deskripsi`, `created_at`) VALUES
(1, 'Ringan', 'Bullying ringan seperti ejekan ringan, candaan kasar sesekali', '2025-05-30 09:13:09'),
(2, 'Sedang', 'Bullying sedang seperti ejekan berulang, pengucilan dari teman', '2025-05-30 09:13:09'),
(3, 'Berat', 'Bullying berat seperti kekerasan fisik, ancaman serius, cyberbullying', '2025-05-30 09:13:09');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_bukti_laporan`
-- (See below for the actual view)
--
CREATE TABLE `v_bukti_laporan` (
`id_bukti` int(11)
,`id_laporan` int(11)
,`jenis_bukti` enum('Foto','Video','Audio','Dokumen')
,`nama_file` varchar(255)
,`keterangan` text
,`created_at` timestamp
,`path_file` varchar(285)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_data_siswa`
-- (See below for the actual view)
--
CREATE TABLE `v_data_siswa` (
`id_siswa` int(11)
,`nisn` varchar(10)
,`nama_siswa` varchar(100)
,`kelas` varchar(10)
,`jenis_kelamin` enum('Laki-laki','Perempuan')
,`alamat` text
,`no_hp_ortu` varchar(15)
,`status` enum('Aktif','Tidak Aktif')
,`created_at` timestamp
,`jumlah_laporan_sebagai_pelapor` bigint(21)
,`frekuensi_sebagai_pelaku` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_bulanan`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_bulanan` (
`id_laporan` int(11)
,`nama_pelapor` varchar(100)
,`nisn_pelapor` varchar(10)
,`kelas_pelapor` varchar(10)
,`nama_pelaku` varchar(100)
,`kelas_pelaku` varchar(10)
,`jenis_bullying` enum('Fisik','Verbal','Sosial','Cyber')
,`tingkat_bullying` enum('Ringan','Sedang','Berat')
,`deskripsi_kejadian` text
,`lokasi_kejadian` varchar(255)
,`tanggal_kejadian` date
,`waktu_kejadian` time
,`status_laporan` enum('Menunggu','Diproses','Selesai','Ditolak')
,`created_at` timestamp
,`guru_penangani` varchar(100)
,`catatan_guru` text
,`jenis_tindakan` varchar(100)
,`status_tindakan` enum('Direncanakan','Dilaksanakan','Selesai')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_untuk_guru`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_untuk_guru` (
`id_laporan` int(11)
,`nama_pelapor` varchar(100)
,`nisn_pelapor` varchar(10)
,`kelas_pelapor` varchar(10)
,`nama_pelaku` varchar(100)
,`kelas_pelaku` varchar(10)
,`jenis_bullying` enum('Fisik','Verbal','Sosial','Cyber')
,`tingkat_bullying` enum('Ringan','Sedang','Berat')
,`deskripsi_kejadian` text
,`lokasi_kejadian` varchar(255)
,`tanggal_kejadian` date
,`waktu_kejadian` time
,`status_laporan` enum('Menunggu','Diproses','Selesai','Ditolak')
,`created_at` timestamp
,`guru_penangani` varchar(100)
,`catatan_guru` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_pelaku_sering`
-- (See below for the actual view)
--
CREATE TABLE `v_pelaku_sering` (
`nama_pelaku` varchar(100)
,`nama_siswa` varchar(100)
,`nisn` varchar(10)
,`kelas` varchar(10)
,`frekuensi` int(11)
,`last_reported` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_riwayat_laporan_siswa`
-- (See below for the actual view)
--
CREATE TABLE `v_riwayat_laporan_siswa` (
`id_laporan` int(11)
,`nama_pelaku` varchar(100)
,`kelas_pelaku` varchar(10)
,`jenis_bullying` enum('Fisik','Verbal','Sosial','Cyber')
,`tingkat_bullying` enum('Ringan','Sedang','Berat')
,`lokasi_kejadian` varchar(255)
,`tanggal_kejadian` date
,`status_laporan` enum('Menunggu','Diproses','Selesai','Ditolak')
,`created_at` timestamp
,`guru_penangani` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_statistik_laporan`
-- (See below for the actual view)
--
CREATE TABLE `v_statistik_laporan` (
`total_laporan` bigint(21)
,`menunggu` decimal(22,0)
,`diproses` decimal(22,0)
,`selesai` decimal(22,0)
,`ditolak` decimal(22,0)
,`fisik` decimal(22,0)
,`verbal` decimal(22,0)
,`sosial` decimal(22,0)
,`cyber` decimal(22,0)
,`ringan` decimal(22,0)
,`sedang` decimal(22,0)
,`berat` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Structure for view `v_bukti_laporan`
--
DROP TABLE IF EXISTS `v_bukti_laporan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bukti_laporan`  AS SELECT `bl`.`id_bukti` AS `id_bukti`, `bl`.`id_laporan` AS `id_laporan`, `bl`.`jenis_bukti` AS `jenis_bukti`, `bl`.`nama_file` AS `nama_file`, `bl`.`keterangan` AS `keterangan`, `bl`.`created_at` AS `created_at`, concat('/assets/uploads/bukti/',case when `bl`.`jenis_bukti` = 'Foto' then 'foto/' when `bl`.`jenis_bukti` = 'Video' then 'video/' when `bl`.`jenis_bukti` = 'Audio' then 'audio/' else 'dokumen/' end,`bl`.`nama_file`) AS `path_file` FROM `bukti_laporan` AS `bl` ;

-- --------------------------------------------------------

--
-- Structure for view `v_data_siswa`
--
DROP TABLE IF EXISTS `v_data_siswa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_data_siswa`  AS SELECT `s`.`id_siswa` AS `id_siswa`, `s`.`nisn` AS `nisn`, `s`.`nama_siswa` AS `nama_siswa`, `s`.`kelas` AS `kelas`, `s`.`jenis_kelamin` AS `jenis_kelamin`, `s`.`alamat` AS `alamat`, `s`.`no_hp_ortu` AS `no_hp_ortu`, `s`.`status` AS `status`, `s`.`created_at` AS `created_at`, count(`lb`.`id_laporan`) AS `jumlah_laporan_sebagai_pelapor`, ifnull(`p`.`frekuensi`,0) AS `frekuensi_sebagai_pelaku` FROM ((`siswa` `s` left join `laporan_bullying` `lb` on(`s`.`id_siswa` = `lb`.`id_siswa_pelapor`)) left join `pelaku` `p` on(`s`.`id_siswa` = `p`.`id_siswa`)) GROUP BY `s`.`id_siswa` ORDER BY `s`.`nama_siswa` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_bulanan`
--
DROP TABLE IF EXISTS `v_laporan_bulanan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_bulanan`  AS SELECT `lb`.`id_laporan` AS `id_laporan`, `s`.`nama_siswa` AS `nama_pelapor`, `s`.`nisn` AS `nisn_pelapor`, `s`.`kelas` AS `kelas_pelapor`, `lb`.`nama_pelaku` AS `nama_pelaku`, `lb`.`kelas_pelaku` AS `kelas_pelaku`, `lb`.`jenis_bullying` AS `jenis_bullying`, `lb`.`tingkat_bullying` AS `tingkat_bullying`, `lb`.`deskripsi_kejadian` AS `deskripsi_kejadian`, `lb`.`lokasi_kejadian` AS `lokasi_kejadian`, `lb`.`tanggal_kejadian` AS `tanggal_kejadian`, `lb`.`waktu_kejadian` AS `waktu_kejadian`, `lb`.`status_laporan` AS `status_laporan`, `lb`.`created_at` AS `created_at`, `gb`.`nama_guru` AS `guru_penangani`, `lb`.`catatan_guru` AS `catatan_guru`, `tl`.`jenis_tindakan` AS `jenis_tindakan`, `tl`.`status_tindakan` AS `status_tindakan` FROM (((`laporan_bullying` `lb` join `siswa` `s` on(`lb`.`id_siswa_pelapor` = `s`.`id_siswa`)) left join `guru_bk` `gb` on(`lb`.`id_guru_penangani` = `gb`.`id_guru`)) left join `tindak_lanjut` `tl` on(`lb`.`id_laporan` = `tl`.`id_laporan`)) ORDER BY `lb`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_untuk_guru`
--
DROP TABLE IF EXISTS `v_laporan_untuk_guru`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_untuk_guru`  AS SELECT `lb`.`id_laporan` AS `id_laporan`, `s`.`nama_siswa` AS `nama_pelapor`, `s`.`nisn` AS `nisn_pelapor`, `s`.`kelas` AS `kelas_pelapor`, `lb`.`nama_pelaku` AS `nama_pelaku`, `lb`.`kelas_pelaku` AS `kelas_pelaku`, `lb`.`jenis_bullying` AS `jenis_bullying`, `lb`.`tingkat_bullying` AS `tingkat_bullying`, `lb`.`deskripsi_kejadian` AS `deskripsi_kejadian`, `lb`.`lokasi_kejadian` AS `lokasi_kejadian`, `lb`.`tanggal_kejadian` AS `tanggal_kejadian`, `lb`.`waktu_kejadian` AS `waktu_kejadian`, `lb`.`status_laporan` AS `status_laporan`, `lb`.`created_at` AS `created_at`, `gb`.`nama_guru` AS `guru_penangani`, `lb`.`catatan_guru` AS `catatan_guru` FROM ((`laporan_bullying` `lb` join `siswa` `s` on(`lb`.`id_siswa_pelapor` = `s`.`id_siswa`)) left join `guru_bk` `gb` on(`lb`.`id_guru_penangani` = `gb`.`id_guru`)) ORDER BY `lb`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_pelaku_sering`
--
DROP TABLE IF EXISTS `v_pelaku_sering`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pelaku_sering`  AS SELECT `p`.`nama_pelaku` AS `nama_pelaku`, `s`.`nama_siswa` AS `nama_siswa`, `s`.`nisn` AS `nisn`, `s`.`kelas` AS `kelas`, `p`.`frekuensi` AS `frekuensi`, `p`.`last_reported` AS `last_reported` FROM (`pelaku` `p` left join `siswa` `s` on(`p`.`id_siswa` = `s`.`id_siswa`)) WHERE `p`.`frekuensi` >= 2 ORDER BY `p`.`frekuensi` DESC, `p`.`last_reported` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_riwayat_laporan_siswa`
--
DROP TABLE IF EXISTS `v_riwayat_laporan_siswa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_riwayat_laporan_siswa`  AS SELECT `lb`.`id_laporan` AS `id_laporan`, `lb`.`nama_pelaku` AS `nama_pelaku`, `lb`.`kelas_pelaku` AS `kelas_pelaku`, `lb`.`jenis_bullying` AS `jenis_bullying`, `lb`.`tingkat_bullying` AS `tingkat_bullying`, `lb`.`lokasi_kejadian` AS `lokasi_kejadian`, `lb`.`tanggal_kejadian` AS `tanggal_kejadian`, `lb`.`status_laporan` AS `status_laporan`, `lb`.`created_at` AS `created_at`, `gb`.`nama_guru` AS `guru_penangani` FROM (`laporan_bullying` `lb` left join `guru_bk` `gb` on(`lb`.`id_guru_penangani` = `gb`.`id_guru`)) ORDER BY `lb`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_statistik_laporan`
--
DROP TABLE IF EXISTS `v_statistik_laporan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistik_laporan`  AS SELECT count(0) AS `total_laporan`, sum(case when `laporan_bullying`.`status_laporan` = 'Menunggu' then 1 else 0 end) AS `menunggu`, sum(case when `laporan_bullying`.`status_laporan` = 'Diproses' then 1 else 0 end) AS `diproses`, sum(case when `laporan_bullying`.`status_laporan` = 'Selesai' then 1 else 0 end) AS `selesai`, sum(case when `laporan_bullying`.`status_laporan` = 'Ditolak' then 1 else 0 end) AS `ditolak`, sum(case when `laporan_bullying`.`jenis_bullying` = 'Fisik' then 1 else 0 end) AS `fisik`, sum(case when `laporan_bullying`.`jenis_bullying` = 'Verbal' then 1 else 0 end) AS `verbal`, sum(case when `laporan_bullying`.`jenis_bullying` = 'Sosial' then 1 else 0 end) AS `sosial`, sum(case when `laporan_bullying`.`jenis_bullying` = 'Cyber' then 1 else 0 end) AS `cyber`, sum(case when `laporan_bullying`.`tingkat_bullying` = 'Ringan' then 1 else 0 end) AS `ringan`, sum(case when `laporan_bullying`.`tingkat_bullying` = 'Sedang' then 1 else 0 end) AS `sedang`, sum(case when `laporan_bullying`.`tingkat_bullying` = 'Berat' then 1 else 0 end) AS `berat` FROM `laporan_bullying` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bukti_laporan`
--
ALTER TABLE `bukti_laporan`
  ADD PRIMARY KEY (`id_bukti`),
  ADD KEY `id_laporan` (`id_laporan`);

--
-- Indexes for table `guru_bk`
--
ALTER TABLE `guru_bk`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `jenis_bullying`
--
ALTER TABLE `jenis_bullying`
  ADD PRIMARY KEY (`id_jenis`);

--
-- Indexes for table `laporan_bullying`
--
ALTER TABLE `laporan_bullying`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `id_siswa_pelapor` (`id_siswa_pelapor`),
  ADD KEY `id_guru_penangani` (`id_guru_penangani`),
  ADD KEY `id_siswa_pelaku` (`id_siswa_pelaku`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_tindak_lanjut` (`id_tindak_lanjut`);

--
-- Indexes for table `pelaku`
--
ALTER TABLE `pelaku`
  ADD PRIMARY KEY (`id_pelaku`),
  ADD UNIQUE KEY `nama_pelaku_id_siswa` (`nama_pelaku`,`id_siswa`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nisn` (`nisn`);

--
-- Indexes for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  ADD PRIMARY KEY (`id_tindak_lanjut`),
  ADD KEY `id_laporan` (`id_laporan`),
  ADD KEY `id_guru_pelaksana` (`id_guru_pelaksana`);

--
-- Indexes for table `tingkat_bullying`
--
ALTER TABLE `tingkat_bullying`
  ADD PRIMARY KEY (`id_tingkat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bukti_laporan`
--
ALTER TABLE `bukti_laporan`
  MODIFY `id_bukti` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `guru_bk`
--
ALTER TABLE `guru_bk`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jenis_bullying`
--
ALTER TABLE `jenis_bullying`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `laporan_bullying`
--
ALTER TABLE `laporan_bullying`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pelaku`
--
ALTER TABLE `pelaku`
  MODIFY `id_pelaku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  MODIFY `id_tindak_lanjut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tingkat_bullying`
--
ALTER TABLE `tingkat_bullying`
  MODIFY `id_tingkat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bukti_laporan`
--
ALTER TABLE `bukti_laporan`
  ADD CONSTRAINT `bukti_laporan_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_bullying` (`id_laporan`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_bullying`
--
ALTER TABLE `laporan_bullying`
  ADD CONSTRAINT `laporan_bullying_ibfk_1` FOREIGN KEY (`id_siswa_pelapor`) REFERENCES `siswa` (`id_siswa`),
  ADD CONSTRAINT `laporan_bullying_ibfk_2` FOREIGN KEY (`id_guru_penangani`) REFERENCES `guru_bk` (`id_guru`),
  ADD CONSTRAINT `laporan_bullying_ibfk_3` FOREIGN KEY (`id_siswa_pelaku`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`id_tindak_lanjut`) REFERENCES `tindak_lanjut` (`id_tindak_lanjut`) ON DELETE CASCADE;

--
-- Constraints for table `pelaku`
--
ALTER TABLE `pelaku`
  ADD CONSTRAINT `pelaku_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE SET NULL;

--
-- Constraints for table `tindak_lanjut`
--
ALTER TABLE `tindak_lanjut`
  ADD CONSTRAINT `tindak_lanjut_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_bullying` (`id_laporan`) ON DELETE CASCADE,
  ADD CONSTRAINT `tindak_lanjut_ibfk_2` FOREIGN KEY (`id_guru_pelaksana`) REFERENCES `guru_bk` (`id_guru`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
