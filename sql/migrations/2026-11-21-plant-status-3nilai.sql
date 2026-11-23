-- Migrasi 2026-11-21: plant_status dari 4 nilai -> 3 nilai.
--
-- Keputusan Owner 21 Nov 2026 (opsi A): status "kering" dihapus dan dipetakan
-- ke "layu". Tiga status final: sehat, layu, sakit.
--
-- Kenapa "kering" dilebur ke "layu": layu = tanaman butuh air. Penyebabnya masih
-- bisa dibedakan dari kolom soil_moisture / air_temperature di baris yang sama.
--
-- URUTAN WAJIB (jangan dibalik):
--   1. UPDATE semua baris 'kering' -> 'layu'   (enum lama masih menerima keduanya)
--   2. ALTER TABLE ... MODIFY ENUM 3 nilai
-- Kalau ALTER dijalankan lebih dulu, UPDATE akan gagal / baris 'kering' jadi ''.
--
-- Aman dijalankan berulang: UPDATE tidak mengubah apa pun kalau sudah tidak ada
-- 'kering'; ALTER ... MODIFY idempoten.
--
-- WAJIB backup sebelum menjalankan file ini. Contoh:
--   mysqldump -u root -p --single-transaction --routines --triggers \
--     --events sistomate sensor_logs > backup-sensor_logs-20261121.sql
--
-- Jalankan:
--   mysql -u root -p sistomate < sql/migrations/2026-11-21-plant-status-3nilai.sql

-- 1. Lebur nilai lama ke nilai baru.
UPDATE sensor_logs SET plant_status = 'layu' WHERE plant_status = 'kering';

-- 2. Baru persempit enum.
ALTER TABLE sensor_logs
  MODIFY plant_status ENUM('sehat','layu','sakit') NOT NULL;

-- Verifikasi (harus 0 baris 'kering' dan enum cuma 3 nilai):
--   SELECT plant_status, COUNT(*) FROM sensor_logs GROUP BY plant_status;
--   SHOW COLUMNS FROM sensor_logs LIKE 'plant_status';
