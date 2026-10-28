-- sql/configs.sql - SISTOMATE: tabel konfigurasi histeresis + akun admin default
-- Jalankan sebagai root/admin, contoh: mysql sistomate < sql/configs.sql
-- Catatan: file ini HANYA menambah tabel configs + 1 baris konfigurasi default
--          + 1 akun admin. Tabel users/devices/sensor_logs/actuator_logs/gallery
--          TIDAK di-ALTER, tidak ada UPDATE/DELETE baris lama.

CREATE TABLE configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  soil_on DECIMAL(5,2) NOT NULL DEFAULT 40.00,       -- pompa air NYALA di bawah ini (%)
  soil_off DECIMAL(5,2) NOT NULL DEFAULT 55.00,      -- pompa air MATI di atas ini (%)
  temp_on DECIMAL(5,2) NOT NULL DEFAULT 32.00,       -- kipas exhaust NYALA di atas ini (C)
  temp_off DECIMAL(5,2) NOT NULL DEFAULT 31.00,      -- kipas exhaust MATI di bawah ini (C)
  fert_duration_s INT NOT NULL DEFAULT 7,            -- durasi dosis fertigasi (detik)
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 1 baris konfigurasi singleton (id=1), nilai = ambang bawaan firmware SISTOMATE.ino
INSERT INTO configs (id, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
VALUES (1, 40.00, 55.00, 32.00, 31.00, 7)
ON DUPLICATE KEY UPDATE id = id;

-- Akun admin default untuk dashboard web.
-- Email : admin@sistomate.local
-- Password: AdminSistomate2026  (GANTI SEGERA setelah login pertama!)
INSERT INTO users (name, email, password_hash)
SELECT 'Administrator', 'admin@sistomate.local',
       '$2y$12$an/VJV0gCODjeeANXPtS4.YAFAKohbRh4Y9uahVByGFoZfRTdNFUW'
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@sistomate.local'
);

-- ---------------------------------------------------------------------------
-- GRANT (jalankan sekali sebagai root, setelah tabel configs dibuat):
-- Tanpa grant ini, user aplikasi 'sistomate_app' tidak bisa membaca/menyimpan
-- konfigurasi dari halaman settings.php.
--
-- GRANT SELECT, INSERT, UPDATE ON sistomate.configs TO 'sistomate_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
