# KATA PENGANTAR {-}

Puji syukur kehadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga laporan yang berjudul **"SISTOMATE: Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat Berbasis Internet of Things dan Integrasi Basis Data Relasional"** dapat diselesaikan dengan baik dan tepat waktu.

Laporan ini disusun sebagai laporan proyek bersama dua mata kuliah, yaitu Workshop Basis Data dan Workshop Sistem Tertanam. Fokus kajian laporan adalah SISTOMATE, yaitu sistem otomasi penyiraman dan monitoring kesehatan tanaman tomat berbasis Internet of Things yang diintegrasikan dengan basis data relasional. Seluruh pembacaan sensor, transisi aktuator, dan dokumentasi kondisi tanaman dicatat dalam basis data MySQL berisi lima tabel, kemudian ditayangkan kembali melalui dashboard web real-time. Greenhouse mini berukuran 100 x 80 x 60 cm yang dirancang bersama panduan perakitannya hanyalah lingkungan uji (testbed) prototipe fisik, bukan fokus kajian utama laporan ini.

Melalui mata kuliah Workshop Basis Data, laporan ini memuat perancangan skema basis data relasional, kamus data tiap tabel, pemetaan relasi dan foreign key, hingga implementasi backend berbasis PHP dan MySQL. Melalui mata kuliah Workshop Sistem Tertanam, laporan ini memuat perancangan firmware mikrokontroler ESP32, pemetaan antarmuka sensor dan aktuator, serta logika kendali histeresis dan penjadwalan fertigasi. Kedua sisi mata kuliah tersebut dihubungkan oleh kontrak data berformat JSON yang dikirim perangkat tertanam ke backend, sehingga sistem tertanam dan basis data bekerja sebagai satu kesatuan sistem yang utuh.

Penulis mengucapkan terima kasih yang sebesar-besarnya kepada ketiga dosen pengajar, yaitu Ibu Zilvanhisna Emka Fitri, S.T., M.T., Ibu Nanik Anita Mukhlisoh, S.ST., M.T., dan Bapak Yogiswara, S.T., M.T., atas bimbingan, arahan, dan masukan yang membangun pada mata kuliah Workshop Basis Data dan Workshop Sistem Tertanam selama penyusunan laporan ini. Ucapan terima kasih juga disampaikan kepada seluruh rekan satu tim, Kelompok 3 Golongan B, yang telah bekerja sama mulai dari perancangan skema basis data, pemrograman firmware, perakitan fisik greenhouse, hingga pengujian sistem.

Penulis menyadari bahwa laporan ini masih jauh dari sempurna. Oleh karena itu, penulis sangat mengharapkan kritik dan saran yang membangun dari semua pihak. Akhir kata, penulis berharap laporan ini dapat memberikan manfaat bagi para pembaca, khususnya bagi pengembangan sistem pertanian presisi skala kecil yang terhubung dengan basis data di lingkungan kampus.

```{=typst}
#v(0.8cm)
#align(right)[
  Oktober 2026

  #v(1cm)
  Tim Penyusun
]
#pagebreak()
#outline(
  title: [#align(center)[#text(size: 14pt, weight: "bold")[DAFTAR ISI]]],
  depth: 3,
)
#pagebreak()
#set page(numbering: "1")
#counter(page).update(1)
```

```{=openxml}
<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:before="454"/></w:pPr><w:r><w:t>Oktober 2026</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:before="567"/></w:pPr><w:r><w:t>Tim Penyusun</w:t></w:r></w:p>
```
