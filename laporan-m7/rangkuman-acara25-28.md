# Rangkuman ACARA 25-28: Data Manipulation Language (DML)

Sumber: `BKPM Workshop Basis Data_TRK.pdf` (220 halaman).
Studi kasus modul: database `mahasiswa` (tabel `akun`, `mhs`, `mata_kuliah`, `daftar_dosen`).
Semua kode SQL di bawah ditranskrip verbatim dari screenshot modul (termasuk typo modul, ditandai `[sic]`).

Peta halaman:

| Acara | Topik | Minggu | Halaman PDF | Nomor cetak |
|-------|-------|--------|-------------|-------------|
| 25 | Insert | 7/1 | 112-117 | 95-100 |
| 26 | Select Table | 7/2 | 118-120 | 101-103 |
| 27 | Update | 7/3 | 121-123 | 104-106 |
| 28 | Delete | 7/4 | 124-125 | 107-108 |

---

# ACARA 25 : DATA MANIPULATION LANGUAGE (DML)

- **Pokok Bahasan**: Insert
- **Acara Praktikum/Pertemuan**: Minggu 7/1
- **Tempat**: Daring/Luring Politeknik Negeri Jember
- **Alokasi Waktu**: 100 menit

## a. Capaian Pembelajaran Mata Kuliah (CPMK)

Mahasiswa mampu memahami dan mengimplementasikan insert pada tabel.

## b. Indikator

Mahasiswa memahami dan mengimplementasikan insert data pada tabel.

## c. Dasar Teori (ringkas)

DML (Data Manipulation Language) adalah kumpulan perintah query untuk memanipulasi data di database: menambah, merubah, menghapus data. Perintah DML tidak terkait struktur/metadata tabel. Empat perintah utama:

- **Insert**: menambah data/record
- **Delete**: menghapus data
- **Update**: merubah/memperbarui data
- **Select**: menampilkan data dari tabel

Bentuk umum pemanggilan INSERT:

```sql
INSERT [INTO] nama_tabel[(nama_kolom1[, nama_kolom2, ...])]{VALUE | VALUES} (isi_kolom1[, isi_kolom2, ...])
```

Keterangan:

1. `INTO` opsional (boleh ditulis, boleh tidak).
2. Daftar nama kolom opsional. Jika tidak ditulis, dianggap sesuai urutan definisi kolom tabel (cek dengan `DESC namatabel`). Pemisah koma.
3. `{VALUE | VALUES}` wajib ditulis; boleh `VALUE` atau `VALUES`.
4. Isi kolom harus sesuai urutan daftar nama kolom; boleh ekspresi atau `DEFAULT`; pemisah koma.

## d. Alat dan Bahan

1. Kertas folio
2. Spidol
3. Bolpoin
4. Penggaris
5. Client server: Xampp
6. Text editor: Mysql Workbench atau sejenisnya

## e. Prosedur Kerja

**Langkah 1.** Langkah pertama membuka database `mahasiswa` yang telah dibuat sebelumnya dan table kira-kira databasenya seperti berikut (screenshot phpMyAdmin, halaman PDF 114/97):

- Tabel `akun`: `nim` int(20) PRIMARY KEY, `password` varchar(225)
- Tabel `mhs`: `nim` int(20) PRIMARY KEY, `nama` varchar(225), `jurusan` varchar(225), `alamat` varchar(225), `phone` int(20)
- ERD: `mahasiswa.akun` (nim, password) terhubung `mahasiswa.mhs` (nim, nama, jurusan, alamat, phone) relasi nim ke nim

**Langkah 2.** Pada table `akun` lakukan code berikut:

```sql
INSERT INTO `akun`(`nim`, `password`) VALUES ("E4123456","12345678")
INSERT INTO `akun`(`nim`, `password`) VALUES ('E$654321','12345678')
```

[sic] nilai `'E$654321'` persis seperti di modul (diduga salah ketik modul, harusnya `E4654321`).

**Langkah 3.** Pada table `mhs` lakukan code berikut:

```sql
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ("E4123456","Mongkey D Luffy","Teknologi Informasi","East Blue","0812345678")
INSERT INTO `mhs`(`nim`, `nama`, `jurusan`, `alamat`, `phone`) VALUES ('E4654321','Zoro','Teknologi Informasi','Nort Blue','08187654321')
```

**Langkah 4.** Tambahkan masing-masing 5 data pada table `akun` dan table `mhs`. Data berupa data diri dari 5 teman yang segolongan Anda. (Tanpa kode, mahasiswa menulis sendiri.)

**Langkah 5.** Tambahkan table `mata_kuliah` seperti code berikut:

```sql
CREATE TABLE mata_kuliah (kode_matkul CHAR(5) PRIMARY KEY,
nama_matkul VARCHAR(50) NOT NULL, jumlah_SKS TINYINT UNSIGNED DEFAULT '2',
nama_jurusan VARCHAR(50));
DESC mata_kuliah;
```

**Langkah 6.** Pada table `mata_kuliah` lakukan code berikut:

```sql
INSERT INTO mata_kuliah VALUES ('KIMDA','Kimia Dasar',2,' Kimia');
```

(spasi pada `' Kimia'` sesuai modul `[sic]`)

**Langkah 7.** Jika kita ingin langsung memasukkan 2 baris data atau lebih dalam satu perintah query INSERT MySQL, pada table `mata_kuliah` lakukan code berikut:

```sql
INSERT INTO mata_kuliah VALUES
('MMDAS','Matematika Dasar',4,'Matematika'),
('JRKOM','Jaringan Komputer',2,'Ilmu Komputer');
```

**Langkah 8.** Pada table `mata_kuliah` lakukan code berikut (INSERT dengan daftar kolom eksplisit):

```sql
INSERT INTO mata_kuliah (kode_matkul,nama_matkul,nama_jurusan)
VALUES ('FIDAS','Fisika Dasar','Fisika'),
('CITRA','Pengolahan CITRA','Ilmu Komputer'),
('PBASE','Pengantar Database','Ilmu Komputer');
```

**Langkah 9.** Tambahkan table `daftar_dosen` seperti code berikut (halaman PDF 116/99):

```sql
CREATE TABLE daftar_dosen (NIP CHAR(10) PRIMARY KEY,
nama_dosen VARCHAR(50) NOT NULL, no_hp CHAR(13),
alamat VARCHAR(100));
DESC daftar_dosen;
CREATE TABLE mata_kuliah (kode_matkul CHAR(6) PRIMARY KEY,
nama_matkul VARCHAR(50) NOT NULL,
jumlah_SKS TINYINT UNSIGNED DEFAULT '2',
semester TINYINT,NIP_dosen CHAR(10));
```

[Nic/anomali] modul menampilkan CREATE TABLE `mata_kuliah` KEDUA (CHAR(6), tambah kolom `semester` + `NIP_dosen`) padahal tabel sudah dibuat di langkah 5; screenshot menampilkan "Query OK". Tabel versi kedua ini yang dipakai langkah-langkah SELECT di ACARA 26 (ada kolom `semester`).

**Langkah 10.** Tambahkan data `daftar_dosen` seperti berikut (ditampilkan sebagai tabel data input, bukan blok kode terminal):

| NIP | Nama Dosen | No HP | Alamat |
|-----|-----------|-------|--------|
| 0160436012 | Sabrina Sari | 0812349900 | Pekanbaru |
| 0260432002 | Maya Ari Putri | 0812345234 | Palembang |
| 0275430005 | Susi Indriani | 0812656532 | Bogor |
| 0480432066 | Tia Santrini | 0812451177 | Padang |
| 0576431001 | M. Siddiq | 0812979005 | Jakarta |
| 0770435006 | Rubin Hadi | 0812567678 | Papua |
| 0869437003 | Mustalifah | 0812338877 | Aceh |
| 1080432007 | Arif Budiman | 0812456345 | Makasar |

**Langkah 11.** Pada masing-masing perubahan perhatikan dengan seksama, lakukan pencatatan dan lakukan analisa terhadap database yag sudang di lakukan tersebut. `[sic]`

## f. Hasil dan Pembahasan

1. Dokumentasi Latihan mahasiswa

## g. Kesimpulan

Mahasiswa memahami dan mengimplementasikan insert data pada sebuah tabel.

## h. Rubrik Penilaian

4 indikator, skor 1-4:

1. Ketepatan waktu dan ketepatan dalam menjelaskan dari tugas ditunjang dengan bukti referensi
2. Ketepatan waktu dan ketepatan dalam menjelaskan dari tugas
3. Ketepatan waktu akan tetapi kurang tepat dalam menjelaskan tugas
4. Keterlambatan pengumpulan tugas dan ketidaktepatan dalam menjelaskan tugas

## Catatan adaptasi ke skema penjualan

- **Struktur berbeda**: modul pakai PK manual (`nim`); skema `penjualan` pakai PK `AUTO_INCREMENT` (`idDistributor`, `idPembeli`, `id_barang`, `id_trans_beli`) → INSERT harus tulis daftar kolom eksplisit dan jangan isi kolom PK.
- **Urutan FK wajib**: insert `Distributor` dulu sebelum `Master_barang` (FK `Distributor_idDistributor`), `Pembeli` + `Master_barang` dulu sebelum `trans_pembelian` (dua FK).
- **Langkah 5 dan 9 (CREATE TABLE)**: skema `penjualan` sudah jadi, CREATE modul tidak cocok langsung. Bisa dilewati atau dipakai latihan membuat tabel turunan (misal tabel `promo`) dengan nama/kolom penjualan. Catatan: di MySQL Linux nama tabel case-sensitive, XAMPP Windows case-insensitive.
- **Langkah 2-4 (INSERT ke akun/mhs)** → padanannya INSERT ke `Pembeli(idPembeli, Nama, Alamat, No_telp)`. Kolom `Tgl_lahir` sudah dihapus dari skema penjualan, jangan ditulis.
- **Langkah 6-8 (INSERT ke mata_kuliah)** → INSERT ke `Master_barang`. Yang bisa langsung dipakai: INSERT tanpa daftar kolom (jika urutan kolom disesuaikan), INSERT multi-baris untuk batch barang, dan INSERT dengan daftar kolom eksplisit:
  ```sql
  INSERT INTO Master_barang (Nama_barang, harga_jual, Pajak, Diskon, Distributor_idDistributor)
  VALUES ('KIMDA','Kimia Dasar',2,' Kimia');
  ```
  Contoh DEFAULT di modul (`jumlah_SKS ... DEFAULT '2'`) analog dengan kolom `Pajak`/`Diskon` yang boleh diberi DEFAULT.
- **Langkah 10 (data daftar_dosen)**: tidak ada padanan dosen di `penjualan`; ganti jadi data distributor pada kolom `Nama_distributor` atau data pembeli.

---

# ACARA 26 : DATA MANIPULATION LANGUAGE (DML)

- **Pokok Bahasan**: Select Table
- **Acara Praktikum/Pertemuan**: Minggu 7/2
- **Tempat**: Daring/Luring Politeknik Negeri Jember
- **Alokasi Waktu**: 100 menit

## a. Capaian Pembelajaran Mata Kuliah (CPMK)

Mahasiswa mampu memahami dan mengimplementasikan select data dari table

## b. Indikator

Mahasiswa memahami dan mengimplementasikan select data dari sebuah table

## c. Dasar Teori (ringkas)

SELECT digunakan untuk mengambil data dari database.

```sql
SELECT {*|daftar_kolom} FROM nama_tabel [WHERE kondisi_where]
```

Keterangan:

1. `*` digunakan untuk menampilkan semua kolom yang ada dalam tabel
2. `daftar_kolom` digunakan untuk mengampikan `[sic]` kolom tertentu saja `[sic]`
3. WHERE digunakan jika ingin membatasi data yang ditampilkan

## d. Alat dan Bahan

1. Kertas folio
2. Spidol
3. Bolpoin
4. Penggaris
5. Client server: Xampp
6. Text editor: Mysql Workbench atau sejenisnya

## e. Prosedur Kerja

**Langkah 1.** Praktikum kali ini akan menampilkan data dari sebuah table yang telah di buat sebelumnya.

**Langkah 2.** Pada table `akun` tambahkan kode berikut:

```sql
SELECT * FROM `akun`
```

**Langkah 3.** Pada table `mhs` tambahkan kode berikut:

```sql
SELECT `nim`, `nama`, `alamat` FROM `mhs`
```

**Langkah 4.** Pada table `mhs` tambahkan kode berikut:

```sql
SELECT `nim`, `nama`, `alamat` FROM `mhs` WHERE nim = "E4123456"
```

**Langkah 5.** Pada table `mhs` tambahkan kode berikut:

```sql
SELECT * FROM `mhs` WHERE nama LIKE '%d%'
```

**Langkah 6.** Pada table `mhs` tambahkan kode berikut:

```sql
SELECT * FROM mhs ORDER BY nama DESC;
```

**Langkah 7.** Pada table `daftar_dosen` tambahkan kode berikut:

```sql
mysql> SELECT * FROM daftar_dosen;
```

**Langkah 8.** Pada table `daftar_dosen` tambahkan kode berikut:

```sql
mysql> SELECT nama_dosen, alamat FROM daftar_dosen;
```

**Langkah 9.** Pada table `daftar_dosen` tambahkan kode berikut:

```sql
mysql> SELECT NIP,nama_dosen FROM daftar_dosen WHERE alamat='Padang';
```

**Langkah 10.** Pada table `mata_kuliah` tambahkan kode berikut:

```sql
mysql> SELECT * FROM mata_kuliah WHERE semester >= 4;
```

**Langkah 11.** Pada table `mata_kuliah` tambahkan kode berikut:

```sql
mysql> SELECT * FROM mata_kuliah WHERE jumlah_SKS=2 AND semester < 5;
```

**Langkah 12.** Pada table `mata_kuliah` tambahkan kode berikut:

```sql
mysql> SELECT * FROM mata_kuliah WHERE jumlah_SKS > 2 ORDER BY jumlah_SKS;
```

**Langkah 13.** Lakukan pengamatan pada data yang telah ditampilkan tersebut, buat laporan hasil analisisnya dan kumpulkan melalui S-Learn.

## f. Hasil dan Pembahasan

1. Dokumentasi tugas berupa laporan (Tugas)
2. Dokumentasi tugas berupa file pdf.

### Format Laporan

- Kertas A4
- Format *.pdf
- Struktur Laporan: Cover, Pendahuluan, Hasil Praktik, Kesimpulan dan Daftar Pustaka
- Berikan Identitas Diri: NIM, Nama, Golongan, Tugas Minggu Ke-X
- Penamaan File: `ACARA-X_GOL_NIM_NAMA.pdf` (contoh: `ACARA-1_A_E1234356_ERY.pdf`)
- Kumpulkan pada https://slearn-jti.com/

## g. Kesimpulan

Mahasiswa memahami dan mengimplementasikan perintah select pada table.

## h. Rubrik Penilaian

Sama dengan ACARA 25 (4 indikator, skor 1-4).

## Catatan adaptasi ke skema penjualan

- Semua pola SELECT bisa langsung dipetakan:
  - `SELECT * FROM akun` → `SELECT * FROM Pembeli` atau `SELECT * FROM trans_pembelian`
  - `SELECT nim,nama,alamat FROM mhs` → `SELECT idPembeli, Nama, Alamat FROM Pembeli`
  - `WHERE nim = "E4123456"` → `WHERE idPembeli = 1` (PK penjualan numerik AI, bukan string)
  - `WHERE nama LIKE '%d%'` → `WHERE Nama LIKE '%d%'` (kolom `Nama` di Pembeli, `Nama_barang` di Master_barang)
  - `ORDER BY nama DESC` → `ORDER BY Nama DESC` atau `ORDER BY harga_jual DESC`
- **Langkah 7-9 (daftar_dosen)** → ganti ke `Distributor`: `SELECT * FROM Distributor;`, `SELECT Nama_distributor FROM Distributor;`, `SELECT idDistributor, Nama_distributor FROM Distributor WHERE ...`.
- **Langkah 10-12 (mata_kuliah pakai kolom `semester`, `jumlah_SKS`)** → tidak ada padanan kolom; ganti ke kolom numerik penjualan:
  - `WHERE semester >= 4` → `WHERE harga_jual >= 50000`
  - `WHERE jumlah_SKS=2 AND semester < 5` → `WHERE Pajak=... AND Diskon < ...`
  - `WHERE jumlah_SKS > 2 ORDER BY jumlah_SKS` → `WHERE jumlah_beli > 1 ORDER BY jumlah_beli`
- Langkah 10-12 bergantung pada versi kedua tabel `mata_kuliah` (dengan `semester`) dari ACARA 25 langkah 9.

---

# ACARA 27 : DATA MANIPULATION LANGUAGE (DML)

- **Pokok Bahasan**: Update
- **Acara Praktikum/Pertemuan**: Minggu 7/3
- **Tempat**: Daring/Luring Politeknik Negeri Jember
- **Alokasi Waktu**: 100 menit

## a. Capaian Pembelajaran Mata Kuliah (CPMK)

Mahasiswa mampu memahami dan mengimplementasikan perintah Update.

## b. Indikator

Mahasiswa memahami dan mengimplementasikan perintah Update untuk merubah data tabel.

## c. Dasar Teori (ringkas)

UPDATE digunakan untuk mengganti isi data.

```sql
UPDATE nama_tabel
SET kolom1={isi|DEFAULT} [, kolom2={expr2|DEFAULT}] ...]
[WHERE kondisi_where]
[ORDER BY nama_kolom_pengurutan]
[LIMIT banyak_baris]
```

Keterangan:

- WHERE digunakan untuk membatasi banyaknya baris yang diupdate
- ORDER BY digunakan untuk melakukan pengurutan data yang akan diupdate
- LIMIT digunakan untuk membatasi baris yang akan diupdate (setelah data dibatasi dengan WHERE)

## d. Alat dan Bahan

1. Kertas folio
2. Spidol
3. Bolpoin
4. Penggaris
5. Client server: Xampp
6. Text editor: Mysql Workbench atau sejenisnya

## e. Prosedur Kerja

**Langkah 1.** Praktikum kali ini akan menampilkan data dari sebuah table yang telah di buat sebelumnya.

**Langkah 2.** Pada table `mhs` tambahkan kode berikut:

```sql
UPDATE `mhs` SET nama= upper(nama)
```

**Langkah 3.** Pada table `mhs` tambahkan kode berikut:

```sql
UPDATE `mhs` SET `jurusan`= "Teknik Informatika" WHERE nim="E4123456"
```

**Langkah 4.** Pada table `mhs` tambahkan kode berikut:

```sql
UPDATE `mhs` SET `nama`="Mahasiswa satu",`jurusan`='Teknologi Informasi' WHERE jurusan LIKE '%Tekinik%'
```

[sic] `'%Tekinik%'` persis seperti di modul (diduga salah ketik, harusnya `Teknik`).

**Langkah 5.** Lakukan pengamatan pada data yang telah ditampilkan tersebut, buat laporan hasil analisisnya dan kumpulkan melalui S-Learn.

## f. Hasil dan Pembahasan

1. Dokumentasi tugas berupa laporan (Tugas)
2. Dokumentasi tugas berupa file pdf.

(Rubrik/format laporan sama dengan ACARA 26; teks format laporan tidak dicetak ulang di halaman ACARA 27.)

## g. Kesimpulan

Mahasiswa memahami dan mengimplementasikan perintah pada table.

## h. Rubrik Penilaian

Sama dengan ACARA 25 (4 indikator, skor 1-4).

## Catatan adaptasi ke skema penjualan

- Langkah 2 (UPDATE tanpa WHERE) mengubah SEMUA baris. Di penjualan hanya aman untuk operasi serupa yang memang global, misal:
  ```sql
  UPDATE Pembeli SET Nama = UPPER(Nama)
  ```
  (identik bentuknya; kalau data nama harus asli, jangan jalankan di produksi).
- Langkah 3 → UPDATE satu baris by PK:
  ```sql
  UPDATE Master_barang SET harga_jual = 15000 WHERE id_barang = 1
  ```
- Langkah 4 → UPDATE multi-kolom dengan WHERE LIKE:
  ```sql
  UPDATE Master_barang SET Diskon = 10, harga_jual = 9000 WHERE Nama_barang LIKE '%KIMDA%'
  ```
- Bentuk umum `ORDER BY` + `LIMIT` pada UPDATE didukung MySQL, bisa dilatihkan ke `trans_pembelian` (misal UPDATE jumlah_beli pada N baris teratas).
- Klausa LIKE modul memakai typo `Tekinik`; saat adaptasi pakai ejaan kolom yang benar.

---

# ACARA 28 : DATA MANIPULATION LANGUAGE (DML)

- **Pokok Bahasan**: Delete
- **Acara Praktikum/Pertemuan**: Minggu 7/4
- **Tempat**: Daring / Luring Politeknik Negeri Jember
- **Alokasi Waktu**: 100 menit

## a. Capaian Pembelajaran Mata Kuliah (CPMK)

Mahasiswa mampu memahami cara implementasi perintah Delete.

## b. Indikator

Kemampuan mahasiswa dalam memahami implementasi perintah Delete pada suatu studi kasus database.

## c. Dasar Teori (ringkas)

DELETE digunakan untuk menghapus data.

```sql
DELETE nama_tabel [WHERE kondisi_where]
```

Keterangan:

- WHERE digunakan untuk membatasi banyaknya baris yang dihapus

[sic] bentuk umum modul tidak menulis kata `FROM`, tetapi semua kode praktik memakai `DELETE FROM ...`.

## d. Alat dan Bahan

1. Kertas folio
2. Spidol
3. Bolpoin
4. Penggaris
5. Client server: Xampp
6. Text editor: Mysql Workbench atau sejenisnya

## e. Prosedur Kerja

**Langkah 1.** Praktikum kali ini akan menampilkan data dari sebuah table yang telah di buat sebelumnya. (Teks modul: "menampilkan data", padahal topiknya Delete `[sic]`.)

**Langkah 2.** Pada table `mhs` tambahkan kode berikut:

```sql
DELETE FROM `mhs` WHERE nim = "E4123456"
```

**Langkah 3.** Pada table `mhs` tambahkan kode berikut:

```sql
DELETE FROM `mhs` WHERE jurusan="Teknik Informatika" and nama LIKE '%Zoro%'
```

**Langkah 4.** Pada table `mhs` tambahkan kode berikut:

```sql
DELETE FROM `mhs`
```

(Tanpa WHERE = menghapus SELURUH baris tabel.)

**Langkah 5.** Lakukan pengamatan pada data yang telah ditampilkan tersebut, buat laporan hasil analisisnya dan kumpulkan melalui S-Learn.

## f. Hasil dan Pembahasan

1. Dokumentasi tugas berupa laporan (Tugas)
2. Dokumentasi tugas berupa file pdf.

## g. Kesimpulan

Mahasiswa berhasil memahami implementasi perintah DELETE pada suatu studi kasus database.

## h. Rubrik Penilaian

Sama dengan ACARA 25 (4 indikator, skor 1-4).

## Catatan adaptasi ke skema penjualan

- Langkah 2 → hapus satu transaksi:
  ```sql
  DELETE FROM trans_pembelian WHERE id_trans_beli = 1
  ```
- Langkah 3 → hapus by kombinasi kondisi:
  ```sql
  DELETE FROM Pembeli WHERE Nama LIKE '%Zoro%'
  ```
  Perhatikan: semua FK di skema penjualan memakai `ON DELETE CASCADE`, jadi menghapus `Pembeli` ikut menghapus baris `trans_pembelian` miliknya.
- Langkah 4 (`DELETE FROM mhs` tanpa WHERE) mengosongkan tabel. Jangan dijalankan mentah ke tabel `penjualan`; gunakan `TRUNCATE TABLE` jika memang ingin kosongkan, dan backup dulu.
- Kondisi `and` (huruf kecil) pada langkah 3 valid di MySQL; di penjualan bisa jadi `WHERE ... AND ...` pada kolom `Nama`/`Diskon`/`tgl_pembelian`.

---

## Sumber halaman PDF (ringkas)

- ACARA 25: PDF 112-117 (struktur + ERD di 114/97, CREATE/INSERT langkah 5-8 di 115/98, CREATE daftar_dosen + data dosen di 116/99, hasil/kesimpulan di 117/100)
- ACARA 26: PDF 118-120 (header + dasar teori di 118/101, 11 langkah SELECT di 119/102, format laporan + kesimpulan di 120/103)
- ACARA 27: PDF 121-123 (header + dasar teori di 121/104, 3 kode UPDATE di 122/105, rubrik di 123/106)
- ACARA 28: PDF 124-125 (header + dasar teori di 124/107, 3 kode DELETE + hasil/kesimpulan/rubrik di 125/108)

Verifikasi: teks per halaman via `pdftotext -f N -l N`, kode SQL via OCR Tesseract (render 200-400 dpi) plus pembacaan visual PNG.
