# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Workshop Basis Data (WBD)** — Mata kuliah workshop basis data. Project: "Sistem Otomatisasi Penyiraman dan Monitoring Tanaman Berbasis IoT Menggunakan ESP32-CAM dan Kecerdasan Buatan" (Studi Kasus: Smart Greenhouse Tanaman Cabai — Otomatisasi Penyiraman + Monitoring Berbasis AI via Gemini API).

**Spec:** `docs/superpowers/specs/2026-09-01-plantmonitor-design.md` — desain lengkap (1190 baris). Baca ini dulu sebelum implementasi apapun.

## Tech Stack (Planned)

| Komponen | Teknologi |
|----------|-----------|
| Hardware | ESP32-CAM (kamera) + ESP32 (6 sensor + relay pompa) |
| AI Analysis | Google Gemini API (kirim gambar → dapat analisis kesehatan tanaman) |
| Database | MySQL 8.x |
| Backend | PHP 8.x + PDO |
| Frontend | Bootstrap 5 + Chart.js + Vanilla JS |
| Server | Nginx + PHP-FPM |
| Protokol | HTTP langsung (ESP32 → server) |

## Database Schema

10 tabel: `plants`, `sensors`, `sensor_readings`, `images`, `pump_actions`, `alerts`, `users`, `pending_commands`, `plant_health_analysis`, `ai_analysis`. Termasuk stored procedures (`sp_insert_reading`, `sp_get_history`, `sp_correlation_analysis`), trigger (`trg_low_moisture`), event (`evt_cleanup_old_data`), views (`vw_plant_summary`, `v_plant_health_summary`).

## Build / Deploy

SISTOMATE udah jalan end-to-end (demo 2026-10-22 aman): ESP32 → nginx+php-fpm → MySQL `sistomate` → dashboard realtime.

### Dev Workflow
- **Stack lokal (bukan VPS):** nginx+php-fpm+mysqld di laptop, webroot `/usr/share/nginx/html/` — start manual: `sudo systemctl start mysqld nginx php-fpm` (auto-start dimatiin)
- **Deploy ingest.php / dashboard:** `sudo cp <file> /usr/share/nginx/html/` (HTML perlu `git add -f`, `.gitignore` blok `*.html`)
- **Syntax check:** `php -l ingest.php`
- **Firmware:** `pkill -x arduino-cli; arduino-cli upload -p /dev/ttyUSB0 --fqbn esp32:esp32:esp32 sistomate-firmware/SISTOMATE`
- **DB:** password root in-session via `export MYSQL_PWD` (jangan simpan di file); app user cuma SELECT+INSERT

## File Structure

```
dashboard/            ← index.html (realtime) + live.php, deploy ke nginx root
sistomate-firmware/   ← SISTOMATE.ino (ESP32) + SKRIPT-DEMO.md
sql/                  ← DDL, seed, stored procedures
chapters/             ← laporan aktif (4 bab), build: make build → Laporan.pdf
laporan-m5|m6|m7/     ← arsip laporan per modul (self-contained)
arsip/                ← output lama (WBD-M4*, dll, di-ignore git)
docs/                 ← prototype-plan, referensi-ta-iot, spec design, BKPM
```

## ESP32 Gotchas

- **GPIO 15:** Strapping pin — pakai GPIO 13 buat DHT11
- **Relay driver:** GPIO max 40mA, relay coil 80mA — **WAJIB** pakai transistor 2N2222 + flyback diode 1N4007
- **GPIO 34/35:** Input only, gak bisa output — OK buat sensor analog

## Deployment Target

**Laptop lokal** (hotspot HP → `10.244.177.37`), BUKAN VPS. Nginx :80 + php-fpm + mysqld, API key `sm_...` di `/etc/sistomate-ingest.env`. Spec section 11–12 tetap referensi desain awal (path VPS udah kegantikan).

## Language

Respond in Indonesian (Bahasa Indonesia). Technical terms (code, CLI commands, config) keep English.

## Parent Context

Parent `~/CLAUDE.md` punya detail VPS (IP, SSH), info STB, dan workspace conventions. Refer ke sana untuk pertanyaan infrastruktur.

## Conventions

- Spec document adalah source of truth. Setiap perubahan design harus update spec dulu.
- Backend PHP pakai PDO prepared statements (bukan mysqli).
- Semua API endpoint harus validasi API key (ESP32) atau session (dashboard).
- Error handling: log detail ke `error_log()`, return generic error ke client.
- Database: semua FK pakai `ON DELETE CASCADE`. Tabel `sensor_readings` pakai `DECIMAL(5,2)` (support suhu negatif).

## Design Session Patterns

- Judul TA: max 12 kata, subjek utama di depan (basis data, bukan IoT).
- Case study harus demonstrable (bisa demo live dengan hardware murah, bukan cuma teori).
- Spec-first: update spec dulu, baru CLAUDE.md.

## Tavily Fallback

- Kalau `webSearch` tool gak ada / error: langsung curl `http://127.0.0.1:20128/api/v1/search` dengan API key + `provider: tavily`.
- Kalau tunnel down: fallback DuckDuckGo HTML (`html.duckduckgo.com/html/?q=...`) via urllib.
