# SPESIFIKASI ARSITEKTUR & KRITERIA UJI - WEB SISTOMATE (Produksi)

| | |
|---|---|
| **Fase siklus** | 🔴 **RED** (spec + acceptance test; implementasi oleh S2) |
| **Peran penyusun** | S1 - Architect & Code Auditor (sesi `sistomate-1`) |
| **Implementer** | S2 - Heavy Coder (sesi `sistomate-2`) |
| **Owner** | User (koj) |
| **Quality Gate** | `tools/audit-web.py` → `[PASS]`/`[FAIL]` W1–W5 |
| **Tanggal** | 28 Oktober 2026 |

> **Aturan main:** S1 menyusun spec + audit script & **TIDAK** menulis kode web. S2 menulis kode (`dashboard/*.php`, `sql/configs.sql`) sampai `audit-web.py` lolos, lalu lapor GREEN. S1 mengaudit independen.

---

## Arsitektur Ringkas

```
[Browser] --> dashboard/*.php --> [MySQL: users, sensor_logs, actuator_logs, configs]
                 ^                        ^
   ESP32 (firmware SISTOMATE.ino) --HTTP POST--> ingest.php --> MySQL
```

- **Stack:** PHP 8 + MySQL (InnoDB), sesi PHP untuk autentikasi, Chart.js untuk grafik realtime.
- **Data realtime:** `sensor_logs` (suhu, kelembapan udara, soil, RGB) & `actuator_logs` (air, pupuk, kipas).
- **Logika kendali (dari firmware, jadi acuan ambang):** histeresis soil `<40%` ON / `>=55%` OFF; suhu `>32°C` ON / `<=31°C` OFF. Ambang ini yang bisa diubah lewat W5 (`configs`).

### File layout target

```
dashboard/
  index.html        # W1 Landing page
  login.php         # W2 Auth
  logout.php        # W2 Auth
  app.php           # W3 Dashboard utama
  history.php       # W4 Riwayat
  export-csv.php    # W4 Export CSV
  settings.php      # W5 Konfigurasi ambang
  live.php          # (sudah ada) endpoint JSON data
sql/
  schema.sql        # (sudah ada) 5 tabel
  configs.sql       # W5 tabel ambang histeresis (BARU)
```

> Catatan path: `login.php`/`logout.php` boleh di root repo **atau** di `dashboard/` - audit menerima keduanya. `app.php`, `history.php`, `export-csv.php`, `settings.php` **wajib** di `dashboard/`.

---

## W1 - Landing Page (`dashboard/index.html`)

**Fungsi:** halaman publik/promosi sebelum login.

Kriteria (dicek otomatis `audit-web.py`):
- [ ] File `dashboard/index.html` ada.
- [ ] Memuat **Hero** (judul besar + ringkasan sistem; tandai dgn class/id/keyword `hero` atau heading utama).
- [ ] Memuat **4 masalah petani vs solusi** - sebutan "masalah" (subjektivitas, keterlambatan, keterbatasan fisik, ketiadaan histori) yang dipasangkan solusi SISTOMATE.
- [ ] Memuat **profil Kelompok 3** (string "Kelompok 3" +/atau daftar anggota).
- [ ] Memuat **tombol CTA ke login** - link `login.php` (anchor `<a ... login.php>` atau tombol "Masuk"/"Login").

## W2 - Autentikasi (`login.php`, `logout.php`)

**Fungsi:** cek kredensial di tabel `users`, jaga sesi, redirect tamu.

Kriteria:
- [ ] `login.php` ada (root atau `dashboard/`).
- [ ] `login.php` memuat **`password_verify`** (verifikasi hash, bukan banding teks-plaintext).
- [ ] `login.php` memuat **`session_start`** + query tabel **`users`**.
- [ ] `logout.php` ada + memuat **`session_destroy`** (akhiri sesi).
- [ ] Terindikasi **proteksi session/redirect** (ada pengecekan sesi login - mis. `$_SESSION` dicek sebelum akses halaman terproteksi).

## W3 - Dashboard Utama (`dashboard/app.php`)

**Fungsi:** tampilan monitoring realtime (terproteksi sesi).

Kriteria:
- [ ] `dashboard/app.php` ada.
- [ ] `app.php` memuat **`session_start`** (terproteksi).
- [ ] Memuat **Chart.js** (`chart.js`/`Chart(`) untuk grafik realtime.
- [ ] Memuat **Gauge Meter** untuk Suhu / Soil / RH (keyword `gauge`).
- [ ] Memuat **status warna daun RGB** (keyword `rgb`/`warna`/`TCS`).
- [ ] Memuat **badge aktuator** air, pupuk, kipas (keyword `pompa`/`kipas`/`aktuator`).
- [ ] **Navbar mobile** (terindikasi nav responsif - keyword `navbar`/`nav`).

## W4 - Riwayat & Export CSV (`dashboard/history.php`, `dashboard/export-csv.php`)

**Fungsi:** tabel log + filter tanggal + unduh CSV.

Kriteria:
- [ ] `dashboard/history.php` ada + memuat **filter tanggal** (keyword `tanggal`/`date`/`between`/filter).
- [ ] `dashboard/export-csv.php` ada.
- [ ] `export-csv.php` memuat **header CSV** - `text/csv` dan/atau `Content-Disposition` + `attachment` (unduhan `.csv`).

## W5 - Konfigurasi Ambang Batas (`dashboard/settings.php`, `sql/configs.sql`)

**Fungsi:** ubah ambang histeresis via form slider, simpan ke tabel `configs`.

Kriteria:
- [ ] `dashboard/settings.php` ada.
- [ ] `settings.php` memuat **slider** (`<input type="range"` / keyword `slider`) untuk ambang.
- [ ] `settings.php` menulis ke tabel **`configs`** (keyword `configs`).
- [ ] `sql/configs.sql` ada + memuat **`CREATE TABLE configs`** dgn kolom ambang (soil/suhu/threshold/hyster).

---

## Kriteria Lolos Global (GREEN)

- [ ] Seluruh file `*.php` di `dashboard/` (+`login.php`/`logout.php`) **lolos `php -l`** (tanpa syntax error).
- [ ] `audit-web.py` mencetak **`[PASS]` untuk W1–W5** & exit 0.

**Verifikasi S1 (independen):** jalankan `python3 tools/audit-web.py` sendiri (build/test gate), lalu audit kode kunci (keamanan `password_verify`, proteksi sesi, query parameterized) secara manual sebelum CLOSED.

---

## Hasil Audit S1 (28 Okt 2026) - 🟢 GREEN & CLOSED

**Gate otomatis:** `python3 tools/audit-web.py` -> **35/35 PASS, exit 0** (audit independen S1, bukan klaim). `php -l` 8/8 file PHP bersih. Sanitasi em-dash: **0 karakter** di seluruh file kerja (diverifikasi grep mandiri).

### Bug gate S1 yang ditemukan & diperbaiki (catatan transparan)
S2 melaporkan 1 FAIL `W2 proteksi session/redirect` sebagai **bug script audit**, bukan bug kode. S1 reproduksi empiris: `_has()` meng-lowercase teks tapi memakai pola mentah (huruf besar) -> `r"\$_SESSION"` mustahil match walau `login.php` berisi literal `$_SESSION` (3 baris). S1 perbaiki ke akar: `_has()` sekarang memakai `re.IGNORECASE` terhadap teks asli, sesuai kontrak "case-insensitive" yang sudah dideklarasikan docstring-nya. Dampak laten (`new\s+Chart` di W3) ikut beres.

### Audit manual keamanan (S1, baca kode langsung)
- **login.php** - LOLOS: `password_verify` thd kolom `password_hash`, prepared statement (`?` + execute), `session_regenerate_id(true)` (anti session-fixation), guard redirect, output escaping `e()`.
- **logout.php** - LOLOS: `$_SESSION = []`, hapus cookie sesi, `session_destroy()`, redirect.
- **db.php** - LOLOS: PDO singleton `ERRMODE_EXCEPTION`, kredensial dari env `/etc/sistomate-ingest.env` (tidak hardcode), `require_login()` (redirect tamu), helper `e()` = `htmlspecialchars ENT_QUOTES`.
- **app.php** - LOLOS: `require_login()`, ambang dari `configs` dgn fallback = nilai firmware (soil 40/55, suhu 32/31, fert 7 dtk - konsisten dgn `SISTOMATE.ino`), output aman.
- **history.php** - LOLOS: `require_login()`, validasi tanggal ketat (`Y-m-d` exact), prepared statement `BETWEEN ? AND ?`, semua sel pake `e()` + cast numerik.
- **export-csv.php** - LOLOS: `require_login()`, whitelist `jenis` sensor|actuator (bukan interpolasi input), parameter binding, header `text/csv` + `attachment` benar.
- **settings.php** - LOLOS: validasi rentang & konsistensi histeresis **server-side** (soil ON < OFF, suhu OFF < ON - sejajar logika firmware), UPDATE prepared + INSERT idempoten.
- **configs.sql** - LOLOS: `CREATE TABLE configs` (soil_on 40 / soil_off 55 / temp_on 32 / temp_off 31 / fert 7 = ambang firmware), baris default idempoten (`ON DUPLICATE KEY` / `WHERE NOT EXISTS`), tidak ALTER tabel lama; akun admin bootstrap memakai bcrypt hash `$2y$12$` valid.

### Catatan minor (dicatat, bukan blocker)
1. Password admin default tertulis di komentar `configs.sql` (akun bootstrap) - sudah diberi peringatan "GANTI SEGERA"; Owner/Operator wajib mengganti setelah deploy.
2. `app.php` merakit badge via `innerHTML` dari data `actuator_logs` - risiko XSS teoretis minimal karena data diisi firmware via `ingest.php` yang di-API-key; untuk hardening lanjutan bisa diganti `textContent`.

**Putusan S1:** W1-W5 terpenuhi seluruhnya, keamanan dasar lolos audit manual. **WEB SISTOMATE GREEN & CLOSED** (audit independen; harness S2 `test-web.sh` 49/49 dicatat sebagai klaim pendukung).
