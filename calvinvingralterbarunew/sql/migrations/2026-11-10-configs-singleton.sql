-- Migrasi 2026-11-10: configs menjadi singleton global.
--
-- ⚠️ SUPERSEDED — jangan dipakai untuk instalasi baru.
-- Migrasi ini dibalik oleh sql/migrations/2026-11-12-configs-per-device.sql
-- (configs kembali per-device dengan id_device + UNIQUE + FK). File ini
-- disimpan hanya sebagai catatan riwayat produksi.
--
-- Konteks: tabel configs produksi memiliki kolom id_device + FK configs_ibfk_1
-- yang tidak pernah ada di DDL repo (drift manual). Aplikasi (settings.php,
-- app.php) hanya memakai baris id=1, sehingga kolom itu tidak diperlukan.
--
-- WAJIB backup sebelum menjalankan file ini. Contoh:
--   mysqldump -u root -p --single-transaction --routines --triggers \
--     --events sistomate > backup-sistomate.sql
--
-- Jalankan sekali:
--   mysql -u root -p sistomate < sql/migrations/2026-11-10-configs-singleton.sql

ALTER TABLE configs DROP FOREIGN KEY configs_ibfk_1;
ALTER TABLE configs DROP COLUMN id_device;
