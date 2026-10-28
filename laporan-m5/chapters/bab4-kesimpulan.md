# KESIMPULAN

## Kesimpulan

1. Database `penjualan` berhasil dibuat dengan perintah DDL `CREATE DATABASE` memakai charset `utf8mb4` sesuai prosedur Acara 17.
2. Keempat tabel desain Acara 17 (`Distributor`, `Pembeli`, `Master_barang`, `trans_pembelian`) berhasil dibuat dengan perintah DDL `CREATE TABLE` di MySQL 8.4, lengkap dengan kunci primer (`AUTO_INCREMENT` dan komposit), tipe data yang tepat (`INT`, `VARCHAR`, `DATE`), serta tiga relasi kunci tamu dengan aturan `ON DELETE` yang berbeda sesuai perannya (`RESTRICT` untuk induk master, `CASCADE` untuk baris transaksi dependen).
3. Verifikasi `SHOW TABLES` dan `SHOW CREATE TABLE` membuktikan seluruh struktur tabel terbentuk sesuai desain Acara 17.

## Saran

1. Praktikum lanjutan dapat membahas perintah DDL `ALTER TABLE`, misalnya menambah indeks atau mengubah panjang kolom setelah data nyata dimasukkan.
2. Panjang kolom `VARCHAR` sebaiknya disesuaikan dengan data terpanjang yang ada agar tidak boros penyimpanan.
3. Sintaks bawaan MySQL Workbench pada Acara 17 (`INT(3)`, nama database `mydb`) perlu disesuaikan dengan konvensi penamaan database praktikum sebelum dieksekusi.
