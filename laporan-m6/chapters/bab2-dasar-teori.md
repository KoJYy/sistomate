# DASAR TEORI

## Perintah ALTER TABLE

`ALTER TABLE` adalah perintah DDL kedua setelah `CREATE`, berfungsi memodifikasi struktur tabel yang sudah ada [@elmasri2016]. Modifikasi yang dapat dilakukan meliputi:

1. Menambah kolom/field baru.
2. Menghapus kolom/field.
3. Mengubah nama atau tipe data kolom (`CHANGE`/`MODIFY`).
4. Menambahkan constraint baru (primary key dan foreign key).

Sintaks umum yang dipakai dalam praktikum ini:

```sql
ALTER TABLE nama_tabel ADD nama_kolom tipe_data(size);
ALTER TABLE nama_tabel DROP COLUMN nama_kolom;
ALTER TABLE nama_tabel CHANGE nama_lama nama_baru tipe_data(size);
ALTER TABLE nama_tabel ADD CONSTRAINT nama_constraint
  FOREIGN KEY (kolom) REFERENCES tabel_induk (kolom);
```

Dua perintah tambahan yang disebut modul: `TRUNCATE` mengosongkan seluruh baris tabel tanpa menghapus tabelnya, dan `RENAME` mengganti nama objek database.

## Kunci Primer dan AUTO_INCREMENT

Kunci primer (*primary key*) adalah constraint yang menjamin keunikan setiap baris dalam tabel. Kolom bertipe numerik dapat diberi `AUTO_INCREMENT` agar nilainya terisi otomatis oleh server, berguna sebagai nomor urut transaksi atau identitas master [@mysql-data-types].

Pada MySQL, kolom `AUTO_INCREMENT` wajib menjadi bagian dari suatu index (biasanya kunci primer). Karena itu penambahan `id_trans_beli INT(5) AUTO_INCREMENT PRIMARY KEY` pada tabel `trans_pembelian` menuntut kunci primer komposit lama dilepas terlebih dahulu.

## Kunci Tamu dan Kardinalitas

*Constraint* membatasi domain nilai yang boleh masuk ke kolom: keunikan data (`PRIMARY KEY`), nilai wajib (`NOT NULL`), dan nilai bawaan (`DEFAULT`). Relasi antartabel diatur lewat `FOREIGN KEY` yang merujuk ke kunci primer tabel lain [@mysql-fk].

Kardinalitas menggambarkan jumlah hubungan antara dua tabel:

1. **Satu-ke-banyak**: satu `Distributor` memiliki banyak `Master_barang`.
2. **Banyak-ke-banyak**: `Pembeli` dan `Master_barang` berhubungan melalui tabel penghubung `trans_pembelian`; satu pembeli bisa membeli banyak barang, satu barang bisa dibeli banyak pembeli [@elmasri2016].

## Aturan ON DELETE dan ON UPDATE

Aturan pada constraint `FOREIGN KEY` menentukan perilaku baris terkait saat baris induk diubah atau dihapus [@silberschatz2020]:

- `CASCADE`: perubahan/penghapusan induk otomatis diteruskan ke baris anak.
- `RESTRICT`: perubahan/penghapusan induk dicegah selama masih ada baris anak yang merujuk.

Acara 17 memakai `ON DELETE RESTRICT` pada relasi `Master_barang` ke `Distributor` agar distributor yang masih memiliki barang tidak dihapus. Acara 22 menetapkan `ON DELETE CASCADE` dan `ON UPDATE CASCADE` pada seluruh tiga relasi, sehingga aturan lama harus dilepas dan dibuat ulang dengan constraint baru.

## Index Pendukung Kunci Tamu

InnoDB membutuhkan index pada kolom anak setiap constraint `FOREIGN KEY`. Kunci primer komposit `(Pembeli_idPembeli, Master_barang_id_barang)` hanya menutup kolom pertama sebagai prefix, sehingga ketika kunci primer tersebut dilepas, index pendukung harus dibuat lebih dulu agar constraint `FOREIGN KEY` tetap sah [@mysql-fk].
