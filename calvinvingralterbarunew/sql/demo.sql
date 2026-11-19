-- SISTOMATE demo queries (presentasi minggu-5). Jalankan setelah schema.sql + seed.sql.
USE sistomate;

-- 1. Struktur + relasi (bukti tabel sesuai ERD)
SHOW TABLES;
SHOW CREATE TABLE sensor_logs\G

-- 2. Bacaan terbaru per perangkat (monitoring real-time)
SELECT d.device_name, s.recorded_at, s.air_temperature, s.air_humidity,
       s.soil_moisture, s.plant_status
FROM sensor_logs s
JOIN devices d ON d.id_device = s.id_device
WHERE s.recorded_at = (
  SELECT MAX(s2.recorded_at) FROM sensor_logs s2
  WHERE s2.id_device = s.id_device
);

-- 3. Distribusi status tanaman (kesehatan kebun sekilas)
SELECT plant_status, COUNT(*) AS jumlah
FROM sensor_logs
GROUP BY plant_status;

-- 4. Riwayat penyiraman pompa per perangkat (JOIN devices + actuator_logs)
SELECT d.device_name, a.actuator_name, a.status, a.executed_at
FROM actuator_logs a
JOIN devices d ON d.id_device = a.id_device
ORDER BY a.executed_at;

-- 5. Dokumentasi foto per perangkat + pengunggah (bukti relasi gallery 2 FK)
SELECT d.device_name, g.image_url, g.caption, g.upload_date, u.name AS pengunggah
FROM gallery g
JOIN devices d ON d.id_device = g.id_device
LEFT JOIN users u ON u.id_user = g.id_user;

-- 6. Time-series 1 perangkat (pakai INDEX id_device, recorded_at)
SELECT recorded_at, soil_moisture, plant_status
FROM sensor_logs
WHERE id_device = 1
ORDER BY recorded_at;
