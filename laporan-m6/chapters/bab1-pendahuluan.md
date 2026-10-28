# PENDAHULUAN

## Latar Belakang

Acara 21 dan Acara 22 mata kuliah Workshop Basis Data pada Minggu Ke-6 membahas *Data Definition Language* (DDL) lebih lanjut, yaitu perintah `ALTER TABLE` untuk memodifikasi struktur tabel yang sudah ada. Setelah Acara 17 pada minggu sebelumnya membuat database `penjualan` beserta keempat tabelnya (`Distributor`, `Pembeli`, `Master_barang`, `trans_pembelian`), Acara 21 memodifikasi tabel tersebut: menambahkan kunci primer `AUTO_INCREMENT`, mengganti nama kolom, menambahkan index pencarian, dan menghapus kolom [@elmasri2016].

Acara 22 melanjutkan dengan menambahkan relasi antartabel (*kardinalitas*) berupa constraint `FOREIGN KEY` dengan aturan `ON DELETE CASCADE` dan `ON UPDATE CASCADE` pada ketiga relasi: `Master_barang` ke `Distributor`, serta `trans_pembelian` ke `Pembeli` dan `Master_barang` [@mysql-fk].

Laporan individu ini mendokumentasikan implementasi kedua acara tersebut pada basis data `penjualan`, termasuk penyesuaian kode modul terhadap penamaan tabel dan kolom pada skema hasil laporan minggu ke-5, serta verifikasi struktur akhir hasil modifikasi.

## Rumusan Masalah

1. Bagaimana mengimplementasikan perintah `ALTER TABLE` untuk menambahkan kunci primer `AUTO_INCREMENT`, mengganti nama kolom, dan menghapus kolom pada basis data `penjualan` sesuai Acara 21?
2. Bagaimana mengimplementasikan constraint `FOREIGN KEY` sebagai relasi kardinalitas antartabel dengan aturan `ON DELETE` dan `ON UPDATE CASCADE` sesuai Acara 22?
3. Bagaimana memverifikasi bahwa struktur tabel dan relasi hasil modifikasi sudah sesuai desain?

## Tujuan

1. Memodifikasi struktur keempat tabel `penjualan` sesuai prosedur Acara 21: kunci primer `AUTO_INCREMENT`, penggantian nama kolom `Harga_satuan` menjadi `harga_jual`, penambahan index pencarian, dan penghapusan kolom `Tgl_lahir`.
2. Menambahkan tiga relasi kardinalitas berupa constraint `FOREIGN KEY` dengan aturan cascade pada ketiga relasi antartabel sesuai prosedur Acara 22.
3. Memverifikasi struktur dan relasi hasil implementasi menggunakan `SHOW CREATE TABLE`, `SHOW INDEX`, serta tampilan Designer phpMyAdmin.

## Batasan Masalah

1. Praktikum dibatasi pada perintah DDL (`ALTER TABLE`), tanpa perintah DML (`INSERT`, `SELECT`, `UPDATE`, `DELETE`) kecuali untuk verifikasi struktur.
2. Implementasi menggunakan MySQL 8.4 melalui tab SQL phpMyAdmin.
3. Studi kasus mengacu pada basis data `penjualan` hasil Acara 17 BKPM Workshop Basis Data; kode modul ACARA 21 dan 22 disesuaikan dengan penamaan tabel dan kolom pada skema laporan minggu ke-5 (nama tabel `Master_barang` peka huruf besar-kecil pada Linux, kolom `Distributor_idDistributor`, serta kunci primer komposit `trans_pembelian`).
