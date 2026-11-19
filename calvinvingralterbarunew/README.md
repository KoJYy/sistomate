# SISTOMATE — Paket Siap Jalan (3 Kondisi Tanaman)

Folder mandiri yang berisi **Dashboard Web**, **Database MySQL 3 Kondisi**, **Firmware ESP32**, dan **Flowchart Logika** sehingga bisa langsung dijalankan setelah `git clone`.

---

## Isi Folder

```text
calvinvingralterbarunew/
├── dashboard/              Web landing page & dashboard monitoring (PHP + HTML/CSS/JS)
├── sql/                    Skema 5 tabel inti + configs + seed 3 kondisi + migrasi
├── sistomate-firmware/     Sketch ESP32 (SISTOMATE.ino), ingest.php, skrip demo
├── gambar/src/             Sumber diagram flowchart 3 kondisi (flowchart-logika.mmd)
└── .env.example            Template konfigurasi koneksi database & API key
```

### Ringkasan Pembaruan di Paket Ini

- **Status Tanaman 3 Kondisi (`sehat`, `layu`, `sakit`):** status `kering` digabung ke `layu` (stres air/panas: kelembapan tanah `< soil_on` atau kipas/suhu panas `> temp_on`); `sakit` dari spektrum daun TCS34725 (`G/R < 0.75` dan `R > 100`); selebihnya `sehat`.
- **Dashboard & Navigasi:** sidebar vertikal dengan jam `LIVE` aktif di semua halaman (`Dashboard`, `Riwayat`, `Galeri`, `Pengaturan`), font `Archivo`, logo baru (`logo-sistomate.png`), serta dukungan multi-perangkat (`?id_device=`).
- **Galeri Kondisi Tanaman (`dashboard/gallery.php`):** unggah, filter status, pratinjau di dashboard, dan hapus foto dokumentasi per perangkat.
- **Manajemen Perangkat (`dashboard/settings.php`):** tambah, ubah, dan hapus perangkat ESP32 beserta pengaturan ambang histeresis (`configs`) yang kompatibel dengan **MySQL 8** dan **MariaDB**.
- **Login Username (`dashboard/login.php`):** mendukung masuk dengan username tim maupun email seed.

---

## Cara Clone & Jalankan di Lokal

### 1. Clone Repositori & Masuk ke Folder Ini

```bash
git clone https://github.com/KoJYy/sistomate.git
cd sistomate/calvinvingralterbarunew
```

### 2. Siapkan File Konfigurasi (`.env`)

Salin `.env.example` menjadi `.env`:

- **Linux / macOS / Git Bash:**
  ```bash
  cp .env.example .env
  ```
- **PowerShell (Windows):**
  ```powershell
  Copy-Item .env.example .env
  ```

Sesuaikan isinya bila username/password MySQL lokal berbeda (default XAMPP/Laragon `DB_USER=root` dengan `DB_PASS=` kosong).

### 3. Impor Database MySQL 3 Kondisi

Jalankan ketiga file SQL secara berurutan (**wajib urut:** `schema.sql` -> `seed.sql` -> `configs.sql`):

- **Linux / macOS / Git Bash / CMD:**
  ```bash
  mysql -u root -p < sql/schema.sql
  mysql -u root -p < sql/seed.sql
  mysql -u root -p sistomate < sql/configs.sql
  ```
- **PowerShell (Windows, contoh Laragon):**
  ```powershell
  Get-Content sql/schema.sql  -Raw | & mysql -u root
  Get-Content sql/seed.sql    -Raw | & mysql -u root
  Get-Content sql/configs.sql -Raw | & mysql -u root sistomate
  ```
- **phpMyAdmin:** impor berurutan lewat tab **SQL / Import** -> `sql/schema.sql` -> `sql/seed.sql` -> pilih database `sistomate` -> impor `sql/configs.sql`.

> **Upgrade dari database lama (4 kondisi):** jika database `sistomate` sudah terpasang sebelumnya dengan kolom `ENUM('sehat','kering','layu','sakit')`, backup database dulu lalu jalankan satu kali:
> ```bash
> mysql -u root -p sistomate < sql/migrations/2026-11-19-plant-status-3-kondisi.sql
> ```

### 4. Jalankan Web Server PHP Lokal

```bash
php -S 127.0.0.1:8000 -t dashboard
```

Lalu buka di browser:
- Landing page: `http://127.0.0.1:8000/index.html`
- Login dashboard: `http://127.0.0.1:8000/login.php`

### 5. Akun Login Default

Dari `sql/configs.sql` (`username` = `password`):

| Username | Password | Nama |
|---|---|---|
| `sistomate` | `sistomate` | Administrator |
| `yudha` | `yudha` | Yuda Maredya A. P. |
| `calvin` | `calvin` | Moch Calvin Aprilian |
| `tio` | `tio` | Tio Dwi Harianto |
| `ibror` | `ibror` | Ibror Yusron S. |
| `nabil` | `nabil` | Nabil Laudza Nur |

Akun demo tambahan dari `sql/seed.sql`: `budi@polije.ac.id` dan `ayu@polije.ac.id` (password `Demo123!`).

---

## Catatan Firmware ESP32

Sebelum mem-flash `sistomate-firmware/SISTOMATE/SISTOMATE.ino` ke ESP32, isi nilai lokal pada baris atas sketch (jangan pernah commit kredensial asli ke Git):
- `WIFI_SSID` & `WIFI_PASS`
- `SERVER_URL` (mengarah ke endpoint `ingest.php`)
- `API_KEY` (samakan dengan `API_KEY` di `.env` atau `/etc/sistomate-ingest.env`)
- `DEVICE_ID` (sesuaikan dengan `id_device` di tabel `devices`)
