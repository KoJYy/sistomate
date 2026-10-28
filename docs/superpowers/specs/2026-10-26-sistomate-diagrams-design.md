# Design: Diagram SISTOMATE (Skematik Alat + Flowchart)

> Tanggal: 2026-09-01 spec lama → revisi 2026-10-26. Sumber kebenaran: firmware asli
> `sistomate-firmware/SISTOMATE/SISTOMATE.ino` + `docs/prototype-plan.md`.

## Intent (kenapa)

Laporan final butuh **skematik alat** dan **flowchart** yang belum ada, dan keduanya
juga dipakai sebagai panduan waktu bikin greenhouse fisik. Diagram harus **akurat sama
firmware yang sudah jalan** (demo 22 Okt aman), bukan gambaran umum ngarang.

## Goals

- Skematik/wiring diagram: ESP32 + 3 sensor + 2 modul relay + beban (2 pompa, kipas) + catu daya + common ground.
- Flowchart logika firmware: baca sensor → histeresis pompa/kipas → cek jadwal fertigasi → POST ke server.
- Hasil berupa **gambar jadi (SVG vector)** siap tempel di laporan **+ source editable** di repo.
- Masuk ke Bab 3 (`chapters/bab3-hasil-pembahasan.md`) dengan konvensi figure yang konsisten.

## Non-goals

- Bukan mengubah firmware, dashboard, atau DB.
- Bukan gambar physical-render 3D / Fritzing photo-realistic.
- Bukan raster PNG kecuali build PDF ternyata gagal nelen SVG.

## Constraints

- Pipeline laporan: `pandoc --pdf-engine=typst`. Typst embed SVG → output diagram = SVG.
- `gambar/` sekarang kosong (baru `.gitkeep`) → konvensi path figure dibuat di sini.
- Pinout & ambang WAJIB ikut firmware: Soil→GPIO34, DHT11→GPIO13, TCS34725 I2C SDA21/SCL22,
  Relay A CH1 Pompa Air→GPIO26, CH2 Pompa Pupuk→GPIO14, Relay B CH1 Kipas→GPIO27, CH2 cadangan (kosong). Aktif-LOW.
- Histeresis: pompa soil<40% ON / ≥55% OFF; kipas suhu>32°C ON / ≤31°C OFF; fertigasi 1×/minggu 07:00 NTP, 7 dtk, interlock soil≥40%.

## Approaches (yang dipertimbangkan)

1. **Mermaid → SVG (flowchart) + hand-authored SVG (skematik)** — *dipilih*.
   Source `.mmd` & `.svg` editable (draw.io/Inkscape/Figma), output SVG vector langsung ke typst.
   Risiko: render Mermaid butuh `npx @mermaid-js/mermaid-cli` + unduh Chromium sekali. Kalau
   diblokir → fallback: flowchart juga aku tulis tangan sebagai SVG (tetap editable).
2. **Fritzing / draw.io / KiCad file** — aku cuma nyiapin isi (komponen + koneksi), kamu rapihin.
   Lebih lambat, nunggu kamu buka tools. → dilepas.
3. **TikZ/typst inline** — vector rapi, tapi iterasi lambat & teknis, nyulitin revisi. → dilepas.

## Keputusan (hasil diskusi)

- Format: **gambar jadi SVG + source editable di repo** (pilihan koj).
- Flowchart pakai Mermaid (mudah direvisi), skematik aku bangun sebagai SVG diagram blok-and-wire
  yang rapi & berlabel (jelas buat laporan + panduan rakitan fisik).

## Bagian dari cara bikinnya

- Output render → `gambar/` (referenced Bab 3): `gambar/skematik-alat.svg`, `gambar/flowchart-logika.svg`.
- Source editable → `gambar/src/`: `flowchart-logika.mmd`, `skematik-alat.svg` (SVG = file gambar biasa, bisa dibuka editor vektor), plus `.drawio` opsional kalau perlu.
- Kalau `pandoc→typst` gagal nelen SVG → render PNG via `magick` sebagai jaring pengaman (kualitas dijaga).
- Opsional (menunggu konfirmasi): diagram **arsitektur sistem** (ESP32 → server → MySQL → dashboard) sebagai figure ketiga.

## Belum diputusin / perlu konfirmasi

- Apakah sekalian diagram **arsitektur sistem** (alur data end-to-end) atau cukup skematik + flowchart dulu.
