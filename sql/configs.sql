-- sql/configs.sql - SISTOMATE: konfigurasi histeresis per-device + akun tim default
-- Jalankan SETELAH sql/schema.sql (tabel devices harus sudah ada), contoh:
--   mysql -u root -p sistomate < sql/configs.sql
-- Model: satu baris configs untuk setiap devices (relasi 1:1 lewat id_device).
-- File ini hanya menambah tabel configs + baris default per device + 6 akun tim;
-- tabel users/devices/sensor_logs/actuator_logs/gallery tidak diubah.

CREATE TABLE IF NOT EXISTS configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_device INT NOT NULL,                             -- pemilik konfigurasi
  soil_on DECIMAL(5,2) NOT NULL DEFAULT 40.00,        -- pompa air NYALA di bawah ini (%)
  soil_off DECIMAL(5,2) NOT NULL DEFAULT 60.00,       -- pompa air MATI di atas ini (%)
  temp_on DECIMAL(5,2) NOT NULL DEFAULT 27.00,        -- kipas exhaust NYALA di atas ini (C)
  temp_off DECIMAL(5,2) NOT NULL DEFAULT 25.00,       -- kipas exhaust MATI di bawah ini (C)
  fert_duration_s INT NOT NULL DEFAULT 7,             -- durasi dosis fertigasi (detik)
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_configs_device (id_device),
  CONSTRAINT fk_configs_device FOREIGN KEY (id_device)
    REFERENCES devices (id_device) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Satu baris konfigurasi per device yang sudah terdaftar (nilai = ambang firmware).
-- Device yang ditambahkan setelah file ini dijalankan akan dibuatkan barisnya
-- otomatis saat admin menyimpan halaman Pengaturan (upsert).
INSERT INTO configs (id_device, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
SELECT d.id_device, 40.00, 60.00, 27.00, 25.00, 7
FROM devices d
WHERE NOT EXISTS (
  SELECT 1 FROM configs c WHERE c.id_device = d.id_device
);

-- Akun tim (keputusan Owner 21 Nov): 6 akun dari paket Calvin, TAPI password
-- sudah diganti kuat & unik (tidak lagi memakai password sama dengan username).
-- File ini hanya menyimpan hash bcrypt; password asli dipegang Owner.
-- Kolom `email` dipakai sebagai username login dashboard.
INSERT INTO users (name, email, password_hash)
SELECT 'Administrator', 'sistomate', '$2y$12$zv.C9w3twO5GB82xGteTZe5jJlg6wnESIOEXdxEbeMrYwW6n.cv4y'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'sistomate');
INSERT INTO users (name, email, password_hash)
SELECT 'Yuda Maredya A. P.', 'yudha', '$2y$12$A73pfSykLb0412NVEChpleY5KnVP1TAXFDVqhgPfsxymcPFKM9Y0S'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'yudha');
INSERT INTO users (name, email, password_hash)
SELECT 'Moch Calvin Aprilian', 'calvin', '$2y$12$3b.u8yA54juG4liFY2ODZubiTD3tYXppzQS6z1rutbruO4CIea.W6'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'calvin');
INSERT INTO users (name, email, password_hash)
SELECT 'Tio Dwi Harianto', 'tio', '$2y$12$iFGQc4WGeRJGzSgpR967/.s64tJ2N4ZsI37vmmFEpvIDLdPHbgoui'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'tio');
INSERT INTO users (name, email, password_hash)
SELECT 'Ibror Yusron S.', 'ibror', '$2y$12$2Tm1JdZ3nKDaYYJlBBAXvOIiVZX6eZU2c0eaJ/jLyqwNI9V/dQwGO'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'ibror');
INSERT INTO users (name, email, password_hash)
SELECT 'Nabil Laudza Nur', 'nabil', '$2y$12$nPyKvNNUBYH0a9y0mu03VORXMuH6BKT2AEEdOKzY/y/hT8x5cD5AS'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'nabil');

-- ---------------------------------------------------------------------------
-- GRANT (jalankan sekali sebagai root, setelah tabel configs dibuat):
-- Tanpa grant ini, user aplikasi 'sistomate_app' tidak bisa membaca/menyimpan
-- konfigurasi dari halaman settings.php.
--
-- GRANT SELECT, INSERT, UPDATE ON sistomate.configs TO 'sistomate_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
