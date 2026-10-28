# SISTOMATE — Project Management Tracker (Kelompok 3 TRK Golongan B)

> Mata Kuliah: Workshop Basis Data (TRK 130805)
> Dosen Pengajar: Zilvanhisna Emka Fitri, S.T., M.T. & Nanik Anita Mukhlisoh, S.ST., M.T.
> Anggota:
> 1. Yuda Maredya Ari Pratama (E43252953) — Lead & Database
> 2. Abiyu Ammar Hady (E43252782) — Hardware & Wiring
> 3. Ibror Yusron S (E43252690) — Firmware & Testing
> 4. Moch Calvin Aprilian (E43252720) — CAD 3D & Miniatur Fisik
> 5. Nabil Laudza Nur (E43252939) — Frontend Dashboard Web
> 6. Tio Dwi Harianto (E43252983) — Laporan & Dokumentasi

---

## Tabel Pelacakan Progres Proyek (Task Tracker)

| No | Task / Pekerjaan | Owner (PIC) | Status | Deadline | File / Tautan Hasil | Catatan Teknis |
|:--:|---|---|:---:|:---:|---|---|
| 1 | Desain ERD & Skema Relasi Database | Yuda | **SELESAI** | 2026-10-15 | `chapters/bab3-hasil-pembahasan.md` | 5 tabel terpusat pada `devices` (`users`, `devices`, `gallery`, `sensor_logs`, `actuator_logs`) |
| 2 | Implementasi Database MySQL & DDL Script | Yuda | **SELESAI** | 2026-10-18 | `sql/` | Engine InnoDB, FK CASCADE, indexing `(id_device, recorded_at)` |
| 3 | Backend Ingestion API (`ingest.php`) | Yuda | **SELESAI** | 2026-10-21 | `sistomate-firmware/ingest.php` | Auth `X-API-Key`, PDO prepared statement, validasi JSON |
| 4 | Pemrograman Firmware ESP32 (`SISTOMATE.ino`) | Ibror | **SELESAI** | 2026-10-22 | `sistomate-firmware/SISTOMATE/` | Histeresis soil moisture (<40%/≥55%), suhu kipas (>32°/≤31°C), POST tiap 2 dtk |
| 5 | Sinkronisasi Fertigasi Pompa Pupuk | Ibror | **SELESAI** | 2026-10-26 | Commit `a780a23` | Dosis 7 dtk, jadwal Senin 07:00 NTP, interlock soil ≥ 40% (Sari 2024) |
| 6 | Pengujian Unit Hardware & Kalibrasi Sensor | Abiyu | **SELESAI** | 2026-10-22 | `sistomate-firmware/SKRIPT-DEMO.md` | Demo gladi sukses; kalibrasi soil air=1500 kering=4095, TCS G/R=0.75 |
| 7 | Perancangan Skematik Wiring & Power Rails | Abiyu | **SELESAI** | 2026-10-26 | `gambar/skematik-alat.png` | Catu daya terpisah: 5V adaptor beban + Common Ground rail (anti restart) |
| 8 | Pembuatan Flowchart Logika Sistem | Tio | **SELESAI** | 2026-10-26 | `gambar/flowchart-logika.png` | Format paralel muat 1 halaman A4; histeresis + fertigasi + 4 status tanaman |
| 9 | Pembuatan Diagram Arsitektur Sistem 4-Layer | Yuda | **SELESAI** | 2026-10-26 | `gambar/arsitektur-sistem.png` | ESP32 Edge → Ingest PHP → MySQL DB → Web Dashboard |
| 10 | Desain Prototipe Fisik Miniatur Greenhouse | Calvin | **SELESAI** | 2026-10-26 | `docs/prototype-plan.md` | Tunnel busur 100x80x60 cm, rangka hibrida kayu + pipa fleksibel PVC |
| 11 | Pemodelan Parametrik 3D CAD (FreeCAD) | Calvin | **SELESAI** | 2026-10-27 | `docs/cad/greenhouse.FCStd` | Export master `.step`, `.stl`, dan snapshot `render-isometric.png` |
| 12 | Panduan Perakitan Fisik (BOM & Cutting List) | Calvin | **SELESAI** | 2026-10-27 | `docs/assembly-guide.md` | Rincian 11 item belanja (~Rp 414.000) & SOP potong bahan pipa/kayu |
| 13 | Belanja Bahan Perakitan Fisik | Semua Anggota | **PROSES** | 2026-11-03 | - | Pipa PVC 1/2", kayu reng, plastik PE bening, relay 2-ch unit 2, kipas 80mm |
| 14 | Perakitan Fisik Greenhouse & Instalasi Komponen | Abiyu, Calvin, Ibror | **PROSES** | 2026-11-08 | - | Merakit rangka kotak kayu, memasang busur pipa, cover mika/plastik, mounting sensor |
| 15 | Dashboard Web Realtime Monitoring | Nabil | **SELESAI** | 2026-10-22 | `dashboard/index.html` + `live.php` | Realtime Chart.js polling 5 dtk, ringkasan harian, log aktuator |
| 16 | Desain Banner / Pamflet / Poster Proyek | Tio | **PROSES** | 2026-11-10 | - | Media promosi & pameran produk SISTOMATE |
| 17 | Pengumpulan Jurnal Ilmiah Referensi | Semua Anggota | **PROSES** | 2026-11-10 | `references.bib` | Minimal 2 jurnal kredibel 8 tahun terakhir per anggota |
| 18 | Penyusunan Laporan Akhir (Bab 1–4) | Semua Anggota | **PROSES** | 2026-11-15 | `chapters/` → `make build` | Integrasi Bab 3 (DB + 3 Diagram) sudah masuk, sisa kesimpulan & lampiran |
| 19 | Pembuatan Slide Presentasi (PPT) Final | Nabil, Tio | **PROSES** | 2026-11-16 | - | Canva / PowerPoint untuk demo presentasi dosen penguji |
| 20 | Pengujian Integrasi End-to-End (UAT) | Semua Anggota | **PROSES** | 2026-11-18 | - | Uji coba alat fisik di greenhouse mini terhubung ke live dashboard |
| 21 | Gladi Bersih & Latihan Presentasi | Semua Anggota | BELUM | 2026-11-19 | - | Simulasi demo di hadapan tim sebelum jadwal sidang/penilaian |
