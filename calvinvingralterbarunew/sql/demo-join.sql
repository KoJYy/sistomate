-- Demo 3 jenis JOIN SISTOMATE (MySQL 8 / MariaDB 10.11).
-- Memakai data nyata database `sistomate`; query bersifat read-only.
--
-- Ringkasan data saat demo (18 Nov 2026):
--   users         : 4 baris (Budi, Ayu, Candra, Administrator)
--   devices       : 3 baris (GH-1 & GH-3 milik Budi, GH-2 milik Ayu)
--   sensor_logs   : 3362 baris (GH-1: 3290, GH-2: 72, GH-3: 0)
--   actuator_logs : 35 baris (semua milik GH-1; GH-2 & GH-3: 0)
--
-- Inti perbedaan:
--   INNER = hanya baris yang punya pasangan di kedua tabel
--   LEFT  = semua baris tabel kiri + pasangannya (NULL bila tidak ada)
--   RIGHT = semua baris tabel kanan + pasangannya (NULL bila tidak ada)

-- 1. INNER JOIN: users + devices ------------------------------------------------
-- Hanya user yang punya device. Candra & Administrator tidak muncul.
-- Hasil: 3 baris.
SELECT u.id_user, u.name, d.device_name, d.location
FROM users u
INNER JOIN devices d ON d.id_user = u.id_user
ORDER BY u.id_user, d.id_device;

-- 2. LEFT JOIN: users + devices -------------------------------------------------
-- Semua user tetap muncul; device kosong (NULL) bila belum punya.
-- Hasil: 5 baris (Candra & Administrator tampil dengan device NULL).
SELECT u.id_user, u.name, d.device_name, d.location
FROM users u
LEFT JOIN devices d ON d.id_user = u.id_user
ORDER BY u.id_user, d.id_device;

-- 3. RIGHT JOIN: actuator_logs + devices (agregat) ------------------------------
-- Semua device tetap muncul, termasuk GH-2 & GH-3 yang belum punya log aktuator.
-- Hasil: 3 baris; GH-2 & GH-3 -> jumlah_aksi = 0, aksi_terakhir = NULL.
SELECT d.id_device, d.device_name,
       COUNT(a.id_action) AS jumlah_aksi,
       MAX(a.executed_at) AS aksi_terakhir
FROM actuator_logs a
RIGHT JOIN devices d ON d.id_device = a.id_device
GROUP BY d.id_device, d.device_name
ORDER BY d.id_device;
