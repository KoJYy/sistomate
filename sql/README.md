# SQL SISTOMATE

Urutan eksekusi untuk instalasi baru (MySQL 8.x):

```bash
mysql -u root -p < sql/schema.sql    # 5 tabel inti
mysql -u root -p < sql/seed.sql      # opsional: 1 device + log contoh
mysql -u root -p < sql/configs.sql   # tabel pendukung configs + 6 akun tim default
```

Catatan:

- `schema.sql` membuat database `sistomate` + 5 tabel inti:
  `users`, `devices`, `sensor_logs`, `actuator_logs`, `gallery`.
- `configs.sql` menambah tabel pendukung `configs` dengan relasi **per-device**
  (`id_device` → `devices.id_device`, `ON DELETE CASCADE`) dan **6 akun tim**
  (`sistomate`, `yudha`, `calvin`, `tio`, `ibror`, `nabil`). Default ambang:
  tanah `40–60%`, suhu `25–27°C`, fertigasi `7 dtk` (sesuai jurnal). Satu baris
  konfigurasi untuk setiap device. File ini idempotent untuk `CREATE TABLE`;
  baris config dibuat untuk device yang sudah ada saat file dijalankan.
  Password akun tim **tidak ditulis di repo** (hanya hash bcrypt); password awal
  dipegang Owner dan wajib diganti setelah login pertama.
- Urutan praktis: jalankan `configs.sql` **setelah** `devices` terisi (misalnya
  setelah `seed.sql`). Kalau terlanjur dijalankan sebelum seed, jalankan ulang
  sekali lagi setelah seed (aman, `WHERE NOT EXISTS`) atau cukup simpan halaman
  Pengaturan — `settings.php` melakukan upsert per device.
- `seed.sql` mengisi 1 device demo (`ESP32-Greenhouse-1`) + contoh log sensor
  dan aktuator. Tidak ada akun demo tambahan; akun admin bersama `sistomate`
  dibuat oleh `seed.sql` dan/atau `configs.sql` (idempotent).
- Untuk DB uji: `schema.sql` dan `seed.sql` memuat `USE sistomate`, jadi ganti nama
  database di berkas (misalnya `sed 's/sistomate/sistomate_webtest/g'`) atau sediakan
  salinan. `configs.sql` tidak memakai `USE`, sehingga bisa diarahkan lewat argumen
  nama database.
- Migrasi produksi ada di `sql/migrations/`; jalankan hanya setelah backup.

Grant minimal untuk user aplikasi:

```sql
GRANT SELECT, INSERT ON sistomate.* TO 'sistomate_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sistomate.configs TO 'sistomate_app'@'localhost';
```
