# PENDAHULUAN

## Latar Belakang

Monitoring kesehatan tanaman tomat di greenhouse saat ini masih mengandalkan pengamatan visual secara manual oleh pekebun, dan cara ini memiliki empat kelemahan utama. Pertama, penilaian sangat subjektif (bias) karena mata manusia mudah tertipu pencahayaan: Pekerja A mungkin melihat daun tomat "masih hijau sehat", sementara Pekerja B melihatnya "mulai kekuningan", sehingga tidak ada standar ukur yang pasti. Kedua, terjadi keterlambatan penanganan karena mata manusia biasanya baru menyadari tanaman kekurangan nutrisi saat warna daun sudah berubah drastis (kuning pucat atau coklat), dan saat itu terjadi tanaman sudah terlanjur sakit parah. Ketiga, keterbatasan fisik mengharuskan pekebun berjalan keliling greenhouse setiap hari untuk mengecek ribuan tanaman satu per satu. Keempat, tidak ada catatan historis karena pekebun tidak punya data riwayat kesehatan tanaman minggu lalu dan semuanya hanya diandalkan pada ingatan, sehingga sulit mengevaluasi efektivitas pupuk.

Untuk mengatasi permasalahan tersebut, dirancang SISTOMATE, yaitu Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat. Sistem ini menggunakan perangkat ESP32 yang dilengkapi sensor suhu dan kelembapan udara, sensor kelembapan tanah, serta sensor warna RGB daun untuk memantau kondisi tanaman secara objektif dan tercatat. Kondisi visual tanaman didokumentasikan melalui webcam ke dalam galeri citra, sedangkan tindakan penyiraman dicatat sebagai log aktuator. Seluruh data pengguna, perangkat, galeri citra, log sensor, dan log aktuator dikelola dalam sebuah basis data relasional yang terstruktur, sehingga riwayat kondisi tanaman dapat dilacak dan dievaluasi dari waktu ke waktu [@date2019database; @silberschatz2020database].

## Rumusan Masalah

Berdasarkan latar belakang di atas, rumusan masalah dalam perancangan basis data ini adalah:

1. Bagaimana merancang struktur tabel master untuk menyimpan informasi pengguna (Users) dan perangkat (Devices) beserta relasi kepemilikannya secara efisien?
2. Bagaimana merancang struktur tabel pencatatan untuk menyimpan log sensor (Sensor Logs) yang mencakup suhu, kelembapan, kelembapan tanah, nilai RGB daun, dan status tanaman dari perangkat ESP32?
3. Bagaimana merancang struktur tabel untuk menyimpan galeri citra tanaman (Gallery) dan log aksi aktuator (Actuator Logs) yang terhubung ke perangkat secara akurat?

## Tujuan

Tujuan dari perancangan basis data ini adalah:

1. Menghasilkan struktur tabel entitas utama (tabel Users dan Devices) lengkap dengan penentuan tipe data dan panjang karakter yang optimal.
2. Membangun tabel Sensor Logs untuk mencatat riwayat pembacaan sensor perangkat, termasuk nilai warna RGB daun dan status kesehatan tanaman.
3. Membangun tabel Gallery dan tabel Actuator Logs untuk mendokumentasikan citra tanaman dari webcam dan mencatat riwayat aksi penyiraman perangkat.
