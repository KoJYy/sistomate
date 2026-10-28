'use strict';

const fs = require('fs');
const path = require('path');

function copyRecursiveSync(src, dest) {
  if (!fs.existsSync(src)) return;
  const stats = fs.statSync(src);
  if (stats.isDirectory()) {
    if (!fs.existsSync(dest)) {
      fs.mkdirSync(dest, { recursive: true });
    }
    fs.readdirSync(src).forEach((childItemName) => {
      copyRecursiveSync(path.join(src, childItemName), path.join(dest, childItemName));
    });
  } else {
    fs.copyFileSync(src, dest);
  }
}

function run(argv = []) {
  const cwd = process.cwd();
  console.log('\n==========================================================');
  console.log('   LAPORAN GENERATOR - INISIALISASI PROYEK DOKUMEN       ');
  console.log('==========================================================\n');

  const metaPath = path.join(cwd, 'metadata.yml');
  if (fs.existsSync(metaPath)) {
    console.log('[WARN] Dokumen laporan sudah ada di direktori ini (metadata.yml ditemukan).');
    return;
  }

  // Cari direktori resources (bisa dari package root atau global skills)
  const pkgResources = path.join(__dirname, '../.agents/skills/laporan-generator/resources');
  const globalResources = path.join(require('os').homedir(), '.agents/skills/laporan-generator/resources');
  const sourceDir = fs.existsSync(pkgResources) ? pkgResources : (fs.existsSync(globalResources) ? globalResources : null);

  if (sourceDir) {
    console.log(`Menyalin template lengkap dari: ${sourceDir} ...\n`);
    const filesToCopy = [
      'metadata.yml',
      'cover.md',
      'references.bib',
      'apa.csl',
      'logo.jpg',
      'template.typ',
      'reference.docx',
      'docx.lua',
      'Makefile',
      'build.sh',
      'build-docx.sh',
      'laporan',
      'laporan.ps1',
      'presets',
      'chapters',
      'scripts'
    ];

    for (const f of filesToCopy) {
      const srcFile = path.join(sourceDir, f);
      const destFile = path.join(cwd, f);
      if (fs.existsSync(srcFile) && !fs.existsSync(destFile)) {
        copyRecursiveSync(srcFile, destFile);
        console.log(`  ✔ Disalin: ${f}`);
      }
    }
  } else {
    // Fallback manual jika source resources tidak ditemukan
    const defaultMeta = `title: "Laporan Akademik & Proyek"
author:
  - name: "Nama Penulis"
    id: "1234567890"
date: "2026"
institution: "Universitas / Institusi Akademik"
faculty: "Fakultas Teknik & Ilmu Komputer"
department: "Program Studi Informatika"
location: "Indonesia"
margin_preset: "skripsi-4433"
font_family: "Times New Roman"
font_size: "12pt"
line_spacing: "0.75em"
first_line_indent: "1.25cm"
heading_chapter_prefix: "BAB "
heading_chapter_num_format: "roman"
`;
    fs.writeFileSync(metaPath, defaultMeta, 'utf8');
    console.log('  ✔ Dibuat: metadata.yml (Preset: skripsi-4433)');

    const coverPath = path.join(cwd, 'cover.md');
    fs.writeFileSync(coverPath, '# Laporan Akademik\n\nDokumen ini disusun sebagai dokumentasi resmi laporan proyek/penelitian.\n', 'utf8');
    console.log('  ✔ Dibuat: cover.md');

    const chaptersDir = path.join(cwd, 'chapters');
    if (!fs.existsSync(chaptersDir)) fs.mkdirSync(chaptersDir, { recursive: true });
    const bab1 = `# Pendahuluan\n\n## Latar Belakang\nTuliskan latar belakang masalah di sini.\n\n## Rumusan Masalah\n1. Masalah pertama.\n2. Masalah kedua.\n\n## Tujuan Penelitian\nTujuan dari pelaksanaan proyek ini adalah menghasilkan analisis yang komprehensif.\n`;
    fs.writeFileSync(path.join(chaptersDir, 'bab1-pendahuluan.md'), bab1, 'utf8');
    console.log('  ✔ Dibuat: chapters/bab1-pendahuluan.md');

    const refBibPath = path.join(cwd, 'references.bib');
    fs.writeFileSync(refBibPath, `@book{contoh2026,\n  title     = {Panduan Penulisan Karya Ilmiah},\n  author    = {Penulis, Contoh},\n  year      = {2026},\n  publisher = {Penerbit Akademik}\n}\n`, 'utf8');
    console.log('  ✔ Dibuat: references.bib');
  }

  // Ensure scripts are executable on Unix
  try {
    if (process.platform !== 'win32') {
      const laporanBin = path.join(cwd, 'laporan');
      const buildSh = path.join(cwd, 'build.sh');
      if (fs.existsSync(laporanBin)) fs.chmodSync(laporanBin, '755');
      if (fs.existsSync(buildSh)) fs.chmodSync(buildSh, '755');
    }
  } catch (_) {}

  console.log('\n✨ Inisialisasi dokumen selesai!');
  console.log('Jalankan "./laporan build" atau "npx laporan-generator build" untuk mengompilasi dokumen ke PDF & Word!\n');
}

module.exports = { run };
