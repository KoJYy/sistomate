-- Migrasi 2026-11-12: configs kembali per-device.
--
-- Konteks: configs sempat dijadikan singleton global (migrasi 2026-11-10).
-- Sekarang dikembalikan ke relasi perangkat: satu baris configs untuk setiap
-- devices, dengan FK id_device -> devices(id_device).
--
-- WAJIB backup sebelum menjalankan file ini. Contoh:
--   mysqldump -u root -p --single-transaction --routines --triggers \
--     --events sistomate > backup-sistomate.sql
--
-- Jalankan sekali:
--   mysql -u root -p sistomate < sql/migrations/2026-11-12-configs-per-device.sql

-- 1. Kolom pemilik + unique agar upsert ON DUPLICATE KEY bekerja per device.
--    DEFAULT 1 menjaga kompatibilitas kode lama selama masa transisi deploy.
ALTER TABLE configs
  ADD COLUMN id_device INT NOT NULL DEFAULT 1 AFTER id,
  ADD UNIQUE KEY uq_configs_device (id_device),
  ADD CONSTRAINT fk_configs_device FOREIGN KEY (id_device)
    REFERENCES devices (id_device) ON DELETE CASCADE ON UPDATE CASCADE;

-- 2. Buat baris konfigurasi untuk device lain, menyalin nilai baris yang ada.
INSERT INTO configs (id_device, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
SELECT d.id_device, c.soil_on, c.soil_off, c.temp_on, c.temp_off, c.fert_duration_s
FROM devices d
JOIN (
  SELECT soil_on, soil_off, temp_on, temp_off, fert_duration_s
  FROM configs ORDER BY id LIMIT 1
) c
WHERE NOT EXISTS (
  SELECT 1 FROM configs x WHERE x.id_device = d.id_device
);

-- Verifikasi:
--   SELECT id, id_device, soil_on, soil_off, temp_on, temp_off FROM configs ORDER BY id_device;
--   SELECT COUNT(*) FROM configs;  -- harus >= jumlah devices
