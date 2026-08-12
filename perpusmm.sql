-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 12 Agu 2026 pada 15.55
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
-- Database: `perpusmm`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(10) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin1', '$2y$10$YImR2qYW1DWQsOUh8iygz.DuzdwbOCuPdQ252/dY.xwyJMeAY7UBi'),
(2, 'admin', '$2y$10$UyWgbFFn41aKPMjAEkXE9.n.90k06dZbg55fAA8PqABvO4100gzqq'),
(3, 'admin2', '$2y$10$QOMbbSkJyiJNbk8R45wGA.xa48489yu22eCqMQJ7GGnh9vcSWcIVu');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku`
--

CREATE TABLE `buku` (
  `id` int(10) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(255) DEFAULT NULL,
  `tahun` int(10) DEFAULT NULL,
  `status` enum('Tersedia','Dipinjam') DEFAULT 'Tersedia',
  `kategori_id` int(10) DEFAULT NULL,
  `rak` varchar(50) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=aktif, 0=tidak aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `tahun`, `status`, `kategori_id`, `rak`, `gambar`, `aktif`) VALUES
(1, 'Matematika SMA/SMK Kelas X', 'Kemdikbud', 2021, 'Tersedia', 1, 'A1', 'mtk_x.jpg', 1),
(2, 'Bumi Manusia', 'Pramoedya Ananta Toer', 1980, 'Dipinjam', 2, 'B1', 'bumi_manusia.jpg', 1),
(3, 'Biologi Kelas XI', 'Erlangga', 2020, 'Tersedia', 1, 'A2', 'biologi_xi.jpg', 1),
(4, 'Sejarah Dunia yang Disembunyikan', 'Jonathan Black', 2007, 'Dipinjam', 4, 'C1', 'sejarah_dunia.jpg', 1),
(5, 'Sejarah Dunia Lengkap', 'H.G. Wells', 2018, 'Tersedia', 4, 'C1', '68f1ce7789d4f.jpg', 1),
(8, 'Rindu yang Tertinggal', 'Kurniawan Gunadi', 2015, 'Tersedia', 2, 'B1', '6939a1f5399ac.jpg', 1),
(10, 'Hujan Bulan Juni', 'Sapardi Djoko Damono', 1994, 'Tersedia', 2, 'B2', 'hujanbulanjuni.jpg', 1),
(11, 'Senja di Ufuk Timur', 'Seno Gumira Ajidarma', 2012, 'Tersedia', 2, 'B2', 'senjadiufuktimur.png', 1),
(12, 'Charlie and the Chocolate Factory (Willy Wonka)', 'Roald Dahl', 1964, 'Tersedia', 2, 'B3', 'willywonka.jpg', 1),
(13, 'Attack on Titan - Vol. 1', 'Hajime Isayama', 2009, 'Tersedia', 2, 'B4', 'AOT_v1.jpg', 1),
(14, 'Detektif Conan - Vol. 1', 'Gosho Aoyama', 1994, 'Dipinjam', 2, 'B4', 'conan_v1.jpg', 1),
(15, 'Doraemon - Vol. 3', 'Fujiko F. Fujio', 1974, 'Tersedia', 2, 'B5', 'doraemon_v3.jpg', 1),
(16, 'Dragon Ball - Vol. 1', 'Akira Toriyama', 1984, 'Tersedia', 2, 'B5', 'dragonball_v1.jpg', 1),
(17, 'Naruto - Vol. 1', 'Masashi Kishimoto', 1999, 'Dipinjam', 2, 'B6', 'naruto_v1.jpg', 1),
(18, 'One Piece - Vol. 198', 'Eiichiro Oda', 1997, 'Tersedia', 2, 'B6', 'onepiece_cp198.jpg', 1),
(19, 'Spy x Family - Vol. 2', 'Tatsuya Endo', 2019, 'Tersedia', 2, 'B7', 'spyfamily_v2.jpg', 1),
(22, 'Fisika Dasar 1', 'Mikrajuddin Abdullah', 2016, 'Tersedia', 3, 'A2', 'fisikidasar_x.jpg', 1),
(23, 'Kimia Organik', 'Fessenden & Fessenden', 1982, 'Tersedia', 1, 'A2', 'kimia_xi.jpg', 1),
(24, 'Matematika Kelas X', 'Kemdikbud', 2021, 'Dipinjam', 1, 'A3', 'mtk_x.jpg', 1),
(25, 'Matematika Dasar', 'Tim Pengajar', 2019, 'Tersedia', 1, 'A3', 'MTKdasar_x.png', 1),
(26, 'Matematika Lanjut', 'Kemdikbud', 2022, 'Tersedia', 1, 'A3', 'MTKlanjut.jpg', 1),
(27, 'Pendidikan Agama Islam XII', 'Kemdikbud', 2020, 'Tersedia', 1, 'A4', 'PAI_xii.jpg', 1),
(28, 'Penjasorkes Kelas X', 'Kemdikbud', 2021, 'Tersedia', 1, 'A4', 'penjasorkes_x.jpg', 1),
(29, 'Penjasorkes Kelas XI', 'Kemdikbud', 2021, 'Tersedia', 1, 'A4', 'penjasorkes_xi.jpg', 1),
(30, 'Prakarya Kelas X', 'Kemdikbud', 2020, 'Dipinjam', 1, 'A5', 'prakarya_x.jpg', 1),
(31, 'Fundamentals of Python for Machine Learning', 'M. Richardson', 2020, 'Tersedia', 3, 'A6', 'python_v1.jpg', 1),
(32, 'Teknologi Informasi & Komunikasi Kelas X', 'Kemdikbud', 2018, 'Tersedia', 3, 'A6', 'TIK_x.webp', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `is_pinned`) VALUES
(1, 'Buku Pelajaran', 1),
(2, 'Novel & Fiksi', 1),
(3, 'Sains & Teknologi', 0),
(4, 'Sejarah', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontak_pesan`
--

CREATE TABLE `kontak_pesan` (
  `id` int(10) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal_kirim` datetime NOT NULL DEFAULT current_timestamp(),
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=belum dibaca, 1=sudah dibaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kontak_pesan`
--

INSERT INTO `kontak_pesan` (`id`, `nama`, `email`, `pesan`, `tanggal_kirim`, `sudah_dibaca`) VALUES
(1, 'abid zidan', 'abidzidanmaulana@gmail.com', 'sahrul mau minjem buku 100 terus mau di jual ke loakan', '2026-04-27 11:00:39', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(10) NOT NULL,
  `buku_id` int(10) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `tanggal_pinjam` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status_pengembalian` enum('Belum Dikembalikan','Tepat Waktu','Terlambat') DEFAULT 'Belum Dikembalikan',
  `total_denda` int(10) DEFAULT 0,
  `status_denda` varchar(20) DEFAULT NULL,
  `user_nis` varchar(20) NOT NULL,
  `user_class` varchar(50) DEFAULT NULL,
  `status` enum('Dipinjam','Kembali') NOT NULL DEFAULT 'Dipinjam',
  `notif_seen` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `buku_id`, `user_name`, `tanggal_pinjam`, `tanggal_kembali`, `status_pengembalian`, `total_denda`, `status_denda`, `user_nis`, `user_class`, `status`, `notif_seen`) VALUES
(5, 1, 'Andi Saputra', '2026-04-01', '2026-04-06', 'Tepat Waktu', 0, 'reset', '10123', 'X IPA 1', 'Kembali', 1),
(6, 2, 'Budi Santoso', '2026-03-20', '2026-03-30', 'Terlambat', 3000, 'reset', '10124', 'X IPS 2', 'Kembali', 1),
(7, 3, 'Citra Lestari', '2026-04-15', '2026-04-18', 'Tepat Waktu', 0, 'reset', '10125', 'XI IPA 3', 'Kembali', 0),
(8, 4, 'Dewi Maharani', '2026-04-05', NULL, 'Belum Dikembalikan', 6000, 'reset', '10126', 'XII Bahasa', 'Dipinjam', 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- Indeks untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `kategori_id` (`kategori_id`) USING BTREE;

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indeks untuk tabel `kontak_pesan`
--
ALTER TABLE `kontak_pesan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `buku_id` (`buku_id`) USING BTREE;

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `kontak_pesan`
--
ALTER TABLE `kontak_pesan`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
