# Naskah Slide Presentasi & Kisi-Kisi Sidang
## SISTOMATE — Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat

| | |
|---|---|
| **Mata Kuliah** | Workshop Basis Data (TRK 130805) |
| **Kelompok** | Kelompok 3 — Golongan B |
| **Dosen Penilai** | Zilvanhisna Emka Fitri, S.T., M.T. & Nanik Anita Mukhlisoh, S.ST., M.T. |
| **Institusi** | Politeknik Negeri Jember — Jurusan Teknologi Informasi |
| **Jumlah Slide** | 14 slide + 1 seksi Kisi-Kisi Q&A |

> **Cara pakai dokumen ini:** tiap slide punya 4 bagian — **Judul**, **Visual**, **Poin-Poin Utama** (yang tampil di layar), dan **Speaker Notes** (naskah yang DIUCAPKAN presenter — tidak tampil di slide). Bagian terakhir adalah kisi-kisi prediksi pertanyaan dosen beserta jawaban teknis.

---

# BAGIAN 1 — DECK PRESENTASI (14 Slide)

---

## Slide 1 — Sampul

**Visual:** Logo Politeknik Negeri Jember + judul besar + daftar 6 nama anggota + nama dosen.

**Poin-Poin Utama:**
- SISTOMATE: Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat
- Kelompok 3 — Golongan B
- Dosen Pengajar: Zilvanhisna Emka Fitri, S.T., M.T. & Nanik Anita Mukhlisoh, S.ST., M.T.

**Speaker Notes:**
> "Assalamualaikum warahmatullahi wabarakatuh. Salam sejahtera. Perkenalkan, kami Kelompok 3 Golongan B. Pada kesempatan ini kami akan mempresentasikan hasil proyek kami, yaitu SISTOMATE — Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat. Terima kasih kepada Ibu Zilvanhisna dan Ibu Nanik yang telah membimbing kami. Kami mulai dari latar belakang."

---

## Slide 2 — Latar Belakang & Rumusan Masalah

**Visual:** Foto tanaman tomat greenhouse + 4 ikon masalah (subjektif, telat, terbatas, tanpa riwayat).

**Poin-Poin Utama:**
- Penyiraman & pemantauan tomat masih MANUAL → rentah human error
- 4 Masalah pemantauan manual: (1) subjektivitas penilaian, (2) keterlambatan penanganan, (3) keterbatasan fisik & waktu, (4) ketiadaan catatan historis
- Kelembapan tanah & kesehatan daun tidak terpantau real-time → gagal panen

**Speaker Notes:**
> "Latar belakangnya begini: merawat tomat di greenhouse itu butuh konsistensi. Saat ini penyiraman dan pemantauan masih manual. Ada empat masalah klasik. Pertama, subjektivitas — petani menilai tanaman 'kayaknya sehat' dengan mata telanjang. Kedua, keterlambatan — begitu terdeteksi layu, sering sudah terlambat. Ketiga, keterbatasan fisik — kita tidak bisa memantau 24 jam. Keempat, ketiadaan riwayat — tidak ada data historis untuk menganalisis pola. Akibatnya: gagal panen. Dari sinilah kami merumuskan kebutuhan akan sistem otomasi yang memantau dan menyiram tanpa campur tangan manual terus-menerus."

---

## Slide 3 — Tujuan & Manfaat

**Visual:** Diagram lingkaran "Sensor → Keputusan → Aksi → Data" (loop otomasi).

**Poin-Poin Utama:**
- **Tujuan:** (1) Otomasi penyiraman berbasis sensor kelembapan tanah, (2) Monitoring kesehatan tanaman (suhu, kelembapan, warna daun RGB), (3) Penyimpanan data terstruktur di basis data relasional, (4) Notifikasi & dashboard real-time
- **Manfaat:** hemat air (siram sesuai kebutuhan), respons cepat, riwayat lengkap, skala banyak greenhouse

**Speaker Notes:**
> "Tujuan kami ada empat. Satu, mengganti penyiraman manual dengan otomatis berdasarkan kelembapan tanah — tanaman disiram saat butuh, bukan saat jadwal. Dua, memantau kesehatan: suhu dan kelembapan udara lewat DHT11, serta warna daun lewat sensor RGB untuk deteksi dini penyakit. Tiga, menyimpan semua data di basis data relasional yang terstruktur. Empat, menampilkan dashboard real-time dan notifikasi. Manfaatnya: air lebih hemat, respons lebih cepat, dan ada riwayat data yang bisa dianalisis — serta siap diskalakan ke banyak greenhouse."

---

## Slide 4 — Arsitektur Sistem (4 Layer)

**Visual:** Diagram blok bertingkat 4 layer (lihat Bagian 2).

**Poin-Poin Utama:**
- **Layer 1 — Persepsi:** Sensor (DHT11, Soil Moisture, TCS34725) + ESP32-CAM
- **Layer 2 — Kontrol & Aktuasi:** ESP32 + logika histeresis/jadwal + Relay (pompa air, pompa pupuk, kipas)
- **Layer 3 — Komunikasi & Data:** WiFi, HTTP POST, `ingest.php`, MySQL (5 tabel)
- **Layer 4 — Presentasi & Kecerdasan:** Dashboard Web, Notifikasi, Analisis AI (Gemini)

**Speaker Notes:**
> "Arsitektur sistem kami dibagi empat layer agar rapi dan modular. Layer pertama, Persepsi — tiga sensor dan kamera mengumpulkan data lingkungan. Layer kedua, Kontrol dan Aktuasi — ESP32 memutuskan aksi lewat logika histeresis dan jadwal, lalu menggerakkan aktuator lewat relay. Layer ketiga, Komunikasi dan Data — data dikirim via WiFi lewat HTTP POST, diproses `ingest.php`, dan disimpan di MySQL. Layer keempat, Presentasi dan Kecerdasan — dashboard web menampilkan data, sistem mengirim notifikasi, dan AI Gemini menganalisis kesehatan daun dari foto. Pemisahan layer ini memudahkan pengujian dan pengembangan per bagian."

---

## Slide 5 — Skematik Alat (Wiring)

**Visual:** Diagram skematik koneksi ESP32 → sensor → relay → beban (lihat Bagian 2).

**Poin-Poin Utama:**
- ESP32: Soil Moisture → GPIO 34 (analog), DHT11 → GPIO 13, TCS34725 → I2C
- Relay 2×2-ch **aktif-LOW**: Pompa Air → GPIO 26, Pompa Pupuk → GPIO 14, Kipas → GPIO 27
- **Common ground** sisi kontrol ESP32 ↔ modul relay (syarat sinyal HIGH/LOW bernilai)
- Sisi beban (adaptor pompa) **diisolasi** kontak relay — arus besar tak melewati ESP32

**Speaker Notes:**
> "Ini skematik pengkabelannya. ESP32 membaca kelembapan tanah lewat pin analog GPIO 34, suhu dan kelembapan udara lewat DHT11 di GPIO 13, dan warna daun lewat sensor RGB TCS34725 lewat I2C. Untuk menggerakkan aktuator, kami pakai modul relay dua kanal dua kanal yang aktif-LOW: pompa air di GPIO 26, pompa pupuk di GPIO 14, dan kipas di GPIO 27. Dua hal kritis di sini. Pertama, common ground — ground modul relay sisi kontrol harus nyambung ke ground ESP32, supaya sinyal HIGH/LOW punya acuan 0 volt dan relay bisa diandalkan. Kedua, isolasi — adaptor daya beban seperti pompa dipisahkan lewat kontak relay, jadi arus besar pompa tidak pernah melewati ESP32. Ini menjaga mikrokontroler dari noise dan risiko kerusakan."

---

## Slide 6 — Flowchart Paralel (Kontrol + Jadwal + AI)

**Visual:** Flowchart dua cabang paralel (lihat Bagian 2).

**Poin-Poin Utama:**
- **Cabang A (tiap 2 detik):** baca sensor → histeresis pompa/kipas → POST actuator_logs
- **Cabang B (berjadwal, NTP):** cek Senin 07:00 → interlock tanah ≥40% → pompa pupuk 7 detik
- **Cabang C (periodik):** kirim foto → analisis AI → status tanaman → sensor_logs
- Ketiga cabang menulis ke MySQL (sinkron via `id_device`)

**Speaker Notes:**
> "Logika sistem kami berjalan paralel. Cabang A berjalan tiap dua detik: baca sensor, terapkan histeresis untuk pompa air dan kipas, lalu setiap transisi relay dicatat ke actuator_logs. Cabang B berjadwal lewat NTP: setiap Senin jam tujuh pagi, asalkan kelembapan tanah masih di atas 40 persen — ini syarat keamanan supaya tidak menyiram tanah yang sudah kering — pompa pupuk dinyalakan selama tujuh detik. Cabang C periodik: ESP32-CAM mengirim foto, AI Gemini menganalisis kesehatan daun, hasilnya jadi status tanaman yang disimpan ke sensor_logs. Ketiga cabang pada akhirnya menulis ke basis data yang sama, terhubung lewat id_device."

---

## Slide 7 — Sensor & Aktuator

**Visual:** Foto/ril tiap komponen + tabel spesifikasi.

**Poin-Poin Utama:**
| Komponen | Fungsi | Nilai |
|---|---|---|
| DHT11 | Suhu & kelembapan udara | 0–50°C, 20–90%RH |
| Soil Moisture | Kelembapan tanah | analog, dikalibrasi % |
| TCS34725 | Warna daun (RGB) | deteksi perubahan warna |
| ESP32-CAM | Foto tanaman | input analisis AI |
| Relay + Pompa | Penyiraman & fertigasi | 3 kanal aktif-LOW |
| Kipas | Sirkulasi/cooling | aktif >32°C |

**Speaker Notes:**
> "Komponennya. DHT11 mengukur suhu dan kelembapan udara. Soil moisture membaca kelembapan tanah secara analog, yang kemudian dikalibrasi menjadi persen. TCS34725 membaca komponen warna RGB daun — perubahan rasio warna bisa menandakan stres atau penyakit. ESP32-CAM memotret daun untuk dianalisis AI. Untuk aktuasi, ada tiga kanal relay: pompa air, pompa pupuk, dan kipas yang menyala otomatis saat suhu lewat 32 derajat."

---

## Slide 8 — Logika Kontrol: Histeresis + Jadwal Fertigasi

**Visual:** Grafik histeresis (loop ganda) + diagram timeline jadwal mingguan.

**Poin-Poin Utama:**
- **Histeresis Pompa:** ON saat soil < 40%, OFF saat soil ≥ 55% (band 15%)
- **Histeresis Kipas:** ON saat suhu > 32°C, OFF saat ≤ 31°C
- **Fertigasi:** 1×/minggu (Senin 07:00, NTP), durasi 7 detik, interlock tanah ≥ 40%
- Histeresis **mencegah chattering** → relay & pompa awet, kontrol stabil

**Speaker Notes:**
> "Ini inti kendali kami: histeresis, bukan ambang tunggal. Pompa air menyala saat kelembapan tanah di bawah 40 persen dan mati saat sudah di atas 55 persen — ada jeda 15 persen. Sama, kipas menyala di atas 32 derajat dan mati di bawah 31. Mengapa tidak pakai satu ambang? Karena di sekitar satu titik, noise sensor dan air yang belum meresap akan membuat nilai bolak-balik, sehingga relay cepat nyala-mati — yang disebut chattering. Ini merusak kontak relay dan pompa. Dengan band histeresis, sistem stabil dan umur komponen panjang. Untuk fertigasi, jadwalnya mingguan setiap Senin pagi, durasi tujuh detik, dengan syarat keamanan tanah masih lembap."

---

## Slide 9 — Pemodelan Fisik 3D CAD (Greenhouse)

**Visual:** Render isometrik + tampak dimensi greenhouse 100×80×60 cm (lihat Bagian 2).

**Poin-Poin Utama:**
- Dimensi: **100 × 80 × 60 cm** (P × L × T), rangka + plastik UV
- Posisi komponen: DHT11 kiri-depan-atas, kipas exhaust, selang masuk dinding belakang
- Pot + tanah (frustum anti-bocor), probe kelembapan tanah miring 28° (kedalaman ~3,4 cm)
- Model CAD (FreeCAD) → STEP/STL untuk perakitan & simulasi

**Speaker Notes:**
> "Desain fisik greenhouse kami dimodelkan di FreeCAD dengan ukuran 100 sentimeter panjang, 80 lebar, dan 60 tinggi. Penempatan komponen dipertimbangkan: DHT11 di kiri depan atas agar mewakili suhu udara rata-rata, kipas exhaust untuk sirkulasi, dan selang masuk dari dinding belakang bawah. Pot berisi tanah berbentuk frustum agar tidak bocor menembus dinding, dan probe kelembapan tanah dipasang miring 28 derajat dengan kedalaman sekitar 3,4 sentimeter. Model 3D ini diekspor ke STEP dan STL sebagai acuan perakitan fisik dan verifikasi tata letak sebelum dirakit."

---

## Slide 10 — Perancangan Basis Data (ERD 5 Tabel)

**Visual:** Diagram ERD 5 tabel berpusat pada `devices` (lihat Bagian 2).

**Poin-Poin Utama:**
- 5 tabel: `users`, `devices`, `gallery`, `sensor_logs`, `actuator_logs`
- **`devices` jadi hub** — semua data operasional merujuk `id_device`
- Relasi: `users` owns `devices`; `devices` generates `sensor_logs`/`actuator_logs`; `gallery` referenced by `devices`
- `sensor_logs`: suhu, kelembapan, soil, RGB, `plant_status` (enum sehat/kering/layu/sakit)

**Speaker Notes:**
> "Sekarang ke jantung tugas mata kuliah ini: basis data. Kami merancang lima tabel. Empat di antaranya — users, gallery, sensor_logs, dan actuator_logs — semuanya berpusat pada tabel devices. Mengapa? Karena perangkat ESP32-lah yang menghasilkan seluruh data operasional: bacaan sensor, aksi aktuator, dan foto tanaman semuanya merujuk id_device. Relasinya: users memiliki devices, devices menghasilkan sensor_logs dan actuator_logs, dan galeri foto terhubung ke devices. Tabel sensor_logs menyimpan suhu, kelembapan udara, kelembapan tanah, nilai RGB daun, dan status tanaman dalam bentuk enum."

---

## Slide 11 — Implementasi Basis Data (DDL, Trigger, Index)

**Visual:** Cuplikan `SHOW CREATE TABLE` + skema indeks + alur stored procedure.

**Poin-Poin Utama:**
- Engine **InnoDB**, kunci asing **ON DELETE CASCADE**
- **Index komposit `(id_device, recorded_at`** → query riwayat per perangkat cepat
- `plant_status` sebagai **ENUM** → hemat & tervalidasi
- Constraint PK/FK → integritas referensial, tanpa redudansi

**Speaker Notes:**
> "Implementasinya memakai engine InnoDB dengan ON DELETE CASCADE, sehingga ketika sebuah perangkat dihapus, seluruh lognya ikut terhapus rapi — tidak ada data yatim. Kami menambahkan index komposit pada id_device dan recorded_at, karena query paling sering adalah mengambil riwayat sensor sebuah perangkat berdasarkan waktu — dengan index ini query tersebut sangat cepat. Status tanaman dibuat sebagai ENUM agar hemat penyimpanan dan otomatis tervalidasi. Constraint primary key dan foreign key menjamin tidak ada data ganda dan integritas antar tabel tetap terjaga."

---

## Slide 12 — Dashboard Web, Notifikasi & Analisis AI

**Visual:** Screenshot dashboard (grafik real-time + notifikasi + hasil analisis daun).

**Poin-Poin Utama:**
- Dashboard **real-time**: nilai sensor terkini + grafik historis
- **Notifikasi otomatis** saat kondisi kritis (tanah kering / suhu tinggi)
- **AI Gemini** menganalisis foto daun → deteksi penyakit/hama → `plant_status`
- Data dari `sensor_logs`/`gallery` → tampilan terpusat per `id_device`

**Speaker Notes:**
> "Di sisi pengguna, ada dashboard web yang menampilkan data real-time: nilai sensor terkini dan grafik historis yang diambil dari tabel sensor_logs. Sistem mengirim notifikasi otomatis saat kondisi kritis, misalnya tanah terlalu kering atau suhu terlalu tinggi. Keunggulannya, kami mengintegrasikan AI Gemini: foto daun dari ESP32-CAM dianalisis untuk mendeteksi penyakit atau hama, dan hasilnya disimpan sebagai status tanaman. Semua tampilan terpusat per perangkat berdasarkan id_device, sehingga satu pengguna bisa memantau banyak greenhouse sekaligus."

---

## Slide 13 — Hasil Pengujian & Pengukuran

**Visual:** Tabel hasil uji (akurasi sensor, waktu respons penyiraman, suhu berbanding acuan).

**Poin-Poin Utama:**
- Uji kelembapan tanah vs alat ukur referensi → penyimpangan dalam toleransi
- Respons penyiraman: histeresis terpicu tepat di ambang 40%/55%
- Kipas menyala stabil di >32°C, mati ≤31°C (tanpa chattering)
- Build dokumen/DB: seluruh skema & log terverifikasi

**Speaker Notes:**
> "Pengujian kami lakukan di beberapa sisi. Kelembapan tanah kami bandingkan dengan alat ukur referensi, dan penyimpangannya masih dalam toleransi. Respons penyiraman terpicu tepat di ambang yang kami tetapkan, yaitu nyala di bawah 40 persen dan mati di atas 55 persen. Kipas menyala stabil di atas 32 derajat dan mati di bawah 31, tanpa chattering — membuktikan histeresis bekerja. Seluruh skema basis data dan alur logging juga kami verifikasi berjalan konsisten."

---

## Slide 14 — Kesimpulan & Saran

**Visual:** Ringkasan 4 poin + ikon pengembangan lanjutan.

**Poin-Poin Utama:**
- **Kesimpulan:** otomasi penyiraman + monitoring kesehatan + basis data relasional 5 tabel terhubung `devices` terwujud & teruji
- Histeresis + jadwal + interlock = kontrol andal & aman
- **Saran:** fertigasi 2-pompa (air+pupuk terpisah), integrasi MQTT/LoRa untuk jangkauan, retensi data log, model AI lebih akurat

**Speaker Notes:**
> "Kesimpulannya, SISTOMATE berhasil mewujudkan sistem otomasi penyiraman sekaligus monitoring kesehatan tanaman tomat, dengan basis data relasional lima tabel yang terhubung rapi pada tabel devices. Kombinasi histeresis, jadwal, dan interlock membuat kontrol menjadi andal dan aman. Untuk pengembangan, kami menyarankan: sistem fertigasi dua pompa yang lebih matang antara air dan pupuk, integrasi MQTT atau LoRa agar jangkauan perangkat lebih luas, mekanisme retensi data agar riwayat log tetap terkendali, serta peningkatan akurasi model analisis AI. Demikian presentasi kami. Terima kasih, kami terima pertanyaan."

---

# BAGIAN 2 — ASET VISUAL (deskripsi untuk slide)

> Catatan: gambar dirujuk dari `docs/cad/` dan hasil render. Pastikan semua file tersedia sebelum presentasi.

- **Slide 4 (4 Layer):** diagram blok 4 kotak bertingkat, panah dari Persepsi → Kontrol → Komunikasi → Presentasi.
- **Slide 5 (Skematik):** ESP32 di tengah; panah masuk dari 3 sensor (Soil→GPIO34, DHT11→GPIO13, TCS34725→I2C); panah keluar ke blok relay 3 kanal (GPIO26/14/27); catatan "Common Ground (kontrol)" dan "Adaptor beban terisolasi".
- **Slide 6 (Flowchart paralel):** tiga kolom paralel A/B/C seperti di Speaker Notes, bertemu di simpul "MySQL (id_device)".
- **Slide 9 (3D CAD):** `docs/cad/render-isometric.png` (tampak isometrik) + `docs/cad/render-dimensi.png` (tampak dimensi 100×80×60).
- **Slide 10 (ERD):** `docs/erdplus.png` — 5 kotak tabel + relasi.
- **Slide 12 (Dashboard):** tangkapan layar dashboard web.

---

# BAGIAN 3 — KISI-KISI: PREDIKSI PERTANYAAN DOSEN & JAWABAN TEKNIS

---

### Q1 — Kenapa relay dipakai aktif-LOW, bukan aktif-HIGH?

**Jawaban:**
1. **Karakteristik modul:** Modul relay 2-ch murah yang umum (berbasis optocoupler) memang dirancang aktif-LOW — kumparan relay baru ter energi saat pin IN ditarik ke LOW (ground).
2. **Alasan utama = KEAMANAN BOOT.** Saat ESP32 boot atau reset, pin GPIO masih *floating* (mengambang) sebelum diinisialisasi. Kalau relay aktif-HIGH, ada risiko pompa menyala sendiri saat boot. Firmware kami mengatasinya dengan **menulis `RELAY_OFF` (HIGH) ke semua pin relay SEBELUM `pinMode()`** — jadi pada mikrodetik pertama, relay sudah dipaksa di kondisi aman (mati).
3. **Konsistensi dengan boot-state:** Karena aktif-LOW, kondisi default aman (tertinggal HIGH = OFF) sejalan dengan perilaku pin ESP32 saat boot — tanpa momen "ceklek" berbahaya.
4. **Bukti di kode:** `#define RELAY_ON LOW`, `#define RELAY_OFF HIGH`, dan blok `setup()` yang mengamankan relay lebih dulu.

---

### Q2 — Mengapa wajib Common Ground antara adaptor beban dan ESP32?

**Jawaban:**
1. **Sinyal butuh acuan 0 V.** Output GPIO ESP32 adalah tegangan *relatif* terhadap GND ESP32. Agar modul relay "melihat" HIGH/LOW dengan benar, **sisi kontrol modul relay (VCC, GND, IN) harus berbagi ground yang sama dengan ESP32**. Tanpa common ground, pin IN melayang (*floating*) dan transistor/optocoupler tak punya acuan untuk menentukan ON/OFF → relay tak bisa diandalkan.
2. **Yang diwajibkan umum ground adalah sisi KONTROL** (ESP32 ↔ modul relay), bukan beban itu sendiri.
3. **Sisi beban justru DIISOLASI.** Relay memisahkan secara elektromagnetik sisi kumparan (kontrol 3,3 V) dari sisi kontak (beban). Adaptor daya pompa (mis. 12 V) boleh terpisah dan **tidak harus** share ground dengan ESP32 — itu justru manfaat utama relay: menjaga ESP32 dari arus besar & noise beban.
4. **Nuansa praktis:** Modul relay 2-ch murah sering meng-ground-kan kedua sisi bersama di papan, sehingga dalam praktiknya GND beban & GND ESP32 akhirnya nyambung juga. Yang tetap krusial dan tidak boleh putus adalah common ground di sisi kontrol.

---

### Q3 — Mengapa algoritma histeresis dipakai pada pompa & kipas, bukan ambang tunggal?

**Jawaban:**
1. **Masalah ambang tunggal = chattering.** Dengan satu titik ambang (mis. pompa ON < 50%, OFF ≥ 50%), di sekitar nilai itu noise sensor dan keterlambatan peresapan air membuat pembacaan bolak-balik melewati ambang → pompa/kipas cepat nyala-mati berulang.
3. **Akibat:** kontak relay cepat aus, pompa cepat rusak, konsumsi listrik naik, dan kontrol tidak stabil.
4. **Solusi histeresis = dua ambang berjeda.** Pompa: ON < 40%, OFF ≥ 55% (band 15%). Kipas: ON > 32°C, OFF ≤ 31°C. Nilai harus bergerak signifikan dulu sebelum relay berpindah → frekuensi perpindahan jauh turun.
5. **Cocok dengan perilaku fisik:** air butuh waktu meresap dan suhu butuh waktu berubah, jadi band merepresentasikan kenyataan lapangan. Untuk fertigasi ditambah **interlock** (tanah ≥ 40%) sebagai syarat keamanan ekstra.

---

### Q4 — Bagaimana jika WiFi putus saat jadwal fertigasi mingguan?

**Jawaban:**
1. **Prinsip desain: kontrol itu EDGE (lokal di ESP32), bukan bergantung cloud.** Logika histeresis pompa & kipas berjalan lokal dari bacaan sensor — ini tetap berfungsi penuh walau WiFi mati. Penyiraman darurat karena tanah kering tetap terjadi berdasarkan sensor.
2. **Waktu jadwal:** setelah sekali sinkron NTP, ESP32 mempertahankan waktu lewat RTC internal. Putus WiFi singkat tidak mengganggu jadwal Senin 07:00.
3. **Jika waktu tidak pasti (putus lama), jangan nekat berdasarkan jadwal yang salah.** Jadikan **kelembapan tanah (histeresis) sebagai pengaman utama**: tanaman tetap disiram saat sensor mendeteksi kering, tanpa bergantung jadwal waktu yang tidak terverifikasi.
4. **Logging tetap lengkap:** setiap transisi relay di-POST sebagai `actuator_logs`. Saat WiFi putus, log di-*buffer* lokal dan dikirim ulang saat koneksi pulih, sehingga riwayat di database tidak bolong.
5. **Transparansi:** dashboard menandai data sebagai *stale*/terputus dan memberi notifikasi ke pengguna bahwa koneksi terputus.

---

### Q5 — Mengapa basis data dipusatkan pada tabel `devices`?

**Jawaban:**
1. **`devices` adalah entitas inti penghasil data.** Setiap baris log sensor, log aksi aktuator, dan foto galeri selalu merujuk `id_device` — perangkatlah yang menghasilkan seluruh data operasional.
2. **Normalisasi:** atribut perangkat (nama, lokasi, pemilik) cukup disimpan sekali di `devices`, tidak diduplikasi di ribuan baris log → hemat & minim redudansi.
3. **Integritas referensial:** FK dari tiap tabel log ke `devices` (dengan `ON DELETE CASCADE`) menjamin setiap pembacaan/aksi/foto dapat ditelusuri ke perangkatnya; menghapus perangkat otomatis membersihkan lognya → tidak ada data yatim.
4. **Skalabilitas:** menambah banyak greenhouse = cukup menambah baris di `devices`; data tiap perangkat terisolasi lewat `id_device` tanpa mengubah skema.
5. **Query efisien:** riwayat per perangkat (sensor terakhir, histori, log aksi, galeri) diambil cepat dengan **index komposit `(id_device, recorded_at)`**. Pola ini (star/snowflake schema) sangat cocok untuk sistem monitoring IoT multi-perangkat.

---

### Q6 (cadangan) — Mengapa memilih MySQL, dan bagaimana performanya untuk data log yang terus bertambah?

**Jawaban:**
- MySQL (InnoDB) dipilih karena mature, ACID, mendukung foreign key & transaksi, serta mudah diintegrasikan dengan ESP32 lewat HTTP/PHP.
- Untuk data log yang tumbuh cepat, kami mitigasi dengan: index komposit `(id_device, recorded_at)` untuk query histori, serta **retensi/arsip** (mis. pindahkan log lama ke tabel arsip) agar tabel aktif tetap ringan.
- `ENUM` untuk status dan tipe data tepat (FLOAT untuk sensor, DATETIME untuk waktu) menjaga ukuran baris tetap efisien.
