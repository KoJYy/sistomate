# KATA PENGANTAR {-}

Puji syukur kehadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga laporan yang berjudul **"Perancangan Basis Data Relasional SISTOMATE (Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat)"** dapat diselesaikan dengan baik dan tepat waktu.

Laporan ini disusun untuk memenuhi tugas mata kuliah Workshop Basis Data pada Program Studi Teknik Rekayasa Komputer, Jurusan Teknologi Informasi. Laporan ini membahas perancangan basis data relasional untuk SISTOMATE, yaitu Sistem Otomasi Penyiraman dan Monitoring Kesehatan Tanaman Tomat di greenhouse, yang mencakup pengelolaan data pengguna, perangkat ESP32, galeri citra tanaman dari webcam, log pembacaan sensor (suhu, kelembapan, kelembapan tanah, warna RGB daun, status tanaman), serta log aksi aktuator penyiraman.

Penulis mengucapkan terima kasih yang sebesar-besarnya kepada Ibu Zilvanhisna Emka Fitri, S.T., M.T. dan Ibu Nanik Anita Mukhlisoh, S.ST., M.T. selaku dosen pengampu mata kuliah Workshop Basis Data atas bimbingan dan arahannya selama penyusunan laporan ini, serta kepada rekan-rekan mahasiswa yang telah saling membantu dalam memahami materi perancangan basis data.

Penulis menyadari bahwa laporan ini masih jauh dari sempurna. Oleh karena itu, penulis sangat mengharapkan kritik dan saran yang membangun dari semua pihak. Akhir kata, penulis berharap laporan ini dapat memberikan manfaat bagi para pembaca.

```{=typst}
#v(0.8cm)
#align(right)[
  September 2026

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
<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:before="454"/></w:pPr><w:r><w:t>September 2026</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:before="567"/></w:pPr><w:r><w:t>Tim Penyusun</w:t></w:r></w:p>
```
