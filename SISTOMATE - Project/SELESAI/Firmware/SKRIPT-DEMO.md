# SISTOMATE — Skrip Demo & Persiapan

Purwarupa smart greenhouse: ESP32 + 3 sensor (soil moisture, DHT11, TCS34725) + relay 2-ch (pompa, kipas) → MySQL `sistomate` (5 tabel) → phpMyAdmin.

**Angka resmi dari firmware (jangan sebut angka lain):**

| Ambang | Nilai |
|--------|-------|
| Pompa ON | soil < 40% |
| Pompa OFF | soil >= 55% (histeresis) |
| Kipas ON | suhu > 32.0 C |
| Kipas OFF | suhu <= 31.0 C (histeresis) |
| Kirim data sensor | tiap 10 detik |
| Baca sensor | tiap 2 detik |

Status tanaman (`plant_status`): `kering` (soil<40) > `layu` (>32C) > `sakit` (daun tidak dominan hijau) > `sehat`.

---

## Bagian A — Persiapan Malem Ini (sekali jalan, ~30 menit)

1. **Nyalakan hotspot HP di mode 2.4 GHz.** (Android: pengaturan hotspot → band 2.4. iPhone: matikan "Maximize Compatibility" → cek ESP32 tetap kelihatan — kalau gak kelihatan, berarti hotspot 5 GHz.)
2. **Laptop nyambung ke hotspot HP yang sama.** Cek IP baru laptop:
   ```bash
   ip -4 addr show wlp2s0
   ```
   Catat IP-nya (misal `192.168.43.57`).
3. **Edit 4 baris config paling atas** `sistomate-firmware/SISTOMATE/SISTOMATE.ino`:
   - `WIFI_SSID` / `WIFI_PASS` = nama & password hotspot HP
   - `SERVER_URL` = `http://<IP_LAPTOP>/ingest.php` (IP dari langkah 2)
   - `API_KEY` udah keisi, gak usah diubah.
4. **Colok ESP32 ke laptop USB**, bilang ke Claude — upload dijalankan dari sini:
   ```bash
   arduino-cli upload -p /dev/ttyUSB0 --fqbn esp32:esp32:esp32 SISTOMATE
   ```
   (Kalau port gak kelihatan: `sudo modprobe ch341` untuk kabel CH340.)
5. **Test penuh 1x tanpa penonton** — buka `http://<IP_LAPTOP>/phpMyAdmin`, database `sistomate`:
   - tab `sensor_logs` → row baru tiap ~10 detik, `id_device` 1
   - celup-taruh sensor tanah → `actuator_logs` dapat `Pompa Air ON/OFF`
   - tiup/hangatkan DHT11 sampai >32C → `Kipas Exhaust ON`
6. **Rekam video 1x full run pakai HP** (layar laptop + alat). Ini jaring pengaman kalau WiFi/alat rewel pas hari-H.

Catatan: sebelum test, 2 row `sensor_logs` + 1 row `actuator_logs` ber-tanggal 21 Okt itu data test endpoint — boleh dihapus biar angka demo fresh, boleh dibiarkan (keangkat sendiri row baru).

---

## Bagian B — Skrip Demo Hari-H (~5 menit)

**Bekal fisik:** pot/gelas berisi tanah basah + gelas air, hair dryer kecil (atau tiupan napas), daun hijau segar, daun kuning/kering.

**Layar:** phpMyAdmin sudah login, dua tab siap: `sensor_logs` dan `actuator_logs`. Tab ketiga opsional: struktur database.

### Babak 0 — Kenalan database (30 detik)
Buka tab Structure/Designer database `sistomate`.

> "Sebelum alatnya, ini database kami: 5 tabel relasional — users, devices, sensor_logs, actuator_logs, gallery — saling terhubung dengan foreign key. Semua yang bakal alat ini kerjakan bakal tercatat di sini."

### Babak 1 — Kondisi normal (1 menit)
Celupkan sensor tanah ke air. DHT11 biarkan di udara ruangan.

> "Ini kondisi stabil: tanah basah, suhu ruangan normal. Sistem diam saja — tidak ada aktuator yang menyala, karena tidak ada masalah yang harus diselesaikan."

**Bukti:** refresh `sensor_logs` → row baru muncul tiap 10 detik, `soil_moisture` tinggi, `plant_status` `sehat`. (Tunjukkan timestamp-nya — data live, bukan rekaman.)

### Babak 2 — Kekeringan → pompa otomatis (1–1,5 menit)
Angkat sensor dari air, lap permukaannya.

> "Sekarang sensor tanah saya angkat — sistem membaca kelembapan di bawah 40 persen."

**Reaksi:** relay CH1 bunyi "ceklek", pompa menyala.

> "Relay langsung menyalakan pompa penyiraman. Keputusan dibuat ESP32 sendiri dari bacaan sensor, tanpa campur tangan manusia."

**Bukti:** buka tab `actuator_logs`, refresh → `Pompa Air / ON`. Lalu celupkan sensor balik ke air (>=55%) → pompa mati → row `OFF` muncul.

> "Histeresis 40 ke 55 persen mencegah pompa ON-OFF-OFF-ON berulang-ulang — ini detail biar alat gak rusak karena ngelakuin kerjaan sia-sia."

### Babak 3 — Suhu panas → kipas otomatis (1–1,5 menit)
Panaskan DHT11: hair dryer kecil paling cepat (~10–20 detik), atau genggam erat/napas hangat (~30–60 detik).

> "DHT11 saya panaskan. Sensor ini punya inersia termal — butuh 20 sampai 60 detik sebelum angkanya naik, mohon ditunggu."

Begitu layar `sensor_logs` menunjukkan suhu lewat 32 derajat:

**Reaksi:** relay CH2 "ceklek", kipas exhaust berputar.

> "Efek rumah kaca berbahaya buat tanaman. Di atas 32 derajat, sistem otomatis nyalakan exhaust fan buat buang udara panas; turun lagi di bawah 31 derajat, fan mati sendiri."

**Bukti:** `actuator_logs` → `Kipas Exhaust / ON`. Hentikan pemanasan, tunggu suhu turun → row `OFF`.

### Babak 4 — Analisis warna daun (1 menit)
Tempelkan daun hijau menutupi mata TCS34725 (2–3 detik), lalu ganti daun kuning/kering.

> "Sensor terakhir: TCS34725, sensor warna presisi komunikasi I2C. Dia membaca spektrum RGB daun. Daun sehat mendominasi hijau; daun kuning/stres memerah. Angkanya terus masuk ke database, jadi riwayat kesehatan tanaman bisa dianalisis nanti."

**Bukti:** refresh `sensor_logs` → kolom `rgb_green` melonjak untuk daun hijau, `rgb_red` naik untuk daun kuning. Kalau kondisi kering/panas belum sempat pulih, `plant_status` bisa berubah `kering`/`layu` — justru bukti logika prioritasnya jalan.

### Penutup (30 detik)
Buka `actuator_logs` penuh, tunjukkan seluruh riwayat.

> "Ringkasnya: SISTOMATE adalah siklus utuh — sensor membaca lingkungan, ESP32 mengambil keputusan, aktuator bekerja, dan setiap peristiwa tercatat rapi di basis data relasional. Hardware, firmware, dan database terintegrasi penuh dan sudah teruji end-to-end."

---

## Bagian C — Kalau Ada Masalah (cheat sheet)

| Gejala | Penyebab paling likely | Fix cepat |
|--------|------------------------|-----------|
| ESP32 gak bisa connect WiFi | hotspot 5 GHz | pindahin hotspot ke 2.4 GHz |
| ESP32 connect tapi row gak masuk | IP laptop berubah / salah | cek `ip -4 addr show wlp2s0`, update `SERVER_URL`, recompile, upload ulang |
| Port serial gak kelihatan | driver kabel | `sudo modprobe ch341` |
| RGB gak berubah-ubah | TCS34725 gak ke-detek / kurang cahaya | cek kabel SDA/SCL (21/22), deketin daun, tutup dari cahaya ruangan |
| DHT11 lambat naik | sifat sensor (normal!) | jelasin inersia termal, pakai hair dryer |
| phpMyAdmin gak kebuka | httpd/nginx mati | `sudo systemctl restart nginx php-fpm` |
| Row gak muncul padahal POST 200 | salah tab/urutan | cek tab `sensor_logs` → urutkan `id_log` DESC |

**Aturan emas:** gak ada yang namanya "panel hijau = berhasil" — buka `actuator_logs`, tunjukkan row-nya. Row masuk = sistem bekerja.
