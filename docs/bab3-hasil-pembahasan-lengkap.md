# HASIL DAN PEMBAHASAN

## Rancangan Tabel dan Database

Perancangan basis data SISTOMATE menghasilkan lima tabel yang terdiri dari dua tabel master (Users, Devices) dan tiga tabel pencatatan (Gallery, Sensor Logs, Actuator Logs). Pendekatan yang digunakan adalah skema terpusat pada perangkat, di mana Tabel Devices menjadi jembatan penghubung antara data pengguna (dari Tabel Users) dan seluruh data operasional greenhouse: data visual tanaman (melalui Tabel Gallery), data kondisi lingkungan (melalui Tabel Sensor Logs), serta data tindakan aktuator (melalui Tabel Actuator Logs).

## Struktur Tabel Basis Data

### Tabel Users

Tabel ini berfungsi sebagai data master untuk menyimpan informasi pengguna yang terdaftar di dalam sistem dan berhak mengakses dashboard monitoring.

| Nama Kolom / Field | Tipe Data | Panjang | Keterangan dan Relasi | Contoh Isi Data |
|---|---|---|---|---|
| id_user | INT | 11 | Primary Key (Kunci Utama), AUTO_INCREMENT | 1 |
| name | VARCHAR | 100 | Nama lengkap pengguna | Budi Santoso |
| email | VARCHAR | 100 | UNIQUE, NOT NULL, untuk login / notifikasi | budi@polije.ac.id |
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

## Perancangan Sistem dan Alur Kerja

Selain rancangan basis data, SISTOMATE didokumentasikan melalui tiga diagram: arsitektur alur data, skematik alat (wiring), dan flowchart logika firmware. Ketiganya disusun mengikuti kondisi nyata firmware SISTOMATE.ino dan rencana prototipe fisik, sehingga dapat dipakai sebagai acuan implementasi sekaligus panduan perakitan greenhouse.

### Arsitektur dan Alur Data

Diagram berikut menggambarkan alur data SISTOMATE dalam empat lapis: perangkat ESP32 di sisi edge, backend ingest berbasis PHP yang memvalidasi kunci API (X-API-Key), basis data MySQL, dan dashboard web. ESP32 mengirim pembacaan sensor tiap 2 detik dan log aktuator setiap transisi relay melalui HTTP POST; backend menyisipkannya ke tabel sensor_logs dan actuator_logs, yang kemudian dibaca kembali oleh live.php untuk ditampilkan dashboard secara berkala.

![Arsitektur dan alur data SISTOMATE (empat lapis).](gambar/arsitektur-sistem.png){width=100%}

### Skematik Alat

Skematik berikut merincikan koneksi antara ESP32, tiga sensor (soil moisture, DHT11, dan TCS34725), dua modul relay 2-channel aktif-LOW, beban berupa dua pompa dan kipas exhaust, serta adaptor 5V eksternal. Relay A mengendalikan pompa air (GPIO26) dan pompa pupuk (GPIO14), sedangkan Relay B mengendalikan kipas exhaust (GPIO27) dengan satu kanal cadangan (GPIO25). Titik yang ditekankan adalah common ground: GND ESP32, GND adaptor 5V, GND modul relay, dan GND beban harus terhubung pada satu rail yang sama, sementara beban bertenaga dialiri adaptor eksternal (bukan dari pin 5V ESP32) untuk menghindari hang akibat kelebihan arus.

![Skematik alat SISTOMATE.](gambar/skematik-alat.png){width=100%}

### Flowchart Logika Firmware

Flowchart berikut menggambarkan logika firmware: histeresis penyiraman (pompa menyala di bawah 40% dan mati di atas 55% kelembapan tanah), histeresis ventilasi (kipas menyala di atas 32 derajat Celsius dan mati di bawah 31 derajat Celsius), interlock fertigasi (dosis pupuk 7 detik, hanya dijalankan jika kelembapan tanah minimal 40%), serta klasifikasi status tanaman menjadi kering, layu, sakit, atau sehat. Jadwal fertigasi digambarkan sesuai acuan yaitu seminggu sekali pada Senin pukul 07:00 waktu NTP.

![Flowchart logika firmware SISTOMATE.](gambar/flowchart-logika.png){width=100%}

## Perancangan Fisik Greenhouse Mini

SISTOMATE tidak hanya dirancang pada lapisan basis data dan firmware, tetapi juga berwujud fisik berupa greenhouse mini yang menampung media tanam dan seluruh perangkat sensor-aktuator. Perancangan fisik ini dimodelkan dalam FreeCAD dengan konvensi sumbu X = panjang 1000 mm, Y = lebar 800 mm, dan Z = tinggi 600 mm (dinding belakang berada pada bidang X = 1000), sehingga seluruh dimensi konsisten dengan dokumen rencana prototipe dan panduan perakitan.

Bentuk greenhouse menggunakan konsep tunnel busur segmental, yaitu setengah lingkaran yang dipotong sehingga sisi kanan-kiri lebih landai. Rangkanya bersifat hibrida: bagian pondasi berupa rangka dasar kayu berbentuk kotak berukuran 100 × 80 cm yang dilengkapi dinding ujung setinggi 60 cm, sedangkan bagian atap berupa empat batang pipa PVC/PP 1/2" yang ditekuk menjadi busur dengan panjang lengkung sekitar 150 cm per batang dan bentangan selebar 80 cm. Rangka tersebut kemudian ditutup selimut plastik bening fleksibel (PE/terpal) yang dapat ditekuk mengikuti busur. Pemilihan busur segmental bertujuan agar embun yang terbentuk di permukaan dalam selimut mengalir menyusuri lengkung menuju sisi, bukan menetes langsung ke daun tanaman.

![Render isometrik model 3D greenhouse mini SISTOMATE (tunnel busur segmental, 100 × 80 × 60 cm).](docs/cad/render-isometric.png){width=100%}

Di dalam greenhouse, pot tanaman diletakkan di tengah alas yang disangga oleh lima slat kayu. Media tanah dimodelkan sebagai frustum (kerucut terbalik) berpusat pada sumbu pot dengan jari-jari 88 mm di dasar dan 98 mm di permukaan setinggi 45 mm, sehingga tanah berada sepenuhnya di dalam dinding pot. Penempatan sensor mengikuti ketentuan pengujian lapangan: probe kelembapan tanah dicolokkan miring 28° dengan kedalaman sekitar 3,4 cm dari permukaan media, sensor suhu dan kelembapan udara DHT11 dipasang di pojok kiri-depan-atas (diagonal terhadap kipas exhaust agar tidak terkena hembusan langsung), dan sensor warna TCS34725 dipasang pada bracket di rangka atas berjarak sekitar 4,2 cm di atas titik paling lebar daun. Kipas exhaust 80 mm dipasang pada dinding belakang atas, sementara selang irigasi masuk melalui lubang kecil di dinding belakang bagian bawah menuju pot. Seluruh elektronik (ESP32, modul relay, dan adaptor 5V) diletakkan di luar greenhouse agar terhindar dari area lembap.

![Render tampak teknis dengan dimensi utama greenhouse mini: panjang 1000 mm, lebar 800 mm, dan tinggi 600 mm.](docs/cad/render-dimensi.png){width=100%}

Model akhir terdiri atas 21 objek yang seluruhnya berada di dalam batas bounding box 1000 × 800 × 600 mm tanpa ada satu pun yang menembus batas. Berkas model tersedia dalam format FreeCAD (`greenhouse.FCStd`), STEP, dan STL pada direktori `docs/cad/` sebagai bahan rujukan perakitan, sedangkan panduan langkah perakitan fisiknya terdapat pada dokumen `docs/assembly-guide.md`.
