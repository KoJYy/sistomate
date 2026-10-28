# Plan — Model 3D Greenhouse SISTOMATE (FreeCAD)

> Sesi: sistomate-1 · 27 Okt 2026
> Tool: FreeCAD 1.1.4 (Flatpak) via MCP `freecad` (uvx freecad-mcp)
> Sumber dimensi: `docs/prototype-plan.md`

## Checklist

- [x] 1. Install FreeCAD (Fedora 44, Flatpak `org.freecad.FreeCAD` 1.1.4)
- [x] 2. Daftarkan MCP `freecad` ke `~/.config/opencode/opencode.jsonc` (via uvx) — RPC healthy
- [x] 3. Setup folder `docs/cad/` (+ `tools/` di `.gitignore`)
- [x] 4. Model parametric greenhouse:
        - Dimensi: P 100 × L 80 × Tinggi puncak 60 cm (X=1000, Y=800, Z=600; dinding belakang X=1000)
        - Rangka pondasi kayu (alas kotak 100x80 + dinding ujung + slat)
        - 4 busur pipa fleksibel (inset dari tepi)
        - Cover plastik transparan
        - Kipas 80mm (dinding belakang), pot tomat di tengah + selang, soil probe,
          DHT11 (depan atas), bracket TCS34725 (gantung 3–5 cm di atas daun)
- [x] 5. Export master: `.FCStd` (199989 B) + `.step` (801993 B) + `.stl` (1.09 MB / 21796 facets)
- [x] 6. Render PNG:
        - A. `render-isometric.png` 2400×1800 isometrik 3D ✓
        - B. `render-dimensi.png` 3509×2480 (A4 lanskap 300dpi) technical drawing
             bergaris ukuran: Panjang 1000 / Lebar 800 / Tinggi 600 mm ✓
             (+ `tampak-dimensi.svg` / `.pdf`)
- [x] 7. Verifikasi: audit 3D violasi=0 / 21 objek dalam bounding box;
        nilai dimensi via `getRawValue()` = 1000/800/600 OK;
        render dicek visual + proyeksi kolom piksel + `pdftotext -bbox`

## Sisa pekerjaan (status 28 Okt 2026, akhir sesi)

- [ ] Integrasi hasil 3D ke **Lampiran** laporan (Bab 3.5 + Gambar 3.4/3.5 sudah beres, commit `406b141`)
- [ ] Audit isi Lampiran (read-only) — sisa tiket S1 sbg auditor
- [ ] Opsional: bersihkan blob STL 32 MB dari git history kalau repo mau dipublikasikan (butuh rewrite — koordinasi dulu)

## Audit assembly-guide.md (28 Okt 2026)

- Cocok: konvensi sumbu, 4 busur span 80/tinggi 60, kipas 80 mm dinding belakang,
  TCS 3,9 cm di atas daun, jumlah budget.
- Direvisi di guide: kayu 4→7 m, pipa 4→2 batang (hemat 50 rb), plastik 110→160 cm,
  busur 130–150→149 cm (terukur), typo greenhous/teuk.
- Direvisi di model 3D: soil probe tegak→miring 28° (kedalaman 3,4 cm), selang
  depan→dinding belakang bawah, DHT11 tengah→kiri-depan-atas (Y=207), tanah lebih
  tebal (Z 130–175). Re-export semua + render ulang.
- Direvisi di prototype-plan.md: panjang busur ±100→±150 cm.

## Catatan teknis (gotcha yang ditemui)

- MCP aktif; RPC `execute_code` jalan di GUI thread, balikin `print()` output.
- **Dimensi TechDraw gaya `ISO Oriented`: posisi garis mengikuti properti `X` (label),
  bukan `References2D`.** `m_linearPoints` dihitung di `execute()` tapi bukan properti →
  GUI tak pernah dapat sinyal redraw saat referensi berubah. Solusi: rujukan diatur benar
  (Vertex470/471 → 800) lalu `X=-66.8 mm` menggeser garis ke 18mm (di luar tepi view 20mm).
- `page.removeView(o)` + `doc.removeObject()` + `page.addView(o)` memaksa item scene
  dibangun ulang — perubahan `References2D` baru tampil setelah itu.
- Dokumen kini bernama `greenhouse` (huruf kecil) setelah saveAs/openDocument.
- Buktui posisi selalu dari render PNG / `pdftotext -bbox`, BUKAN `getArrowPositions()`
  (sering stale).
