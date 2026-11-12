# AGENTS.md — Panduan Kontribusi SISTOMATE

Panduan singkat untuk anggota tim dan agen coding yang membantu pengembangan repo.
Dokumentasi penggunaan ada di `README.md`.

## Isi repo

| Folder | Isi |
|---|---|
| `dashboard/` | Dashboard web (PHP + HTML/CSS/JS): landing, login, monitoring, riwayat, pengaturan. Aset di `assets/`, galeri logo di `showcase/`. |
| `sistomate-firmware/` | Sketch ESP32 (`SISTOMATE/SISTOMATE.ino`), endpoint `ingest.php`, dan skrip demo. |
| `sql/` | Skema, konfigurasi, seed, query demo, dan migrasi. |
| `gambar/` | Diagram arsitektur, skematik, flowchart, dan render 3D. |

Dokumen internal (CAD, ERD, spesifikasi) disimpan di luar repo dan tidak dipublikasikan.

## Sumber kebenaran

- Skema database: `sql/schema.sql` (5 tabel inti) + `sql/configs.sql` (tabel `configs`).
- Web: `dashboard/*.php` dan `dashboard/assets/theme.css`.
- Firmware: `sistomate-firmware/SISTOMATE/SISTOMATE.ino`.
- Sistem final memakai 5 tabel inti (`users`, `devices`, `sensor_logs`,
  `actuator_logs`, `gallery`) + `configs`; tanpa AI/Gemini dan tanpa ESP32-CAM.
- Acuan pin firmware: relay aktif-LOW GPIO 26/14/27, soil GPIO 34, DHT11 GPIO 13,
  TCS34725 I2C (SDA 21, SCL 22). Hindari GPIO strapping 0/2/12/15; GPIO 34/35 input-only.

## Aturan web

- Halaman terautentikasi wajib memakai `require_login()` + `navbar()` dari
  `dashboard/db.php`. Jangan menduplikasi header/navbar per halaman.
- Tema bersama ada di `dashboard/assets/theme.css`; `app.php` memakai CSS inline sendiri.
- Lint setiap perubahan: `php -l dashboard/<file>.php`.
- `SISTOMATE_ENV` dipakai `dashboard/db.php` dan `dashboard/live.php` untuk menunjuk
  file env alternatif (DB uji). `ingest.php` selalu membaca `/etc/sistomate-ingest.env`.
- Jangan menjalankan seed/DDL/destructive query ke database produksi saat menguji.

## Database

- MySQL 8.x. Urutan instalasi: `schema.sql` → `seed.sql` (opsional) → `configs.sql`.
  `configs.sql` harus dijalankan setelah tabel `devices` terisi.
- `configs` bersifat per-device (`id_device` UNIQUE + FK ke `devices`).
- Migrasi ada di `sql/migrations/`; backup dulu sebelum menjalankan migrasi produksi.
- Detail grant user aplikasi ada di `sql/README.md`.

## Firmware

- `API_KEY`, `WIFI_SSID`, dan `WIFI_PASS` di sketch wajib placeholder di repo.
  Isi nilai asli secara lokal dari `/etc/sistomate-ingest.env` sebelum flash.
  **Jangan pernah commit kredensial asli.**
- Upload:
  ```bash
  arduino-cli upload -p /dev/ttyUSB0 --fqbn esp32:esp32:esp32 sistomate-firmware/SISTOMATE
  ```

## Git

- Remote utama: `KoJYy/sistomate`. Push hanya setelah diminta pemilik repo.
- Sebelum commit: cek `git status` dan `git diff`; jangan commit/revert pekerjaan
  anggota lain yang belum selesai.
- Jangan commit file lokal/artefak (mis. `tools/`, `docs/`, arsip, screenshot mentah).
- Sebelum mengklaim perbaikan selesai, jalankan pengujian yang relevan dan
  sertakan buktinya.
