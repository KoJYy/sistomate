# PENUTUP

## Kesimpulan

Berdasarkan perancangan, implementasi, dan pengujian yang telah dilakukan, dapat ditarik beberapa kesimpulan sebagai berikut.

1. Arsitektur sistem empat lapis yang dirancang terbukti mengalirkan data utuh dari persepsi hingga presentasi: ketiga sensor pada ESP32 dikirim sebagai payload JSON melalui HTTP POST, divalidasi dan disimpan oleh backend ingest.php ke basis data MySQL, lalu ditayangkan pada dashboard monitoring tanpa gangguan pada lapisan mana pun.

2. Perancangan basis data relasional lima tabel (users, devices, gallery, sensor_logs, actuator_logs) dengan pendekatan berpusat pada perangkat berhasil menjamin integritas data melalui kunci primer, kunci tamu beserta kebijakan on delete yang sesuai karakteristik data, serta indeks komposit pada pola akses riwayat per perangkat. Penggunaan prepared statement pada backend menjaga keamanan kueri terhadap injeksi.

3. Logika kendali berbasis histeresis terbukti mencegah aktuator berdenyut di sekitar ambang: penyiraman beroperasi pada rentang kelembapan tanah 40-55 persen dan ventilasi pada rentang suhu 31-32 derajat Celsius. Penjadwalan fertigasi satu kali per minggu pada Senin pukul 07.00 dengan durasi tujuh detik disertai interlock kelembapan tanah dan kunci persisten berhasil mencegah dosis ganda, sejalan dengan praktik penelitian terdahulu [@sari2024sistem].

4. Penalaran status tanaman dari data terukur (kelembapan tanah, suhu, dan rasio warna daun dari sensor TCS34725) memberikan penilaian kesehatan tanaman yang objektif dan tercatat, sehingga keempat kelemahan pemantauan manual (subjektivitas, keterlambatan, keterbatasan fisik, dan ketiadaan riwayat) dapat diatasi.

5. Pemodelan fisik greenhouse mini dalam FreeCAD menghasilkan prototipe berdimensi 100 x 80 x 60 cm yang seluruh komponennya berada di dalam batas dimensi tanpa pelanggaran, serta dilengkapi bill of materials dan cutting list yang menjadikan perakitan dapat direplikasi dengan belanja komponen baru sekitar Rp404.000.

## Saran

Untuk pengembangan selanjutnya, beberapa hal berikut layak dievaluasi. Pertama, penambahan mekanisme penyangga data (offline buffer) pada perangkat agar pengiriman tidak terputus saat jaringan bermasalah, mengingat buffer lokal sejenis telah terbukti efektif pada sistem sejenis. Kedua, pemanfaatan mode deep sleep ESP32 pada malam hari untuk menghemat konsumsi daya, khususnya jika prototipe dikembangkan dengan catu daya surya. Ketiga, penambahan sensor pH tanah dan kelembapan media yang lebih presisi untuk memperkaya kualitas keputusan fertigasi, mengingat penelitian terdahulu menunjukkan manfaat pemantauan pH bagi kesehatan akar [@sari2024sistem]. Keempat, penerapan perhitungan kebutuhan air tanaman berbasis evapotranspirasi [@allen1998fao] sebagai pengganti ambang tetap sehingga jadwal penyiraman menyesuaikan kondisi musim. Kelima, pengembangan lapisan analitik lebih lanjut berupa klasifikasi penyakit daun berbasis pembelajaran mesin dari data citra galeri, yang akan mengubah status tanaman dari penalaran ambang menjadi diagnosis spesifik [@chandan2022monitoring]. Terakhir, sistem layak dikembangkan menjadi greenhouse multi-perangkat dengan penambahan tabel agregasi dan dashboard perbandingan antarunit guna mendukung percobaan budidaya skala lebih besar.

# DAFTAR PUSTAKA {-}

::: {#refs}
:::

# LAMPIRAN 1: DAFTAR ANGGOTA KELOMPOK DAN MATRIKS KONTRIBUSI {-}

Kelompok 3 (Golongan B) terdiri atas enam anggota yang membagi tugas sesuai bidang keahlian masing-masing sejak tahap perancangan hingga pengujian sistem. Matriks kontribusi tiap anggota dirangkum pada tabel berikut.

| No. | Nama | NIM | Pembagian Tugas |
|---|---|---|---|
| 1. | Yuda Maredya Ari Pratama | E43252953 | Database: perancangan skema MySQL lima tabel, kamus data, dan pengelolaan basis data melalui DBeaver |
| 2. | Abiyu Ammar Hady | E43252782 | Hardware: perakitan greenhouse mini, pemasangan sensor dan modul relay, serta penataan catu daya |
| 3. | Ibror Yusron S | E43252690 | Firmware: pengembangan firmware ESP32 meliputi akuisisi sensor, logika histeresis, dan fertigasi terjadwal |
| 4. | Moch Calvin Aprilian | E43252720 | 3D CAD: pemodelan greenhouse mini pada FreeCAD beserta ekspor berkas STEP dan STL |
| 5. | Nabil Laudza Nur | E43252939 | Frontend: pembangunan dashboard web real-time beserta endpoint data live.php |
| 6. | Tio Dwi Harianto | E43252983 | Dokumentasi: penyusunan laporan, panduan perakitan, dan pengelolaan repositori proyek |

```{=typst}
#pagebreak()
```

# LAMPIRAN 2: REPOSITORI KODE SUMBER DAN AKSES BERKAS DIGITAL {-}

Seluruh kode sumber dan berkas pendukung proyek SISTOMATE didistribusikan sebagai proyek open-source dan dapat diakses secara publik melalui repositori GitHub berikut.

[https://github.com/KoJYy/sistomate](https://github.com/KoJYy/sistomate)

Struktur direktori proyek pada repositori tersebut dirangkum sebagai berikut.

```
sistomate/
├── sistomate-firmware/
│   ├── SISTOMATE/SISTOMATE.ino   Firmware ESP32
│   └── ingest.php                Backend ingest PHP
├── dashboard/
│   ├── index.html                Dashboard web real-time
│   └── live.php                  Endpoint data JSON
├── sql/schema.sql                Skema basis data MySQL
├── docs/cad/
│   ├── greenhouse.FCStd          Model 3D asli FreeCAD
│   ├── greenhouse.step           Ekspor STEP (CAD)
│   └── greenhouse.stl            Ekspor STL (cetak 3D)
├── gambar/                       Diagram sistem (PNG/SVG)
└── laporan-final/                Sumber laporan ini (Markdown)
```

Firmware ESP32 pada berkas SISTOMATE.ino memuat akuisisi tiga sensor, logika histeresis dan fertigasi, serta pengiriman payload JSON, sedangkan ingest.php memuat autentikasi X-API-Key dan prepared statement PDO. Halaman index.html beserta live.php menyediakan antarmuka dashboard web real-time dengan pemanggilan data JSON berkala.

Melalui repositori tersebut, seluruh source code perangkat lunak SISTOMATE, yaitu firmware ESP32, backend ingest berbasis PHP, dan dashboard web real-time, dapat diakses, dipelajari, dan dikembangkan lebih lanjut oleh publik. Berkas pemodelan tiga dimensi berupa model asli FreeCAD beserta ekspor STEP dan STL tersedia untuk diunduh guna keperluan replikasi fisik atau pencetak tiga dimensi, sedangkan skema basis data tersedia pada berkas sql/schema.sql sehingga dapat langsung dibuat pada server MySQL mana pun. Dengan ketersediaan seluruh berkas tersebut secara terbuka, diharapkan proyek SISTOMATE dapat direplikasi dan dikembangkan oleh pihak lain, khususnya dalam rangka penerapan pertanian presisi skala kecil berbasis Internet of Things dan basis data relasional.
