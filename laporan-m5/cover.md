# KATA PENGANTAR {-}

Puji syukur kehadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga laporan praktikum yang berjudul **"Implementasi Data Definition Language (DDL) pada Basis Data Penjualan"** dapat diselesaikan dengan baik dan tepat waktu.

Laporan ini disusun untuk memenuhi tugas individu mata kuliah Workshop Basis Data (Minggu Ke-5 / Acara 17: Data Definition Language) pada Program Studi Teknik Rekayasa Komputer, Jurusan Teknologi Informasi. Laporan ini membahas implementasi perintah DDL `CREATE DATABASE` dan `CREATE TABLE` sesuai prosedur kerja Acara 17, mencakup empat tabel: `Distributor`, `Pembeli`, `Master_barang`, dan `trans_pembelian`.

Penulis mengucapkan terima kasih yang sebesar-besarnya kepada Ibu Zilvanhisna Emka Fitri, S.T., M.T. dan Ibu Nanik Anita Mukhlisoh, S.ST., M.T. selaku dosen pengampu mata kuliah Workshop Basis Data atas bimbingan dan arahannya selama penyusunan laporan ini.

Penulis menyadari bahwa laporan ini masih jauh dari sempurna. Oleh karena itu, penulis sangat mengharapkan kritik dan saran yang membangun dari semua pihak. Akhir kata, penulis berharap laporan ini dapat memberikan manfaat bagi para pembaca.

```{=typst}
#v(0.8cm)
#align(right)[
  September 2026

  #v(1cm)
  Penulis
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
<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:before="567"/></w:pPr><w:r><w:t>Penulis</w:t></w:r></w:p>
```
