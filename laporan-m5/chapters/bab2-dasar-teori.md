# DASAR TEORI

## SQL dan Data Definition Language

SQL (*Structured Query Language*) merupakan bahasa komputer standar ANSI untuk mengakses data dalam basis data relasional. Fungsinya mencakup mengakses database, menjalankan query pengambilan data, menambah, menghapus, dan memperbarui data [@elmasri2016].

DDL (*Data Definition Language*) adalah sub-bahasa SQL yang berfungsi mendefinisikan database dan objek-objek di dalamnya (tabel, index, view, stored procedure, trigger). Perintah DDL ada tiga macam [@mysql-create-table]:

1. `CREATE`: membuat database dan objek di dalamnya.
2. `DROP`: menghapus database dan objek di dalamnya.
3. `ALTER`: memodifikasi struktur tabel yang sudah ada.

## Perintah CREATE

Database dibuat dengan sintaks `CREATE DATABASE nama_database`, kemudian diaktifkan dengan `USE nama_database`. Tabel dibuat dengan sintaks `CREATE TABLE` yang di dalamnya didefinisikan nama kolom, tipe data, dan aturan kolom. Catatan penting: kolom yang wajib diisi diberi penanda `NOT NULL`, dan setiap kolom harus ditentukan tipe datanya [@mysql-data-types].

## Tipe Data MySQL

Tiga kelompok tipe data yang dipakai dalam praktikum ini adalah [@mysql-data-types]:

1. **Numerik**: `INT` untuk bilangan bulat (misal harga satuan, jumlah beli).
2. **String**: `VARCHAR(n)` untuk teks dengan panjang maksimum `n` karakter (misal nama barang 25 karakter).
3. **Tanggal**: `DATE` untuk tanggal tanpa komponen waktu (misal tanggal lahir, tanggal pembelian).

Selain itu dipakai `AUTO_INCREMENT` pada kunci primer numerik agar nomor baris terisi otomatis oleh server.

## Constraint dan Relasi Antar Tabel

*Constraint* membatasi nilai yang boleh masuk ke kolom: keunikan data (`PRIMARY KEY`), nilai wajib (`NOT NULL`), dan nilai bawaan (`DEFAULT`). Hubungan antar tabel diatur lewat `FOREIGN KEY` yang merujuk ke kunci primer tabel lain, dengan sintaks `CONSTRAINT nama FOREIGN KEY (kolom) REFERENCES tabel_induk (kolom)` [@mysql-fk].

Aturan penghapusan (`ON DELETE`) menentukan perilaku baris anak saat baris induk dihapus:

- `CASCADE`: baris anak ikut terhapus mengikuti induknya.
- `RESTRICT`: penghapusan induk dicegah selama masih ada baris anak yang merujuk [@silberschatz2020].
