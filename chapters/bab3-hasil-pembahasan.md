# HASIL DAN PEMBAHASAN

## Rancangan Tabel dan Database

Perancangan basis data SISTOMATE menghasilkan lima tabel yang terdiri dari dua tabel master (Users, Devices) dan tiga tabel pencatatan (Gallery, Sensor Logs, Actuator Logs). Pendekatan yang digunakan adalah skema terpusat pada perangkat, di mana Tabel Devices menjadi jembatan penghubung antara data pengguna (dari Tabel Users) dan seluruh data operasional greenhouse: data visual tanaman (melalui Tabel Gallery), data kondisi lingkungan (melalui Tabel Sensor Logs), serta data tindakan aktuator (melalui Tabel Actuator Logs).

![Diagram Relasi Antar Tabel (ERD) SISTOMATE](docs/erdplus.png)

```{=typst}
#pagebreak()
```

## Struktur Tabel Basis Data

### Tabel Users

Tabel ini berfungsi sebagai data master untuk menyimpan informasi pengguna yang terdaftar di dalam sistem dan berhak mengakses dashboard monitoring.

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_user | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| name | VARCHAR | 100 | Nama lengkap pengguna | Budi Santoso |
| email | VARCHAR | 100 | UNIQUE, NOT NULL, untuk login / notifikasi | `budi@polije.ac.id` |
| password_hash | VARCHAR | 255 | NOT NULL, kata sandi terenkripsi | $2y$10$... |

### Tabel Devices

Tabel ini berfungsi sebagai data master untuk menyimpan informasi perangkat ESP32 yang terpasang di greenhouse, mencakup nama perangkat, lokasi penempatan, dan kepemilikan pengguna. Setiap perangkat dimiliki oleh satu pengguna.

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_device | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| device_name | VARCHAR | 100 | Nama identifikasi perangkat | ESP32-Greenhouse-1 |
| location | VARCHAR | 150 | Lokasi penempatan perangkat | Bed-1-Baris-2 |
| id_user | INT | 11 | Foreign Key (dari tabel Users), ON DELETE RESTRICT | 1 |

### Tabel Gallery

Tabel ini menyimpan metadata setiap citra tanaman tomat yang ditangkap oleh webcam, mencakup lokasi berkas gambar, keterangan, dan waktu pengunggahan. Setiap citra terhubung ke satu perangkat dan satu pengguna sebagai pengunggah, di mana pengunggah citra dapat berbeda dari pemilik perangkat (misalnya anggota tim lain yang mendokumentasikan tanaman).

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_gallery | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| image_url | VARCHAR | 255 | Lokasi penyimpanan berkas gambar | /img/tomat-a01-001.jpg |
| caption | VARCHAR | 255 | Keterangan citra, boleh NULL | Daun tomat Bed-1 pagi hari |
| upload_date | DATETIME | - | Waktu pengunggahan citra, DEFAULT CURRENT_TIMESTAMP | 2026-09-18 08:05:00 |
| id_user | INT | 11 | Foreign Key (dari tabel Users), boleh NULL, ON DELETE SET NULL | 1 |
| id_device | INT | 11 | Foreign Key (dari tabel Devices), ON DELETE CASCADE | 1 |

### Tabel Sensor Logs

Tabel ini mencatat setiap data pembacaan sensor yang dikirim oleh perangkat ESP32, mencakup suhu dan kelembapan udara, kelembapan tanah, nilai warna RGB daun, status tanaman hasil klasifikasi, dan waktu pencatatan. Setiap baris log terhubung ke satu perangkat.

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_log | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| recorded_at | DATETIME | - | Waktu pencatatan, diisi dari timestamp ESP32 | 2026-09-18 08:00:00 |
| air_temperature | FLOAT | - | Suhu udara dalam derajat Celsius (°C) | 28,50 |
| air_humidity | FLOAT | - | Kelembapan udara dalam persen (%) | 70,00 |
| soil_moisture | FLOAT | - | Kelembapan tanah dalam persen (%) | 45,50 |
| rgb_red | INT | 11 | Komponen merah warna daun (0–255) | 120 |
| rgb_green | INT | 11 | Komponen hijau warna daun (0–255) | 180 |
| rgb_blue | INT | 11 | Komponen biru warna daun (0–255) | 90 |
| plant_status | ENUM | - | Hasil klasifikasi otomatis: 'sehat', 'kering', 'layu', 'sakit' | layu |
| id_device | INT | 11 | Foreign Key (dari tabel Devices), ON DELETE CASCADE | 1 |

Indeks tambahan pada tabel ini: INDEX (id_device, recorded_at) untuk mempercepat query deret waktu (time-series) per perangkat.

Nilai plant_status ditentukan oleh aturan klasifikasi di firmware ESP32 berdasarkan pembacaan soil_moisture dan sensor warna RGB daun: 'sehat' jika kelembapan tanah cukup (≥ 40%) dan komponen hijau daun dominan (rgb_green > rgb_red); 'kering' jika media tanam mulai kering (25–40%) tetapi daun masih hijau; 'layu' jika kelembapan tanah kritis (< 25%); 'sakit' jika komponen hijau daun tidak lagi dominan (rgb_green < rgb_red) yang mengindikasikan klorosis atau nekrosis, terlepas dari nilai kelembapan tanah. Nilai ambang di atas merupakan nilai awal berdasarkan acuan umum agronomi dan akan dikalibrasi ulang dengan pengukuran lapangan saat tahap implementasi.

### Tabel Actuator Logs

Tabel ini mencatat setiap aksi aktuator (misalnya pompa penyiraman) yang dijalankan oleh perangkat, mencakup nama aktuator, status aksi, dan waktu pelaksanaan. Setiap baris log terhubung ke satu perangkat.

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_action | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| actuator_name | VARCHAR | 100 | Nama aktuator yang dijalankan, misal "pompa air" | pompa air |
| status | ENUM | - | Status aksi: 'ON' / 'OFF' | ON |
| executed_at | DATETIME | - | Waktu pelaksanaan aksi | 2026-09-18 08:10:00 |
| id_device | INT | 11 | Foreign Key (dari tabel Devices), ON DELETE CASCADE | 1 |

## Pemetaan Relasi Antar Tabel

### Pemetaan Tabel Users dan Tabel Devices (Relasi: One-to-Many)

Alur: Satu pengguna dapat memiliki banyak perangkat ESP32 yang terpasang di greenhouse, sedangkan setiap perangkat hanya dimiliki oleh satu pengguna.

Implementasi: Karena relasinya satu-ke-banyak (One-to-Many), maka id_user (Primary Key di Tabel Users) ditarik menjadi Foreign Key ke tabel Devices.

Koneksi Kunci:

- id_user (Primary Key di Tabel Users) ditarik menjadi Foreign Key ke tabel Devices dengan ON DELETE RESTRICT, karena id_user di sini menyatakan kepemilikan: perangkat tidak boleh menjadi "tidak bertuan", sehingga pengguna pemilik perangkat tidak dapat dihapus selama perangkatnya masih ada.
- id_user (Primary Key di Tabel Users) juga ditarik menjadi Foreign Key ke tabel Gallery dengan ON DELETE SET NULL (kolom boleh NULL), karena id_user di sini hanya merupakan jejak audit siapa yang mengunggah: citra dokumentasi tetap berharga sebagai riwayat tanaman walaupun akun pengunggahnya sudah dihapus.

### Pemetaan Tabel Devices dengan Tabel Gallery, Sensor Logs, dan Actuator Logs

Alur: Satu perangkat menghasilkan banyak citra (Gallery), banyak baris pembacaan sensor (Sensor Logs), dan banyak aksi aktuator (Actuator Logs) sepanjang waktu operasionalnya, sedangkan setiap baris pada ketiga tabel tersebut hanya milik satu perangkat.

Implementasi: Karena seluruh relasinya satu-ke-banyak (One-to-Many), maka id_device (Primary Key di Tabel Devices) ditarik menjadi Foreign Key ke ketiga tabel pencatatan tersebut.

Koneksi Kunci:

- id_device (Primary Key di Tabel Devices) ditarik menjadi Foreign Key ke tabel Gallery dengan ON DELETE CASCADE untuk melacak perangkat sumber citra: jika perangkat dihapus, dokumentasi citranya ikut terhapus karena sudah tidak relevan.
- id_device (Primary Key di Tabel Devices) ditarik menjadi Foreign Key ke tabel Sensor Logs dengan ON DELETE CASCADE untuk melacak perangkat sumber data sensor.
- id_device (Primary Key di Tabel Devices) ditarik menjadi Foreign Key ke tabel Actuator Logs dengan ON DELETE CASCADE untuk melacak perangkat pelaksana aksi.

Kesimpulan Alur Skema Relasi: Sistem ini menggunakan skema terpusat pada perangkat. Tabel Devices menjadi pusat yang dihubungkan ke entitas pengguna melalui id_user (untuk urusan kepemilikan perangkat dan pengunggah citra), serta dihubungkan ke seluruh data operasional melalui id_device: ke Tabel Gallery (untuk urusan dokumentasi visual tanaman), ke Tabel Sensor Logs (untuk urusan pemantauan suhu, kelembapan, kelembapan tanah, warna RGB daun, dan status tanaman), dan ke Tabel Actuator Logs (untuk urusan pencatatan aksi penyiraman dan aktuator lainnya).
