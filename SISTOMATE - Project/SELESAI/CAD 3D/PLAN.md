# Plan — Model 3D Greenhouse SISTOMATE (FreeCAD)

> Sesi: sistomate-1 · 27 Okt 2026
> Tool: FreeCAD (headless via FreeCADCmd) + MCP `neka-nat/freecad-mcp` (untuk sesi depan)
> Sumber dimensi: `docs/prototype-plan.md`

## Checklist

- [ ] 1. Install FreeCAD (Fedora 44, `sudo dnf install -y freecad`)
- [ ] 2. Daftarkan `neka-nat/freecad-mcp` ke `~/.config/opencode/opencode.jsonc` (via uvx) — aktif setelah restart OpenCode
- [ ] 3. Setup folder `docs/cad/`
- [ ] 4. Script Python FreeCAD: parametric greenhouse (busur tunnel segmental)
        - Dimensi: P 100 × L 80 × Tinggi puncak 60 cm
        - Bagian 1: rangka pondasi kayu (alas kotak 100x80 + dinding ujung)
        - Bagian 2: 3–4 busur pipa fleksibel (PVC/PP)
        - Bagian 3: cover plastik transparan
        - Bagian 4: kipas 80mm (dinding atas belakang), pot tomat di tengah + selang,
                    soil sensor (di tanah), DHT11 (pojok depan), bracket TCS34725 (3–5cm di atas daun)
- [ ] 5. Export master: `.FCStd` + `.step` + `.stl` ke `docs/cad/`
- [ ] 6. Render PNG:
        - A. Isometrik 3D keseluruhan (transparan)
        - B. Technical drawing 2D tampak atas + depan, bergaris ukuran (cm/mm)
- [ ] 7. Verifikasi semua file ada & render kebaca

## Catatan teknis
- MCP baru aktif di sesi OpenCode berikutnya (config dibaca saat start).
  Sesi INI modeling jalan via script Python FreeCAD langsung — inti yang sama.
- Render: GUI aktif (DISPLAY=:0) + xvfb-run tersedia sbg fallback.
