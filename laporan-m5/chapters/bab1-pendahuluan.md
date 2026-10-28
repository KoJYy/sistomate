# PENDAHULUAN

## Latar Belakang

Acara 17 mata kuliah Workshop Basis Data membahas Data Definition Language (DDL), yaitu sub-bahasa SQL untuk mendefinisikan database beserta objek-objek di dalamnya (tabel, index, view, stored procedure, trigger). Prosedur kerja Acara 17 memberi sebuah desain basis data **penjualan** terdiri dari dua tabel master (`Distributor`, `Pembeli`), satu tabel master barang (`Master_barang`), dan satu tabel transaksi (`trans_pembelian`), yang kemudian diwujudkan menjadi tabel fisik di MySQL melalui perintah `CREATE DATABASE` dan `CREATE TABLE` [@mysql-create-table].

Laporan individu ini mendokumentasikan implementasi tersebut: pembuatan database `penjualan`, pembuatan keempat tabel sesuai sintaks Acara 17 dengan penyesuaian kunci primer dan kunci tamu, serta verifikasi struktur hasil.

## Rumusan Masalah

1. Bagaimana mengimplementasikan perintah `CREATE DATABASE` untuk basis data `penjualan` di MySQL?
2. Bagaimana mengimplementasikan perintah `CREATE TABLE` untuk keempat tabel Acara 17 beserta tipe data, kunci primer, dan kunci tamunya?
3. Bagaimana memverifikasi bahwa struktur tabel yang terbentuk sudah sesuai desain?

## Tujuan

1. Membuat database `penjualan` menggunakan perintah DDL `CREATE DATABASE`.
2. Membuat empat tabel (`Distributor`, `Pembeli`, `Master_barang`, `trans_pembelian`) menggunakan perintah DDL `CREATE TABLE` dengan constraint yang tepat.
3. Memverifikasi struktur tabel hasil implementasi menggunakan `SHOW TABLES` dan `SHOW CREATE TABLE`.

## Batasan Masalah

1. Praktikum dibatasi pada perintah DDL (`CREATE`), tanpa perintah DML (`INSERT`, `SELECT`, `UPDATE`, `DELETE`) kecuali untuk verifikasi.
2. Implementasi menggunakan MySQL 8.4.10 melalui tab SQL phpMyAdmin.
3. Studi kasus mengacu pada desain penjualan Acara 17 BKPM Workshop Basis Data.
