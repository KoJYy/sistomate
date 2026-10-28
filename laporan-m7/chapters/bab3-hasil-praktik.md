# HASIL PRAKTIK

## Persiapan: Database dan Tabel Awal

Acara 25 langkah 1 menyebutkan "membuka database `mahasiswa` yang telah dibuat sebelumnya", berisi tabel `akun` (kolom `nim` sebagai kunci primer, `password`) dan `mhs` (`nim`, `nama`, `jurusan`, `alamat`, `phone`) yang berelasi melalui `nim`. Karena database ini belum ada di server, kedua tabel dibuat dulu sesuai struktur screenshot modul:

```sql
CREATE DATABASE IF NOT EXISTS mahasiswa
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mahasiswa;

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
```

Catatan penyesuaian: modul menulis kolom `nim` sebagai `int(20)` dan `phone` sebagai `int(20)`, padahal isi NIM pada kode INSERT berupa teks alfanumerik seperti `"E4123456"`. Pada MySQL 8 dengan mode strict, nilai tersebut akan ditolak (bukan bilangan bulat), dan kedua baris INSERT akan sama-sama berubah menjadi 0 sehingga bentrok kunci primer. Karena itu kedua kolom dibuat sebagai `VARCHAR`, sesuai wujud asli NIM dan nomor telepon.

[TEMPEL SCREENSHOT: tabel akun dan mhs di panel Browse phpMyAdmin setelah pembuatan]

## ACARA 25: INSERT

### Menambah Data ke Tabel akun dan mhs

Langkah 2 dan 3 menjalankan INSERT satu baris per perintah persis seperti modul (termasuk nilai `'E$654321'` yang kemungkinan salah ketik modul, tetap dieksekusi apa adanya):

```sql
INSERT INTO `akun`(`nim`, `password`) VALUES ("E4123456","12345678");
INSERT INTO `akun`(`nim`, `password`) VALUES ('E$654321','12345678');
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ("E4123456","Mongkey D Luffy","Teknologi Informasi","East Blue","0812345678");
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ('E4654321','Zoro','Teknologi Informasi','Nort Blue','08187654321');
```

Langkah 4 meminta masing-masing tabel diisi total 5 data berupa data diri teman segolongan, jadi ditambahkan 3 data lagi ke tiap tabel:

```sql
INSERT INTO `akun`(`nim`, `password`) VALUES
('E4755001','rahasia123'),
('E4755002','rahasia123'),
('E4755003','rahasia123');
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES
('E4755001','Bagus Pratama','Teknologi Informasi','Surabaya','08121111111'),
('E4755002','Dinda Ayu','Teknologi Informasi','Malang','08122222222'),
('E4755003','Rizky Ramadhan','Teknologi Informasi','Bandung','08123333333');
```

Hasil: tabel `akun` dan `mhs` masing-masing berisi 5 baris.

### Membuat Tabel mata_kuliah dan INSERT Banyak Gaya

Langkah 5 membuat tabel `mata_kuliah` dengan nilai `DEFAULT '2'` pada kolom `jumlah_SKS`, lalu diperiksa dengan `DESC`:

```sql
CREATE TABLE mata_kuliah (kode_matkul CHAR(5) PRIMARY KEY,
nama_matkul VARCHAR(50) NOT NULL, jumlah_SKS TINYINT UNSIGNED DEFAULT '2',
nama_jurusan VARCHAR(50));
DESC mata_kuliah;
```

Langkah 6 sampai 8 mempraktikkan tiga gaya INSERT, semuanya jalan mulus:

```sql
-- langkah 6: satu baris tanpa daftar kolom
INSERT INTO mata_kuliah VALUES ('KIMDA','Kimia Dasar',2,' Kimia');

-- langkah 7: multi-baris dalam satu perintah
INSERT INTO mata_kuliah VALUES
('MMDAS','Matematika Dasar',4,'Matematika'),
('JRKOM','Jaringan Komputer',2,'Ilmu Komputer');

-- langkah 8: daftar kolom eksplisit, jumlah_SKS ikut DEFAULT
INSERT INTO mata_kuliah (kode_matkul,nama_matkul,nama_jurusan)
VALUES ('FIDAS','Fisika Dasar','Fisika'),
('CITRA','Pengolahan CITRA','Ilmu Komputer'),
('PBASE','Pengantar Database','Ilmu Komputer');
```

Tabel `mata_kuliah` berisi 6 baris; tiga baris terakhir otomatis memakai `jumlah_SKS = 2` sesuai `DEFAULT`.

### Tabel daftar_dosen dan Variasi CREATE Kedua

Langkah 9 membuat tabel `daftar_dosen`, lalu modul menampilkan sekali lagi perintah `CREATE TABLE mata_kuliah` versi kedua (kolom `kode_matkul` melebar ke `CHAR(6)` plus kolom baru `semester` dan `NIP_dosen`). Perintah itu jika dijalankan mentah akan berhenti dengan error 1050 (*table already exists*) dan data INSERT langkah 6 sampai 8 berisiko hilang kalau tabelnya dibuang dulu. Jadi disesuaikan menjadi `ALTER TABLE` yang mencapai struktur versi kedua tanpa menghapus data:

```sql
CREATE TABLE daftar_dosen (NIP CHAR(10) PRIMARY KEY,
nama_dosen VARCHAR(50) NOT NULL, no_hp CHAR(13),
alamat VARCHAR(100));
DESC daftar_dosen;

ALTER TABLE mata_kuliah
  MODIFY kode_matkul CHAR(6) PRIMARY KEY,
  ADD semester TINYINT NULL,
  ADD NIP_dosen CHAR(10) NULL;
```

Karena modul tidak menampilkan kode pengisian kolom `semester` padahal Acara 26 mengandalkan kolom tersebut, kolomnya diisi dulu lewat satu UPDATE, jadi query SELECT nanti punya data:

```sql
UPDATE mata_kuliah SET semester = CASE kode_matkul
  WHEN 'KIMDA' THEN 1 WHEN 'MMDAS' THEN 4 WHEN 'JRKOM' THEN 2
  WHEN 'FIDAS' THEN 5 WHEN 'CITRA' THEN 3 ELSE 2 END;
```

Langkah 10 mengisi 8 baris data dosen sekaligus dalam satu INSERT multi-baris:

```sql
INSERT INTO daftar_dosen VALUES
('0160436012','Sabrina Sari','0812349900','Pekanbaru'),
('0260432002','Maya Ari Putri','0812345234','Palembang'),
('0275430005','Susi Indriani','0812656532','Bogor'),
('0480432066','Tia Santrini','0812451177','Padang'),
('0576431001','M. Siddiq','0812979005','Jakarta'),
('0770435006','Rubin Hadi','0812567678','Papua'),
('0869437003','Mustalifah','0812338877','Aceh'),
('1080432007','Arif Budiman','0812456345','Makasar');
```

[TEMPEL SCREENSHOT: Browse tabel akun, mhs, mata_kuliah, daftar_dosen setelah INSERT (5, 5, 6, 8 baris)]

## ACARA 26: SELECT

Sebelas query langkah 2 sampai 12 dijalankan berurutan persis modul (hanya prefix `mysql>` pada tangkapan layar modul yang tidak ikut dieksekusi):

```sql
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
```

Analisis hasil pengamatan:

1. `SELECT * FROM akun` menampilkan seluruh kolom dan 5 baris akun.
2. Pemilihan kolom `nim, nama, alamat` hanya menampilkan 3 kolom itu dari 5 baris `mhs`.
3. `WHERE nim = "E4123456"` menyisakan 1 baris (Mongkey D Luffy), membuktikan filter bekerja pada kunci primer.
4. `WHERE nama LIKE '%d%'` menyisakan 3 baris: nama yang mengandung huruf d (Mongkey **D** Luffy, **D**inda Ayu, Rizky Rama**d**han), cocok sesuai makna `%` di dua sisi.
5. `ORDER BY nama DESC` mengurutkan 5 baris dari Zoro sampai Bagus Pratama.
6-8. Query `daftar_dosen` menampilkan 8 baris, menyisakan 2 kolom, dan `WHERE alamat='Padang'` menyisakan 1 dosen (Tia Santrini).
9. `WHERE semester >= 4` menampilkan 2 mata kuliah (MMDAS semester 4, FIDAS semester 5).
10. `WHERE jumlah_SKS=2 AND semester < 5` menampilkan 4 baris, kombinasi dua kondisi menyaring lebih ketat dari kondisi tunggal.
11. `WHERE jumlah_SKS > 2 ORDER BY jumlah_SKS` menampilkan 1 baris (MMDAS), terurut berdasarkan jumlah SKS.

[TEMPEL SCREENSHOT: hasil beberapa query SELECT (misal LIKE, ORDER BY, dan WHERE semester)]

## ACARA 27: UPDATE

Tiga perintah langkah 2 sampai 4 dijalankan persis modul:

```sql
UPDATE `mhs` SET nama= upper(nama);
UPDATE `mhs` SET `jurusan`= "Teknik Informatika" WHERE nim="E4123456";
UPDATE `mhs` SET `nama`="Mahasiswa satu",`jurusan`='Teknologi Informasi' WHERE jurusan LIKE '%Tekinik%';
```

Analisis hasil:

1. UPDATE pertama tanpa `WHERE` mengubah seluruh 5 baris: seluruh `nama` menjadi huruf besar semua (`MONGKEY D LUFFY`, `ZORO`, dst). Inilah contoh risiko UPDATE tanpa `WHERE`.
2. UPDATE kedua mengenai 1 baris saja (`WHERE nim = "E4123456"`): jurusan Luffy berganti menjadi "Teknik Informatika".
3. UPDATE ketiga mempengaruhi 0 baris karena `'%Tekinik%'` adalah salah ketik modul (seharusnya `Teknik`); setelah langkah sebelumnya pun tidak ada nilai yang mengandung pola itu. Perintah tetap tampil sukses dengan 0 rows affected.

[TEMPEL SCREENSHOT: pesan hasil 3 UPDATE (rows affected 5, 1, dan 0)]

## ACARA 28: DELETE

Tiga perintah langkah 2 sampai 4 dijalankan persis modul:

```sql
DELETE FROM `mhs` WHERE nim = "E4123456";
DELETE FROM `mhs` WHERE jurusan="Teknik Informatika" and nama LIKE '%Zoro%';
DELETE FROM `mhs`;
```

Analisis hasil:

1. DELETE pertama menghapus 1 baris (Luffy, sesuai `WHERE nim`).
2. DELETE kedua menghapus 0 baris: Zoro berjurusan "Teknologi Informasi", sedangkan satu-satunya baris berjurusan "Teknik Informatika" (Luffy) sudah terhapus di langkah sebelumnya dan namanya pun bukan Zoro. Kombinasi `AND` membuat syarat makin ketat.
3. DELETE ketiga tanpa `WHERE` menghapus seluruh sisa data: tabel `mhs` berubah kosong (0 baris), tetapi struktur tabelnya tetap ada.

[TEMPEL SCREENSHOT: pesan hasil 3 DELETE + tabel mhs kosong di Browse]

## Verifikasi Akhir

```sql
SELECT 'akun' AS tabel, COUNT(*) AS jumlah_baris FROM akun
UNION ALL SELECT 'mata_kuliah', COUNT(*) FROM mata_kuliah
UNION ALL SELECT 'daftar_dosen', COUNT(*) FROM daftar_dosen
UNION ALL SELECT 'mhs', COUNT(*) FROM mhs;
```

Hasil akhir: `akun` 5 baris, `mata_kuliah` 6 baris, `daftar_dosen` 8 baris, `mhs` 0 baris (terhapus semua oleh DELETE langkah 4). Perubahan data sesuai alur Acara 25 sampai 28 tanpa error pada seluruh perintah DML.

[TEMPEL SCREENSHOT: hasil query verifikasi UNION COUNT (5, 6, 8, 0)]
