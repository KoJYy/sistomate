# HASIL PRAKTIK

## Pembuatan Database

Langkah pertama sesuai Acara 17 adalah membuat database `penjualan` dengan perintah berikut:

```sql
CREATE DATABASE penjualan
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE penjualan;
```

Perintah di atas dieksekusi sekali melalui tab SQL di phpMyAdmin (login sebagai `root`). Charset `utf8mb4` dipilih agar database mendukung seluruh karakter Unicode. Verifikasi dilakukan dengan `SHOW DATABASES`, yang menampilkan `penjualan` dalam daftar.

## Pembuatan Tabel Distributor

Tabel `Distributor` adalah tabel master tanpa kunci tamu, dibuat sesuai sintaks Acara 17 langkah 3 dengan tambahan kunci primer:

```sql
CREATE TABLE IF NOT EXISTS Distributor (
  idDistributor INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama_distributor VARCHAR(45) NULL
) ENGINE=InnoDB;
```

Kolom `idDistributor` memakai `AUTO_INCREMENT` sehingga nomor distributor terisi otomatis. Hasil eksekusi: pesan hijau *"MySQL returned an empty result set"*, dan tabel `Distributor` muncul di panel navigasi database `penjualan`.

## Pembuatan Tabel Pembeli

Tabel `Pembeli` adalah tabel master kedua, dibuat sesuai sintaks Acara 17 langkah 4 dengan tambahan kunci primer:

```sql
CREATE TABLE IF NOT EXISTS Pembeli (
  idPembeli INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama VARCHAR(45) NULL,
  Alamat VARCHAR(45) NULL,
  No_telp VARCHAR(45) NULL,
  Tgl_lahir DATE NULL
) ENGINE=InnoDB;
```

Kolom `Tgl_lahir` memakai tipe `DATE` karena hanya menyimpan tanggal lahir tanpa komponen waktu. Kedua tabel master ini dibuat lebih dulu karena akan dirujuk oleh tabel berikutnya.

## Pembuatan Tabel Master Barang

Tabel `Master_barang` menyimpan data barang beserta distributornya, dibuat sesuai sintaks Acara 17 langkah 2 dengan tambahan kunci primer dan kunci tamu:

```sql
CREATE TABLE IF NOT EXISTS Master_barang (
  id_barang INT(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Nama_barang VARCHAR(25) NULL,
  Harga_satuan INT(3) NULL,
  Pajak INT(3) NULL,
  Diskon INT(3) NULL,
  Distributor_idDistributor INT NOT NULL,
  CONSTRAINT fk_barang_distributor FOREIGN KEY (Distributor_idDistributor)
    REFERENCES Distributor (idDistributor) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

Aturan `ON DELETE RESTRICT` dipilih karena distributor yang masih memiliki barang tidak boleh dihapus agar barang tidak menjadi "tidak bertuan". Hasil eksekusi: `SHOW CREATE TABLE Master_barang` menampilkan `CONSTRAINT fk_barang_distributor ... ON DELETE RESTRICT` sesuai rancangan.

## Pembuatan Tabel Transaksi Pembelian

Tabel terakhir, `trans_pembelian`, mencatat transaksi pembelian sebagai tabel penghubung antara pembeli dan barang, dibuat sesuai sintaks Acara 17 langkah 5 dengan tambahan kunci primer komposit dan dua kunci tamu:

```sql
CREATE TABLE IF NOT EXISTS trans_pembelian (
  Pembeli_idPembeli INT NOT NULL,
  Master_barang_id_barang INT(3) NOT NULL,
  tgl_pembelian DATE NULL,
  jumlah_beli INT(3) NULL,
  PRIMARY KEY (Pembeli_idPembeli, Master_barang_id_barang),
  CONSTRAINT fk_trans_pembeli FOREIGN KEY (Pembeli_idPembeli)
    REFERENCES Pembeli (idPembeli) ON DELETE CASCADE,
  CONSTRAINT fk_trans_barang FOREIGN KEY (Master_barang_id_barang)
    REFERENCES Master_barang (id_barang) ON DELETE CASCADE
) ENGINE=InnoDB;
```

Kunci primer komposit `(Pembeli_idPembeli, Master_barang_id_barang)` dipakai karena tabel ini memodelkan relasi banyak-ke-banyak: satu pasangan pembeli dan barang muncul sekali per baris transaksi. Kedua kunci tamu memakai `ON DELETE CASCADE` karena baris transaksi tanpa pembeli atau barang sumbernya sudah tidak relevan. Kolom `tgl_pembelian` memakai `DATE`, dan `jumlah_beli` memakai `INT`.

## Verifikasi Akhir

Setelah keempat tabel terbentuk, verifikasi akhir dijalankan:

```sql
SHOW TABLES;
```

Hasilnya menampilkan keempat tabel: `Distributor`, `Master_barang`, `Pembeli`, `trans_pembelian`. Setiap tabel kemudian diperiksa dengan `SHOW CREATE TABLE`, dan seluruh kunci primer, kunci tamu, serta aturan `ON DELETE` tampil sesuai rancangan. Tampilan relasi pada menu Designer phpMyAdmin memperlihatkan `Distributor` terhubung ke `Master_barang`, serta `Pembeli` dan `Master_barang` keduanya terhubung ke `trans_pembelian`, sesuai desain Acara 17.
