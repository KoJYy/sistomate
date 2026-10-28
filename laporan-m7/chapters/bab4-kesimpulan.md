# KESIMPULAN

## Kesimpulan

1. Perintah `INSERT` pada Acara 25 berhasil mengisi keempat tabel basis data `mahasiswa`: 5 baris `akun`, 5 baris `mhs`, 6 baris `mata_kuliah`, dan 8 baris `daftar_dosen`, mempraktikkan empat gaya INSERT sekaligus: satu baris, multi-baris, daftar kolom eksplisit, dan nilai `DEFAULT`.
2. Sebelas query `SELECT` pada Acara 26 berhasil menampilkan data dengan pemilihan kolom, filter `WHERE`, pola `LIKE '%d%'`, kombinasi `AND`, dan pengurutan `ORDER BY ... DESC`, serta analisis jumlah baris tiap hasil menunjukkan kondisi bekerja sesuai harapan.
3. Tiga perintah `UPDATE` pada Acara 27 berhasil dijalankan: satu UPDATE tanpa `WHERE` mengubah seluruh 5 baris, satu UPDATE terarah mengubah 1 baris, dan satu UPDATE dengan pola salah ketik modul (`Tekinik`) mempengaruhi 0 baris.
4. Tiga perintah `DELETE` pada Acara 28 berhasil menghapus 1 baris berdasarkan kunci primer, 0 baris karena kombinasi kondisi `AND` tidak ada yang cocok, dan seluruh sisa baris oleh `DELETE` tanpa `WHERE` sehingga tabel `mhs` kosong tanpa menghilangkan strukturnya.

## Saran

1. UPDATE dan DELETE tanpa `WHERE` mempengaruhi seluruh baris. Untuk data produksi, selalu uji dengan `SELECT` berfilter dulu atau pakai `LIMIT` sebagai jaring pengaman sebelum eksekusi.
2. Kesalahan ketik pada kondisi (seperti `Tekinik`) membuat perintah tetap sukses tetapi 0 baris terpengaruh, jadi jumlah baris affected pada pesan MySQL harus selalu diperiksa, bukan hanya warna hijau.
3. Tipe data kolom harus cocok dengan isi datanya; kolom `nim` yang berisi teks alfanumerik sebaiknya memakai `VARCHAR`, bukan `INT` seperti tertulis di modul, agar tidak terkena mode strict MySQL 8 maupun kehilangan data.
