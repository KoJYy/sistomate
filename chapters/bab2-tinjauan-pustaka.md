# TINJAUAN PUSTAKA

## Konsep Basis Data Relasional

Basis data relasional menyimpan data dalam bentuk tabel yang terdiri dari baris (record) dan kolom (field), di mana setiap tabel merepresentasikan satu entitas dan setiap baris merepresentasikan satu instance dari entitas tersebut [@connolly2014database; @elmasri2016fundamentals]. Model relasional menekankan struktur data yang konsisten, kemudahan akses melalui bahasa query terstruktur, serta jaminan integritas data melalui aturan-aturan yang didefinisikan pada level skema [@silberschatz2020database].

## Kunci Primer dan Kunci Tamu

Setiap tabel dalam basis data relasional wajib memiliki kunci primer (Primary Key), yaitu satu atau gabungan kolom yang nilainya unik untuk setiap baris dan tidak boleh kosong, sehingga setiap record dapat diidentifikasi secara tepat [@date2019database]. Hubungan antar tabel kemudian dibangun melalui kunci tamu (Foreign Key), yaitu kolom pada suatu tabel yang nilainya merujuk pada kunci primer tabel lain. Penerapan kunci tamu menjamin integritas referensial, artinya setiap nilai rujukan pasti memiliki pasangan yang valid pada tabel induknya [@elmasri2016fundamentals; @kroenke2018database].

## Jenis Relasi Antar Tabel

Relasi antar tabel pada model relasional terdiri dari tiga jenis utama [@connolly2014database; @silberschatz2020database]. Pertama, relasi satu-ke-satu (one-to-one), di mana satu baris pada tabel pertama berhubungan dengan tepat satu baris pada tabel kedua. Kedua, relasi satu-ke-banyak (one-to-many), di mana satu baris pada tabel induk dapat berhubungan dengan banyak baris pada tabel anak, dan jenis ini merupakan relasi yang paling umum digunakan. Ketiga, relasi banyak-ke-banyak (many-to-many), di mana banyak baris pada satu tabel dapat berhubungan dengan banyak baris pada tabel lain.

Relasi banyak-ke-banyak tidak dapat diimplementasikan secara langsung dalam model relasional, sehingga harus dipecah menjadi dua relasi satu-ke-banyak melalui sebuah tabel perantara yang disebut junction table atau tabel relasi [@date2019database; @connolly2014database]. Pada perancangan SISTOMATE ini tidak terdapat relasi banyak-ke-banyak: seluruh hubungan antar tabel bersifat satu-ke-banyak (One-to-Many), di mana satu pengguna memiliki banyak perangkat, dan satu perangkat menghasilkan banyak citra, banyak baris log sensor, serta banyak aksi aktuator sepanjang waktu operasionalnya.
