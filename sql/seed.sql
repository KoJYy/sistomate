-- SISTOMATE seed data demo untuk instalasi baru.
-- Isi: 1 akun admin bersama (`sistomate`) + 1 device + contoh log.
-- TIDAK membuat akun demo Budi/Ayu/Candra (dihapus 26 Nov 2026).
-- Password akun `sistomate` memakai hash yang sama dengan sql/configs.sql;
-- password asli dipegang Owner (lihat README).
USE sistomate;

-- 1) Akun admin bersama (idempotent; configs.sql juga membuat akun ini).
INSERT INTO users (name, email, password_hash)
SELECT 'Administrator', 'sistomate',
       '$2y$12$zv.C9w3twO5GB82xGteTZe5jJlg6wnESIOEXdxEbeMrYwW6n.cv4y'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'sistomate');

-- 2) Satu device demo.
INSERT INTO devices (device_name, location, id_user)
SELECT 'ESP32-Greenhouse-1', 'Bed Kanan', u.id_user
FROM users u
WHERE u.email = 'sistomate'
  AND NOT EXISTS (
    SELECT 1 FROM devices d WHERE d.device_name = 'ESP32-Greenhouse-1'
  );

SET @id_device := (
  SELECT id_device FROM devices
  WHERE device_name = 'ESP32-Greenhouse-1'
  LIMIT 1
);

-- 3) Log sensor contoh (hanya jika device belum punya log).
INSERT INTO sensor_logs
  (recorded_at, air_temperature, air_humidity, soil_moisture,
   rgb_red, rgb_green, rgb_blue, plant_status, id_device)
SELECT * FROM (
  SELECT '2026-09-18 08:00:00' AS recorded_at, 28.50 AS air_temperature,
         70.00 AS air_humidity, 45.50 AS soil_moisture,
         120 AS rgb_red, 180 AS rgb_green, 90 AS rgb_blue,
         'sehat' AS plant_status, @id_device AS id_device
  UNION ALL
  SELECT '2026-09-18 09:00:00', 30.20, 65.00, 32.00, 130, 175, 95, 'layu', @id_device
  UNION ALL
  SELECT '2026-09-18 10:00:00', 32.10, 58.00, 22.50, 140, 150, 100, 'layu', @id_device
) AS demo
WHERE @id_device IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM sensor_logs s WHERE s.id_device = @id_device
  );

-- 4) Log aktuator contoh (hanya jika device belum punya log).
INSERT INTO actuator_logs (actuator_name, status, executed_at, id_device)
SELECT * FROM (
  SELECT 'pompa air' AS actuator_name, 'ON' AS status,
         '2026-09-18 10:05:00' AS executed_at, @id_device AS id_device
  UNION ALL
  SELECT 'pompa air', 'OFF', '2026-09-18 10:10:00', @id_device
) AS demo
WHERE @id_device IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM actuator_logs a WHERE a.id_device = @id_device
  );

-- Galeri sengaja dibiarkan kosong; gambar diunggah lewat dashboard.
