# PENDAHULUAN

## Latar Belakang

Tomat (*Solanum lycopersicum*) adalah salah satu komoditas hortikultura paling penting di Indonesia sebagai sumber vitamin bagi jutaan rumah tangga. Akan tetapi, produksinya rentan terhadap fluktuasi mikroklima: suhu udara yang terlalu tinggi memicu penguapan berlebih dan stress pada tanaman, kelembapan udara yang terlalu lembap membuka peluang serangan patogen, sedangkan kelembapan tanah yang tidak terjaga membuat tanaman mengalami layu fisiologis bahkan kematian dalam hitungan jam [@tzounis2017iotagri]. Greenhouse atau rumah kaca hadir sebagai solusi pengendalian mikroklima tersebut, namun keunggulannya baru benar-benar dirasakan jika parameter lingkungan dipantau dan ditindaklanjuti secara konsisten.

Di lapangan, pemantauan kesehatan tanaman tomat di greenhouse umumnya masih mengandalkan pengamatan visual manual oleh pekebun, dan cara ini memiliki empat masalah utama. Pertama, penilaian sangat subjektif karena sangat bergantung pada bias pengamatan visual: mata manusia mudah tertipu kondisi pencahayaan, sehingga satu pekebun dapat melihat daun tomat "masih hijau sehat" sementara pekebun lain menilainya "mulai kekuningan" pada daun dan waktu yang sama; tanpa standar ukur yang pasti, hasil penilaian dua pekebun tidak pernah benar-benar dapat dibandingkan. Kedua, terjadi keterlambatan penanganan karena mata manusia biasanya baru menyadari tanaman kekurangan nutrisi ketika warna daun sudah berubah drastis menjadi kuning pucat atau coklat, dan pada saat gejala sedemikian jelas terlihat, tanaman sudah terlanjur sakit parah sehingga panen menurun. Ketiga, terdapat keterbatasan fisik yang mengharuskan pekebun berjalan keliling greenhouse setiap hari untuk memeriksa ribuan tanaman satu per satu, pekerjaan yang melelahkan sekaligus rawan terlewat pada bagian-bagian tertentu. Keempat, tidak ada catatan historis karena riwayat kesehatan tanaman minggu lalu hanya diandalkan pada ingatan pekebun, sehingga sulit mengevaluasi apakah efektivitas pupuk, jadwal penyiraman, atau penyesuaian ventilasi benar-benar membaik dari minggu ke minggu.

Kemajuan Internet of Things (IoT) membuka jalan untuk mengatasi keempat masalah tersebut. Arsitektur IoT konvensional tersusun atas perangkat persepsi berupa sensor dan aktuator, lapisan komunikasi, penyimpanan data, serta lapisan aplikasi yang menyajikan informasi kepada pengguna [@alfuqaha2015iot]. Pada sektor pertanian, penerapannya dikenal dengan istilah smart greenhouse: greenhouse yang dilengkapi sensor untuk mengukur parameter lingkungan, aktuator untuk menindaklanjuti, serta basis data dan dashboard untuk dokumentasi serta pengambilan keputusan [@tzounis2017iotagri]. Sejumlah penelitian terdahulu telah membangun pola serupa, misalnya greenhouse cerdas dengan penyiraman otomatis berbasis ESP32 [@rifanie2026greenhouse] dan sistem kendali penyiraman taman berbasis IoT [@setiaji2024penyiraman]; namun sebagian besar berhenti pada pemantauan parameter semata tanpa menyertakan penilaian kesehatan tanaman yang objektif.

Untuk mengatasi keempat masalah di atas, dirancang SISTOMATE (Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat Berbasis Internet of Things dan Integrasi Basis Data Relasional). Jawaban atas masalah subjektivitas penilaian disediakan oleh sensor warna RGB TCS34725 yang membaca komposisi spektral cahaya yang dipantulkan daun, sehingga status daun diturunkan dari angka rasio kanal warna yang terukur dan sama untuk setiap pengamat [@ams2014tcs34725]. Jawaban atas masalah keterlambatan penanganan disediakan oleh sensor suhu dan kelembapan udara DHT11 [@aosong2011dht11] serta sensor kelembapan tanah kapasitif [@dfrobot2021sen0193], yang nilainya dibaca terus-menerus oleh mikrokontroler ESP32 dan langsung ditindaklanjuti oleh aktuator berupa pompa air untuk penyiraman dan kipas exhaust untuk ventilasi melalui modul relay [@espressif2021esp32]. Jawaban atas masalah keterbatasan fisik disediakan oleh dashboard web real-time yang membuat pekebun cukup melihat layar untuk memantau seluruh kondisi greenhouse. Jawaban atas masalah ketiadaan catatan historis disediakan oleh basis data relasional MySQL yang menyimpan seluruh pembacaan sensor dan transisi aktuator sebagai riwayat panjang yang dapat ditelusuri kapan pun, dikelola melalui antarmuka grafis DBeaver yang terhubung ke server MySQL pada laptop lokal [@silberschatz2020database].

SISTOMATE membedakan diri dari penelitian terdahulu yang umumnya hanya mengatur penyiraman: sistem ini menggabungkan kendali histeresis penyiraman, kendali ventilasi terjadwal, penjadwalan fertigasi dengan interlock keamanan, serta penilaian kesehatan tanaman dari warna daun dalam satu prototipe utuh yang seluruh aktivitasnya tercatat dalam basis data [@sari2024sistem]. Greenhouse mini berdimensi 100 x 80 x 60 cm dirancang sebagai lingkungan uji prototipe fisik, sedangkan basis data lima tabel beserta backend dan antarmuka web menjadi inti kajian laporan proyek bersama ini.

## Rumusan Masalah

Berdasarkan latar belakang di atas, rumusan masalah dalam perancangan dan implementasi SISTOMATE adalah sebagai berikut.

1. Bagaimana mengatasi sifat subjektif dan bias pengamatan visual pekebun dalam menilai kesehatan daun tomat, sehingga penilaian menjadi objektif dan dapat dipertanggungjawabkan?
2. Bagaimana mengatasi keterlambatan penanganan tanaman tomat yang selama ini baru disadari ketika gejala pada daun sudah berat, sehingga tindakan penyiraman dan ventilasi dapat berjalan sejak kondisi mulai menyimpang?
3. Bagaimana mengatasi keterbatasan fisik pekebun yang harus memeriksa tanaman satu per satu setiap hari, sehingga pemantauan dapat dilakukan dari satu tempat secara menyeluruh?
4. Bagaimana mengatasi ketiadaan catatan historis kesehatan tanaman, sehingga riwayat kondisi dan efektivitas tindakan (pupuk, penyiraman, ventilasi) tersimpan rapi dan dapat dievaluasi?
5. Bagaimana merancang basis data relasional dan sistem tertanam ESP32 yang menghubungkan keseluruhan jawaban atas keempat masalah tersebut menjadi satu sistem utuh dari sensor hingga dashboard?

## Tujuan dan Manfaat

### Tujuan Umum

Tujuan umum perancangan ini adalah membangun prototipe SISTOMATE berupa sistem otomasi penyiraman dan monitoring kesehatan tanaman tomat berbasis Internet of Things dengan integrasi basis data relasional, yang mampu mengukur kondisi lingkungan dan kesehatan tanaman secara objektif, menjalankan penyiraman serta ventilasi secara otomatis, dan menyeluruhkan seluruh aktivitasnya dalam basis data yang dapat ditelusuri.

### Tujuan Khusus

1. Membangun penilaian kesehatan daun tomat yang objektif berdasarkan komponen warna RGB hasil pembacaan sensor TCS34725, guna menggantikan penilaian visual subjektif pekebun.
2. Mewujudkan penanganan yang cepat melalui pembacaan berkelanjutan sensor DHT11 dan sensor kelembapan tanah kapasitif yang langsung menggerakkan aktuator pompa dan kipas berdasarkan logika histeresis, guna menghindari keterlambatan penanganan.
3. Menyediakan dashboard web real-time sebagai satu titik pemantauan sehingga pekebun tidak lagi terbatas pada pemeriksaan fisik tanaman satu per satu.
4. Merancang basis data relasional MySQL lima tabel (users, devices, gallery, sensor_logs, actuator_logs) yang lengkap dengan kunci primer, kunci tamu, dan indeks pendukung, guna menghasilkan catatan historis yang rapi dan dapat dievaluasi, dikelola melalui GUI DBeaver pada server laptop lokal.
5. Mengintegrasikan seluruh komponen melalui firmware ESP32 dan backend ingest, lalu menguji fungsionalitas sistem utuh dari akuisisi sensor hingga penayangan dashboard.

### Manfaat

Manfaat teoretis laporan ini adalah menyediakan rujukan perancangan basis data relasional untuk sistem pertanian presisi skala kecil yang menggabungkan sensor lingkungan, sensor warna tanaman, dan aktuator dalam satu kerangka data. Manfaat praktis laporan ini adalah tersedianya acuan sistem pemantauan dan otomasi greenhouse mini yang terjangkau, mudah direplikasi di lingkungan kampus, serta dapat dikembangkan lebih lanjut menjadi sistem budidaya tomat skala produksi kecil.

## Batasan Masalah

Agar perancangan tetap fokus dan terukur, ruang lingkup laporan ini dibatasi sebagai berikut.

1. Prototipe greenhouse mini berdimensi 100 x 80 x 60 cm dengan rangka kayu dan busur pipa, ditanami satu pot tomat sebagai lingkungan uji, bukan greenhouse skala lahan produksi.
2. Perangkat kendali menggunakan satu modul ESP32 dengan tiga sensor, yaitu kelembapan tanah kapasitif, suhu dan kelembapan udara DHT11, serta warna RGB daun TCS34725; kamera, sensor pH tanah, dan sensor cahaya tidak dibahas.
3. Aktuator dibatasi pada empat kanal relay: pompa air, pompa pupuk untuk fertigasi, kipas exhaust, dan satu kanal cadangan yang belum dipakai.
4. Basis data menggunakan MySQL yang dikelola melalui GUI DBeaver pada server laptop lokal dengan lima tabel hasil perancangan sendiri; sistem manajemen basis data lain dan replikasi tidak dibahas.
5. Lapisan aplikasi dibatasi pada backend ingest berbasis PHP dan dashboard web yang memanggil endpoint data; pengembangan aplikasi mobile dan pembelajaran mesin berada di luar lingkup laporan.
6. Kendali aktuator menggunakan logika on-off berbasis histeresis, bukan pengendali PID atau model prediktif.

## Struktur Laporan

Laporan ini disusun dalam empat bab. Bab I (Pendahuluan) memaparkan latar belakang yang berpusat pada empat masalah utama petani tomat, rumusan masalah, tujuan, manfaat, dan batasan masalah. Bab II (Tinjauan Pustaka) membahas landasan teori yang menopang sistem, meliputi konsep greenhouse cerdas dan IoT, mikrokontroler ESP32, prinsip kerja sensor yang digunakan, logika histeresis, fertigasi tanaman tomat, serta konsep basis data relasional. Bab III (Hasil dan Pembahasan) menyajikan hasil perancangan dan implementasi sistem, meliputi arsitektur empat lapis, perancangan basis data lengkap dengan kamus data dan pemetaan relasi, firmware dan logika kendali, antarmuka dashboard web real-time, implementasi perangkat lunak beserta listing program, pemodelan fisik tiga dimensi beserta bill of materials, dan hasil pengujian fungsional. Bab IV (Penutup) memuat kesimpulan perancangan dan saran pengembangan prototipe selanjutnya, dilengkapi daftar pustaka dan lampiran daftar anggota kelompok.
