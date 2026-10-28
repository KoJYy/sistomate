-- SISTOMATE schema (MySQL 8.x). Source: bab3-hasil-pembahasan.md + ERD final.
CREATE DATABASE IF NOT EXISTS sistomate
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistomate;

CREATE TABLE users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE devices (
  id_device INT AUTO_INCREMENT PRIMARY KEY,
  device_name VARCHAR(100) NOT NULL,
  location VARCHAR(150) NOT NULL,
  id_user INT NOT NULL,
  CONSTRAINT fk_devices_user FOREIGN KEY (id_user)
    REFERENCES users (id_user) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sensor_logs (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  recorded_at DATETIME NOT NULL,
  air_temperature FLOAT NOT NULL,
  air_humidity FLOAT NOT NULL,
  soil_moisture FLOAT NOT NULL,
  rgb_red INT NOT NULL,
  rgb_green INT NOT NULL,
  rgb_blue INT NOT NULL,
  plant_status ENUM('sehat','kering','layu','sakit') NOT NULL,
  id_device INT NOT NULL,
  CONSTRAINT fk_sensor_device FOREIGN KEY (id_device)
    REFERENCES devices (id_device) ON DELETE CASCADE,
  INDEX idx_sensor_device_time (id_device, recorded_at)
) ENGINE=InnoDB;

CREATE TABLE actuator_logs (
  id_action INT AUTO_INCREMENT PRIMARY KEY,
  actuator_name VARCHAR(100) NOT NULL,
  status ENUM('ON','OFF') NOT NULL,
  executed_at DATETIME NOT NULL,
  id_device INT NOT NULL,
  CONSTRAINT fk_actuator_device FOREIGN KEY (id_device)
    REFERENCES devices (id_device) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gallery (
  id_gallery INT AUTO_INCREMENT PRIMARY KEY,
  image_url VARCHAR(255) NOT NULL,
  caption VARCHAR(255) NULL,
  upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_user INT NULL,
  id_device INT NOT NULL,
  CONSTRAINT fk_gallery_user FOREIGN KEY (id_user)
    REFERENCES users (id_user) ON DELETE SET NULL,
  CONSTRAINT fk_gallery_device FOREIGN KEY (id_device)
    REFERENCES devices (id_device) ON DELETE CASCADE
) ENGINE=InnoDB;
