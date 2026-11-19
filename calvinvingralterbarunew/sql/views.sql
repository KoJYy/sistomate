-- VIEW riwayat SISTOMATE (MySQL 8 / MariaDB 10.11).
-- Tujuan: halaman Riwayat tidak perlu menulis JOIN berulang di PHP;
-- cukup SELECT dari view. Data tetap non-redundan karena view tidak
-- menyimpan salinan baris, hanya definisi query.
--
-- Cara pakai:
--   mysql -u root -p sistomate < sql/views.sql
-- atau di phpMyAdmin: pilih database `sistomate` -> tab SQL -> paste file ini.
--
-- Catatan: view TIDAK menyimpan data. Nama device tetap satu-satunya
-- sumber di tabel devices; view mengambilnya saat dibaca (fresh).

CREATE OR REPLACE VIEW v_riwayat_sensor AS
SELECT
    s.id_log,
    s.recorded_at,
    s.id_device,
    d.device_name,
    d.location,
    s.air_temperature,
    s.air_humidity,
    s.soil_moisture,
    s.rgb_red,
    s.rgb_green,
    s.rgb_blue,
    s.plant_status
FROM sensor_logs s
JOIN devices d ON d.id_device = s.id_device;

CREATE OR REPLACE VIEW v_riwayat_aktuator AS
SELECT
    a.id_action,
    a.executed_at,
    a.id_device,
    d.device_name,
    d.location,
    a.actuator_name,
    a.status
FROM actuator_logs a
JOIN devices d ON d.id_device = a.id_device;
