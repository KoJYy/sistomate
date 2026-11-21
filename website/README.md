# Website SISTOMATE

Folder web produksi: landing page publik + dashboard terautentikasi.

```text
website/
├── index.html                  # Landing page publik (URL "/") + mini-dashboard real-time
├── README.md                   # Dokumentasi struktur website
├── assets/                     # CSS, ikon, logo & aset visual landing/login/navbar
│   ├── landing.css             # CSS khusus landing page (index.html)
│   ├── theme.css               # CSS bersama (login, history, gallery, settings, showcase)
│   ├── favicon.svg             # Ikon tab browser
│   ├── logo.svg                # Master logo vektor (salinan Opsi A)
│   ├── logo-a.svg              # Opsi A: Botanical Crest
│   ├── logo-b.svg              # Opsi B: S-Flora
│   ├── logo-c.svg              # Opsi C: Agritech Shield
│   └── figma/                  # 22 aset gambar & ikon aktif landing/login/navbar
├── showcase/
│   └── logos.html              # Galeri tiga opsi logo (/showcase/logos.html)
└── dashboard/                  # Aplikasi terautentikasi
    ├── login.php               # Form login (/dashboard/login.php)
    ├── logout.php              # Keluar session -> ../index.html
    ├── app.php                 # Dashboard utama (CSS inline sendiri)
    ├── history.php             # Riwayat sensor + aktuator
    ├── gallery.php             # Galeri foto kondisi tanaman (unggah, filter, hapus)
    ├── settings.php            # Pengaturan ambang histeresis & kelola perangkat
    ├── export-csv.php          # Unduhan CSV dari halaman riwayat
    ├── live.php                # JSON API real-time (termasuk config & threshold)
    ├── db.php                  # Koneksi PDO, require_login(), navbar(), helper galeri
    └── uploads/
        └── gallery/            # Penyimpanan foto unggahan galeri (dilindungi .htaccess)
```

Catatan:

- Document root diarahkan ke `website/`, bukan ke `website/dashboard/`.
- Landing page ada di `/` (`index.html`) dan memanggil `dashboard/live.php` setiap 5 detik untuk pratinjau dashboard real-time di hero laptop; login ada di `/dashboard/login.php`.
- `assets/` dipakai bersama oleh landing page (`assets/...`) dan dashboard (`../assets/...`).
- `app.php` memakai CSS inline sendiri, `index.html` memakai `assets/landing.css`, dan halaman lain memakai `assets/theme.css`.
- `showcase/logos.html` dibuka langsung lewat `/showcase/logos.html`.
- Endpoint firmware `ingest.php` tetap ditempatkan di root web (di luar folder ini, dari `sistomate-firmware/ingest.php`).
