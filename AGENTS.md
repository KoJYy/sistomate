# AGENTS.md — Panduan Kontribusi SISTOMATE

Panduan singkat untuk anggota tim dan agen coding yang membantu pengembangan repo.
Dokumentasi penggunaan ada di `README.md`.

## Isi repo

| Folder | Isi |
|---|---|
| `website/` | Web produksi: landing page (`index.html`) + dashboard terautentikasi (`dashboard/`: login, monitoring, riwayat, pengaturan). Aset di `assets/`, galeri logo di `showcase/`. |
| `arsip-website/` | Arsip versi web lama — tidak dipakai. |
| `web-up/` | Kandidat versi dashboard berikutnya — belum dipakai. |
| `sistomate-firmware/` | Sketch ESP32 (`SISTOMATE/SISTOMATE.ino`), endpoint `ingest.php`, dan skrip demo. |
| `sql/` | Skema, konfigurasi, seed, query demo, dan migrasi. |
| `gambar/` | Diagram arsitektur, skematik, flowchart, dan render 3D. |

Dokumen internal (CAD, ERD, spesifikasi) disimpan di luar repo dan tidak dipublikasikan.

## Sumber kebenaran

- Skema database: `sql/schema.sql` (5 tabel inti) + `sql/configs.sql` (tabel `configs`).
- Web: `website/dashboard/*.php` dan `website/assets/theme.css`.
- Firmware: `sistomate-firmware/SISTOMATE/SISTOMATE.ino`.
- Sistem final memakai 5 tabel inti (`users`, `devices`, `sensor_logs`,
  `actuator_logs`, `gallery`) + `configs`; tanpa AI/Gemini dan tanpa ESP32-CAM.
- Acuan pin firmware: relay aktif-LOW GPIO 26/**25**/27 (GPIO14 JANGAN dipakai — PWM saat boot),
  soil GPIO 34, DHT11 GPIO 13,
  TCS34725 I2C (SDA 21, SCL 22). Hindari GPIO strapping 0/2/12/15; GPIO 34/35 input-only.

## Aturan web

- Halaman terautentikasi wajib memakai `require_login()` + `navbar()` dari
  `website/dashboard/db.php`. Jangan menduplikasi header/navbar per halaman.
- Tema bersama ada di `website/assets/theme.css`; `app.php` memakai CSS inline sendiri.
- Lint setiap perubahan: `php -l website/dashboard/<file>.php`.
- `SISTOMATE_ENV` dipakai `website/dashboard/db.php` dan `website/dashboard/live.php`
  untuk menunjuk file env alternatif (DB uji). `ingest.php` selalu membaca
  `/etc/sistomate-ingest.env`.
- Jangan menjalankan seed/DDL/destructive query ke database produksi saat menguji.

## Database

- MySQL 8.x. Urutan instalasi: `schema.sql` → `seed.sql` (opsional) → `configs.sql`.
  `configs.sql` harus dijalankan setelah tabel `devices` terisi.
- `configs` bersifat per-device (`id_device` UNIQUE + FK ke `devices`).
- Migrasi ada di `sql/migrations/`; backup dulu sebelum menjalankan migrasi produksi.
- Detail grant user aplikasi ada di `sql/README.md`.

## Firmware

- Satu sketch utama: `sistomate-firmware/SISTOMATE/SISTOMATE.ino`. Buka folder
  `sistomate-firmware/SISTOMATE` di Arduino IDE, edit blok **KONFIGURASI** di
  baris paling atas, lalu klik Upload. Tidak ada skrip render/flash perantara.
- Nilai yang diisi manual di blok konfigurasi: `WIFI_SSID`, `WIFI_PASS`,
  `SERVER_URL` (IP LAN laptop + `/ingest.php`), `API_KEY`, dan `MQTT_HOST` /
  `MQTT_PASS`. `MQTT_HOST` memakai IP yang sama dengan `SERVER_URL`.
- Di repo, blok **KONFIGURASI** wajib berisi placeholder (`YOUR_...`).
  Isi nilai asli hanya di salinan lokal sebelum upload, dan **jangan pernah
  commit kredensial asli**. Salinan lokal berisi kredensial boleh disimpan di
  `tools/backup/` (gitignored).
- Sebelum commit, jalankan `tools/test-firmware-mqtt.sh`; gate itu menolak
  placeholder yang hilang dan API key asli, jadi hasilnya harus 90/90 GREEN.
- Board: **ESP32 Dev Module**; core **esp32 by Espressif 2.0.17** (sketch memakai
  `mqtt_client.h` dari core, bukan library tambahan).
- Library lewat Library Manager: **DHT sensor library** (Adafruit),
  **Adafruit Unified Sensor**, **Adafruit TCS34725**.
- `arduino-cli compile --fqbn esp32:esp32:esp32 sistomate-firmware/SISTOMATE`
  hanya dipakai untuk verifikasi otomatis, bukan alur utama pengguna.

## Git

- Remote utama: `KoJYy/sistomate`. Push hanya setelah diminta pemilik repo.
- Sebelum commit: cek `git status` dan `git diff`; jangan commit/revert pekerjaan
  anggota lain yang belum selesai.
- Jangan commit file lokal/artefak (mis. `tools/`, `docs/`, arsip, screenshot mentah).
- Sebelum mengklaim perbaikan selesai, jalankan pengujian yang relevan dan
  sertakan buktinya.
