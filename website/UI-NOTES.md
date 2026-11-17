# Catatan Perapian UI/UX — SISTOMATE

Tidak ada perubahan skema database, query, endpoint, maupun nama field POST.
Yang berubah hanya tampilan (markup + CSS) dan sedikit JavaScript antarmuka.

## Yang dirapikan

**Satu sistem desain (`theme.css`)**
- Semua halaman kini memakai satu sumber gaya: token warna, skala tipografi
  (basis 16px), skala jarak, radius, dan bayangan.
- `app.php` tidak lagi menyimpan CSS sendiri; ia memuat `theme.css?v=2`
  ditambah komponen khusus dashboard. Tidak ada lagi aturan yang menduplikasi
  gaya antar halaman, jadi perubahan tema cukup di satu berkas.

**Header & navigasi**
- Header menempel di atas (sticky) dengan garis bawah halus saat digulir.
- Logo jadi tautan ke dashboard, item nav aktif memakai `aria-current="page"`.
- Di bawah 900px nav pindah ke baris sendiri dan bisa digeser, nama pengguna
  disembunyikan agar tidak berdesakan.

**Dashboard (`app.php`)**
- Kartu status dipadatkan jadi satu baris: status, alasan, dan waktu pembacaan
  terakhir. Tidak lagi memakan setengah layar.
- Tiga kartu metrik dibuat berstruktur sama (label, nilai, gauge, keterangan)
  sehingga tingginya seragam; kartu RH kini punya gauge 0–100% juga.
- Gauge diberi zona: kering/basah untuk tanah, sejuk/panas untuk suhu, plus
  pita histeresis bergaris dan label skala di bawahnya.
- Kartu aktuator diisi merata + baris ringkasan "x dari 3 kanal aktif", dan
  panel riwayat diberi tautan "Buka riwayat lengkap".
- Grafik tren memakai area bergradasi, label min/maks, dan sumbu Y/X yang
  sejajar; kedua panel dipastikan sama tinggi.
- Bagian warna daun: bar per kanal, rasio G/R, dan vonis berwarna.

**Riwayat (`history.php`)**
- Filter tanggal + tombol Terapkan dalam satu baris, dengan pintasan
  Hari ini / 7 hari / 30 hari yang menandai rentang aktif.
- Tombol ekspor CSV dipindah ke kepala halaman (aksi, bukan tautan kecil).
- Tabel: header menempel saat digeser, angka rata kanan, waktu diformat
  "12 Nov 2026 12:44:02", badge jumlah baris, dan gradasi halus di tepi bawah
  sebagai isyarat tabel masih bisa digeser.

**Pengaturan (`settings.php`)**
- Tiap kelompok pengaturan jadi kartu sendiri dengan pita histeresis visual
  (zona ON / bertahan / OFF) yang bergerak saat slider digeser, plus legenda
  berwarna.
- Nilai tidak valid (ON ≥ OFF) langsung ditandai, tombol simpan dinonaktifkan.
- Ada indikator "ada perubahan yang belum disimpan".
- Slider diberi label, satuan, dan rentang aman yang jelas.

**Login & landing**
- Kartu login: label lebih tegas, atribut `autocomplete`, tombol lihat sandi
  dengan `aria-pressed`, dan tautan kembali ke beranda.
- Landing: hero lebih ringkas, strip 4 fakta (3 sensor, 6 tabel, 5 dtk,
  4 kanal relay), kartu masalah/solusi 2 kolom, hardware & tim 3 kolom,
  arsitektur bernomor. Semua bagian bisa dijangkau dari nav yang menempel.

## Catatan teknis
- Semua `id` yang dipakai JavaScript (`suhu`, `ndl-soil`, `clock`, dst.)
  dipertahankan; logika polling 5 detik, ambang histeresis dari tabel
  `configs`, dan heuristik RGB tidak diubah.
- Aksesibilitas dasar: kontras teks ≥ 4.5:1, fokus keyboard terlihat
  (`:focus-visible`), `prefers-reduced-motion` dihormati, tabel memakai
  `<thead>`, dan gambar punya teks alternatif.
- `logos.html` masih berfungsi (halaman showcase logo, tidak ditautkan dari app).
