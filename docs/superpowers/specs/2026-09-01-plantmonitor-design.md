# Sistem Otomatisasi Penyiraman dan Monitoring Tanaman Berbasis IoT Menggunakan ESP32-CAM dan Kecerdasan Buatan

**Mata Kuliah:** Workshop Basis Data
**Studi Kasus:** Smart Greenhouse Tanaman Cabai — Otomatisasi Penyiraman + Monitoring Berbasis AI
**Tanggal:** 2026-09-01
**Status:** Draft — Menunggu Review

---

## 1. Ringkasan

Sistem otomatisasi penyiraman dan monitoring tanaman berbasis IoT menggunakan ESP32-CAM (kamera) dan ESP32 (6 sensor + relay pompa), dengan MySQL sebagai database relasional utama. Gambar dari ESP32-CAM dikirim ke server PHP, dianalisis menggunakan Google Gemini API untuk mendeteksi kesehatan tanaman (penyakit, hama, pertumbuhan). Data sensor (suhu, kelembapan, cahaya, air) dikirim via HTTP, disimpan ke database menggunakan stored procedures, triggers, dan events. Dashboard webapp menampilkan data real-time, grafik historis, notifikasi otomatis, dan hasil analisis AI. Studi kasus: Smart Greenhouse untuk tanaman cabai — otomatisasi penyiraman berbasis sensor + monitoring kesehatan berbasis AI.

---

## 2. Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                      VPS (Ubuntu)                       │
│                                                         │
│  ┌──────────┐   ┌──────────┐   ┌────────────────────┐  │
│  │  MySQL   │◄──│   PHP    │◄──│   Web Dashboard    │  │
│  │ Database │   │ Backend  │   │ (HTML/CSS/JS)      │  │
│  └────▲─────┘   └────▲─────┘   └────────────────────┘  │
│       │              │                                  │
└───────┼──────────────┼──────────────────────────────────┘
        │              │ HTTP POST
        │         ┌────┴────┐
        │         │  Nginx  │ (reverse proxy)
        │         └────▲────┘
        │              │
   ┌────┴──────────────┴────┐
   │     Internet / WiFi     │
   └────┬──────────────┬────┘
        │              │
   ┌────┴────┐   ┌────┴────┐
   │  ESP32  │   │ESP32-CAM│
   │(6 sensor│   │(kamera) │
   │+ pompa) │   └─────────┘
   └─────────┘
```

**Protokol:** HTTP langsung (tanpa MQTT)
**Backend:** PHP 8.x + PDO (prepared statements)
**Database:** MySQL 8.x
**Frontend:** Bootstrap 5 + Chart.js + Vanilla JS

---

## 3. Komponen Hardware

### 3.1 ESP32 Utama

| Komponen | Pin | Fungsi | Tipe |
|----------|-----|--------|------|
| Soil Moisture | GPIO 34 | Kelembapan tanah | Analog (0-4095 → 0-100%) |
| DHT11 | GPIO 13 | Suhu & kelembapan udara | Digital (Hindari GPIO 15 — strapping pin!) |
| LDR | GPIO 35 | Intensitas cahaya | Analog (ADC1, input only) |
| Rain Sensor | GPIO 27 | Deteksi hujan | Digital (0/1) |
| Water Level | GPIO 26 | Level air di tangki | Analog |
| Relay Pompa | GPIO 4 | Kontrol pompa air | Digital — **butuh transistor driver (2N2222 + flyback diode)**, GPIO max 40mA, relay coil 80mA |

**⚠️ Catatan Hardware:**
- **GPIO 34/35:** Input only (gak bisa OUTPUT). OK buat sensor analog.
- **GPIO 15:** Strapping pin — pull-down saat boot bisa cause boot failure. Pindah ke GPIO 13.
- **Relay driver:** ESP32 GPIO max 40mA (recommended 20mA). Relay module butuh 70-80mA. **WAJIB** pakai transistor driver (2N2222/NPN) + flyback diode (1N4007). Jangan drive relay langsung dari GPIO!
- **Watchdog timer:** ESP32 firmware harus init `esp_task_wdt_init(30, true)` di setup() — biar auto-restart kalau firmware hang.

**Interval pembacaan:** Tiap 30 detik (sufficient untuk solar-powered; lebih cepat = lebih boros baterai)

### 3.2 ESP32-CAM

| Komponen | Fungsi |
|----------|--------|
| Camera (OV2640) | Ambil gambar daun/buah tanaman |

**Interval pengambilan gambar:** Tiap 5 menit

---

## 4. Database Schema

### 4.1 ER Diagram

```
┌─────────────┐      ┌──────────────────┐      ┌─────────────┐
│   plants    │      │ sensor_readings  │      │   sensors   │
├─────────────┤      ├──────────────────┤      ├─────────────┤
│ id (PK)     │◄─────│ plant_id (FK)    │      │ id (PK)     │
│ name        │      │ sensor_id (FK)   │─────►│ plant_id(FK)│
│ species     │      │ value            │      │ type        │
│ location    │      │ unit             │      │ pin         │
│ created_at  │      │ recorded_at      │      │ status      │
└──────┬──────┘      └──────────────────┘      └─────────────┘
       │
       ├──────┌──────────────────┐      ┌──────────────────┐
       │      │     images       │      │  pump_actions    │
       │      ├──────────────────┤      ├──────────────────┤
       │      │ id (PK)          │      │ id (PK)          │
       │      │ plant_id (FK)    │      │ plant_id (FK)    │
       │      │ file_path        │      │ action_type      │
       │      │ caption          │      │ duration_sec     │
       │      │ captured_at      │      │ triggered_by     │
       │      └──────────────────┘      │ executed_at      │
       │                                └──────────────────┘
       ├──────┌──────────────────┐      ┌──────────────────┐
       │      │     alerts       │      │     users        │
       │      ├──────────────────┤      ├──────────────────┤
       │      │ id (PK)          │      │ id (PK)          │
       │      │ plant_id (FK)    │      │ username (UNIQUE)│
       │      │ type             │      │ password_hash    │
       │      │ message          │      │ role             │
       │      │ is_read          │      │ last_login       │
       │      │ created_at       │      │ created_at       │
       │      └──────────────────┘      └──────────────────┘
       │
       ├──────┌──────────────────┐
       │      │pending_commands  │
       │      ├──────────────────┤
       │      │ id (PK)          │
       │      │ plant_id (FK)    │
       │      │ command          │
       │      │ params (JSON)    │
       │      │ status           │
       │      │ created_at       │
       │      │ executed_at      │
       │      └──────────────────┘
       │
       └──────┌──────────────────────────┐
              │  plant_health_analysis    │
              ├──────────────────────────┤
              │ id (PK)                  │
              │ plant_id (FK)            │
              │ image_id (FK → images)   │
              │ health_status (ENUM)     │
              │ confidence (DECIMAL)     │
              │ notes (TEXT)             │
              │ analyzed_at              │
              └──────────────────────────┘
```

> **Note:** `users` adalah tabel autentikasi independent — **tidak ada FK ke plants**. Dashboard bisa digunakan untuk memantau banyak tanaman, tidak terikat per-user.
> **Note:** `plant_health_analysis` menghubungkan data citra daun dengan hasil klasifikasi ML (Sehat/Layu/Terinfeksi Jamur) untuk analisis korelasi dengan data sensor lingkungan.

### 4.2 Tabel `plants`

```sql
CREATE TABLE plants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    species VARCHAR(100),
    location VARCHAR(100),
    image_url VARCHAR(255),               -- Foto profil tanaman (di-set dari dashboard, bukan dari ESP32-CAM)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 4.3 Tabel `sensors`

```sql
CREATE TABLE sensors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    type ENUM('soil_moisture','temperature','humidity','light','rain','water_level'),
    pin VARCHAR(10),
    status ENUM('active','inactive','error') DEFAULT 'active',
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_plant_sensor (plant_id, type)  -- 1 sensor type per tanaman
);
```

### 4.4 Tabel `sensor_readings`

```sql
CREATE TABLE sensor_readings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    sensor_id INT NOT NULL,
    value DECIMAL(5,2) NOT NULL,          -- DECIMAL(5,2) = -999.99 s/d 999.99 (support suhu negatif)
    unit VARCHAR(20) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    INDEX idx_plant_time (plant_id, recorded_at),
    INDEX idx_sensor_time (sensor_id, recorded_at)
);
```

### 4.5 Tabel `images`

```sql
CREATE TABLE images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255),
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);
```

### 4.6 Tabel `pump_actions`

```sql
CREATE TABLE pump_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    action_type ENUM('auto','manual') NOT NULL,
    duration_sec INT UNSIGNED NOT NULL,   -- Durasi pompa nyala (detik), wajib diisi, tidak negatif
    triggered_by VARCHAR(50) NOT NULL,    -- "system" atau username
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);
```

### 4.7 Tabel `alerts`

```sql
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    type ENUM('low_moisture','high_temp','pump_failure','water_low') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    INDEX idx_alerts_unread (plant_id, is_read)  -- Optimasi dashboard query
);
```

### 4.8 Tabel `users`

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','viewer') DEFAULT 'viewer',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 4.9 Tabel `pending_commands` (untuk kontrol manual pompa dari dashboard)

```sql
CREATE TABLE pending_commands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    command VARCHAR(50) NOT NULL,        -- 'pump_on', 'pump_off'
    params JSON,                         -- {"duration_sec": 30}
    status ENUM('pending','executing','done','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    INDEX idx_cmd_pending (plant_id, status)  -- ESP32 query pending commands
);
```

### 4.10 Tabel `plant_health_analysis` (analisis kesehatan daun dari citra ESP32-CAM)

> **Studi Kasus:** Deteksi dini penyakit daun pada tanaman cabai/tomat. ESP32-CAM mengambil gambar daun, dikirim ke backend, lalu dianalisis menggunakan model ML (TensorFlow Lite) untuk mengklasifikasi kondisi: Sehat, Layu, atau Terinfeksi Jamur. Hasil analisis disimpan di tabel ini bersama data sensor terkait untuk analisis korelasi.

```sql
CREATE TABLE plant_health_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    image_id INT NOT NULL,
    health_status ENUM('sehat','layu','terinfeksi_jamur') NOT NULL,
    confidence DECIMAL(5,4) NOT NULL,    -- 0.0000 s/d 1.0000 (skor confidence ML)
    notes TEXT,                          -- Catatan manual dari operator (opsional)
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_health_plant (plant_id, analyzed_at),
    INDEX idx_health_status (health_status, analyzed_at)
);
```

**Relasi:**
- `plant_id` → `plants.id` (tanaman yang dianalisis)
- `image_id` → `images.id` (gambar daun yang dianalisis)
- Data sensor pada saat pengambilan gambar bisa di-query dari `sensor_readings` WHERE `recorded_at` ≈ `analyzed_at`

---

## 5. Fitur Database (Demonstrasi Workshop)

| Fitur | Penerapan di Project |
|-------|---------------------|
| PRIMARY KEY / FOREIGN KEY | Relasi semua tabel |
| INSERT | ESP32 kirim data → insert sensor_readings |
| SELECT + WHERE | "Ambil data suhu tanaman 1 hari ini" |
| JOIN | Dashboard gabungkan plants + sensors + readings |
| GROUP BY + AVG | Rata-rata kelembapan per hari |
| ORDER BY + LIMIT | 10 pembacaan terakhir |
| VIEWS | Summary view untuk dashboard, **v_plant_health_summary** (analisis korelasi) |
| STORED PROCEDURES | sp_insert_reading, sp_get_history, **sp_correlation_analysis** |
| TRIGGERS | trg_low_moisture → auto-create alert |
| EVENTS | Cleanup data > 90 hari |
| INDEX | Optimasi query histori besar, **idx_health_plant, idx_health_status** |
| ENUM | Validasi data di level database |

### 5.1 Stored Procedures

```sql
-- Insert data sensor dari ESP32
DELIMITER //
CREATE PROCEDURE sp_insert_reading(
    IN p_plant_id INT,
    IN p_sensor_type VARCHAR(20),
    IN p_value DECIMAL(5,2),
    IN p_unit VARCHAR(20)
)
BEGIN
    DECLARE v_sensor_id INT;

    -- Cari sensor_id berdasarkan plant_id + type
    SELECT id INTO v_sensor_id
    FROM sensors
    WHERE plant_id = p_plant_id AND type = p_sensor_type
    LIMIT 1;

    -- Validasi: sensor harus ada
    IF v_sensor_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sensor not found for this plant and type';
    END IF;

    -- Insert data
    INSERT INTO sensor_readings (plant_id, sensor_id, value, unit)
    VALUES (p_plant_id, v_sensor_id, p_value, p_unit);
END //
DELIMITER ;

-- Ambil histori sensor (untuk grafik)
DELIMITER //
CREATE PROCEDURE sp_get_history(
    IN p_plant_id INT,
    IN p_sensor_type VARCHAR(20),
    IN p_hours INT
)
BEGIN
    SELECT sr.value, sr.unit, sr.recorded_at
    FROM sensor_readings sr
    JOIN sensors s ON sr.sensor_id = s.id
    WHERE sr.plant_id = p_plant_id
      AND s.type = p_sensor_type
      AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL p_hours HOUR)
    ORDER BY sr.recorded_at ASC;
END //
DELIMITER ;

-- Analisis korelasi: kelembapan udara tinggi vs kesehatan daun
-- Untuk studi kasus: "Kelembapan tinggi memicu timbulnya jamur pada daun"
DELIMITER //
CREATE PROCEDURE sp_correlation_analysis(
    IN p_plant_id INT,
    IN p_days INT
)
BEGIN
    -- Ambil rata-rata kelembapan udara per hari + jumlah kasus jamur terdeteksi
    SELECT
        DATE(pha.analyzed_at) AS analysis_date,
        AVG(CASE WHEN s.type = 'humidity' THEN sr.value END) AS avg_humidity,
        AVG(CASE WHEN s.type = 'temperature' THEN sr.value END) AS avg_temperature,
        AVG(CASE WHEN s.type = 'soil_moisture' THEN sr.value END) AS avg_soil_moisture,
        SUM(CASE WHEN pha.health_status = 'terinfeksi_jamur' THEN 1 ELSE 0 END) AS jamur_count,
        SUM(CASE WHEN pha.health_status = 'sehat' THEN 1 ELSE 0 END) AS sehat_count,
        COUNT(pha.id) AS total_analyses
    FROM plant_health_analysis pha
    JOIN images i ON pha.image_id = i.id
    LEFT JOIN sensor_readings sr ON sr.plant_id = pha.plant_id
        AND ABS(TIMESTAMPDIFF(SECOND, sr.recorded_at, pha.analyzed_at)) < 300  -- sensor reading dalam ±5 menit dari analisis
    LEFT JOIN sensors s ON sr.sensor_id = s.id
    WHERE pha.plant_id = p_plant_id
      AND pha.analyzed_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY DATE(pha.analyzed_at)
    ORDER BY analysis_date ASC;
END //
DELIMITER ;
```

### 5.2 Triggers

```sql
-- Auto-create alert jika soil moisture rendah
DELIMITER //
CREATE TRIGGER trg_low_moisture
AFTER INSERT ON sensor_readings
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(20);
    SELECT type INTO v_type FROM sensors WHERE id = NEW.sensor_id;

    IF v_type = 'soil_moisture' AND NEW.value < 30.00 THEN
        INSERT INTO alerts (plant_id, type, message)
        VALUES (NEW.plant_id, 'low_moisture',
                CONCAT('Tanaman butuh air! Kelembapan: ', NEW.value, '%'));
    END IF;
END //
DELIMITER ;
```

### 5.3 Events

```sql
-- Cleanup data > 90 hari (semua tabel yang grow unbounded)
DELIMITER //
CREATE EVENT evt_cleanup_old_data
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE FROM sensor_readings
    WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    DELETE FROM pump_actions
    WHERE executed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    DELETE FROM alerts
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    -- Images: hapus file fisik via PHP cron, lalu DELETE dari DB
    -- (MySQL gak bisahapus file langsung, tapi row bisa di-cleanup)
    DELETE FROM images
    WHERE captured_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;
```

### 5.4 Views

```sql
CREATE VIEW vw_plant_summary AS
SELECT
    p.id,
    p.name,
    p.species,
    COUNT(DISTINCT s.id) AS sensor_count,
    MAX(sr.recorded_at) AS last_reading,
    (SELECT value FROM sensor_readings sr2
     JOIN sensors s2 ON sr2.sensor_id = s2.id
     WHERE sr2.plant_id = p.id AND s2.type = 'soil_moisture'
     ORDER BY sr2.recorded_at DESC LIMIT 1) AS last_soil_moisture
FROM plants p
LEFT JOIN sensors s ON s.plant_id = p.id
LEFT JOIN sensor_readings sr ON sr.sensor_id = s.id
GROUP BY p.id;

-- Ringkasan kesehatan tanaman (untuk dashboard utama)
CREATE VIEW v_plant_health_summary AS
SELECT
    p.id AS plant_id,
    p.name AS plant_name,
    COUNT(pha.id) AS total_analyses,
    SUM(CASE WHEN pha.health_status = 'sehat' THEN 1 ELSE 0 END) AS count_sehat,
    SUM(CASE WHEN pha.health_status = 'layu' THEN 1 ELSE 0 END) AS count_layu,
    SUM(CASE WHEN pha.health_status = 'terinfeksi_jamur' THEN 1 ELSE 0 END) AS count_jamur,
    ROUND(SUM(CASE WHEN pha.health_status = 'terinfeksi_jamur' THEN 1 ELSE 0 END) / COUNT(pha.id) * 100, 2) AS jamur_percentage,
    MAX(pha.analyzed_at) AS last_analysis
FROM plants p
LEFT JOIN plant_health_analysis pha ON pha.plant_id = p.id
GROUP BY p.id, p.name;
```

---

## 6. Backend PHP

### 6.1 Struktur File

```
/var/www/plantmonitor/
├── api/
│   ├── config.php
│   ├── sensor.php
│   ├── image.php
│   ├── pump.php
│   ├── dashboard.php
│   └── auth.php
├── uploads/
│   └── images/
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── index.php
├── dashboard.php
├── plants.php
├── history.php
└── images.php
```

### 6.2 API Endpoints

| Endpoint | Method | Fungsi | Input |
|----------|--------|--------|-------|
| `/api/sensor.php` | POST | Terima data sensor | JSON: {plant_id, readings[]} |
| `/api/image.php` | POST | Upload gambar | multipart: plant_id, image |
| `/api/pump.php` | POST | Kontrol pompa | JSON: {plant_id, duration_sec} |
| `/api/pump.php` | GET | Status pompa | Query: plant_id |
| `/api/dashboard.php` | GET | Data dashboard | Query: plant_id, hours |
| `/api/auth.php` | POST | Login | JSON: {username, password} |

### 6.3 Config (config.php)

```php
<?php
// api/config.php
// ⚠️ Simpan credentials di .env file, JANGAN hardcode di source code!
// Contoh .env:
//   DB_HOST=localhost
//   DB_NAME=plantmonitor
//   DB_USER=plantuser
//   DB_PASS=your_secure_password
//   API_KEY_SENSOR=key_sensor_abc123
//   API_KEY_CAMERA=key_camera_xyz789

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'plantmonitor';
$DB_USER = getenv('DB_USER') ?: 'plantuser';
$DB_PASS = getenv('DB_PASS') ?: 'CHANGEME';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
    exit;
}

// Session security config
function initSession() {
    session_start([
        'cookie_lifetime' => 86400,         // 24 jam
        'cookie_httponly' => true,           // JS gak bisa akses cookie
        'cookie_secure'   => true,           // HTTPS only (set false saat dev)
        'cookie_samesite' => 'Lax',         // CSRF protection
        'use_strict_mode' => true
    ]);
}

// API key validation — pakai constant_time comparison (anti timing attack)
function validateApiKey() {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $sensorKey = getenv('API_KEY_SENSOR') ?: '';
    $cameraKey = getenv('API_KEY_CAMERA') ?: '';

    $valid = hash_equals($key, $sensorKey) || hash_equals($key, $cameraKey);

    if (!$valid) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}
```

### 6.4 Sensor Endpoint (sensor.php)

```php
<?php
// api/sensor.php
require_once 'config.php';
header('Content-Type: application/json');

validateApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['plant_id']) || !isset($input['readings'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

$plant_id = (int)$input['plant_id'];
$VALID_TYPES = ['soil_moisture','temperature','humidity','light','rain','water_level'];

try {
    foreach ($input['readings'] as $reading) {
        // Validasi sensor_type — harus match ENUM di database
        $type = $reading['sensor_type'] ?? '';
        if (!in_array($type, $VALID_TYPES)) {
            continue; // Skip invalid sensor type
        }

        // Validasi unit length
        $unit = substr($reading['unit'] ?? '', 0, 20);

        $stmt = $pdo->prepare("CALL sp_insert_reading(?, ?, ?, ?)");
        $stmt->execute([
            $plant_id,
            $type,
            $reading['value'],
            $unit
        ]);
    }
    echo json_encode([
        "status" => "ok",
        "count" => count($input['readings'])
    ]);
} catch (Exception $e) {
    error_log("PlantMonitor Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
```

### 6.5 Image Endpoint (image.php)

```php
<?php
// api/image.php — Upload (POST) + Serve (GET)
require_once 'config.php';

// GET: Serve image by ID (authenticated via session)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    initSession();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); exit; }

    $stmt = $pdo->prepare("SELECT file_path FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row || !file_exists($row['file_path'])) {
        http_response_code(404);
        exit;
    }

    // Determine MIME from actual file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $row['file_path']);
    finfo_close($finfo);

    header("Content-Type: $mime");
    header("Cache-Control: public, max-age=3600");
    readfile($row['file_path']);
    exit;
}

// POST: Upload image (authenticated via API key from ESP32-CAM)
header('Content-Type: application/json');
validateApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

// Validate sensor_type
$VALID_TYPES = ['soil_moisture','temperature','humidity','light','rain','water_level'];

try {
    $plant_id = (int)$_POST['plant_id'];
    $file = $_FILES['image'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload gagal");
    }

    // Validate size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File terlalu besar");
    }

    // Validate MIME type
    $allowed = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        throw new Exception("Format tidak didukung");
    }

    // Generate unique filename — gunakan extension dari MIME, BUKAN dari filename (anti path traversal)
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $ext = $extMap[$mime] ?? 'jpg';
    $newPath = 'uploads/images/' . date('Y/m/d/') . uniqid('img_') . '.' . $ext;

    // Create directory if not exists
    $dir = dirname($newPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $newPath)) {
        throw new Exception("Gagal simpan file");
    }

    // Insert to database
    $stmt = $pdo->prepare(
        "INSERT INTO images (plant_id, file_path, caption) VALUES (?, ?, ?)"
    );
    $stmt->execute([$plant_id, $newPath, $_POST['caption'] ?? '']);

    echo json_encode([
        "status" => "ok",
        "path" => $newPath
    ]);
} catch (Exception $e) {
    error_log("PlantMonitor Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
```

### 6.6 Dashboard Endpoint (dashboard.php)

```php
<?php
// api/dashboard.php
require_once 'config.php';
header('Content-Type: application/json');

initSession();  // HARUS pakai initSession(), bukan session_start() biasa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Login required"]);
    exit;
}

$plant_id = (int)($_GET['plant_id'] ?? 1);
$hours = (int)($_GET['hours'] ?? 24);

try {
    // Latest readings
    $stmt = $pdo->prepare("
        SELECT s.type, sr.value, sr.unit, sr.recorded_at
        FROM sensor_readings sr
        JOIN sensors s ON sr.sensor_id = s.id
        WHERE sr.plant_id = ?
        ORDER BY sr.recorded_at DESC
        LIMIT 6
    ");
    $stmt->execute([$plant_id]);
    $latest = $stmt->fetchAll();

    // Historical data for charts
    $stmt = $pdo->prepare("CALL sp_get_history(?, 'soil_moisture', ?)");
    $stmt->execute([$plant_id, $hours]);
    $soilHistory = $stmt->fetchAll();

    $stmt = $pdo->prepare("CALL sp_get_history(?, 'temperature', ?)");
    $stmt->execute([$plant_id, $hours]);
    $tempHistory = $stmt->fetchAll();

    // Unread alerts
    $stmt = $pdo->prepare(
        "SELECT * FROM alerts WHERE plant_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 10"
    );
    $stmt->execute([$plant_id]);
    $alerts = $stmt->fetchAll();

    // Latest images untuk dashboard "Gambar Terbaru"
    $stmt = $pdo->prepare(
        "SELECT id, file_path, caption, captured_at FROM images WHERE plant_id = ? ORDER BY captured_at DESC LIMIT 4"
    );
    $stmt->execute([$plant_id]);
    $images = $stmt->fetchAll();

    echo json_encode([
        "latest" => $latest,
        "soil_history" => $soilHistory,
        "temp_history" => $tempHistory,
        "alerts" => $alerts,
        "images" => $images
    ]);
} catch (Exception $e) {
    error_log("PlantMonitor Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
```

### 6.7 Pump Endpoint (pump.php)

```php
<?php
// api/pump.php — Kontrol pompa (manual dari dashboard + polling dari ESP32)
require_once 'config.php';
header('Content-Type: application/json');

$plant_id = (int)($_GET['plant_id'] ?? $_POST['plant_id'] ?? 0);
if ($plant_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "plant_id required"]);
    exit;
}

// GET: ESP32 polls untuk pending commands
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    validateApiKey();  // ESP32 akses pakai API key

    $stmt = $pdo->prepare(
        "SELECT id, command, params FROM pending_commands
         WHERE plant_id = ? AND status = 'pending'
         ORDER BY created_at ASC LIMIT 1"
    );
    $stmt->execute([$plant_id]);
    $cmd = $stmt->fetch();

    if ($cmd) {
        // Mark sebagai executing
        $pdo->prepare("UPDATE pending_commands SET status = 'executing' WHERE id = ?")
            ->execute([$cmd['id']]);

        echo json_encode([
            "status" => "ok",
            "command" => $cmd['command'],
            "params" => json_decode($cmd['params'], true),
            "command_id" => $cmd['id']
        ]);
    } else {
        echo json_encode(["status" => "no_command"]);
    }
    exit;
}

// POST: Dashboard sends manual pump command
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    initSession();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Login required"]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $duration = (int)($input['duration_sec'] ?? 30);
    $duration = max(1, min($duration, 300)); // Clamp 1-300 detik

    $stmt = $pdo->prepare(
        "INSERT INTO pending_commands (plant_id, command, params, status)
         VALUES (?, 'pump_on', ?, 'pending')"
    );
    $stmt->execute([$plant_id, json_encode(["duration_sec" => $duration])]);

    echo json_encode([
        "status" => "ok",
        "message" => "Pump command queued",
        "duration_sec" => $duration
    ]);
    exit;
}

// PUT dari ESP32: mark command selesai
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    validateApiKey();

    $input = json_decode(file_get_contents('php://input'), true);
    $cmd_id = (int)($input['command_id'] ?? 0);
    $status = ($input['status'] === 'done') ? 'done' : 'failed';

    $pdo->prepare("UPDATE pending_commands SET status = ?, executed_at = NOW() WHERE id = ?")
        ->execute([$status, $cmd_id]);

    echo json_encode(["status" => "ok"]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
```

---

## 7. Frontend Webapp

### 7.1 Halaman

| Halaman | Fungsi |
|---------|--------|
| index.php | Login form |
| dashboard.php | Stat cards + grafik + notifikasi |
| plants.php | CRUD tanaman (tambah/edit/hapus) |
| history.php | Tabel riwayat data sensor (filter tanggal) |
| images.php | Galeri gambar dari ESP32-CAM |

### 7.2 Tech Stack

| Komponen | Teknologi | Fungsi |
|----------|-----------|--------|
| Layout | Bootstrap 5 | Responsive grid, cards, navbar |
| Grafik | Chart.js | Line chart histori sensor |
| Fetch data | Vanilla JS `fetch()` | Auto-update tanpa reload |
| Auto-refresh | `setInterval` | Update tiap 10 detik |

### 7.3 Dashboard Layout

```
┌─────────────────────────────────────────────────────────┐
│ 🌱 PlantMonitor    [Dashboard] [Tanaman] [Histori] [Keluar] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────┐  │
│  │ 🌡️ Suhu  │ │ 💧 Kelemb.│ │ 🌱 Soil  │ │ ☀️ Cahaya  │  │
│  │  28.3°C  │ │  72.1%   │ │  65.5%   │ │  450 lux  │  │
│  │  [normal]│ │  [normal]│ │  [baik]  │ │ [sedang]  │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────┘  │
│                                                         │
│  ┌─────────────────────┐ ┌────────────────────────────┐ │
│  │ 📈 Grafik Soil 24h │ │ 🌡️ Grafik Suhu 24h        │ │
│  │ [Chart.js line]    │ │ [Chart.js line]             │ │
│  └─────────────────────┘ └────────────────────────────┘ │
│                                                         │
│  ┌─────────────────────┐ ┌────────────────────────────┐ │
│  │ 📸 Gambar Terbaru  │ │ ⚠️ Notifikasi              │ │
│  │ [thumb] [thumb]     │ │ • Soil rendah (12:00)      │ │
│  │ [thumb] [thumb]     │ │ • Pompa aktif (11:30)      │ │
│  └─────────────────────┘ └────────────────────────────┘ │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 📊 Riwayat Pompa                               │   │
│  │ 12:05 — Auto — 30 detik — Soil: 28% → 65%     │   │
│  │ 11:30 — Manual — 15 detik — User: admin        │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### 7.4 Auto-refresh Flow

```
User buka dashboard.php
    │
    ▼
PHP render HTML + data awal
    │
    ▼
JS mulai setInterval (10 detik)
    │
    ▼ (tiap 10 detik)
fetch('/api/dashboard.php?plant_id=1')
    │
    ▼
Update stat cards + chart tanpa reload
```

### 7.5 CSS (style.css)

```css
:root {
    --bg: #f8f9fa;
    --card-bg: #ffffff;
    --text: #212529;
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg: #1a1a2e;
        --card-bg: #16213e;
        --text: #e0e0e0;
    }
    body { background: var(--bg); color: var(--text); }
    .card { background: var(--card-bg); }
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.card:hover { transform: translateY(-2px); }

.badge-normal { background: #28a745; }
.badge-warning { background: #ffc107; color: #000; }
.badge-danger { background: #dc3545; }
```

---

## 8. ESP32 Firmware

### 8.1 ESP32 Utama (PlantSensor.ino)

**Library:** WiFi.h, HTTPClient.h, DHT.h

**Fungsi:**
- Baca 6 sensor tiap 30 detik
- Kirim JSON ke `/api/sensor.php`
- Auto-pompa: nyala 30 detik jika soil < 30%
- Kirim log pompa ke `/api/pump.php`

**JSON yang dikirim:**
```json
{
    "plant_id": 1,
    "readings": [
        {"sensor_type": "soil_moisture", "value": 65.50, "unit": "%"},
        {"sensor_type": "temperature", "value": 28.30, "unit": "°C"},
        {"sensor_type": "humidity", "value": 72.10, "unit": "%"},
        {"sensor_type": "light", "value": 450.00, "unit": "lux"},
        {"sensor_type": "rain", "value": 0.00, "unit": "bool"},
        {"sensor_type": "water_level", "value": 85.00, "unit": "%"}
    ]
}
```

### 8.2 ESP32-CAM (PlantCamera.ino)

**Board:** AI-Thinker ESP32-CAM

**Fungsi:**
- Ambil foto (VGA, JPEG) tiap 5 menit
- Upload multipart ke `/api/image.php`
- Auto-reconnect WiFi

---

## 9. Security

| Layer | Ancaman | Solusi |
|-------|---------|--------|
| ESP32 → Server | Data palsu | API key per device (header `X-API-Key`), hash_equals() comparison |
| Dashboard | Unauthorized access | Session login + bcrypt password hash |
| Dashboard | Session hijacking | Cookie flags: HttpOnly, Secure, SameSite=Lax |
| Dashboard | CSRF | SameSite cookie + CSRF token untuk form submissions |
| Database | SQL injection | PDO prepared statements + stored procedures |
| File upload | Malicious files | Validate MIME (finfo) + extension from MIME (bukan filename) + size limit |
| File upload | Path traversal | Uniqid() rename, bukan original filename |
| API | DoS / flooding | Nginx rate limiting (30 req/m per IP) |
| PHP | Error leaking | Log detail ke server, return generic error ke client |
| WiFi | Sniffing | HTTPS (TLS) via Let's Encrypt — **WAJIB di production** |
| ESP32 | Hardcoded secrets | API key di firmware = acceptable untuk development. Production: gunakan HTTPS + certificate pinning |

---

## 10. Error Handling

| Komponen | Error | Penanganan |
|----------|-------|------------|
| ESP32 | WiFi putus | Auto-reconnect, retry 5x, sleep on failure |
| ESP32 | Server timeout | HTTPClient timeout 10s, log error |
| ESP32 | DHT read fail | Skip reading, log warning, continue loop |
| ESP32 | Hang/freeze | Watchtimer (WDT) auto-restart setelah 30 detik |
| ESP32-CAM | Upload gagal | Retry 3x, skip jika gagal |
| PHP | DB error | Try-catch, **log detail ke error_log**, return generic error |
| PHP | Upload gagal | Validate sebelum move, return error |
| MySQL | Sensor not found | sp_insert_reading SIGNAL SQLSTATE '45000' |
| MySQL | Constraint violation | ON DELETE CASCADE handle relasi |
| PHP | Unauthenticated | Return 401, log IP address |

---

## 11. Folder Deployment

```
/var/www/plantmonitor/
├── api/                    # Backend PHP
│   ├── config.php
│   ├── sensor.php
│   ├── image.php
│   ├── pump.php
│   ├── dashboard.php
│   └── auth.php
├── uploads/
│   └── images/             # Gambar dari ESP32-CAM (755权限)
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── index.php               # Login
├── dashboard.php           # Dashboard utama
├── plants.php              # CRUD tanaman
├── history.php             # Riwayat data
└── images.php              # Galeri gambar ESP32-CAM
```

**Nginx config:**

> ⚠️ `limit_req_zone` HARUS di blok `http {}`, bukan di `server {}`.

```nginx
# /etc/nginx/nginx.conf — tambahkan di dalam blok http { }:
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/m;

# /etc/nginx/sites-available/plantmonitor:
server {
    listen 80;
    server_name plantmonitor.domain.com;
    return 301 https://$server_name$request_uri;  # Redirect HTTP → HTTPS
}

server {
    listen 443 ssl http2;
    server_name plantmonitor.domain.com;
    root /var/www/plantmonitor;
    index index.php;

    # SSL (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/plantmonitor.domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/plantmonitor.domain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block direct access to uploads — images harus lewat /api/image.php?id=
    location /uploads/ {
        return 403;
    }

    # Rate limiting untuk API endpoints
    location /api/ {
        limit_req zone=api burst=5 nodelay;
    }
}

# Setup SSL: sudo certbot --nginx -d plantmonitor.domain.com
```

---

## 12. Database Setup Commands

```bash
# Login MySQL
mysql -u root -p

# Buat database & user
CREATE DATABASE plantmonitor;
CREATE USER 'plantuser'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON plantmonitor.* TO 'plantuser'@'localhost';
FLUSH PRIVILEGES;

# Jalankan schema
USE plantmonitor;
SOURCE /path/to/schema.sql;
SOURCE /path/to/procedures.sql;
SOURCE /path/to/triggers.sql;
SOURCE /path/to/events.sql;

# Enable event scheduler
SET GLOBAL event_scheduler = ON;
```

### 12.1 Initial Data Seeding

```sql
-- Insert admin user (password: admin123 — GANTI di production!)
-- Generate hash sendiri: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
-- Copy hasilnya ke bawah ini (format: $2y$10$...)
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$GENERATE_YOUR_OWN_HASH_HERE', 'admin');

-- Insert tanaman pertama
INSERT INTO plants (name, species, location) VALUES
('Tanaman Cabai', 'Capsicum annuum', 'Halaman Depan');

-- Insert sensor untuk tanaman ID 1
INSERT INTO sensors (plant_id, type, pin) VALUES
(1, 'soil_moisture', 'GPIO34'),
(1, 'temperature',   'GPIO13'),
(1, 'humidity',      'GPIO13'),  -- DHT11 gabungkan suhu + kelembapan
(1, 'light',         'GPIO35'),
(1, 'rain',          'GPIO27'),
(1, 'water_level',   'GPIO26');

-- Insert gambar sample (asumsi ESP32-CAM sudah upload gambar ke /uploads/)
INSERT INTO images (plant_id, file_path, caption) VALUES
(1, 'uploads/2026-09-01_10-00-00.jpg', 'Daun cabai pagi hari'),
(1, 'uploads/2026-09-01_10-05-00.jpg', 'Daun cabai setelah penyiraman');

-- Insert hasil analisis kesehatan daun (contoh output dari ML model)
INSERT INTO plant_health_analysis (plant_id, image_id, health_status, confidence) VALUES
(1, 1, 'sehat', 0.9200),
(1, 2, 'terinfeksi_jamur', 0.8700);
```

### 12.2 API Key Provisioning

API keys disimpan sebagai environment variable di VPS:

```bash
# Di /etc/environment atau .env di /var/www/plantmonitor/
API_KEY_SENSOR=key_sensor_abc123_development
API_KEY_CAMERA=key_camera_xyz789_development
```

**Di ESP32 firmware (hardcoded untuk development):**
```cpp
const char* API_KEY = "key_sensor_abc123_development";
// Kirim sebagai header: http.addHeader("X-API-Key", API_KEY);
```

> ⚠️ Untuk production, generate key baru: `openssl rand -hex 32`

### 12.3 Manual Pump Control Path

Dashboard → PHP → ESP32 (pull model):

```
1. User klik "Nyalakan Pompa" di dashboard
2. PHP insert ke table: pending_commands (plant_id, command, status='pending')
3. ESP32 polling GET /api/pump.php?plant_id=1 setiap 10 detik
4. ESP32 baca response, eksekusi command, UPDATE status='done'
5. Dashboard auto-refresh tampilkan status
```

> Schema `pending_commands` ada di section 4.9. PHP endpoint ada di section 6.7.

### 12.4 Alerts Delivery

Saat ini alerts ditampilkan di dashboard (polling). Untuk notifikasi real-time:

| Metode | Implementasi |
|--------|-------------|
| **Dashboard (aktif)** | Auto-refresh tiap 10 detik, tampilkan badge notifikasi |
| **Email (opsional)** | PHP `mail()` atau PHPMailer → kirim saat trigger create alert |
| **Telegram (opsional)** | PHP curl ke Bot API → kirim pesan ke grup/个人 |

**Minimal: Dashboard sudah cukup** untuk demo workshop basis data. Notifikasi email/Telegram = future improvement.

---

## 13. Sensor Calibration Notes

| Sensor | Catatan Kalibrasi |
|--------|-------------------|
| Soil Moisture | ADC bervariasi per modul. Kalibrasi: rendam 100% = baseline basah, angin 100% = baseline kering. Map manual: `map(raw, dry_val, wet_val, 0, 100)` |
| DHT11 | Akurasi ±2°C / ±5%RH. Cukup untuk demo. Untuk presisi: upgrade DHT22 |
| LDR | Non-linear. Lux ≈ `pow(10, (1023 - raw) / 100)`. Atau pakai tabel lookup sederhana |
| Rain Sensor | Digital output — threshold bisa diset di potensiometer di modul |
| Water Level | Ultrasonic atau resistive. Kalibrasi: 0% = kosong, 100% = penuh. Map manual berdasarkan jarak terukur |

---

## 14. Checklist Implementasi

| # | Task | Status |
|---|------|--------|
| 1 | Setup VPS (Nginx + PHP + MySQL) | ⬜ |
| 2 | Buat database schema + procedures + triggers + seed data | ⬜ |
| 3 | Buat PHP backend (config, sensor, image, dashboard, auth) | ⬜ |
| 4 | Buat frontend (dashboard, plants, history, images, login) | ⬜ |
| 5 | Setup .env file (DB credentials + API keys) | ⬜ |
| 6 | Upload ESP32 firmware (PlantSensor.ino) | ⬜ |
| 7 | Upload ESP32-CAM firmware (PlantCamera.ino) | ⬜ |
| 8 | Test end-to-end: ESP32 → PHP → MySQL → Dashboard | ⬜ |
| 9 | Setup HTTPS (Let's Encrypt: `sudo certbot --nginx`) | ⬜ |
| 10 | Demo presentasi ke dosen | ⬜ |

---

## 15. Future Improvements

| Fitur | Keterangan |
|-------|------------|
| Multi-plant | Schema sudah scalable, tinggal tambah data |
| Mobile app | Pakai API yang sama |
| Machine Learning | Analisis gambar daun (sehat/sakit) |
| Telegram notification | Kirim alert ke Telegram |
| Historical export | Export data ke CSV/Excel |
