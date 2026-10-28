# PENDAHULUAN

## Latar Belakang

Setelah minggu-minggu sebelumnya membahas *Data Definition Language* (DDL) untuk mendefinisikan struktur database, minggu ke-7 berpindah ke *Data Manipulation Language* (DML), yaitu kumpulan perintah untuk memanipulasi isi data: menambah, mengambil, mengubah, dan menghapus data. Perintah DML tidak mengubah struktur atau metadata tabel, hanya baris data di dalamnya [@elmasri2016].

Minggu ini terdiri dari empat acara berurutan pada basis data `mahasiswa` yang dibuat sebelumnya: Acara 25 (`INSERT`) menambahkan data ke tabel `akun` dan `mhs` serta membuat tabel `mata_kuliah` dan `daftar_dosen`, Acara 26 (`SELECT`) menampilkan data dengan berbagai kondisi, Acara 27 (`UPDATE`) mengubah isi data, dan Acara 28 (`DELETE`) menghapus data [@silberschatz2020].

Laporan individu ini mendokumentasikan implementasi keempat perintah tersebut beserta analisis hasil tiap langkah sesuai prosedur kerja modul BKPM.

## Rumusan Masalah

1. Bagaimana mengimplementasikan perintah `INSERT` untuk menambahkan data ke tabel, termasuk INSERT multi-baris, INSERT dengan daftar kolom eksplisit, dan nilai `DEFAULT`?
2. Bagaimana mengimplementasikan perintah `SELECT` untuk menampilkan data dengan pemilihan kolom, kondisi `WHERE`, `LIKE`, dan pengurutan `ORDER BY`?
3. Bagaimana mengimplementasikan perintah `UPDATE` untuk mengubah data, baik pada seluruh baris maupun baris tertentu?
4. Bagaimana mengimplementasikan perintah `DELETE` untuk menghapus data dengan dan tanpa kondisi `WHERE`?

## Tujuan

1. Menambahkan data ke tabel `akun`, `mhs`, `mata_kuliah`, dan `daftar_dosen` sesuai prosedur Acara 25.
2. Menampilkan data dari keempat tabel dengan 11 query `SELECT` sesuai prosedur Acara 26.
3. Mengubah data tabel `mhs` dengan tiga perintah `UPDATE` sesuai prosedur Acara 27.
4. Menghapus data tabel `mhs` dengan tiga perintah `DELETE` sesuai prosedur Acara 28 serta menganalisis jumlah baris yang terpengaruh tiap perintah.

## Batasan Masalah

1. Praktikum berfokus pada empat perintah DML (`INSERT`, `SELECT`, `UPDATE`, `DELETE`) pada MySQL 8.4 melalui tab SQL phpMyAdmin.
2. Studi kasus mengacu pada basis data `mahasiswa` (tabel `akun`, `mhs`, `mata_kuliah`, `daftar_dosen`) sesuai modul BKPM Acara 25-28, terpisah dari basis data `penjualan` yang dibahas minggu sebelumnya.
3. Penyesuaian terhadap kode modul hanya sebatas kebutuhan teknis agar perintah berjalan di MySQL 8 (lihat bab Hasil Praktik).
