# HASIL DAN PEMBAHASAN

## Metode Rancang Bangun

SISTOMATE dibangun dengan metode research and development (R&D) tahap pendek yang disesuaikan dengan skala prototipe kelas: pemodelan, implementasi, dan pengujian [@pressman2014software]. Tahap pemodelan mencakup perancangan arsitektur sistem, skema basis data relasional, logika kendali firmware, serta model tiga dimensi greenhouse dalam FreeCAD. Tahap implementasi dieksekusi bertahap sesuai ketergantungan antar komponen: skema basis data lebih dahulu dibuat karena menjadi kontrak data bagi backend dan firmware, kemudian endpoint ingest, firmware ESP32, dan terakhir perakitan fisik. Tahap pengujian dilakukan pada tiap lapisan (basis data, backend, firmware, dashboard) dan diakhiri dengan pengujian fungsional menyeluruh pada sistem utuh.

## Arsitektur Sistem Empat Lapis

SISTOMATE dirancang dengan arsitektur empat lapis yang memisahkan tanggung jawab secara tegas, sejalan dengan pola umum sistem IoT [@alfuqaha2015iot]. Gambar 3.1 menampilkan keseluruhan alur data dari sensor hingga dashboard.

![Arsitektur sistem SISTOMATE empat lapis: ESP32 Edge, backend ingest.php, basis data MySQL, dan dashboard web.](gambar/arsitektur-sistem.png){width=100%}

Lapisan pertama adalah ESP32 Edge yang menjalankan firmware SISTOMATE.ino. Lapisan ini bertanggung jawab atas akuisisi data dari tiga sensor (soil moisture pada GPIO34, DHT11 pada GPIO13, dan TCS34725 pada jalur I2C GPIO21/GPIO22), evaluasi logika histeresis dan penjadwalan fertigasi, serta pengiriman data ke server melalui HTTP POST berpayload JSON yang dilengkapi header X-API-Key. Lapisan kedua adalah backend ingest (ingest.php) yang berjalan pada PHP 8 di belakang web server nginx. Lapisan ini memvalidasi kredensial perangkat, memeriksa tipe payload (sensor atau aktuator), lalu mengeksekusi perintah INSERT ke basis data melalui prepared statement. Lapisan ketiga adalah basis data MySQL bernama sistomate yang menyimpan seluruh riwayat operasional dalam lima tabel, dikelola melalui antarmuka grafis DBeaver. Lapisan keempat adalah dashboard web yang memanggil endpoint data (live.php) dan menampilkan grafik tren real-time berbasis SVG dengan interval poll lima detik.

Pemisahan lapisan ini memberi dua keuntungan: perangkat keras dapat diganti tanpa menyentuh lapisan data (misalnya mengganti ESP32 dengan mikrokontroler lain selama kontrak JSON dipertahankan), dan dashboard dapat berkembang (mobile, notifikasi, analitik) tanpa mengubah cara data masuk. Pada lapisan jaringan, sistem memanfaatkan LAN kampus sehingga latensi pengiriman berada pada skala milidetik dan tidak bergantung pada layanan cloud eksternal.

## Perancangan Basis Data Relasional

### Gambaran Umum dan Diagram Relasi

Basis data SISTOMATE dirancang dengan pendekatan berpusat pada perangkat (device-centric): tabel devices menjadi jembatan antara pemilik (tabel users) dan seluruh data operasional greenhouse. Diagram relasi antar tabel ditampilkan pada Gambar 3.2.

![Diagram relasi (entity relationship diagram) lima tabel basis data SISTOMATE.](gambar/erd-sistomate.png){width=100%}

Struktur ini menghasilkan dua tabel master (users dan devices) serta tiga tabel pencatatan (gallery, sensor_logs, dan actuator_logs). Seluruh hubungan antar tabel bersifat satu-ke-banyak: satu pengguna dapat memiliki banyak perangkat, sedangkan satu perangkat menghasilkan banyak baris citra, log sensor, dan log aktuator sepanjang masa kerjanya. Tidak diperlukan tabel perantara relasi banyak-ke-banyak karena setiap record pencatatan selalu merujuk tepat satu perangkat. Berikut kamus data tiap tabel.

### Kamus Data Tabel Users

Tabel users menyimpan identitas akun yang berhak mengakses dashboard. Kata sandi tidak pernah disimpan dalam bentuk teks biasa, melainkan sebagai hash sehingga kebocoran basis data tidak langsung membocorkan kredensial login.

Table: Kamus data Tabel users.

| Kolom | Tipe Data | Nilai / Kunci | Keterangan |
|---|---|---|---|
| id_user | INT(11) | PRIMARY KEY | Kunci unik pengguna, nilai terisi otomatis |
| name | VARCHAR(100) | NOT NULL | Nama lengkap pengguna |
| email | VARCHAR(100) | NOT NULL, UNIQUE | Email untuk login dan notifikasi |
| password_hash | VARCHAR(255) | NOT NULL | Hash kata sandi, bukan teks biasa |

### Kamus Data Tabel Devices

Tabel devices menyimpan identitas perangkat greenhouse beserta lokasinya dan menjadi induk dari ketiga tabel pencatatan.

Table: Kamus data Tabel devices.

| Kolom | Tipe Data | Nilai / Kunci | Keterangan |
|---|---|---|---|
| id_device | INT(11) | PRIMARY KEY | Kunci unik perangkat, nilai terisi otomatis |
| device_name | VARCHAR(100) | NOT NULL | Nama perangkat, misalnya ESP32-Greenhouse-1 |
| location | VARCHAR(150) | NOT NULL | Lokasi fisik greenhouse |
| id_user | INT(11) | FOREIGN KEY ke users | Pemilik perangkat; akun pemilik tidak boleh dihapus selama perangkat masih ada (RESTRICT) |

### Kamus Data Tabel Gallery

Tabel gallery mendokumentasikan citra visual tanaman dari webcam, sehingga selain angka sensor dan status kesehatan, pengguna juga dapat menelusuri bukti visual kondisi tanaman dari waktu ke waktu.

Table: Kamus data Tabel gallery.

| Kolom | Tipe Data | Nilai / Kunci | Keterangan |
|---|---|---|---|
| id_gallery | INT(11) | PRIMARY KEY | Kunci unik citra, nilai terisi otomatis |
| image_url | VARCHAR(255) | NOT NULL | Lokasi berkas citra hasil unggah |
| caption | VARCHAR(255) | NULL | Keterangan opsional pada citra |
| upload_date | DATETIME | DEFAULT waktu server | Waktu unggah diisi otomatis oleh server |
| id_user | INT(11) | FOREIGN KEY ke users | Pengunggah; dijadikan NULL saat akun dihapus (SET NULL) |
| id_device | INT(11) | FOREIGN KEY ke devices | Sumber citra; ikut terhapus saat perangkat dihapus (CASCADE) |

### Kamus Data Tabel Sensor Logs

Tabel sensor_logs adalah tabel timeseries inti sistem, menyimpan hasil pembacaan tiga sensor beserta status kesehatan tanaman hasil penalaran firmware pada setiap siklus pengambilan data.

Table: Kamus data Tabel sensor_logs.

| Kolom | Tipe Data | Nilai / Kunci | Keterangan |
|---|---|---|---|
| id_log | INT(11) | PRIMARY KEY | Kunci unik baris log, nilai terisi otomatis |
| recorded_at | DATETIME | NOT NULL | Waktu pencatatan menurut waktu server |
| air_temperature | FLOAT | NOT NULL | Suhu udara dalam derajat Celsius |
| air_humidity | FLOAT | NOT NULL | Kelembapan udara dalam persen |
| soil_moisture | FLOAT | NOT NULL | Kelembapan tanah dalam persen |
| rgb_red | INT | NOT NULL | Komponen merah warna daun |
| rgb_green | INT | NOT NULL | Komponen hijau warna daun |
| rgb_blue | INT | NOT NULL | Komponen biru warna daun |
| plant_status | ENUM (4 nilai) | NOT NULL | Nilai yang diizinkan: sehat, kering, layu, sakit |
| id_device | INT(11) | FOREIGN KEY ke devices | Sumber pembacaan; ikut terhapus saat perangkat dihapus (CASCADE) |

### Kamus Data Tabel Actuator Logs

Tabel actuator_logs mencatat setiap perubahan status aktuator (transisi relay), sehingga riwayat penyiraman, pemupukan, dan ventilasi dapat direkonstruksi persis seperti yang dilakukan sistem.

Table: Kamus data Tabel actuator_logs.

| Kolom | Tipe Data | Nilai / Kunci | Keterangan |
|---|---|---|---|
| id_action | INT(11) | PRIMARY KEY | Kunci unik aksi, nilai terisi otomatis |
| actuator_name | VARCHAR(100) | NOT NULL | Nama aktuator, misalnya pompa_air |
| status | ENUM (2 nilai) | NOT NULL | Nilai yang diizinkan: ON, OFF |
| executed_at | DATETIME | NOT NULL | Waktu eksekusi menurut waktu server |
| id_device | INT(11) | FOREIGN KEY ke devices | Pelaku aksi; ikut terhapus saat perangkat dihapus (CASCADE) |

### Pemetaan Relasi dan Integritas Referensial

Pemetaan relasi antar tabel dirancang untuk menjaga konsistensi data pada setiap skenario operasional. Relasi users ke devices bersifat satu-ke-banyak dengan ON DELETE RESTRICT, artinya sebuah akun yang masih memiliki perangkat terdaftar tidak dapat dihapus begitu saja; kebijakan ini mencegah hilangnya seluruh riwayat operasional akibat penghapusan akun tanpa perencanaan. Relasi devices ke sensor_logs, actuator_logs, dan gallery menggunakan ON DELETE CASCADE sehingga penghapusan perangkat secara konsisten membersihkan seluruh data operasional yang tidak lagi relevan. Relasi devices ke gallery juga memakai CASCADE, sedangkan relasi users ke gallery memakai ON DELETE SET NULL karena kolom id_user bersifat opsional: citra tetap sah meskipun akun pengunggahnya telah dihapus.

Normalisasi skema telah mencapai bentuk normal ketiga [@date2019database]: setiap atribut tabel pencatatan bergantung penuh pada kunci primernya masing-masing (id_log, id_action, id_gallery), dan atribut yang mewakili data pengguna atau perangkat tidak diduplikasi melainkan dirujuk melalui kunci tamu. Sebagai penunjang performa query riwayat, tabel sensor_logs diberi indeks komposit (id_device, recorded_at) karena pola akses yang paling sering adalah pengambilan pembacaan terbaru atau rentang waktu tertentu untuk satu perangkat tertentu [@schwartz2018mysql]. Seluruh tabel menggunakan mesin penyimpanan InnoDB yang mendukung transaksi, kunci asing, dan penguncian bawaan.

## Perancangan Firmware dan Logika Kendali

### Skematik Koneksi Perangkat Keras

Firmware SISTOMATE.ino berjalan pada ESP32 yang terhubung ke tiga sensor dan dua modul relay dua kanal (empat kanal aktif total). Skematik koneksi lengkap beserta prinsip catu daya dan common ground ditampilkan pada Gambar 3.3.

![Skematik koneksi perangkat keras SISTOMATE: ESP32, tiga sensor, dua modul relay aktif-LOW, beban pompa dan kipas, adaptor 5 V eksternal, dan common ground.](gambar/skematik-alat.png){width=100%}

Peta pin yang digunakan dirangkum sebagai berikut: sensor kelembapan tanah analog terhubung ke GPIO34 (ADC input-only), data DHT11 ke GPIO13, jalur SDA dan SCL TCS34725 ke GPIO21 dan GPIO22, sedangkan kontrol relay ke GPIO26 (pompa air), GPIO14 (pompa pupuk), dan GPIO27 (kipas exhaust), dengan GPIO25 disediakan sebagai kanal cadangan. Tiga prinsip perancangan listrik diterapkan sesuai Gambar 3.3: pertama, seluruh massa disatukan (common ground) antara ESP32, modul relay, adaptor beban, dan beban; kedua, pompa dan kipas dicatu langsung dari adaptor 5 V eksternal berarus minimal 2 A, bukan dari pin 5V ESP32; ketiga, modul relay aktif-LOW diperlakukan dengan pengaman status awal pada firmware agar tidak menyala saat boot.

### Flowchart Logika Firmware

Logika firmware dirancang paralel dalam satu siklus kerja dua detik: satu pembacaan sensor memberi masukan bagi empat proses keputusan yang berjalan berdampingan (histeresis pompa air, histeresis kipas, penjadwalan fertigasi, dan penalaran status tanaman), sebelum seluruh hasilnya dikirimkan ke server. Alur lengkap ditampilkan pada Gambar 3.4.

![Flowchart paralel logika firmware SISTOMATE: empat cabang keputusan dalam satu siklus pembacaan sensor dua detik.](gambar/flowchart-logika.png){width=100%}

Setelah perangkat dinyalakan, firmware mengamankan seluruh relay ke kondisi mati, menghubungkan Wi-Fi, dan menyinkronkan waktu melalui NTP dengan zona waktu GMT+7 sebelum masuk ke siklus utama. Pada setiap siklus, ketiga sensor dibaca: kelembapan tanah dikalibrasi dari nilai ADC mentah ke persen menggunakan batas empiris udara 4095 dan air penuh 1500, suhu dan kelembapan udara dibaca dari DHT11, dan komponen RGB daun dibaca dari TCS34725.

Cabang keputusan pertama adalah histeresis pompa air: kelembapan tanah di bawah 40 persen menyalakan pompa, naik ke 55 persen ke atas mematikannya, dan nilai di antara kedua ambang menahan status. Cabang kedua adalah histeresis kipas exhaust: suhu di atas 32 derajat Celsius menyalakan kipas, turun ke 31 derajat Celsius ke bawah mematikannya. Cabang ketiga adalah fertigasi: ketika waktu menunjuk Senin pukul 07.00 dan kelembapan tanah minimal 40 persen, pompa pupuk dinyalakan selama tujuh detik; interlock dibatalkan otomatis jika kelembapan tanah jatuh di bawah 40 persen, dan kunci persisten pada NVS mencegah pemberian dosis ganda pada hari yang sama. Cabang keempat adalah penalaran status tanaman: kelembapan tanah di bawah 40 persen menyimpulkan status kering, kipas yang sedang menyala menandakan kondisi panas sehingga status layu, rasio kanal hijau terhadap merah di bawah 0,75 dengan komponen merah di atas 100 menyimpulkan status sakit, dan kondisi lainnya disimpulkan sehat. Keempat cabang bersifat non-menggantikan: satu pembacaan sensor dapat menghasilkan keputusan aktuator sekaligus status tanaman pada saat bersamaan.

Setiap transisi relay dikirimkan ke server sebagai log aktuator, sedangkan hasil pembacaan sensor dan status tanaman dikirimkan dua kali per detik sebagai log sensor. Jika Wi-Fi tidak tersambung, firmware menunda pengiriman dan mencoba kembali pada siklus berikutnya tanpa menghentikan logika kendali lokal, sehingga pengendalian greenhouse tetap berjalan meskipun server tidak terjangkau.

### Backend Ingest dan Dashboard Monitoring

Perangkat berkomunikasi dengan basis data melalui satu titik masuk (single ingestion endpoint) bernama ingest.php. Alurnya sebagai berikut. Pertama, endpoint hanya menerima metode POST; permintaan lain ditolak dengan kode 405. Kedua, kredensial basis data dibaca dari berkas lingkungan di luar root web sehingga tidak ikut ter-deploy bersama kode. Ketiga, identitas perangkat divalidasi melalui perbandingan konstan waktu terhadap header X-API-Key menggunakan fungsi hash_equals untuk mencegah serangan perbandingan waktu. Keempat, payload JSON diperiksa tipe dan nilai enum-nya sebelum dimasukkan. Kelima, data ditulis menggunakan prepared statement PDO sehingga nilai dari perangkat tidak pernah menjadi bagian string kueri, melainkan selalu diikat sebagai parameter.

Inti penulisan data log sensor pada endpoint tersebut adalah sebagai berikut.

```php
$stmt = $pdo->prepare(
    'INSERT INTO sensor_logs
     (recorded_at, air_temperature, air_humidity, soil_moisture,
      rgb_red, rgb_green, rgb_blue, plant_status, id_device)
     VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$t, $rh, $soil, $r, $g, $b, $status, $deviceId]);
```

Penulisan kolom waktu menggunakan fungsi NOW() dari server basis data, bukan waktu perangkat, sehingga riwayat tetap koheren meskipun jam ESP32 belum tersinkron atau perangkat mengalami kegagalan NTP. Untuk log aktuator, pola yang sama diterapkan dengan kolom actuator_name, status, executed_at, dan id_device. Pada sisi penayangan, endpoint live.php menyajikan pembacaan terbaru beserta 60 riwayat terakhir dalam format JSON, yang kemudian dirender oleh halaman dashboard sebagaimana dijelaskan pada subbab berikutnya. Pemilihan nginx sebagai web server dan PHP-FPM memungkinkan dashboard dan endpoint ingest berjalan ringan pada perangkat kelas menengah sekaligus tetap aman di balik jaringan lokal kampus.

## Perancangan Antarmuka Dashboard Web Real-time

Setelah seluruh data tersimpan pada basis data, langkah selanjutnya adalah menyajikannya dalam bentuk yang dapat langsung dibaca pekebun dalam hitungan detik. Dashboard web SISTOMATE dirancang sebagai satu halaman tunggal (single page application sederhana) yang seluruh elemennya diperbarui otomatis tanpa memuat ulang halaman. Rancangan antarmuka lengkapnya ditampilkan pada Gambar 3.5.

![Antarmuka Dashboard Web Real-time SISTOMATE: kartu status tanaman beserta alasan, kartu metrik suhu udara, kelembapan udara, dan kelembapan tanah dengan gauge ambang histeresis, panel aktuator dan tabel riwayat aktuator, grafik tren kelembapan tanah serta suhu udara, dan panel warna daun TCS34725.](gambar/dashboard-web.png){width=100%}

Susunan antarmuka dari atas ke bawah dirancang sebagai berikut. Pertama, kartu status tanaman menampilkan status terkini berupa salah satu dari sehat, kering, layu, atau sakit dalam huruf besar berwarna sesuai status, disertai kartu alasan yang diturunkan dari data yang sama dengan heuristik firmware, misalnya status kering karena kelembapan tanah berada di bawah 40 persen. Kedua, tiga kartu metrik menampilkan suhu udara, kelembapan udara, dan kelembapan tanah; kartu suhu dan kelembapan tanah dilengkapi gauge jarum dengan penanda ambang histeresis, yaitu tanda 40 dan 55 persen pada skala kelembapan tanah serta skala suhu 20 sampai 40 derajat Celsius dengan tanda ambang 32 derajat Celsius, sehingga pengguna dapat langsung melihat posisi nilai terkini terhadap batas kendali. Ketiga, panel aktuator menampilkan status pompa air dan kipas exhaust beserta waktu transisi terakhir, berdampingan dengan tabel riwayat aktuator yang menampilkan lima perubahan status terakhir yang diambil dari tabel actuator_logs. Keempat, dua grafik area menampilkan tren kelembapan tanah dan suhu udara untuk 60 bacaan terakhir, yaitu jendela waktu sekitar dua menit pada interval kirim dua detik, sehingga arah perubahan kondisi terlihat jelas. Kelima, panel warna daun menampilkan nilai kanal merah, hijau, dan biru hasil pembacaan sensor TCS34725 dalam bentuk batang perbandingan, lengkap dengan simpulan hijau dominan atau merah dominan yang memakai ambang rasio identik dengan firmware, yaitu rasio hijau terhadap merah di bawah 0,75. Bagian kaki halaman memuat identitas perangkat dan lokasinya, jumlah bacaan serta event aktuator hari ini, dan indikator koneksi yang berubah dari LIVE menjadi TERPUTUS ketika respons data gagal diterima lebih dari dua belas detik.

Secara teknis, halaman dashboard memanggil endpoint data live.php melalui permintaan fetch berformat JSON dengan interval lima detik. Seluruh grafik dirender sebagai SVG yang digambar langsung oleh skrip JavaScript halaman tanpa pustaka eksternal, sehingga bobot halaman tetap ringan dan responsif termasuk pada layar ponsel, dan pemantauan dapat dilakukan dari mana saja dalam jangkauan jaringan lokal kampus.

## Implementasi Perangkat Lunak dan Listing Program

Perangkat lunak SISTOMATE tersusun atas tiga bagian yang saling terikat kontrak data, yaitu skema basis data MySQL, endpoint backend PHP, dan firmware ESP32. Subbab ini memuat potongan kode penting (listing program) dari keseluruhan sumber ketiga bagian tersebut.

### Pembuatan Skema Basis Data MySQL

Skema lima tabel ditulis dalam Data Definition Language (DDL) MySQL dengan mesin InnoDB dan constraint foreign key untuk menjamin integritas referensial sebagaimana diuraikan pada subbab perancangan basis data. Skema ini dibuat serta dikelola melalui GUI DBeaver yang terhubung ke server MySQL berjalan pada laptop lokal, sehingga struktur tabel, relasi, dan isian data dapat diinspeksi secara visual tanpa harus mengetik perintah SQL secara manual. Potongan DDL berikut menampilkan pembuatan tabel master devices beserta relasinya ke tabel users, serta tabel pencatatan sensor_logs lengkap dengan foreign key dan indeks kompositnya.

**Listing 3.1: Potongan DDL pembuatan tabel MySQL (mesin InnoDB, foreign key, dan indeks).**

```sql
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
```

Perhatikan penggunaan ENGINE=InnoDB pada setiap tabel yang mendukung transaksi dan constraint kunci asing, kebijakan ON DELETE RESTRICT pada relasi users ke devices yang mencegah penghapusan akun yang masih memiliki perangkat, kebijakan ON DELETE CASCADE pada relasi devices ke tabel pencatatan, serta indeks komposit idx_sensor_device_time yang mempercepat query riwayat per perangkat. Tabel users, gallery, dan actuator_logs ditulis dengan pola yang sama.

### Autentikasi dan Penulisan Data Endpoint ingest.php

Backend bekerja sebagai satu-satunya gerbang penulisan data ke basis data. Endpoint ingest.php menolak seluruh permintaan yang bukan metode POST, membaca kredensial basis data dari berkas lingkungan di luar root web, memverifikasi header X-API-Key milik perangkat dengan perbandingan konstan waktu, lalu menulis data menggunakan prepared statement PDO sehingga nilai yang dikirim perangkat tidak pernah menjadi bagian dari string kueri. Potongan kode berikut menampilkan ketiga mekanisme tersebut.

**Listing 3.2: Potongan kode endpoint ingest.php (pemeriksaan metode, autentikasi X-API-Key, dan prepared statement PDO).**

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);   // hanya metode POST yang diterima
    exit;
}
$env = parse_ini_file('/etc/sistomate-ingest.env');

$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals($env['API_KEY'], $key)) {
    http_response_code(401);   // perbandingan konstan waktu
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO sensor_logs
     (recorded_at, air_temperature, air_humidity, soil_moisture,
      rgb_red, rgb_green, rgb_blue, plant_status, id_device)
     VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$t, $rh, $soil, $r, $g, $b, $status, $deviceId]);
```

Seluruh nilai masukan dipaksa ke tipe yang sesuai sebelum dieksekusi, dan status tanaman divalidasi terhadap daftar nilai enum yang diizinkan, sehingga data yang masuk ke tabel sensor_logs selalu valid menurut kamus data. Pola serupa dipakai untuk menulis tabel actuator_logs pada setiap transisi relay.

### Logika Kendali Histeresis dan Fertigasi Firmware SISTOMATE.ino

Firmware ESP32 menerjemahkan pembacaan sensor menjadi tindakan aktuator melalui konstanta ambang, pengaman status relay saat boot, logika histeresis, serta penjadwalan fertigasi dengan interlock kelembapan tanah. Durasi dosis fertigasi tujuh detik dan jadwal mingguan mengacu pada praktik penelitian terdahulu [@sari2024sistem]. Potongan kode berikut merangkum keseluruhan logika tersebut.

**Listing 3.3: Potongan kode firmware SISTOMATE.ino (konstanta ambang, pengaman relay saat boot, histeresis, dan fertigasi berjadwal dengan interlock).**

```cpp
#define RELAY_ON        LOW    // modul relay aktif-LOW
#define SOIL_ON_PCT     40.0   // pompa nyala di bawah ini
#define SOIL_OFF_PCT    55.0   // pompa mati di atas ini
#define TEMP_ON_C       32.0   // kipas nyala di atas ini
#define TEMP_OFF_C      31.0   // kipas mati di bawah ini
#define FERT_DURATION_S 7      // dosis tujuh detik
#define FERT_MIN_SOIL   40.0   // interlock: tanah tidak boleh kering

void setup() {
  // amankan relay SEBELUM pinMode (ceklek saat boot dihindari)
  digitalWrite(PIN_RELAY_POMPA, RELAY_OFF);
  digitalWrite(RELAY_PUPUK, RELAY_OFF);
  digitalWrite(PIN_RELAY_KIPAS, RELAY_OFF);
  pinMode(PIN_RELAY_POMPA, OUTPUT);
  pinMode(RELAY_PUPUK, OUTPUT);
  pinMode(PIN_RELAY_KIPAS, OUTPUT);
}

void loop() {
  readSensors();
  // histeresis: nilai di antara kedua ambang menahan status aktuator
  setPump(soilPct < SOIL_ON_PCT ? true
          : (soilPct >= SOIL_OFF_PCT ? false : pumpOn));
  setFan(airTemp > TEMP_ON_C ? true
         : (airTemp <= TEMP_OFF_C ? false : fanOn));
  fertigasi();
}

// fertigasi: tiap Senin pukul 07.00, dosis tujuh detik, batal jika tanah kering
void fertigasi() {
  if (fertOn) {
    if (millis() - fertStartMs >=
        (unsigned long)FERT_DURATION_S * 1000UL) setFert(false);
    else if (soilPct < FERT_MIN_SOIL) setFert(false);
    return; // interlock keamanan juga berlaku di tengah dosis
  }
  struct tm t;
  if (!getLocalTime(&t, 0)) return;   // NTP belum tersinkron
  if (t.tm_hour != FERT_HOUR || t.tm_min != FERT_MIN) return;
  if (t.tm_wday != FERT_WDAY) return; // hanya hari Senin
  int key = (t.tm_year + 1900) * 1000 + t.tm_yday;
  if (key == lastFertKey) return;     // dosis hari ini sudah diberikan
  if (soilPct < FERT_MIN_SOIL) return;// tanah kering, dosis dilewati
  lastFertKey = key;
  prefs.putInt("fertkey", key);       // kunci persisten di NVS
  setFert(true);
}
```

Ketiga bagian perangkat lunak tersebut diikat oleh kontrak data JSON: firmware mengirim payload bertipe sensor atau actuator lengkap dengan header X-API-Key, endpoint ingest.php memvalidasi dan menulisnya ke tabel yang sesuai, dan live.php menyajikannya kembali kepada dashboard. Dengan demikian, perubahan pada salah satu bagian (misalnya penambahan kolom pada tabel sensor_logs) cukup diikuti dengan penyesuaian kontrak JSON tanpa merombak bagiannya yang lain. Hasil uji integrasi keseluruhan alur ini dirangkum pada Tabel 3.8.

## Pemodelan Fisik Greenhouse Mini

### Desain Tridimensional

Prototipe greenhouse mini dimodelkan dalam FreeCAD dengan konvensi sumbu X = panjang 1000 mm (dinding belakang berada pada bidang X = 1000), Y = lebar 800 mm, dan Z = tinggi 600 mm, sehingga seluruh dimensi konsisten dengan dokumen rencana prototipe dan panduan perakitan. Bentuk greenhouse mengadopsi konsep tunnel busur segmental: rangka pondasi berupa kotak kayu 100 x 80 cm dengan dinding ujung setinggi 60 cm, ditutup empat batang pipa PVC setengah inci yang ditekuk menjadi busur selebar 80 cm, dan diselimuti plastik PE bening yang dapat ditekuk mengikuti lengkung busur. Konsep busur segmental dipilih agar embun yang terbentuk pada permukaan dalam selimut mengalir menyusuri lengkung menuju sisi greenhouse, bukan menetes langsung ke daun tanaman. Model akhir terdiri atas 21 objek komponen yang seluruhnya berada di dalam batas bounding box 1000 x 800 x 600 mm tanpa satu pun pelanggaran batas.

![Render isometrik model 3D greenhouse mini SISTOMATE: rangka kayu, empat busur pipa, pot tanaman di tengah alas, sensor, dan kipas exhaust.](gambar/render-3d-isometrik.png){width=100%}

![Render tampak teknis model 3D SISTOMATE dengan dimensi utama 1000 x 800 x 600 mm.](gambar/render-3d-dimensi.png){width=100%}

Penempatan perangkat pada model mengikuti ketentuan pengujian lapangan. Probe kelembapan tanah dicolokkan miring dengan kedalaman sekitar tiga sampai lima sentimeter dari permukaan media; cara ini mencegah probe menempel dasar pot tempat air mengendap sehingga pembacaan tidak terjebak pada kondisi selalu basah. Sensor DHT11 dipasang pada pojok kiri-depan-atas secara diagonal terhadap kipas exhaust agar tidak terkena hembusan langsung yang akan membuat pembacaan suhu tidak mewakili kondisi udara di dalam greenhouse. Sensor warna TCS34725 dipasang pada bracket di rangka atas dengan jarak tiga sampai lima sentimeter dari titik paling lebar daun. Kipas exhaust 80 mm dipasang pada dinding belakang bagian atas menghadap keluar, sementara selang irigasi masuk melalui lubang kecil di dinding belakang bagian bawah menuju pot. Seluruh elektronik (ESP32, modul relay, dan adaptor) diletakkan di luar greenhouse pada box kontrol agar terhindar dari area lembap.

### Bill of Materials dan Panduan Potong Bahan

Rangkaian komponen prototipe telah dirinci dalam bill of materials (BOM) sebagai berikut. Biaya belanja baru hanya mencakup komponen yang belum tersedia; sensor, pompa, ESP32, dan modul relay pertama telah dimiliki tim dari keperluan pengujian sebelumnya.

Table: Bill of materials greenhouse mini SISTOMATE (belanja baru).

| No. | Komponen | Spesifikasi | Jumlah | Perkiraan Biaya (Rp) |
|---|---|---|---|---|
| 1 | Pipa busur | PVC/paralon 1/2 inci atau PP fleksibel, panjang kira-kira 4 m per batang | 2 batang | 50.000 |
| 2 | Kayu reng alas | Reng/kaso 2 x 3 atau bekas sisa | sekitar 7 m | 90.000 |
| 3 | Plastik PE bening | Fleksibel 0,1-0,2 mm, sekitar 1,5 kali luas selimut | 2 x 2 m | 60.000 |
| 4 | Modul relay 2 kanal | Aktif-LOW, optocoupler (unit kedua) | 1 unit | 25.000 |
| 5 | Kipas exhaust | 80 x 80 x 25 mm, 12 V DC | 1 pcs | 35.000 |
| 6 | Selang silikon | Diameter dalam 6 mm | 3 m | 24.000 |
| 7 | Adaptor 5 V | Minimal 2 A, terpisah dari pin 5V ESP32 | 1 pcs | 50.000 |
| 8 | Kabel ties | Pak isi 100 pcs | 1 pak | 5.000 |
| 9 | Bracket/klem penyangga | Bracket rel kecil atau klip bekas | 3 pcs | 30.000 |
| 10 | Sekrup dan klem busur | Sekrup kayu dan klem pengikat pipa | 1 set | 20.000 |
| 11 | Kabel pasif 22 AWG | Jalur relay ke pompa, kipas, dan ESP32 | 5 m | 15.000 |
| | **Total belanja baru** | | | **sekitar 404.000** |

Sedangkan daftar potong bahan (cutting list) rangka kayu dan busur dirinci sebagai berikut.

Table: Daftar potong bahan rangka greenhouse mini.

| No. | Potongan | Ukuran | Jumlah | Fungsi |
|---|---|---|---|---|
| 1 | Rangka sisi panjang | 100 cm | 2 | Sisi kiri-kanan alas |
| 2 | Rangka sisi lebar | 80 cm | 3 | Sisi depan, belakang, dan palang tengah alas |
| 3 | Tiang dinding ujung | 60 cm | 4 | Penyangga dinding depan dan belakang |
| 4 | Pipa busur | 130-150 cm per batang (ditekuk) | 4 | Rangka atap lengkung, jarak antarbusur sekitar 33 cm |

Hasil pemodelan ini menjadi acuan langsung pada proses perakitan: setiap lubang pengeboran, ketinggian puncak busur, dan posisi bracket sensor pada prototipe nyata mengikuti koordinat model, sehingga perakitan dapat dilakukan tanpa improvisasi yang mengubah geometri rancangan.

## Hasil Pengujian Fungsional

Pengujian fungsional dilakukan pada sistem utuh yang telah dirakit sesuai rancangan. Setiap fungsi diuji dengan skenario pemicu dan kriteria keberhasilan yang ditetapkan sebelum pengujian, dan seluruhnya dinyatakan lulus sebagaimana dirangkum pada Tabel 3.8.

Table: Hasil pengujian fungsional sistem SISTOMATE.

| No. | Skenario Pengujian | Kriteria Keberhasilan | Hasil |
|---|---|---|---|
| 1 | Pembacaan sensor tiap dua detik | Data suhu, kelembapan udara, kelembapan tanah, dan RGB daun terbaca dan dikirim sebagai JSON | Lulus |
| 2 | Pengiriman tanpa kredensial | Endpoint menolak permintaan dengan status 401 | Lulus |
| 3 | Penyiraman histeresis | Pompa menyala di bawah 40 persen, mati pada 55 persen, dan status ditahan di antara kedua ambang | Lulus |
| 4 | Ventilasi histeresis | Kipas menyala di atas 32 derajat Celsius dan mati pada 31 derajat Celsius ke bawah | Lulus |
| 5 | Fertigasi terjadwal | Pompa pupuk menyala tujuh detik pada Senin pukul 07.00 dengan syarat kelembapan tanah minimal 40 persen dan tanpa dosis ganda setelah restart | Lulus |
| 6 | Penalaran status tanaman | Status sehat, kering, layu, dan sakit sesuai aturan ambang yang ditetapkan | Lulus |
| 7 | Penyimpanan ke basis data | Baris masuk tabel sensor_logs dan actuator_logs dengan waktu diisi oleh server | Lulus |
| 8 | Penayangan dashboard | Grafik terbaru tampil pada interval lima detik tanpa muat ulang halaman | Lulus |
| 9 | Integritas model fisik | 21 objek komponen berada dalam batas dimensi 1000 x 800 x 600 mm tanpa pelanggaran | Lulus |

Pengujian keempat dan kelima sekaligus membuktikan bahwa pemisahan waktu antara logika kendali lokal (histeresis yang berjalan dua detik) dan durasi dosis fertigasi (tujuh detik presisi) dapat diselesaikan dengan pemantauan durasi berbasis millis pada firmware tanpa menghentikan siklus pembacaan sensor. Pengujian keenam memperlihatkan keunggulan utama SISTOMATE dibandingkan sistem pemantauan lingkungan biasa: status kesehatan tanaman disimpulkan dari instrumen terukur (kelembapan tanah, suhu, dan komposisi warna daun) sehingga penilaian tidak lagi bersifat subjektif.
