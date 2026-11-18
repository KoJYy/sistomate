-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Nov 2026 pada 05.38
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistomate`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_sensor`
--

CREATE TABLE `riwayat_sensor` (
  `id_log` int(11) NOT NULL DEFAULT 0,
  `waktu` datetime NOT NULL,
  `nama_perangkat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `suhu` float NOT NULL,
  `kelembapan_udara` float NOT NULL,
  `kelembapan_tanah` float NOT NULL,
  `rgb_red` int(11) NOT NULL,
  `rgb_green` int(11) NOT NULL,
  `rgb_blue` int(11) NOT NULL,
  `status_tanaman` enum('sehat','kering','layu','sakit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `riwayat_sensor`
--

INSERT INTO `riwayat_sensor` (`id_log`, `waktu`, `nama_perangkat`, `suhu`, `kelembapan_udara`, `kelembapan_tanah`, `rgb_red`, `rgb_green`, `rgb_blue`, `status_tanaman`) VALUES
(3, '2026-09-18 10:00:00', 'ESP32-Greenhouse-1', 32.1, 58, 22.5, 140, 150, 100, 'layu'),
(2, '2026-09-18 09:00:00', 'ESP32-Greenhouse-1', 30.2, 65, 32, 130, 175, 95, 'kering'),
(1, '2026-09-18 08:00:00', 'ESP32-Greenhouse-1', 28.5, 70, 45.5, 120, 180, 90, 'sehat'),
(4, '2026-09-18 08:00:00', 'ESP32-Greenhouse-2', 27.8, 72, 48, 150, 110, 95, 'sakit');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
