# Dashboard SISTOMATE

Struktur folder setelah pengelompokan aset. Tidak ada berkas yang dihapus;
berkas tampilan tetap di root agar URL halaman tidak berubah.

```text
dashboard/
├── index.html          landing page (publik)
├── login.php           form login
├── logout.php          keluar session
├── app.php             dashboard utama (setelah login)
├── history.php         riwayat sensor + aktuator
├── settings.php        pengaturan ambang histeresis
├── export-csv.php      unduhan CSV dari halaman riwayat
├── live.php            JSON API untuk polling dashboard
├── db.php              koneksi PDO, require_login(), navbar()
├── assets/
│   ├── theme.css       CSS bersama (index/login/history/settings/showcase)
│   ├── favicon.svg     ikon tab browser (dipakai semua halaman)
│   ├── logo.svg        master logo (salinan Opsi A)
│   ├── logo-a.svg      Opsi A: Botanical Crest
│   ├── logo-b.svg      Opsi B: S-Flora
│   └── logo-c.svg      Opsi C: Agritech Shield
└── showcase/
    └── logos.html      galeri tiga opsi logo (buka /showcase/logos.html)
```

Catatan:

- `app.php` memakai CSS inline sendiri; `assets/theme.css` dipakai halaman
  lain. Mengubah `theme.css` tidak mengubah tampilan dashboard utama.
- Logo navbar ditanam sebagai SVG inline di `db.php`/`index.html`/`login.php`,
  sedangkan ikon tab memakai `assets/favicon.svg`. `assets/logo.svg` adalah
  berkas master, bukan sumber otomatis untuk navbar.
- `showcase/logos.html` tidak ditautkan dari navigasi; buka langsung melalui
  `http://localhost/showcase/logos.html` bila perlu.
