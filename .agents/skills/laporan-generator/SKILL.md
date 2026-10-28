---
name: laporan-generator
description: >-
  Universal Academic Report & Thesis Generator for AI Agents. Use when the user wants
  to create, draft, format, or compile academic reports, thesis (skripsi/TA), internship reports (PKL),
  or scientific papers adhering to Indonesian & international campus standards.
---

# Laporan Generator AI Agent Skill

This skill empowers AI coding agents to autonomously scaffold, draft, format, audit, and compile publication-grade academic documents using the bundled Typst and Pandoc engines.

> [!CRITICAL]
> **DO NOT WRITE RAW TYPST FILES FROM SCRATCH (e.g. `laporan.typ` or importing `@preview/ieee`).**
> Never attempt to craft a monolithic `.typ` file or manually configure Typst margins and headers.
> The engine uses a battle-tested **Modular Markdown Architecture**:
> 1. Document metadata is defined in `metadata.yml`.
> 2. Cover, preface, and outline are in `cover.md`.
> 3. Document body is written in **pure standard Markdown** inside `chapters/bab*.md`.
> 4. Bibliography is stored in BibTeX format in `references.bib`.
> 5. Compilation to PDF (via Typst) and Word DOCX (via OpenXML) is handled automatically by `./laporan build` or `bash build.sh && make docx`.

--------------------------------------------------------------------------------

## 1. Resource Locations & Scaffolding

The full engine and templates are bundled in `resources/` inside this skill:
- Location: `<skill_dir>/resources/` or `~/.agents/skills/laporan-generator/resources/`

### Starter & Template Files Included:
- `metadata.yml`: Official academic metadata (title, author, institution, faculty, department, year, preset, fonts).
- `cover.md`: Official academic cover page & abstract/kata pengantar.
- `references.bib`: Standard academic citation database (BibTeX APA 7th).
- `apa.csl`: APA 7th edition citation style file for Pandoc citeproc.
- `logo.jpg`: Institution logo placeholder.
- `chapters/`: Modular chapter templates:
  - `chapters/bab1-pendahuluan.md`: Background, problem formulation, objectives, scope.
  - `chapters/bab2-tinjauan-pustaka.md`: Theoretical framework, literature review.
  - `chapters/bab3-metodologi.md`: Research design, workflow, data collection.
  - `chapters/bab4-hasil-dan-pembahasan.md`: Implementation, experimental results, discussion.
  - `chapters/bab5-penutup.md`: Conclusions and future work recommendations.
- `presets/`: Campus presets:
  - `standard.yml`: Standard university margins (2.5/2.5/2/3 cm)
  - `skripsi-4433.yml`: National standard skripsi/thesis (4/4/3/3 cm)
  - `ui-skripsi.yml`: Universitas Indonesia
  - `itb-ta.yml`: Institut Teknologi Bandung
  - `ugm-skripsi.yml`: Universitas Gadjah Mada
  - `its-skripsi.yml`: Institut Teknologi Sepuluh Nopember
- `template.typ`: Typst typesetting engine (dual roman/arabic pagination, inverted pyramid title).
- `reference.docx` & `docx.lua`: Pandoc OpenXML Word engine with page breaks & table formatting.
- `build.sh`, `laporan`, `laporan.ps1`, `Makefile`: One-click compilation scripts.

### How to Scaffold a Project in Current Directory:
If the workspace does not yet have `metadata.yml` or `chapters/`, run:
```bash
# Copy all templates directly from bundled resources:
cp -r ~/.agents/skills/laporan-generator/resources/* .
# Or if npx is available:
npx laporan-generator init
```

--------------------------------------------------------------------------------

## 2. Multi-Stage Interactive Protocol (Wajib)

When the user requests an academic report or thesis, execute these 3 stages:

### Stage 1: Identification & Genre Selection
Identify document type & campus target:
1. **Laporan Proyek / Praktikum / Tugas Besar**: Standard 5 chapters (`bab1` to `bab5`).
2. **Laporan Magang / PKL / KKN**: Executive summary, company profile, activity log, system analysis.
3. **Tugas Akhir / Skripsi**: Formal approval sheet, bilingual abstract, TOC, list of figures/tables, standard academic bibliography.
4. **Makalah Kuliah / Paper Akademik**: Makalah tugas kuliah di Indonesia tetap menggunakan format penomoran bab standar kampus (`BAB I`, `BAB II`, dst.) dengan angka Romawi secara default. HANYA gunakan `heading_chapter_prefix: ""` jika pengguna secara eksplisit meminta format artikel jurnal ilmiah internasional/manuskrip tanpa bab.

### Stage 2: Workspace Setup & Metadata Configuration
1. Scaffold workspace using `cp -r ~/.agents/skills/laporan-generator/resources/* .`
2. Edit `metadata.yml` with the user's specific details:
   - `title`: Inverted pyramid title
   - `author`: Array of authors with name and NIM/ID
   - `institution`, `faculty`, `department`, `year`
   - `preset`: Select appropriate campus preset (e.g. `skripsi-4433`, `itb-ta`, `ui-skripsi`, etc.)
3. Edit `cover.md` with appropriate preface, acknowledgments, or abstract.

### Stage 3: Drafting Chapters in Pure Markdown
Write high quality content into separate files in `chapters/`:
- `chapters/bab1-pendahuluan.md`
- `chapters/bab2-tinjauan-pustaka.md`
- `chapters/bab3-metodologi.md`
- `chapters/bab4-hasil-dan-pembahasan.md`
- `chapters/bab5-penutup.md`

#### Formatting Rules for Chapters:
- **Headings**: Use `# Judul Bab` for chapter level (e.g. `# Pendahuluan`), `## Sub-bab` for section level.
- **Citations**: Use Pandoc syntax `[@citekey]` matching keys in `references.bib`.
- **Formulas**: Inline `$E = m c^2$`, block `$$\sum_{i=1}^n x_i$$`.
- **Currencies & Special Symbols**: Escape dollar signs in body text as `\$100` or write `100 USD` so Pandoc does not parse it as LaTeX math.
- **Images**: Store images in `gambar/` and reference them as `![Caption](gambar/file.png)`. Never reference non-existent images.

--------------------------------------------------------------------------------

## 3. Compilation & Verification Commands

Compile both PDF & Word DOCX:
```bash
./laporan build
# Atau manual:
bash build.sh && make docx
```

Audit document health & check for broken images or missing citation keys:
```bash
./laporan doctor
# Atau:
python3 scripts/report-doctor.py
```

Analyze word count, statistics & distribution:
```bash
./laporan stats
```

--------------------------------------------------------------------------------

## 4. Common AI Pitfalls to Avoid

| Pitfall | Why It Fails | Correct Solution |
| :--- | :--- | :--- |
| **Writing `.typ` files manually** | Creates syntax errors, breaks DOCX export, loses presets | Always write Markdown in `chapters/bab*.md` and let the engine compile. |
| **`#import "@preview/ieee"`** | Offline environments fail with package not found | Use the bundled self-contained `template.typ`. |
| **Unescaped `$` in text** | Treated as math mode delimiter causing syntax errors | Use `\$10,000` or text `10,000 USD`. |
| **Missing images** | Referenced images not present cause build failure | Ensure images exist in `gambar/` before referencing. |
| **Monolithic single file** | Incompatible with multi-pass DOCX and chapter numbering | Keep chapters strictly split into `chapters/bab*.md`. |
