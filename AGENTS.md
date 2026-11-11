# AGENTS.md — SISTOMATE (WBD)

Proyek Workshop Basis Data: SISTOMATE (otomasi penyiraman & monitoring tanaman tomat berbasis ESP32 + PHP + MySQL) beserta model CAD 3D mini greenhouse.

Isi repo publik: `dashboard/`, `sistomate-firmware/`, `sql/`, `docs/`, `gambar/`, `README.md`, `LICENSE`, `.gitignore`.

## Sumber kebenaran (dan dokumen usang)

- **`CLAUDE.md` (arsip lokal, tidak dipublikasikan) sebagian usang** — masih menyebut Gemini, ESP32-CAM, 10 tabel, tanaman cabai, dan webroot `/usr/share/nginx/html`. Jangan implementasi dari situ.
- Juga usang: `docs/superpowers/specs/2026-09-01-plantmonitor-design.md` (desain awal, bukan sistem final).
- Yang berlaku: `sql/schema.sql` + `sql/configs.sql`, `dashboard/*.php`, `sistomate-firmware/SISTOMATE/SISTOMATE.ino`, `docs/web-spec.md`.
- Sistem final: 5 tabel inti (`users`, `devices`, `sensor_logs`, `actuator_logs`, `gallery`) + `configs`; tanpa AI/Gemini, tanpa ESP32-CAM.
- Firmware acuan: relay aktif-LOW GPIO 26/14/27, soil GPIO 34, DHT11 GPIO 13, TCS34725 I2C 21/22, histeresis 40/55/32/31.

## Web dashboard

- Halaman terautentikasi wajib `require_login()` + `navbar()` dari `dashboard/db.php`; navbar single-source, jangan duplikasi header per halaman. Tema bersama: `dashboard/assets/theme.css`.
- Lint: `php -l dashboard/<file>.php`.
- Gate cepat & aman: `python3 tools/audit-web.py` (W1–W5, harus exit 0).
- Harness penuh: `bash tools/test-web.sh` — `php -S 127.0.0.1:8181` + DB `sistomate_webtest`; butuh `mysqld` aktif.
- **`SISTOMATE_ENV` dibaca `dashboard/db.php` dan `dashboard/live.php`.** `ingest.php` selalu membaca `/etc/sistomate-ingest.env`; jangan asumsikan semua request terisolasi DB test.
- Deploy: `bash tools/deploy.sh` → `/var/www/sistomate` (root nginx di `/etc/nginx/conf.d/sistomate.conf`), **bukan** `/usr/share/nginx/html`. Deploy hanya jika diminta.
- Admin default di `sql/configs.sql` masih `admin@sistomate.id` dengan password contoh; ganti sebelum deploy publik.

## Lingkungan database

- Fedora 44, MySQL 8.4 systemd: `sudo systemctl start mysqld nginx php-fpm`. `mysqld` disabled — mati setelah reboot.
- Root MySQL memakai password: `sudo mysql` gagal. Pakai phpMyAdmin (`http://localhost/phpMyAdmin/`) atau `MYSQL_PWD`.
- Produksi: DB `sistomate`, kredensial di `/etc/sistomate-ingest.env`. Test: `sistomate_webtest` (kredensial di `tools/test-web.sh`).
- Jangan menjalankan seed/DDL/destructive query ke `sistomate` saat menguji; gunakan `sistomate_webtest`.
- Skema: `sql/schema.sql` (inti), `sql/configs.sql` (+configs & admin); data demo: `sql/seed.sql`/`sql/demo.sql`.

## Firmware

- Upload: `pkill -x arduino-cli; arduino-cli upload -p /dev/ttyUSB0 --fqbn esp32:esp32:esp32 sistomate-firmware/SISTOMATE`.
- `API_KEY` di sketch = placeholder `sm_YOUR_SECRET_API_KEY`; isi ulang dari `/etc/sistomate-ingest.env` sebelum flash. Jangan commit key asli.
- Hindari GPIO strapping 0/2/12/15; GPIO 34/35 input-only.

## CAD & FreeCAD

- Model: `docs/cad/greenhouse.FCStd`; sumbu X=1000 (panjang), Y=800 (lebar), Z=600 (tinggi).
- FreeCAD MCP: `execute_code` aman; `execute_code_async` yang menyentuh dokumen/view/GUI membuat FreeCAD crash. Flatpak `DISPLAY=:0`, RPC :9875.

## Git & kerja tim

- `origin` = `KoJYy/sistomate`. Push hanya ke origin, dan hanya setelah Owner minta.
- `.gitignore` mengabaikan `tools/`, `*.pdf`, `*.docx`, `*.html` — kecuali `dashboard/*.html` dan `dashboard/showcase/*.html`. Gate scripts di `tools/` tidak ikut clone.
- Sesi paralel bisa meninggalkan perubahan/untracked (mis. `docs/WBD.txt`, `dashboard.zip`); jangan commit/revert pekerjaan yang bukan milikmu. Cek `git status` sebelum commit.
- Konvensi tim: S1 = Tech Lead/Auditor, S2 = Implementer; komunikasi via `node whisper.js s1|s2 "..."`. TDD: buktikan RED dulu, klaim GREEN harus disertai output gate.

## Referensi

- Status/backlog lokal: `tools/TODO.md` (gitignored).
- Spesifikasi web W1–W5: `docs/web-spec.md` (status fase di header sudah usang).
