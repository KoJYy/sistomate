# Website SISTOMATE

Folder web produksi: landing page publik + dashboard terautentikasi.

```text
website/
├── index.html          landing page publik (URL "/")
├── assets/             CSS, ikon, dan logo bersama
│   ├── theme.css       CSS bersama (landing/login/history/settings/showcase)
│   ├── favicon.svg     ikon tab browser
│   ├── logo.svg        master logo (salinan Opsi A)
│   ├── logo-a.svg      Opsi A: Botanical Crest
│   ├── logo-b.svg      Opsi B: S-Flora
│   └── logo-c.svg      Opsi C: Agritech Shield
├── showcase/
│   └── logos.html      galeri tiga opsi logo
└── dashboard/          aplikasi setelah login
    ├── login.php       form login
    ├── logout.php      keluar session
    ├── app.php         dashboard utama
    ├── history.php     riwayat sensor + aktuator
    ├── settings.php    pengaturan ambang histeresis
    ├── export-csv.php  unduhan CSV dari halaman riwayat
    ├── live.php        JSON API untuk polling dashboard
    └── db.php          koneksi PDO, require_login(), navbar()
```

Catatan:

- Document root diarahkan ke `website/`, bukan ke `website/dashboard/`.
- Landing page ada di `/`; login di `/dashboard/login.php`.
- `assets/` dipakai bersama oleh landing page dan dashboard.
- `app.php` memakai CSS inline sendiri; `assets/theme.css` dipakai halaman lain.
- `showcase/logos.html` dibuka langsung lewat `/showcase/logos.html`.
- Endpoint firmware `ingest.php` tetap ditempatkan di root web (di luar folder ini,
  dari `sistomate-firmware/ingest.php`).
