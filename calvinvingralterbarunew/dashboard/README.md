# Dashboard SISTOMATE

Struktur folder dashboard web. Halaman publik & PHP berada di root `dashboard/`,
aset (CSS, ikon, logo) di `assets/`, galeri foto unggahan di `uploads/gallery/`,
dan galeri logo di `showcase/`.

```text
dashboard/
├── index.html          landing page (publik)
├── login.php           form login (username/email)
├── logout.php          keluar session
├── app.php             dashboard utama (setelah login)
├── history.php         riwayat sensor + aktuator
├── gallery.php         galeri foto kondisi tanaman (unggah, filter, hapus)
├── settings.php        kelola perangkat + pengaturan ambang histeresis
├── export-csv.php      unduhan CSV dari halaman riwayat
├── live.php            JSON API untuk polling dashboard
├── db.php              koneksi PDO, require_login(), navbar(), helper galeri
├── assets/
│   ├── theme.css       CSS bersama (index/login/history/gallery/settings/showcase)
│   ├── favicon.svg     ikon tab browser (dipakai semua halaman)
│   ├── logo-sistomate.png wordmark utama navbar/login/landing
│   ├── logo-jti.svg    logo Jurusan Teknologi Informasi
│   ├── logo-trk.svg    logo Prodi Teknologi Rekayasa Komputer
│   ├── logo.svg        master logo SVG (Opsi A)
│   ├── logo-a.svg      Opsi A: Botanical Crest
│   ├── logo-b.svg      Opsi B: S-Flora
│   └── logo-c.svg      Opsi C: Agritech Shield
├── uploads/
│   └── gallery/        lokasi foto unggahan pengguna (dilindungi .htaccess)
└── showcase/
    └── logos.html      galeri tiga opsi logo (buka /showcase/logos.html)
```

Catatan:

- `app.php` memakai CSS inline sendiri; `assets/theme.css` dipakai halaman
  lain (`index.html`, `login.php`, `history.php`, `gallery.php`, `settings.php`).
- `db.php` dan `live.php` membaca konfigurasi dari `SISTOMATE_ENV`, `.env` di
  folder paket, `dashboard/.env`, `.env` di root repo, atau
  `/etc/sistomate-ingest.env`.
- `showcase/logos.html` tidak ditautkan dari navigasi; buka langsung melalui
  `http://127.0.0.1:8000/showcase/logos.html` bila perlu.
