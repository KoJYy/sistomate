-- Demo 3 jenis JOIN SISTOMATE (MySQL 8 / MariaDB 10.11).
-- Memakai data nyata database `sistomate`; query bersifat read-only.
--
-- Ringkasan data (26 Nov 2026):
--   users         : 7 baris (2 Administrator + 5 anggota tim)
--   devices       : 1 baris (ESP32-Greenhouse-1, milik Administrator)
--   sensor_logs   : 14106 baris (semua GH-1)
--   actuator_logs : 743 baris (semua GH-1)
--
-- Inti perbedaan:
--   INNER = hanya baris yang punya pasangan di kedua tabel
--   LEFT  = semua baris tabel kiri + pasangannya (NULL bila tidak ada)
--   RIGHT = semua baris tabel kanan + pasangannya (NULL bila tidak ada)

-- 1. INNER JOIN: users + devices ------------------------------------------------
-- Hanya user yang punya device (pemilik device). Hasil: 1 baris.
SELECT u.id_user, u.name, d.device_name, d.location
FROM users u
INNER JOIN devices d ON d.id_user = u.id_user
ORDER BY u.id_user, d.id_device;

-- 2. LEFT JOIN: users + devices -------------------------------------------------
-- Semua user tetap muncul; device kosong (NULL) bila belum punya.
-- Hasil: 7 baris (6 user tampil dengan device NULL).
SELECT u.id_user, u.name, d.device_name, d.location
FROM users u
LEFT JOIN devices d ON d.id_user = u.id_user
ORDER BY u.id_user, d.id_device;

-- 3. RIGHT JOIN: actuator_logs + devices (agregat) ------------------------------
-- Semua device tetap muncul. Hasil: 1 baris (GH-1 dengan 743 aksi).
SELECT d.id_device, d.device_name,
       COUNT(a.id_action) AS jumlah_aksi,
       MAX(a.executed_at) AS aksi_terakhir
FROM actuator_logs a
RIGHT JOIN devices d ON d.id_device = a.id_device
GROUP BY d.id_device, d.device_name
ORDER BY d.id_device;
