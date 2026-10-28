# DASAR TEORI

## Data Manipulation Language

DML (*Data Manipulation Language*) adalah kumpulan perintah query untuk memanipulasi data pada database: menambah data, mengubah data, dan menghapus data. Berbeda dari DDL yang mengubah struktur tabel, perintah DML tidak terkait dengan struktur dan metadata objek dalam tabel [@elmasri2016]. Empat perintah utamanya:

1. `INSERT`: menambah data atau record baru.
2. `SELECT`: menampilkan data dari tabel.
3. `UPDATE`: mengubah atau memperbarui data yang sudah ada.
4. `DELETE`: menghapus data.

## Perintah INSERT

Bentuk umum `INSERT` [@mysql-create-table]:

```sql
INSERT [INTO] nama_tabel [(daftar_kolom)] VALUES (nilai1, nilai2, ...);
```

Keterangan modul: `INTO` opsional; daftar kolom opsional (jika tidak ditulis, nilai harus sesuai urutan definisi kolom tabel, bisa dicek dengan `DESC nama_tabel`); `VALUES` wajib ditulis; nilai boleh berupa literal, ekspresi, atau `DEFAULT` untuk memakai nilai bawaan kolom [@mysql-data-types]. Satu perintah `INSERT` juga dapat memuat beberapa baris sekaligus dengan memisahkan tiap baris pakai koma.

## Perintah SELECT

Bentuk umum `SELECT`:

```sql
SELECT {*|daftar_kolom} FROM nama_tabel [WHERE kondisi] [ORDER BY kolom urutan];
```

Keterangan modul: `*` menampilkan semua kolom, daftar kolom menampilkan kolom tertentu saja, dan `WHERE` membatasi baris yang ditampilkan [@silberschatz2020]. operator `LIKE` memfilter pola teks (misal `'%d%'` berarti mengandung huruf d di mana saja), `AND` menggabungkan dua kondisi, dan `ORDER BY ... DESC` mengurutkan hasil dari terbesar ke terkecil.

## Perintah UPDATE

Bentuk umum `UPDATE`:

```sql
UPDATE nama_tabel
SET kolom1 = nilai1 [, kolom2 = nilai2] ...
[WHERE kondisi] [ORDER BY kolom] [LIMIT banyak_baris];
```

`WHERE` membatasi baris yang diubah, `ORDER BY` mengurutkan baris yang akan diubah, dan `LIMIT` membatasi jumlah baris (setelah `WHERE`). Tanpa `WHERE`, seluruh baris tabel ikut diubah, karena itu perintah tanpa `WHERE` harus dipakai hati-hati [@silberschatz2020].

## Perintah DELETE

Bentuk umum modul: `DELETE nama_tabel [WHERE kondisi]`, sementara semua kode praktik memakai bentuk baku MySQL:

```sql
DELETE FROM nama_tabel [WHERE kondisi];
```

`WHERE` membatasi baris yang dihapus. Tanpa `WHERE`, seluruh baris tabel terhapus, tetapi struktur tabel tetap ada (berbeda dari `DROP TABLE` yang menghapus tabelnya) [@mysql-create-table].
