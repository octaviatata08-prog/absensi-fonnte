-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2026 at 01:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_fonnte`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `id_karyawan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'HADIR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `id_karyawan`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`) VALUES
(1, 5, '2026-10-08', '12:19:28', '12:30:40', 'HADIR'),
(3, 3, '2026-10-08', '00:00:00', '00:00:00', 'Sakit'),
(8, 6, '2026-10-08', '17:44:18', NULL, 'HADIR'),
(9, 9, '2026-10-08', '17:46:03', '17:46:10', 'HADIR');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int(11) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `nik`, `nama`, `jabatan`, `no_hp`, `email`, `password`, `role`, `created_at`) VALUES
(2, '0062026', 'Hayulita', 'Karyawan', '08132615495', 'hayhayu@gmail.com', '$2y$10$niKPV70x7ObbHl6K7RJifOLPqXfgTLDSEXg6tTIEo/Dx69K2NuEoC', 'karyawan', '2026-10-08 04:43:23'),
(3, '0072026', 'Arly', 'Karyawan', '08132615495', 'ararlyy@gmail.com', '$2y$10$hajsKaPdW9MwQCD5D6cElecBTy7lMv21joT9yAi.F1VZ5IdXstqOi', 'karyawan', '2026-10-08 04:46:32'),
(4, '0082026', 'Putri Atletika', 'Karyawan', '08132615495', 'puputt@gmail.com', '$2y$10$Frykks3U6L4c0ZJqBU3Ulu8J.Q6AR9oiIhiA1riHKSxBFf.398/su', 'karyawan', '2026-10-08 04:50:38'),
(5, '0092026', 'Azzam', 'Karyawan', '08132615495', 'zammul@gmail.com', '$2y$10$hYDhSBwEwHkKqouhSzx30O8iByKma6QNGcgdE0mba4KJMFC20UPZK', 'karyawan', '2026-10-08 05:18:28'),
(6, 'ADM001', 'Administrator', 'Admin', '081234567890', 'admin@gmail.com', '$2y$10$pVHDqu2Fwb8v.WKnWVMgwOyb6A5c8ELSwtbhL3MZy.YwGe/MARg6S', 'admin', '2026-10-08 05:26:05'),
(7, '0102026', 'Luna Dista', 'Karyawan', '08132615495', 'lunaluna@gmail.com', '$2y$10$BsLpGbjNwy/o1RJaYWH.IeXOpND6iz44w2abk43Fg8LR6uGV1I7Wy', 'karyawan', '2026-10-08 10:04:35'),
(8, '0112026', 'Luna Dista', 'Karyawan', '08132615495', 'luna@gmail.com', '$2y$10$geXBz2ZEr2FyXBjM2ptrQuATdmHTBGtoDAAa4AqxdYS.HFbTwnHJm', 'karyawan', '2026-10-08 10:35:34'),
(9, '0122026', 'Luna Dista', 'Karyawan', '08132615495', 'LUNA2@gmail.com', '$2y$10$DaCPLPcnYD8z/ZeFUnVVn.w7hVnhxsk3505VPyNigz.yiQt9LmFA2', 'karyawan', '2026-10-08 10:45:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_absensi_karyawan` (`id_karyawan`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `fk_absensi_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
