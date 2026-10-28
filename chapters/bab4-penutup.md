# PENUTUP

## Kesimpulan

Perancangan basis data SISTOMATE ini menghasilkan lima tabel terstruktur yang terdiri dari dua tabel master (Users, Devices) dan tiga tabel pencatatan (Gallery, Sensor Logs, Actuator Logs). Skema basis data ini menggunakan pendekatan berpusat pada perangkat, di mana Tabel Devices menjadi jembatan penghubung antara data pengguna (dari Tabel Users melalui relasi kepemilikan) dan seluruh data operasional greenhouse: data visual tanaman (melalui Tabel Gallery), data kondisi lingkungan dan status tanaman (melalui Tabel Sensor Logs yang mencakup suhu, kelembapan, kelembapan tanah, nilai RGB daun, dan status tanaman), serta data tindakan (melalui Tabel Actuator Logs). Penggunaan Primary Key dan penerapan Foreign Key pada tabel pencatatan memastikan tidak ada data ganda (redudansi) dan menjamin integritas referensial, sehingga riwayat kondisi dan kesehatan tanaman tomat dapat dilacak dengan presisi oleh sistem dan keempat kelemahan pemantauan manual (subjektivitas, keterlambatan penanganan, keterbatasan fisik, dan ketiadaan catatan historis) dapat teratasi.

## Saran

Untuk pengembangan lebih lanjut, disarankan agar perancangan basis data ini diimplementasikan ke dalam sistem manajemen basis data MySQL agar dapat diuji secara langsung bersama perangkat ESP32 dan webcam, serta dilengkapi dengan mekanisme retensi data (misalnya pengarsipan otomatis log sensor lama) agar volume data log yang tumbuh cepat tetap terkendali.

Pada sisi aktuator, sistem berpotensi dikembangkan menjadi sistem fertigasi (penyiraman sekaligus pemupukan) dengan menambahkan satu modul relay kanal tunggal dan pompa kedua khusus larutan pupuk cair. Pompa air tetap dikendalikan berdasarkan histeresis kelembapan tanah, sedangkan pompa pupuk dikendalikan terpisah berdasarkan jadwal waktu dengan durasi aktivasi yang dibatasi sehingga dosis pupuk terkontrol, dilengkapi syarat pengaman berupa kelembapan tanah tertentu agar larutan pupuk tidak diaplikasikan pada tanah yang terlalu kering. Pola dua pompa terpisah ini sejalan dengan praktik pada penelitian terdahulu [@sari2024sistem] dan seluruh durasi aktivasi dapat dicatat ke dalam tabel Actuator Logs melalui kolom duration_sec tanpa perubahan skema basis data.

::: {#refs}
:::

# LAMPIRAN: DAFTAR ANGGOTA KELOMPOK {-}

Kelompok 3 — Golongan B

| No. | Nama | NIM |
|---|---|---|
| 1. | Abiyu Ammar Hady | E43252782 |
| 2. | Ibror Yusron S | E43252690 |
| 3. | Moch Calvin Aprilian | E43252720 |
| 4. | Nabil Laudza Nur | E43252939 |
| 5. | Tio Dwi Harianto | E43252983 |
| 6. | Yuda Maredya Ari Pratama | E43252953 |
