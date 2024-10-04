-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2024 at 12:21 PM
-- Server version: 10.4.18-MariaDB
-- PHP Version: 8.0.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spk`
--

-- --------------------------------------------------------

--
-- Table structure for table `alternatif`
--

CREATE TABLE `alternatif` (
  `id_alternatif` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `alternatif`
--

INSERT INTO `alternatif` (`id_alternatif`, `nama`) VALUES
(5, 'Bumi Etam'),
(6, 'Bumi Rapak'),
(7, 'Bumi Jaya'),
(8, 'Cipta Graha'),
(9, 'Mata Air'),
(10, 'Bukit Permata'),
(11, 'Kadungan Jaya'),
(12, 'Pengadan Baru');

-- --------------------------------------------------------

--
-- Table structure for table `hasil`
--

CREATE TABLE `hasil` (
  `id_hasil` int(11) NOT NULL,
  `id_alternatif` int(11) NOT NULL,
  `nilai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `hasil`
--

INSERT INTO `hasil` (`id_hasil`, `id_alternatif`, `nilai`) VALUES
(1, 5, '0.30949199238916'),
(2, 6, '0.97410289660373'),
(3, 7, '0.56449798547297'),
(4, 8, '0.70807281806099'),
(5, 9, '0.19738656668848'),
(6, 10, '0.16798190159975'),
(7, 11, '0.20068123864647'),
(8, 12, '0.19609308640788');

-- --------------------------------------------------------

--
-- Table structure for table `kriteria`
--

CREATE TABLE `kriteria` (
  `id_kriteria` int(11) NOT NULL,
  `kode_kriteria` varchar(10) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `type` enum('Benefit','Cost') NOT NULL,
  `bobot` varchar(50) DEFAULT NULL,
  `ada_pilihan` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kriteria`
--

INSERT INTO `kriteria` (`id_kriteria`, `kode_kriteria`, `nama`, `type`, `bobot`, `ada_pilihan`) VALUES
(41, 'C1', 'Tekstur Tanah', 'Benefit', '0.15245877600667', 1),
(42, 'C2', 'pH Tanah', 'Benefit', '0.2544452768332', 1),
(43, 'C3', 'Curah Hujan', 'Benefit', '0.063704171953314', 1),
(44, 'C4', 'Suhu', 'Benefit', '0.034435054958665', 1),
(45, 'C5', 'Metotde Irigasi', 'Benefit', '0.49495672024815', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kriteria_ahp`
--

CREATE TABLE `kriteria_ahp` (
  `id_kriteria_ahp` int(11) NOT NULL,
  `id_kriteria_1` int(11) NOT NULL,
  `id_kriteria_2` int(11) NOT NULL,
  `nilai_1` varchar(50) NOT NULL,
  `nilai_2` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kriteria_ahp`
--

INSERT INTO `kriteria_ahp` (`id_kriteria_ahp`, `id_kriteria_1`, `id_kriteria_2`, `nilai_1`, `nilai_2`) VALUES
(1, 41, 42, '0.33333333333333', '3'),
(2, 41, 43, '5', '0.2'),
(3, 41, 44, '5', '0.2'),
(4, 41, 45, '0.2', '5'),
(5, 42, 43, '5', '0.2'),
(6, 42, 44, '7', '0.14285714285714'),
(7, 42, 45, '0.33333333333333', '3'),
(8, 43, 44, '3', '0.33333333333333'),
(9, 43, 45, '0.14285714285714', '7'),
(10, 44, 45, '0.11111111111111', '9');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id_penilaian` int(11) NOT NULL,
  `id_alternatif` int(10) NOT NULL,
  `id_kriteria` int(10) NOT NULL,
  `nilai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id_penilaian`, `id_alternatif`, `id_kriteria`, `nilai`) VALUES
(128, 5, 41, '21'),
(129, 5, 42, '25'),
(130, 5, 43, '28'),
(131, 5, 44, '31'),
(132, 5, 45, '33'),
(133, 6, 41, '22'),
(134, 6, 42, '25'),
(135, 6, 43, '28'),
(136, 6, 44, '31'),
(137, 6, 45, '35'),
(138, 7, 41, '21'),
(139, 7, 42, '25'),
(140, 7, 43, '27'),
(141, 7, 44, '32'),
(142, 7, 45, '34'),
(143, 8, 41, '22'),
(144, 8, 42, '23'),
(145, 8, 43, '28'),
(146, 8, 44, '32'),
(147, 8, 45, '35'),
(148, 9, 41, '21'),
(149, 9, 42, '24'),
(150, 9, 43, '27'),
(151, 9, 44, '31'),
(152, 9, 45, '33'),
(153, 10, 41, '20'),
(154, 10, 42, '24'),
(155, 10, 43, '27'),
(156, 10, 44, '30'),
(157, 10, 45, '33'),
(158, 11, 41, '21'),
(159, 11, 42, '24'),
(160, 11, 43, '27'),
(161, 11, 44, '32'),
(162, 11, 45, '33'),
(163, 12, 41, '21'),
(164, 12, 42, '24'),
(165, 12, 43, '27'),
(166, 12, 44, '30'),
(167, 12, 45, '33');

-- --------------------------------------------------------

--
-- Table structure for table `sub_kriteria`
--

CREATE TABLE `sub_kriteria` (
  `id_sub_kriteria` int(11) NOT NULL,
  `id_kriteria` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `nilai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sub_kriteria`
--

INSERT INTO `sub_kriteria` (`id_sub_kriteria`, `id_kriteria`, `nama`, `nilai`) VALUES
(19, 41, 'Kasar', '1'),
(20, 41, 'Agak Kasar', '2'),
(21, 41, 'Sedang', '3'),
(22, 41, 'Halus, Agak halus', '4'),
(23, 42, '< 4,5 atau > 8,0', '2'),
(24, 42, '4,6 – 5,5 atau 7,1 – 8, 0', '3'),
(25, 42, '5,6 – 7,0', '4'),
(26, 43, '< 200 mm', '2'),
(27, 43, '201 – 410 mm', '3'),
(28, 43, '> 411 mm', '4'),
(29, 44, '< 18 C  atau > 35 C', '1'),
(30, 44, '19 – 21 C atau 32 – 34 C', '2'),
(31, 44, '22 – 23 C atau 30 – 31C', '3'),
(32, 44, '24 – 29 C', '4'),
(33, 45, 'Irigasi tadah hujan', '2'),
(34, 45, 'Irigasi setengah teknis', '3'),
(35, 45, 'Irigasi teknis', '4');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(5) NOT NULL,
  `username` varchar(16) NOT NULL,
  `password` varchar(50) NOT NULL,
  `nama` varchar(70) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `role` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `username`, `password`, `nama`, `email`, `role`) VALUES
(1, 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 'Admin', 'admin@gmail.com', '1'),
(8, 'user', '12dea96fec20593566ab75692c9949596833adc9', 'User', 'user@gmail.com', '2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alternatif`
--
ALTER TABLE `alternatif`
  ADD PRIMARY KEY (`id_alternatif`);

--
-- Indexes for table `hasil`
--
ALTER TABLE `hasil`
  ADD PRIMARY KEY (`id_hasil`);

--
-- Indexes for table `kriteria`
--
ALTER TABLE `kriteria`
  ADD PRIMARY KEY (`id_kriteria`);

--
-- Indexes for table `kriteria_ahp`
--
ALTER TABLE `kriteria_ahp`
  ADD PRIMARY KEY (`id_kriteria_ahp`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id_penilaian`);

--
-- Indexes for table `sub_kriteria`
--
ALTER TABLE `sub_kriteria`
  ADD PRIMARY KEY (`id_sub_kriteria`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alternatif`
--
ALTER TABLE `alternatif`
  MODIFY `id_alternatif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `hasil`
--
ALTER TABLE `hasil`
  MODIFY `id_hasil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kriteria`
--
ALTER TABLE `kriteria`
  MODIFY `id_kriteria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `kriteria_ahp`
--
ALTER TABLE `kriteria_ahp`
  MODIFY `id_kriteria_ahp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id_penilaian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `sub_kriteria`
--
ALTER TABLE `sub_kriteria`
  MODIFY `id_sub_kriteria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
