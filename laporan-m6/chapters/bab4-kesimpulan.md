# KESIMPULAN

## Kesimpulan

1. Perintah `ALTER TABLE` pada Acara 21 berhasil dimodifikasi pada keempat tabel basis data `penjualan`: kolom kunci primer `id_barang`, `idDistributor`, dan `idPembeli` diubah ke tipe `INT` lebar 5/10 dengan `AUTO_INCREMENT PRIMARY KEY`; tabel `trans_pembelian` memperoleh kunci primer tunggal baru `id_trans_beli` setelah kunci primer komposit dilepas dengan disertai index pendukung untuk kolom kunci tamu; kolom `Harga_satuan` diganti namanya menjadi `harga_jual`; index `idx_nama_barang` ditambahkan untuk mempercepat pencarian; dan kolom `Tgl_lahir` dihapus dari tabel `Pembeli`.
2. Pada Acara 22, ketiga relasi kardinalitas berhasil dibuat ulang sebagai constraint `FOREIGN KEY` baru (`id_distributor`, `idPembeli`, `id_barang`) dengan aturan `ON DELETE CASCADE` dan `ON UPDATE CASCADE`, menggantikan aturan `ON DELETE RESTRICT` pada relasi `Master_barang` ke `Distributor` dari Acara 17.
3. Verifikasi menggunakan `SHOW CREATE TABLE` dan `SHOW INDEX` membuktikan seluruh perubahan struktur, index, dan relasi sudah sesuai prosedur Acara 21 dan 22; tampilan Designer phpMyAdmin memperlihatkan keempat tabel terhubung sesuai desain.

## Saran

1. Aturan `CASCADE` pada relasi `trans_pembelian` membuat penghapusan pembeli atau barang ikut menghapus riwayat transaksi. Untuk basis data produksi dengan data historis, pertimbangkan kembali `ON DELETE RESTRICT` atau `ON DELETE SET NULL` sesuai kebutuhan bisnis.
2. Penghapusan kolom `Tgl_lahir` bersifat permanen. Pada tabel yang sudah berisi data, lakukan backup atau ekspor data kolom tersebut terlebih dahulu sebelum dihapus.
3. Kode modul perlu diperiksa ulang sebelum dieksekusi: penamaan tabel peka huruf besar-kecil pada Linux (`master_barang` vs `Master_barang`), nama kolom mengikuti skema yang dipakai, dan penambahan kunci primer pada tabel yang sudah memiliki kunci primer memerlukan langkah tambahan.
