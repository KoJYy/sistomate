-- ============================================================================
-- ACARA 25-28 — Minggu Ke-7 — Database mahasiswa (persis modul BKPM)
-- Jalankan: phpMyAdmin -> tab SQL -> paste seluruh file -> Go (satu kali jalan)
-- ============================================================================

-- ===== SETUP: database + tabel awal (isi "telah dibuat sebelumnya") =====
CREATE DATABASE IF NOT EXISTS mahasiswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mahasiswa;

DROP TABLE IF EXISTS daftar_dosen;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS mhs;
DROP TABLE IF EXISTS akun;

CREATE TABLE akun (
  nim VARCHAR(20) PRIMARY KEY,
  password VARCHAR(225)
) ENGINE=InnoDB;

CREATE TABLE mhs (
  nim VARCHAR(20) PRIMARY KEY,
  nama VARCHAR(225),
  jurusan VARCHAR(225),
  alamat VARCHAR(225),
  phone VARCHAR(20)
) ENGINE=InnoDB;

-- ===== ACARA 25: INSERT =====

-- Langkah 2: tabel akun
INSERT INTO `akun`(`nim`, `password`) VALUES ("E4123456","12345678");
INSERT INTO `akun`(`nim`, `password`) VALUES ('E$654321','12345678');

-- Langkah 3: tabel mhs
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ("E4123456","Mongkey D Luffy","Teknologi Informasi","East Blue","0812345678");
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ('E4654321','Zoro','Teknologi Informasi','Nort Blue','08187654321');

-- Langkah 4: masing-masing tabel diisi total 5 data (tambahan 3 data diri
-- teman segolongan, sesuai perintah modul)
INSERT INTO `akun`(`nim`, `password`) VALUES
('E4755001','rahasia123'),
('E4755002','rahasia123'),
('E4755003','rahasia123');
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES
('E4755001','Bagus Pratama','Teknologi Informasi','Surabaya','08121111111'),
('E4755002','Dinda Ayu','Teknologi Informasi','Malang','08122222222'),
('E4755003','Rizky Ramadhan','Teknologi Informasi','Bandung','08123333333');

-- Langkah 5: tabel mata_kuliah
CREATE TABLE mata_kuliah (kode_matkul CHAR(5) PRIMARY KEY,
nama_matkul VARCHAR(50) NOT NULL, jumlah_SKS TINYINT UNSIGNED DEFAULT '2',
nama_jurusan VARCHAR(50));
DESC mata_kuliah;

-- Langkah 6: INSERT satu baris
INSERT INTO mata_kuliah VALUES ('KIMDA','Kimia Dasar',2,' Kimia');

-- Langkah 7: INSERT multi-baris
INSERT INTO mata_kuliah VALUES
('MMDAS','Matematika Dasar',4,'Matematika'),
('JRKOM','Jaringan Komputer',2,'Ilmu Komputer');

-- Langkah 8: INSERT dengan daftar kolom eksplisit (jumlah_SKS pakai DEFAULT)
INSERT INTO mata_kuliah (kode_matkul,nama_matkul,nama_jurusan)
VALUES ('FIDAS','Fisika Dasar','Fisika'),
('CITRA','Pengolahan CITRA','Ilmu Komputer'),
('PBASE','Pengantar Database','Ilmu Komputer');

-- Langkah 9: tabel daftar_dosen + data
CREATE TABLE daftar_dosen (NIP CHAR(10) PRIMARY KEY,
nama_dosen VARCHAR(50) NOT NULL, no_hp CHAR(13),
alamat VARCHAR(100));
DESC daftar_dosen;

-- Catatan adaptasi: modul menampilkan CREATE TABLE mata_kuliah KEDUA
-- (CHAR(6) + semester + NIP_dosen). Dijalankan mentah = error 1050 (sudah
-- ada) dan data INSERT langkah 6-8 hilang. Disesuaikan jadi ALTER TABLE
-- supaya struktur versi 2 tercapai tanpa menghapus data.
ALTER TABLE mata_kuliah
  MODIFY kode_matkul CHAR(6) PRIMARY KEY,
  ADD semester TINYINT NULL,
  ADD NIP_dosen CHAR(10) NULL;

-- Langkah 10: data 8 dosen
INSERT INTO daftar_dosen VALUES
('0160436012','Sabrina Sari','0812349900','Pekanbaru'),
('0260432002','Maya Ari Putri','0812345234','Palembang'),
('0275430005','Susi Indriani','0812656532','Bogor'),
('0480432066','Tia Santrini','0812451177','Padang'),
('0576431001','M. Siddiq','0812979005','Jakarta'),
('0770435006','Rubin Hadi','0812567678','Papua'),
('0869437003','Mustalifah','0812338877','Aceh'),
('1080432007','Arif Budiman','0812456345','Makasar');

-- isi kolom semester (pengganti langkah modul yang tidak terlihat), supaya
-- query SELECT ACARA 26 mempunyai data
UPDATE mata_kuliah SET semester = CASE kode_matkul
  WHEN 'KIMDA' THEN 1 WHEN 'MMDAS' THEN 4 WHEN 'JRKOM' THEN 2
  WHEN 'FIDAS' THEN 5 WHEN 'CITRA' THEN 3 ELSE 2 END;

-- ===== ACARA 26: SELECT (11 langkah) =====
SELECT * FROM `akun`;
SELECT `nim`, `nama`, `alamat` FROM `mhs`;
SELECT `nim`, `nama`, `alamat` FROM `mhs` WHERE nim = "E4123456";
SELECT * FROM `mhs` WHERE nama LIKE '%d%';
SELECT * FROM mhs ORDER BY nama DESC;
SELECT * FROM daftar_dosen;
SELECT nama_dosen, alamat FROM daftar_dosen;
SELECT NIP,nama_dosen FROM daftar_dosen WHERE alamat='Padang';
SELECT * FROM mata_kuliah WHERE semester >= 4;
SELECT * FROM mata_kuliah WHERE jumlah_SKS=2 AND semester < 5;
SELECT * FROM mata_kuliah WHERE jumlah_SKS > 2 ORDER BY jumlah_SKS;

-- ===== ACARA 27: UPDATE (3 langkah) =====
UPDATE `mhs` SET nama= upper(nama);
UPDATE `mhs` SET `jurusan`= "Teknik Informatika" WHERE nim="E4123456";
UPDATE `mhs` SET `nama`="Mahasiswa satu",`jurusan`='Teknologi Informasi' WHERE jurusan LIKE '%Tekinik%';

-- ===== ACARA 28: DELETE (3 langkah) =====
DELETE FROM `mhs` WHERE nim = "E4123456";
DELETE FROM `mhs` WHERE jurusan="Teknik Informatika" and nama LIKE '%Zoro%';
DELETE FROM `mhs`;

-- ===== VERISIKASI AKHIR =====
SELECT 'akun' AS tabel, COUNT(*) AS jumlah_baris FROM akun
UNION ALL SELECT 'mata_kuliah', COUNT(*) FROM mata_kuliah
UNION ALL SELECT 'daftar_dosen', COUNT(*) FROM daftar_dosen
UNION ALL SELECT 'mhs', COUNT(*) FROM mhs;
