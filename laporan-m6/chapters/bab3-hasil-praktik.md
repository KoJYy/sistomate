# HASIL PRAKTIK

## Persiapan: Menyamakan Skema Awal

Sebelum mulai, saya samakan dulu kondisi database `penjualan` dengan skema laporan Acara 17 biar titik awalnya jelas:

```sql
DROP TABLE IF EXISTS trans_pembelian;
DROP TABLE IF EXISTS Master_barang;
DROP TABLE IF EXISTS Pembeli;
DROP TABLE IF EXISTS Distributor;
```

Setelah itu keempat tabel dibuat ulang persis seperti laporan minggu ke-5: `Distributor`, `Pembeli`, `Master_barang`, dan `trans_pembelian`, lengkap dengan kunci primer komposit serta tiga constraint lama.

[TEMPEL SCREENSHOT: kondisi tabel sebelum modifikasi, misalnya SHOW CREATE TABLE trans_pembelian]

## ACARA 21: Ubah Kunci dan Field

### Tambah AUTO_INCREMENT Primary Key

Langkah 3 sampai 5 modul sebenarnya minta menulis `AUTO_INCREMENT PRIMARY KEY` di ketiga tabel master. Tapi karena di Bagian A kunci primernya sudah dibuat, perintah itu ditolak MySQL dengan error 1068 (multiple primary key): modul menulisnya begitu karena versi mereka tabel belum punya kunci primer. Jadi disesuaikan, cukup ubah lebar kolomnya saja, kunci primernya tetap dipakai yang sudah ada. Sambil jalan, nama tabel juga disesuaikan: di Linux nama tabel peka huruf besar-kecil, jadi `master_barang` harus ditulis `Master_barang`:

```sql
ALTER TABLE Master_barang CHANGE id_barang id_barang INT(10) AUTO_INCREMENT;
ALTER TABLE Distributor CHANGE idDistributor idDistributor INT(5) AUTO_INCREMENT;
ALTER TABLE Pembeli CHANGE idPembeli idPembeli INT(5) AUTO_INCREMENT;
```

### Ganti PK Komposit trans_pembelian

Langkah 6 modul minta menambah `id_trans_beli INT(5) AUTO_INCREMENT PRIMARY KEY` di `trans_pembelian`. Masalahnya, tabel ini kunci primernya sudah komposit, jadi kalau langsung jalan pasti ditolak MySQL (error 1068, multiple primary key). Ditambah lagi, kolom foreign key wajib punya index sebelum kunci primer komposit dilepas (aturan InnoDB). Karena itu saya jalanin dua tahap:

```sql
-- 6a. index pendukung buat kolom foreign key
ALTER TABLE trans_pembelian
  ADD INDEX idx_pembeli (Pembeli_idPembeli),
  ADD INDEX idx_barang (Master_barang_id_barang);

-- 6b. lepas PK komposit, langsung ganti dengan id_trans_beli
ALTER TABLE trans_pembelian
  DROP PRIMARY KEY,
  ADD id_trans_beli INT(5) AUTO_INCREMENT PRIMARY KEY;
```

### Ganti Nama Kolom dan Tambah Index

Langkah 8 dan 9: `Harga_satuan` diganti jadi `harga_jual`, lalu saya tambah index di `Nama_barang` biar pencarian data lebih cepat (modul tidak kasih kode spesifik buat langkah ini):

```sql
ALTER TABLE Master_barang CHANGE Harga_satuan harga_jual INT(4);
ALTER TABLE Master_barang ADD INDEX idx_nama_barang (Nama_barang);
```

### Hapus Kolom Tgl_lahir

Terakhir langkah 10, kolom `Tgl_lahir` saya hapus dari tabel `Pembeli`:

```sql
ALTER TABLE Pembeli DROP COLUMN Tgl_lahir;
```

[TEMPEL SCREENSHOT: hasil eksekusi ALTER ACARA 21 di phpMyAdmin]

## ACARA 22: Relasi Kardinalitas

Bedanya sama Acara 17, kali ini ketiga relasi harus memakai `ON DELETE CASCADE` dan `ON UPDATE CASCADE`. MySQL tidak bisa mengubah aturan constraint di tempat, jadi constraint lama dilepas dulu:

```sql
ALTER TABLE Master_barang DROP FOREIGN KEY fk_barang_distributor;
ALTER TABLE trans_pembelian DROP FOREIGN KEY fk_trans_pembeli;
ALTER TABLE trans_pembelian DROP FOREIGN KEY fk_trans_barang;
```

Baru sesudah itu ketiga relasi dibuat ulang mengikuti langkah 2 dan 3 modul. Nama kolom saya sesuaikan dengan skema yang dipakai (modul menulis `id_distributor`, punya saya `Distributor_idDistributor`):

```sql
ALTER TABLE Master_barang ADD CONSTRAINT id_distributor
  FOREIGN KEY (Distributor_idDistributor) REFERENCES Distributor (idDistributor)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE trans_pembelian ADD CONSTRAINT idPembeli
  FOREIGN KEY (Pembeli_idPembeli) REFERENCES Pembeli (idPembeli)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE trans_pembelian ADD CONSTRAINT id_barang
  FOREIGN KEY (Master_barang_id_barang) REFERENCES Master_barang (id_barang)
  ON DELETE CASCADE ON UPDATE CASCADE;
```

Singkatnya, `RESTRICT` di Acara 17 melarang menghapus induk yang masih dirujuk anak, sedangkan `CASCADE` di Acara 22 justru mewajibkan penghapusan induk diteruskan ke baris anak. Jadi kalau ada pembeli dihapus, riwayat transaksinya ikut terhapus. Aturan ini yang diminta modul, walaupun konsekuensinya lebih berani.

[TEMPEL SCREENSHOT: hasil eksekusi ALTER ACARA 22 di phpMyAdmin]

## Verifikasi Akhir

Sebagai pengecekan akhir, saya jalankan kueri berikut:

```sql
SELECT VERSION() AS versi_mysql;
SHOW TABLES;
SHOW CREATE TABLE Distributor;
SHOW CREATE TABLE Pembeli;
SHOW CREATE TABLE Master_barang;
SHOW CREATE TABLE trans_pembelian;
SHOW INDEX FROM trans_pembelian;
```

[TEMPEL SCREENSHOT: hasil SELECT VERSION() dan SHOW TABLES]

[TEMPEL SCREENSHOT: SHOW CREATE TABLE keempat tabel, terlihat constraint id_distributor, idPembeli, id_barang dengan ON DELETE CASCADE dan ON UPDATE CASCADE]

[TEMPEL SCREENSHOT: SHOW INDEX FROM trans_pembelian, terlihat PRIMARY (id_trans_beli), idx_pembeli, dan idx_barang]

[TEMPEL SCREENSHOT: tampilan Designer phpMyAdmin, keempat tabel terhubung]

Alhamdulillah, semua perintah `ALTER TABLE` dari Acara 21 dan 22 jalan tanpa error. Struktur tabel sesuai desain, dan relasi antartabel tampil utuh di Designer phpMyAdmin.
