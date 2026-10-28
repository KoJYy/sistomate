-- SISTOMATE seed data demo (presentasi minggu-5).
USE sistomate;

INSERT INTO users (name, email, password_hash) VALUES
  ('Budi Santoso', 'budi@polije.ac.id', '$2y$10$demohashbudi santosoxxx'),
  ('Ayu Lestari', 'ayu@polije.ac.id', '$2y$10$demohashayulestarixxxx');

INSERT INTO devices (device_name, location, id_user) VALUES
  ('ESP32-Greenhouse-1', 'Bed-1-Baris-2', 1),
  ('ESP32-Greenhouse-2', 'Bed-2-Baris-1', 2);

INSERT INTO sensor_logs
  (recorded_at, air_temperature, air_humidity, soil_moisture,
   rgb_red, rgb_green, rgb_blue, plant_status, id_device) VALUES
  ('2026-09-18 08:00:00', 28.50, 70.00, 45.50, 120, 180, 90, 'sehat', 1),
  ('2026-09-18 09:00:00', 30.20, 65.00, 32.00, 130, 175, 95, 'kering', 1),
  ('2026-09-18 10:00:00', 32.10, 58.00, 22.50, 140, 150, 100, 'layu', 1),
  ('2026-09-18 08:00:00', 27.80, 72.00, 48.00, 150, 110, 95, 'sakit', 2);

INSERT INTO actuator_logs (actuator_name, status, executed_at, id_device) VALUES
  ('pompa air', 'ON', '2026-09-18 10:05:00', 1),
  ('pompa air', 'OFF', '2026-09-18 10:10:00', 1);

INSERT INTO gallery (image_url, caption, upload_date, id_user, id_device) VALUES
  ('/img/tomat-a01-001.jpg', 'Daun tomat Bed-1 pagi hari', '2026-09-18 08:05:00', 2, 1),
  ('/img/tomat-b02-001.jpg', 'Gejala klorosis Bed-2', '2026-09-18 08:06:00', 2, 2);
