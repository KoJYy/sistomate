-- Migrasi 2026-11-19: konsolidasi status tanaman dari 4 menjadi 3 kondisi (sehat, layu, sakit).
--
-- Status 'kering' (soil < ambang) digabung ke 'layu' (stres air/panas).
-- WAJIB backup sebelum menjalankan file ini ke database produksi. Contoh:
--   mysqldump -u root -p --single-transaction --routines --triggers \
--     --events sistomate > backup-sistomate.sql
--
-- Jalankan sekali:
--   mysql -u root -p sistomate < sql/migrations/2026-11-19-plant-status-3-kondisi.sql

-- 1. Gabungkan baris status 'kering' yang sudah ada menjadi 'layu'.
UPDATE sensor_logs
SET plant_status = 'layu'
WHERE plant_status = 'kering';

-- 2. Persempit ENUM plant_status menjadi 3 kondisi: sehat, layu, sakit.
ALTER TABLE sensor_logs
  MODIFY COLUMN plant_status ENUM('sehat','layu','sakit') NOT NULL;

-- 3. Normalisasi awalan caption galeri [Kering] (case-insensitive) menjadi [Layu].
UPDATE gallery
SET caption = CONCAT('[Layu]', SUBSTRING(caption, 9))
WHERE caption LIKE '[kering]%';
