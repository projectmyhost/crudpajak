-- Database Perpajakan (DJP / Sistem Manajemen Pajak Relasional)
-- DDL & Sample Data sesuai Spesifikasi Dokumen Perpajakan

CREATE DATABASE IF NOT EXISTS `db_perpajakan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_perpajakan`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabel KPP (Kantor Pelayanan Pajak)
DROP TABLE IF EXISTS `pembayaran_pajak`;
DROP TABLE IF EXISTS `spt`;
DROP TABLE IF EXISTS `wajib_pajak`;
DROP TABLE IF EXISTS `petugas_pajak`;
DROP TABLE IF EXISTS `jenis_pajak`;
DROP TABLE IF EXISTS `kpp`;

CREATE TABLE `kpp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_kpp` VARCHAR(3) NOT NULL,
  `nama_kpp` VARCHAR(100) NOT NULL,
  `alamat` TEXT NULL,
  `no_telepon` VARCHAR(15) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kpp_kode` (`kode_kpp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Petugas Pajak (Account Representative / Pemeriksa)
CREATE TABLE `petugas_pajak` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(18) NOT NULL,
  `kpp_id` INT(11) NOT NULL,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `jabatan` ENUM('AR','Pemeriksa','Penelaah','Admin') NOT NULL DEFAULT 'AR',
  `email` VARCHAR(100) NOT NULL,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_petugas_nip` (`nip`),
  UNIQUE KEY `idx_petugas_email` (`email`),
  KEY `fk_petugas_kpp` (`kpp_id`),
  CONSTRAINT `fk_petugas_kpp` FOREIGN KEY (`kpp_id`) REFERENCES `kpp` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Wajib Pajak (Master Data Wajib Pajak)
CREATE TABLE `wajib_pajak` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `npwp` VARCHAR(25) NOT NULL,
  `nama_wp` VARCHAR(150) NOT NULL,
  `jenis_wp` ENUM('Orang Pribadi','Badan') NOT NULL DEFAULT 'Orang Pribadi',
  `nik` VARCHAR(16) NULL,
  `kpp_id` INT(11) NOT NULL,
  `petugas_ar_id` INT(11) NULL,
  `alamat` TEXT NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `status_efin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wp_npwp` (`npwp`),
  UNIQUE KEY `idx_wp_nik` (`nik`),
  KEY `fk_wp_kpp` (`kpp_id`),
  KEY `fk_wp_ar` (`petugas_ar_id`),
  CONSTRAINT `fk_wp_kpp` FOREIGN KEY (`kpp_id`) REFERENCES `kpp` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_wp_ar` FOREIGN KEY (`petugas_ar_id`) REFERENCES `petugas_pajak` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Jenis Pajak (Master Jenis Pajak & Tarif)
CREATE TABLE `jenis_pajak` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_akun_pajak` VARCHAR(6) NOT NULL,
  `nama_jenis_pajak` VARCHAR(100) NOT NULL,
  `kategori_pajak` ENUM('PPh','PPN','PPnBM','PBB','Bea Materai') NOT NULL DEFAULT 'PPh',
  `tarif_standar` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_jenis_kap` (`kode_akun_pajak`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel SPT (Surat Pemberitahuan Pajak)
CREATE TABLE `spt` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nomor_spt` VARCHAR(30) NOT NULL,
  `wajib_pajak_id` INT(11) NOT NULL,
  `jenis_pajak_id` INT(11) NOT NULL,
  `jenis_spt` ENUM('Masa','Tahunan') NOT NULL DEFAULT 'Tahunan',
  `tahun_pajak` YEAR(4) NOT NULL,
  `masa_pajak` VARCHAR(5) NULL,
  `pembetulan_ke` INT(2) NOT NULL DEFAULT 0,
  `status_spt` ENUM('Nihil','Kurang Bayar','Lebih Bayar') NOT NULL DEFAULT 'Nihil',
  `nominal_pajak` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tanggal_lapor` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_spt_nomor` (`nomor_spt`),
  KEY `fk_spt_wp` (`wajib_pajak_id`),
  KEY `fk_spt_jenis` (`jenis_pajak_id`),
  CONSTRAINT `fk_spt_wp` FOREIGN KEY (`wajib_pajak_id`) REFERENCES `wajib_pajak` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_spt_jenis` FOREIGN KEY (`jenis_pajak_id`) REFERENCES `jenis_pajak` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Pembayaran Pajak (Transaksi Pembayaran Billing)
CREATE TABLE `pembayaran_pajak` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_billing` VARCHAR(15) NOT NULL,
  `ntpn` VARCHAR(16) NULL,
  `spt_id` INT(11) NULL,
  `wajib_pajak_id` INT(11) NOT NULL,
  `jenis_pajak_id` INT(11) NOT NULL,
  `dpp` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `jumlah_bayar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `bank_persepsi` VARCHAR(50) NULL,
  `status_pembayaran` ENUM('Unpaid','Paid','Expired') NOT NULL DEFAULT 'Unpaid',
  `tanggal_bayar` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bayar_billing` (`kode_billing`),
  UNIQUE KEY `idx_bayar_ntpn` (`ntpn`),
  KEY `fk_bayar_spt` (`spt_id`),
  KEY `fk_bayar_wp` (`wajib_pajak_id`),
  KEY `fk_bayar_jenis` (`jenis_pajak_id`),
  CONSTRAINT `fk_bayar_spt` FOREIGN KEY (`spt_id`) REFERENCES `spt` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_bayar_wp` FOREIGN KEY (`wajib_pajak_id`) REFERENCES `wajib_pajak` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_bayar_jenis` FOREIGN KEY (`jenis_pajak_id`) REFERENCES `jenis_pajak` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- SEED DATA AWAL

-- 1. Data KPP
INSERT INTO `kpp` (`id`, `kode_kpp`, `nama_kpp`, `alamat`, `no_telepon`) VALUES
(1, '012', 'KPP Pratama Jakarta Gambir Satu', 'Jl. Ridwan Rais No. 5-7, Gambir, Jakarta Pusat', '021-3841234'),
(2, '045', 'KPP Pratama Bandung Cibeunying', 'Jl. Purnawarman No. 21, Bandung, Jawa Barat', '022-4205678'),
(3, '078', 'KPP Pratama Surabaya Gubeng', 'Jl. Dinoyo No. 111, Gubeng, Surabaya, Jawa Timur', '031-5678901');

-- 2. Data Petugas Pajak
INSERT INTO `petugas_pajak` (`id`, `nip`, `kpp_id`, `nama_petugas`, `jabatan`, `email`, `status_aktif`) VALUES
(1, '198504122008011001', 1, 'Hendro Prasetyo, S.E., M.Ak.', 'AR', 'hendro.prasetyo@pajak.go.id', 1),
(2, '199003152012022002', 2, 'Siti Rahmawati, S.Tr.Ak.', 'AR', 'siti.rahmawati@pajak.go.id', 1),
(3, '198811202010121003', 3, 'Bambang Trianto, S.E.', 'Pemeriksa', 'bambang.trianto@pajak.go.id', 1),
(4, '199201012015031004', 1, 'Dian Kusuma, S.Sos.', 'Admin', 'dian.kusuma@pajak.go.id', 1);

-- 3. Data Jenis Pajak (Sesuai docx 2.2)
INSERT INTO `jenis_pajak` (`id`, `kode_akun_pajak`, `nama_jenis_pajak`, `kategori_pajak`, `tarif_standar`) VALUES
(1, '411211', 'Pajak Pertambahan Nilai (PPN)', 'PPN', 11.00),
(2, '411121', 'PPh Pasal 21 (Penghasilan Karyawan)', 'PPh', 5.00),
(3, '411124', 'PPh Pasal 23 (Jasa & Sewa)', 'PPh', 2.00),
(4, '411122', 'PPh Pasal 22 (Impor & Pengadaan)', 'PPh', 1.50),
(5, '411315', 'Pajak Bumi dan Bangunan (PBB)', 'PBB', 0.50);

-- 4. Data Wajib Pajak (Sesuai docx 2.1)
INSERT INTO `wajib_pajak` (`id`, `npwp`, `nama_wp`, `jenis_wp`, `nik`, `kpp_id`, `petugas_ar_id`, `alamat`, `email`, `status_efin`) VALUES
(1, '01.234.567.8-012.000', 'PT Maju Sejahtera', 'Badan', NULL, 1, 1, 'Jl. Sudirman No. 45, Jakarta Pusat', 'finance@majusejahtera.co.id', 1),
(2, '02.987.654.3-045.000', 'CV Nusantara Jaya', 'Badan', NULL, 2, 2, 'Jl. Gatot Subroto No. 12, Bandung', 'admin@nusantarajaya.id', 1),
(3, '03.456.789.1-078.000', 'Budi Santoso', 'Orang Pribadi', '3201011505880001', 3, 3, 'Jl. Pemuda No. 88, Surabaya', 'budi.santoso@gmail.com', 1);

-- 5. Data SPT (Sesuai skema SPT)
INSERT INTO `spt` (`id`, `nomor_spt`, `wajib_pajak_id`, `jenis_pajak_id`, `jenis_spt`, `tahun_pajak`, `masa_pajak`, `pembetulan_ke`, `status_spt`, `nominal_pajak`, `tanggal_lapor`) VALUES
(1, 'BPS-2026-PPN-001', 1, 1, 'Masa', 2026, '01-01', 0, 'Kurang Bayar', 11000000.00, '2026-02-10 09:30:00'),
(2, 'BPS-2026-PPH23-002', 2, 3, 'Masa', 2026, '01-01', 0, 'Kurang Bayar', 1000000.00, '2026-02-12 11:15:00'),
(3, 'BPS-2026-PPH21-003', 3, 2, 'Tahunan', 2025, NULL, 0, 'Kurang Bayar', 750000.00, '2026-03-01 14:00:00');

-- 6. Data Pembayaran Pajak (Sesuai docx 2.3)
INSERT INTO `pembayaran_pajak` (`id`, `kode_billing`, `ntpn`, `spt_id`, `wajib_pajak_id`, `jenis_pajak_id`, `dpp`, `jumlah_bayar`, `bank_persepsi`, `status_pembayaran`, `tanggal_bayar`) VALUES
(1, '123456789012345', '8F7E6D5C4B3A2026', 1, 1, 1, 100000000.00, 11000000.00, 'Bank Mandiri', 'Paid', '2026-02-10 10:15:22'),
(2, '234567890123456', '7A6B5C4D3E2F2026', 2, 2, 3, 50000000.00, 1000000.00, 'Bank BCA', 'Paid', '2026-02-12 13:40:10'),
(3, '345678901234567', NULL, 3, 3, 2, 15000000.00, 750000.00, NULL, 'Unpaid', NULL);

