-- sql/configs.sql - SISTOMATE: konfigurasi histeresis per-device + akun admin default
-- Jalankan SETELAH sql/schema.sql (tabel devices harus sudah ada), contoh:
--   mysql -u root -p sistomate < sql/configs.sql
-- Model: satu baris configs untuk setiap devices (relasi 1:1 lewat id_device).
-- File ini hanya menambah tabel configs + baris default per device + 1 akun admin;
-- tabel users/devices/sensor_logs/actuator_logs/gallery tidak diubah.

CREATE TABLE IF NOT EXISTS configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_device INT NOT NULL,                             -- pemilik konfigurasi
  soil_on DECIMAL(5,2) NOT NULL DEFAULT 40.00,        -- pompa air NYALA di bawah ini (%)
  soil_off DECIMAL(5,2) NOT NULL DEFAULT 55.00,       -- pompa air MATI di atas ini (%)
  temp_on DECIMAL(5,2) NOT NULL DEFAULT 32.00,        -- kipas exhaust NYALA di atas ini (C)
  temp_off DECIMAL(5,2) NOT NULL DEFAULT 31.00,       -- kipas exhaust MATI di bawah ini (C)
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
SELECT d.id_device, 40.00, 55.00, 32.00, 31.00, 7
FROM devices d
WHERE NOT EXISTS (
  SELECT 1 FROM configs c WHERE c.id_device = d.id_device
);

-- Akun admin default untuk dashboard web.
-- Email : admin@sistomate.id
-- Password: admin123@  (GANTI SEGERA setelah login pertama!)
-- Hash: password_hash('admin123@', PASSWORD_BCRYPT, cost 12), diverifikasi password_verify.
INSERT INTO users (name, email, password_hash)
SELECT 'Administrator', 'admin@sistomate.id',
       '$2y$12$ZhNhUzsjX6Wi3B98aMzo5ux/SZZ.Q2GDI6cikjtAM2wUp3R938GTG'
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@sistomate.id'
);

-- ---------------------------------------------------------------------------
-- GRANT (jalankan sekali sebagai root, setelah tabel configs dibuat):
-- Tanpa grant ini, user aplikasi 'sistomate_app' tidak bisa membaca/menyimpan
-- konfigurasi dari halaman settings.php.
--
-- GRANT SELECT, INSERT, UPDATE ON sistomate.configs TO 'sistomate_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
