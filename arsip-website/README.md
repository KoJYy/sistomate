# Dashboard SISTOMATE v2

UI landing page & dashboard operasional yang diperbarui (`dashboard-v2/`), terhubung ke MySQL 8 (`sistomate`) via PDO dengan fallback mode demo otomatis bila konfigurasi/DB lokal belum aktif.

```text
dashboard-v2/
├── index.html          landing page publik
├── credits.html        atribusi foto Wikimedia Commons
├── login.php           form login operator (verifikasi tabel users)
├── logout.php          keluar session
├── demo.php            pintu masuk cepat demo (login otomatis + fallback demo)
├── demo-data.php       generator simulasi telemetri saat mode fallback aktif
├── app.php             dashboard pemantauan real-time & kendali histeresis
├── history.php         riwayat sensor_logs & actuator_logs + filter tanggal
├── settings.php        pengaturan ambang histeresis per-device (tabel configs)
├── export-csv.php      unduhan CSV riwayat sensor/aktuator
├── live.php            JSON API untuk polling dashboard 5 detik
├── db.php              koneksi PDO MySQL (.env.local / SISTOMATE_ENV / /etc/sistomate-ingest.env) + fallback demo
├── theme.css           stylesheet bersama halaman dashboard
└── assets/             sistem desain landing, ikon, gambar WebP, diagram & efek UI
```

## Menjalankan Lokal (Laragon MySQL + PHP Built-in Server)

```bash
mysql -u root -p < sql/sistomate.sql
php -S 0.0.0.0:8080 -t dashboard-v2
```

Buka `http://127.0.0.1:8080/index.html` atau `demo.php`.
