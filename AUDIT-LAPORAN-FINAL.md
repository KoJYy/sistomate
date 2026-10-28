# AUDIT — LAPORAN FINAL SISTOMATE
### Kriteria Pengujian / Acceptance Test (Fase RED — Quality Gate)

| | |
|---|---|
| **Fase siklus** | 🔴 **RED** (acceptance test ditulis, implementasi BELUM ada) |
| **Peran penyusun** | S1 — Tech Lead & Auditor (sesi `sistomate-1`) |
| **Implementer** | S2 — Implementer & Coder (sesi `sistomate-2`) |
| **Owner** | User (koj) |
| **Tanggal** | 28 Oktober 2026 |
| **Status akhir dokumen ini** | 🟢 **GREEN — AUDITED & CLOSED** (audit independen S1, 28 Okt 2026) |

---

## 0. Diagnostik RED (bukti kondisi awal GAGAL)

Diverifikasi S1 secara independen pada 28 Okt 2026 **sebelum** tiket dikirim ke S2:

| # | Fakta terverifikasi | Implikasi |
|---|---|---|
| 1 | Folder kerja masih `laporan-proyek/` (BELUM `laporan-final/`) | K1 (rename) **GAGAL** |
| 2 | `metadata.yml` masih judul *"Laporan Generator"* (bukan SISTOMATE) | K3 (metadata) **GAGAL** |
| 3 | `laporan-proyek/` status git = **UNTRACKED** (belum commit) | Perlu hati-hati; rename+rewrite = laporan Laporan Generator bisa hilang |
| 4 | `presets/pedoman-jember.yml` **ADA** & nilai TEPAT (4/3/4/3cm, Libertinus Serif 10pt, 1.0em, indent 1.25cm, "BAB "+arabic) | K2 achievable (bukan kriteria mustahil) |
| 5 | Belum ada `Laporan.pdf` berisi 5 tabel DB, skematik alat, flowchart, arsitektur 4-layer, atau 3D CAD | K4 **GAGAL** |

> **Kesimpulan RED:** Seluruh kriteria K1–K5 saat ini **BELUM terpenuhi** → siklus TDD RED sah. S2 wajib mengubah kondisi di atas menjadi GREEN.

---

## K1 — Struktur Folder

- [ ] Folder kerja sudah di-**rename** `laporan-proyek/` → **`laporan-final/`**.
- [ ] Tidak ada folder `laporan-proyek/` tersisa.

**Verifikasi S1:** `test -d laporan-final && ! test -d laporan-proyek`

---

## K2 — Preset Format Wajib (pedoman-jember)

- [ ] `metadata.yml` memakai `preset: "pedoman-jember"`.
- [ ] Build memakai `presets/pedoman-jember.yml` dengan nilai **TEPAT**:

| Parameter | Nilai wajib |
|---|---|
| `margin_top` | `4cm` |
| `margin_bottom` | `3cm` |
| `margin_left` | `4cm` |
| `margin_right` | `3cm` |
| `font_family` | `Libertinus Serif` |
| `font_size` | `10pt` |
| `line_spacing` | `1.0em` |
| `first_line_indent` | `1.25cm` |
| `heading_chapter_prefix` + `num_format` | `"BAB "` + arabic → `BAB 1`, `BAB 2`, … |

**Verifikasi S1:** baca `metadata.yml`; render PDF → ukur margin terpasang; cek heading bab berformat `BAB <angka>`.

---

## K3 — Metadata & Identitas

- [ ] **6 anggota Kelompok 3 — TRK Golongan B** lengkap (nama + NIM) di `metadata.yml`:

| No. | Nama | NIM |
|---|---|---|
| 1 | Yuda Maredya Ari Pratama | E43252953 |
| 2 | Abiyu Ammar Hady | E43252782 |
| 3 | Ibror Yusron S | E43252690 |
| 4 | Moch Calvin Aprilian | E43252720 |
| 5 | Nabil Laudza Nur | E43252939 |
| 6 | Tio Dwi Harianto | E43252983 |

- [ ] **Dosen Pengajar:** Zilvanhisna Emka Fitri, S.T., M.T. & Nanik Anita Mukhlisoh, S.ST., M.T.
- [ ] **Institusi:** Politeknik Negeri Jember — Jurusan Teknologi Informasi.

**Verifikasi S1:** baca `metadata.yml` + cocokkan dengan halaman cover di PDF.

---

## K4 — Kelengkapan Teknis Konten (badan laporan)

Seluruh butir berikut **wajib ada** dan tampil benar di dalam `Laporan.pdf`:

- [ ] **5 tabel basis data** SISTOMATE — `users`, `devices`, `gallery`, `sensor_logs`, `actuator_logs` (lengkap dengan kolom & tipe data).
- [ ] **Skematik alat (common ground)** — diagram koneksi/perangkat keras.
- [ ] **Flowchart paralel**.
- [ ] **Diagram arsitektur 4-layer**.
- [ ] **Pemodelan fisik 3D CAD FreeCAD 100×80×60 cm**.
- [ ] Setiap gambar/diagram punya caption & penomoran yang benar.

**Verifikasi S1 (independen):** ekstrak teks (`pdftotext`) + render halaman (`pdftoppm`) + `pdfimages -list` → semua gambar ter-embed, tidak ada placeholder / gambar hilang / caption rusak.

---

## K5 — Kriteria Lolos (GREEN)

- [ ] `./build.sh` di dalam `laporan-final/` **sukses** → `Laporan.pdf` terbentuk (exit code 0).
- [ ] Build **TANPA warning/error** di stdout & stderr.
- [ ] **Semua gambar ter-embed sempurna** (tidak ada gambar hilang / broken image).
- [ ] PDF valid: halaman A4, penomoran depan (Romawi) vs isi (Arab) benar, daftar pustaka rapi.

**Verifikasi S1:** jalankan `bash build.sh` **sendiri** sebagai S1 (audit independen — jangan percaya klaim S2), tangkap output penuh, cek exit code, lalu audit visual PDF.

---

## Aturan Main Siklus (RED → GREEN → Audit)

1. **S1 (auditor)** menyusun kriteria ini + **TIDAK** menulis isi bab.
2. **S2 (implementer)** menulis isi bab & mengubah struktur sampai seluruh K1–K5 terpenuhi, lalu **whisper balik: GREEN**.
3. **S1** mengaudit GREEN secara independen (menjalankan build sendiri). Lolos → **CLOSED**; gagal → kirim temuan balik ke S2 (siklus RED ulang untuk butir yang gagal).

**Catatan proteksi:** `laporan-proyek/` (laporan tool *Laporan Generator*) berstatus untracked. Sebelum tiket dieksekusi, PDF laporan tersebut di-backup sementara ke `tools/` agar tidak hilang saat rename+rewrite.

---
---

# HASIL AUDIT GREEN (S1 — Auditor Independen)

**Tanggal:** 28 Oktober 2026 · **Metode:** S1 menjalankan `bash build.sh` & audit **sendiri** (tidak mempercayai klaim S2).

## Ringkasan: 🟢 LOLOS — 12/12 kriteria · CLOSED

Quality Gate otomatis `tools/audit-laporan.py` (S1, TDD-validated):

```
[PASS] K1 Folder kerja ada — laporan-final
[PASS] K2 metadata.yml pakai preset pedoman-jember
[PASS] K3 6 NIM anggota di metadata.yml — 6/6
[PASS] K3 2 dosen pengajar di metadata.yml — 2/2
[PASS] K5 build.sh exit code 0 — exit=0
[PASS] K5 build tanpa warning/error — bersih
[PASS] K5 jumlah halaman >= 15 — 31 halaman
[PASS] K5 ukuran halaman A4 — A4
[PASS] K5 pdftotext bisa ekstrak teks
[PASS] K5 memuat heading BAB 1–4 (arabik) — 4/4
[PASS] K5 identitas 6 anggota di PDF (via NIM) — 6/6
[PASS] K4 gambar ter-embed (>= 3) — 7 gambar
HASIL: LOLOS — 12/12 (exit 0)
```

## Temuan audit visual mandiri (render PDF per halaman)

| Kriteria | Hasil | Bukti |
|---|---|---|
| **K1** rename | ✅ | `laporan-final` ADA, `laporan-proyek` HILANG |
| **K2** preset & margin | ✅ | `preset: "pedoman-jember"`; margin template `top4/bot3/left4/right3` cm; heading **arabik** `BAB 1…4`; Libertinus 10pt |
| **K3** identitas | ✅ | Cover: 6 anggota Kelompok 3 + NIM (urut), 2 dosen (Zilvanhisna & Nanik), Politeknik Negeri Jember 2026, logo |
| **K4** konten teknis | ✅ | **5 tabel DB** (Tabel 3.1–3.5: users/devices/gallery/sensor_logs/actuator_logs, 4 kolom tanpa overflow); **Gambar 3.1** arsitektur 4-layer; **3.2** ERD devices-centric; **3.3** skematik common ground (aktif-LOW, GPIO26/14/27); **3.4** flowchart paralel; **3.5/3.6** render 3D tampak **1000×800×600 mm = 100×80×60 cm**. Semua gambar ter-embed & tampil, tak ada placeholder/broken |
| **K5** build & PDF | ✅ | Build exit 0, log bersih tanpa warning; 31 hal A4; penomoran Romawi (depan) vs Arab (isi) benar; daftar pustaka rapi |

## Temuan jujur (transparan)

1. **Bug di script audit S1 (bukan di laporan S2).** Run pertama memberi `[FAIL] K5 memuat BAB I–IV` karena `BAB_WAJIB` script-ku salah pakai angka **romawi** (`BAB I`), padahal requirement di dokumen K2 = **arabik** (`BAB 1`). Laporan S2 ternyata **sudah benar** (`BAB 1…4`, sesuai preset `num_format: arabic`). Script diperbaiki (regex word-boundary, arabik) → 12/12. **Ini ketidakcocokan uji, bukan cacat produk** — goalpost (dokumen) memang arabik sejak RED.
2. **Catatan kosmetik non-blocking:** di cover, NIM anggota terakhir (Tio Dwi Harianto) agak rapat dengan blok "JURUSAN TEKNOLOGI INFORMASI". Tidak melanggar kriteria apa pun — opsional dipoles S2 bila mau.

## Putusan

Seluruh K1–K5 **terpenuhi** berdasar verifikasi independen S1. **Laporan Final SISTOMATE dinyatakan GREEN & CLOSED.**

---
---

# REVISI 14 POIN OWNER (Batch 2 — siklus audit baru)

Owner memberi 14 poin revisi; **7 poin pertama** diterjemahkan jadi cek otomatis di `tools/audit-laporan.py` (REV1–REV7). Catatan: sebagian poin **membalik** kriteria batch-1 (cover kini dilarang memuat 6 nama; dosen 2→3) = requirement change dari Owner, bukan cacat produk.

| # | Kriteria revisi | Cek otomatis |
|---|---|---|
| REV1 | Naskah **tanpa em-dash** (—) | `text.count("—")==0` |
| REV2 | Cover **tanpa 6 nama/NIM**, hanya "Disusun oleh: Kelompok 3 (Golongan B)". 6 NIM tetap di **LAMPIRAN** | cover page-1 bebas NIM/nama + ada "Kelompok 3" & "Golongan B" |
| REV3 | Cover: **2 mata kuliah** (Workshop Basis Data & Workshop Sistem Tertanam) + **3 dosen** (Zilvanhisna/Zilva, Nanik, Yogiswara) | keyword cover |
| REV4 | Cover: Prodi **"Teknologi Rekayasa Komputer"**; urutan footer **Prodi→Jurusan→Kampus→2026/2027** | substring + cek urutan indeks |
| REV5 | **Halaman Abstrak hilang** (langsung Kata Pengantar & Daftar Isi) | tak ada heading standalone "ABSTRAK" + ada KP & DI |
| REV6 | **Bab 1**: 4 masalah petani (subjektivitas, keterlambatan, keterbatasan fisik, ketiadaan histori data) | 4 keyword dalam segmen Bab 1 |
| REV7 | **Bab 3**: gambar antarmuka **Dashboard Web** + **listing source code** | caption "Gambar 3.x:…Dashboard" + caption listing ("Listing 3.x"/"Kode Program") + gambar≥8 |

> **Sinyal deteksi (dikirim ke S2 biar auditable):** REV7 listing harus ber-caption "Listing 3.x:"/"Kode Program"/"Potongan Kode"/"Sumber Kode" (code block murni susah dideteksi dari PDF); REV4 Prodi harus literally "Teknologi Rekayasa Komputer" + tahun "2026/2027".

**TDD RED (diverifikasi S1 thd PDF lama belum-revisi):** K3 dosen 2/3 FAIL · REV1 3 em-dash FAIL · REV2 NIM di cover FAIL · REV3 1/2 matkul+2/3 dosen FAIL · REV4 prodi salah+tahun tdk ada FAIL · REV5 abstrak MASIH ADA FAIL · REV6 2/4 (kurang subjektivitas+keterlambatan) FAIL · REV7 tanpa dashboard+listing FAIL → **8 gagal, gate jujur mendeteksi kondisi belum-revisi.**

### VERDICT AUDIT REVISI — 🟢 GREEN (S1, audit independen build+visual)

`python3 tools/audit-laporan.py` (build sendiri): **19/19 PASS, exit 0, 36 hal A4, 8 gambar.** Verifikasi visual mandiri:
- **Cover** — 2 mata kuliah (WBD & WST), 3 dosen (Zilvanhisna, Nanik, Yogiswara), "Disusun oleh: Kelompok 3 (Golongan B)" **tanpa 6 nama**, footer urut **Prodi "Teknologi Rekayasa Komputer"→Jurusan→Kampus→2026/2027**. 6 NIM pindah ke **LAMPIRAN (hal.36)**.
- **Bab 1** — 4 masalah petani (subjektivitas, keterlambatan, keterbatasan fisik, ketiadaan histori data) terdeteksi.
- **Bab 3** — **Gambar 3.5 Antarmuka Dashboard Web Real-time** (UI: status tanaman, kartu suhu/kelembapan, panel aktuator, grafik tren, warna daun RGB) render sempurna + **listing program** (kode `ingest.php` prepared-statement & Listing 3.1 DDL MySQL, syntax-highlight) tampil benar.
- **REV1** naskah bersih tanpa em-dash.

Seluruh K1–K5 + 7 poin revisi **terpenuhi**. **Revisi batch-2 dinyatakan GREEN & CLOSED.**

---
---

# TAMBAHAN LAPORAN: LAMPIRAN 1 & 2 (batch-3)

Owner menambah 2 halaman Lampiran setelah Daftar Pustaka. Audit S1 independen:

### VERDICT — 🟢 GREEN (S1, audit independen build+visual+remote)

`audit-laporan.py`: **19/19 PASS, exit 0, 37 hal A4, 8 gambar, em-dash 0.**

- **Lampiran 1 (hal.36)** — *Daftar Anggota Kelompok & Matriks Kontribusi*: tabel 4-kolom (No/Nama/NIM/Pembagian Tugas), 6 anggota + NIM benar + 6 bidang (Database/Hardware/Firmware/3D CAD/Frontend/Dokumentasi). Render rapi tanpa overflow.
- **Lampiran 2 (hal.37)** — *Repositori Kode Sumber & Akses Berkas Digital*: pernyataan open-source + struktur direktori + tautan GitHub.

### Verifikasi remote (independen via `gh api`, bukan klaim)
Awalnya S2 menautkan Lampiran 2 ke `muadzhdz/laporan-generator` — **S1 temukan ketidakcocokan**: repo itu isinya tool *Laporan Generator*, bukan kode SISTOMATE. Atas keputusan Owner dibuat repo baru **`https://github.com/KoJYy/sistomate`**. S1 verifikasi:
- Repo **PUBLIC** ✅ · deskripsi sesuai ✅
- Struktur root **cocok 100%** dgn Lampiran 2: `sistomate-firmware/` (SISTOMATE.ino, ingest.php), `dashboard/` (index.html, live.php), `sql/schema.sql`, `docs/cad/greenhouse.FCStd/.step/.stl`, `gambar/`, `laporan-final/` — semua **ADA (HTTP OK)** ✅
- URL di Lampiran 2 (bab4) = `KoJYy/sistomate`; label tree = `sistomate/` ✅

### Catatan keamanan (diterima Owner, dicatat untuk transparansi)
Firmware `SISTOMATE.ino` semula memuat API key hardcoded; sudah diganti **placeholder `sm_YOUR_SECRET_API_KEY`** & di-push (commit `d39c8ad`, terverifikasi). **Risiko sisa yang DITERIMA Owner:** key asli masih ada di *git history* & `/etc/sistomate-ingest.env` — Owner menilai dapat diterima karena endpoint di IP privat `192.168.1.101` (LAN di balik NAT). Dicatat sebagai known-issue, bukan blocker.

**Putusan:** Lampiran 1 & 2 **LOLOS**; ketidakcocokan repo awal **RESOLVED** via `KoJYy/sistomate` (terverifikasi remote). **Batch-3 GREEN & CLOSED.**
