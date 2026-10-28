-- ============================================================================
-- ACARA 21 & 22 — Minggu Ke-6 — Basis Data Penjualan
-- Adaptasi kode modul BKPM ke skema laporan m5 (Distributor_idDistributor,
-- PK komposit trans_pembelian, nama tabel Master_barang case-sensitive).
--
-- Cara pakai: phpMyAdmin -> tab SQL -> pastikan database "penjualan" aktif
-- -> paste seluruh file -> Go. Jalankan URUT dari atas.
--
-- PERINGATAN: Bagian A me-DELETE seluruh tabel penjualan (skema masih kosong
-- tanpa data DML). Lewati bagian A hanya kalau yakin skema sudah persis m5
-- (cek: SHOW CREATE TABLE trans_pembelian harus ada kolom
-- Distributor_idDistributor di Master_barang + PK komposit di trans).
-- ============================================================================

-- ============ A. BASELINE: skema hasil ACARA 17 / laporan m5 ============
DROP TABLE IF EXISTS trans_pembelian;
DROP TABLE IF EXISTS Master_barang;
DROP TABLE IF EXISTS Pembeli;
DROP TABLE IF EXISTS Distributor;

CREATE TABLE Distributor (
  idDistributor INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama_distributor VARCHAR(45) NULL
) ENGINE=InnoDB;

CREATE TABLE Pembeli (
  idPembeli INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama VARCHAR(45) NULL,
  Alamat VARCHAR(45) NULL,
  No_telp VARCHAR(45) NULL,
  Tgl_lahir DATE NULL
) ENGINE=InnoDB;

CREATE TABLE Master_barang (
  id_barang INT(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama_barang VARCHAR(25) NULL,
  Harga_satuan INT(3) NULL,
  Pajak INT(3) NULL,
  Diskon INT(3) NULL,
  Distributor_idDistributor INT NOT NULL,
  CONSTRAINT fk_barang_distributor FOREIGN KEY (Distributor_idDistributor)
    REFERENCES Distributor (idDistributor) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE trans_pembelian (
  Pembeli_idPembeli INT NOT NULL,
  Master_barang_id_barang INT(3) NOT NULL,
  tgl_pembelian DATE NULL,
  jumlah_beli INT(3) NULL,
  PRIMARY KEY (Pembeli_idPembeli, Master_barang_id_barang),
  CONSTRAINT fk_trans_pembeli FOREIGN KEY (Pembeli_idPembeli)
    REFERENCES Pembeli (idPembeli) ON DELETE CASCADE,
  CONSTRAINT fk_trans_barang FOREIGN KEY (Master_barang_id_barang)
    REFERENCES Master_barang (id_barang) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ B. ACARA 21: Alter Table (kunci & field) ============

-- Langkah 3: master_barang -> ubah lebar id_barang ke INT(10)
-- (PK sudah ada dari Bagian A, jadi TANPA PRIMARY KEY — kalau ditulis ulang
--  kena error 1068. Modul nulis PK karena versi mereka tabel belum punya PK.
--  Nama tabel case-sensitive di Linux: master_barang = Master_barang)
ALTER TABLE Master_barang CHANGE id_barang id_barang INT(10) AUTO_INCREMENT;

-- Langkah 4: Distributor
ALTER TABLE Distributor CHANGE idDistributor idDistributor INT(5) AUTO_INCREMENT;

-- Langkah 5: Pembeli
ALTER TABLE Pembeli CHANGE idPembeli idPembeli INT(5) AUTO_INCREMENT;

-- Langkah 6 (adaptasi): modul menambah id_trans_beli AI PRIMARY KEY, tetapi
-- tabel ini sudah punya PK komposit. PK komposit harus dilepas dulu, dan
-- kolom FK-nya wajib punya index pendukung sebelum PK dilepas (aturan InnoDB).
-- 6a. index pendukung dua kolom anak:
ALTER TABLE trans_pembelian
  ADD INDEX idx_pembeli (Pembeli_idPembeli),
  ADD INDEX idx_barang (Master_barang_id_barang);
-- 6b. lepas PK komposit + tambah id_trans_beli AI PK dalam satu statement:
ALTER TABLE trans_pembelian
  DROP PRIMARY KEY,
  ADD id_trans_beli INT(5) AUTO_INCREMENT PRIMARY KEY;

-- Langkah 8: rename kolom Harga_satuan -> harga_jual
ALTER TABLE Master_barang CHANGE Harga_satuan harga_jual INT(4);

-- Langkah 9: index bantu proses pencarian data (modul langkah 9 tanpa kode)
ALTER TABLE Master_barang ADD INDEX idx_nama_barang (Nama_barang);

-- Langkah 10: drop kolom Tgl_lahir
ALTER TABLE Pembeli DROP COLUMN Tgl_lahir;

-- ============ C. ACARA 22: kardinalitas (relasi FOREIGN KEY) ============
-- Modul ACARA 22 menetapkan ON DELETE CASCADE + ON UPDATE CASCADE pada
-- ketiga relasi (menggantikan aturan ON DELETE RESTRICT acara 17).

-- Lepas 3 constraint lama hasil acara 17:
ALTER TABLE Master_barang DROP FOREIGN KEY fk_barang_distributor;
ALTER TABLE trans_pembelian DROP FOREIGN KEY fk_trans_pembeli;
ALTER TABLE trans_pembelian DROP FOREIGN KEY fk_trans_barang;

-- Langkah 2: relasi Master_barang -> Distributor
-- (modul: kolom "id_distributor"; skema m5: Distributor_idDistributor)
ALTER TABLE Master_barang ADD CONSTRAINT id_distributor
  FOREIGN KEY (Distributor_idDistributor) REFERENCES Distributor (idDistributor)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Langkah 3a: relasi trans_pembelian -> Pembeli
ALTER TABLE trans_pembelian ADD CONSTRAINT idPembeli
  FOREIGN KEY (Pembeli_idPembeli) REFERENCES Pembeli (idPembeli)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Langkah 3b: relasi trans_pembelian -> Master_barang
ALTER TABLE trans_pembelian ADD CONSTRAINT id_barang
  FOREIGN KEY (Master_barang_id_barang) REFERENCES Master_barang (id_barang)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ============ D. VERISIKASI (paste hasilnya ke Claude) ============
SELECT VERSION() AS versi_mysql;
SHOW TABLES;
SHOW CREATE TABLE Distributor;
SHOW CREATE TABLE Pembeli;
SHOW CREATE TABLE Master_barang;
SHOW CREATE TABLE trans_pembelian;
SHOW INDEX FROM trans_pembelian;
