# Rencana Prototipe SISTOMATE (Mini Greenhouse)

> Hasil evaluasi pasca-demo 22 Okt 2026. Sumber: diskusi desain fisik rig.
> Elektronik & firmware udah teruji end-to-end (demo aman) — ini soal **bentuk fisiknya**.

## Keputusan Desain

| Komponen | Keputusan | Alasan |
|---|---|---|
| Bentuk & tinggi | **Tunnel busur (setengah lingkaran segmental), tinggi maks 60 cm**, footprint ±100×80 cm | Busur = bentuk paling optimal buat aliran air embun (turun lengkung, gak netes ke daun). Segmental (kurang dari setengah lingkaran penuh) biar sisi miring ke bawah & tetap mini |
| Rangka pondasi | **Hibrida: kayu (dasar + dinding ujung) + pipa busur fleksibel** | Dasar & dinding ujung tetap kayu (gampang disekrup). Busur ditekuk dari **pipa PP 1/2" atau paralon (PVC conduit)** — fleksibel tapi kuat, murah, gampang dicari |
| Dinding | **Plastik bening FLEKSIBEL (terpal/PE bening)** — bukan mika rigid | Busur butuh selimut yang bisa ditekuk; mika rigid gak bisa ditekuk. Plastik fleksibel ditarik di atas busur, diklem |
| Kipas exhaust | **80mm (8cm), 12V** (atau 60mm 5V opsional) | 80mm aliran udara lega buat box kecil; 5V 60mm cukup juga (~20-30 CFM) |
| Pompa | **Punya sendiri, submersible 5V** — udah dites idup kenceng pake adaptor charger HP | Gak usah beli. Outlets **6mm** |
| Selang | **Silicone ID 6mm**, ~2m + 1m cadangan | Press-fit ke outlet 6mm tanpa adapter. Kalau murah → PVC bening boleh, tapi gampang kempis |
| Aksesories | Kabel ties (nyekruk selang ke rangka), dripper/nipple opsional | ~5rb/100pc |
| **Relay** | **2× modul relay 2-ch (total 4 kanal, aktif-LOW)** — pengganti opsi 1-ch | CH1 Pompa Air (GPIO26) · CH2 Kipas (GPIO27) · CH3 Pompa Pupuk (GPIO14) · CH4 cadangan (mis. lampu). Modul ke-2 = beli 1 unit (1 udah ada) |

## Catatan hari ini (22 Okt)

- ✅ Demo **mulus semua** — tinggal bikin prototype fisik
- Pompa celup udah ada & 5V → colok adaptor charger HP langsung kenceng
- Sisa belanja: kipas 80mm, **rangka busur (pipa fleksibel) + plastik bening fleksibel** (lihat "Belanja Belum Dibeli"), kayu dasar, selang silicone 6mm, bracket rel kecil

## Arsitektur Daya & Wiring

⚠️ **JANGAN colok pompa/kipas ke pin 5V ESP32** — arus beban (200mA–1A) melebihi supply ESP32 (~500mA) → hang/restart.

```
Modul Relay A (2-ch, udah ada)      Modul Relay B (2-ch, beli 1)
  CH1 IN ← GPIO26  Pompa Air         CH1 IN ← GPIO27  Kipas Exhaust
  CH2 IN ← GPIO14  Pompa Pupuk       CH2 IN ← GPIO25  cadangan
        │                                   │
  VCC ← 5V, GND ←──────── common GND ──────┘ (sama dgn GND ESP32 + adaptor)

Adaptor 5V ──┬── COM modul A (dua pompa se-jalur 5V)
             ├── NO CH1-A ─── pompa air +
             └── NO CH2-A ─── pompa pupuk +
kipas/adc:  COM modul B ← sumber sesuai spek, NO CH1-B → kipas +
pompa/kipas − ── GND adaptor (common ke ESP32)
```

- **2× modul 2-ch, aktif-LOW** — dua pompa SE-modul (A) biar supply 5V satu jalur; kipas+cadangan di modul B.
- Ground adaptor beban **wajib nyambung** ke ground ESP32 (common ground antar semua modul).
- Modul relay driver udah punya flyback diode internal — gak perlu tambah.

**★ Insight:** catu daya terpisah + common ground = relay cuma "ganti" arus besar dari adaptor; ESP32 cuma ngirim sinyal 3.3V ke kumparan relay. Modul A tetep di posisi lama → firmware pompa/kipas gak usah disentuh, cuma nambah kanal baru.

## Layout & Penempatan Sensor

```
   [kipas 80mm dinding atas belakang]
┌─────────────────────────┐ ← plastik bening fleksibel (selimut busur)
│                         │
│   [TCS]┐   ┌[DHT]       │  TCS: bracket dari rangka atas,
│         ╲ ╱             │       jarak 3–5cm ke permukaan daun
│        DAUN             │  DHT: pojok depan-atas,
│         │               │       diagonal dari kipas
│    ┌────┴────┐          │  Soil: media tanah, arah mendatar,
│    │ pot+soil│ ← probe  │       kedalaman 3–5cm, gak nempel dasar
│    └─────────┘          │
└─────────────────────────┘
     [selang masuk bawah] → drip ke pot
```

### Aturan penempatan (pelajaran dari gladi)

1. **Soil moisture** — dicolok **mendatar/nyaris miring**, kedalaman ±3–5cm.
   Jangan tegak nempel dasar pot → air ngendap = kebaca "selalu basah".
2. **DHT11** — pojok depan-atas, sejajar tinggi daun, **diagonal/berlawanan sama kipas exhaust**.
   Gladi kemarin: kipas nembak langsung → suhu gak stabil. Jauhin dari tetesan selang.
3. **TCS34725** — bracket di rangka atas, ngadep ke bawah, jarak 3–5cm dari titik paling lebar daun.
   Bracket agak tinggi biar ada ruang tumbuh.

### Pemisahan elektronik

- **ESP32 & kabel di LUAR** greenhouse — lubang tembus kecil di dinding belakang bawah.
- Elektronik gak boleh masuk area basah → konsleting kalau kena spray/percikan.
- **Pompa & reservoir juga di luar**, cuma selang yang tembus dinding.

## Belanja Belum Dibeli

> Disesuaikan bentuk tunnel busur (fleksibel tapi kuat). Rangka dasar + dinding ujung tetap kayu; busur & selimut ganti bahan fleksibel.

**Rangka & selimut (busur):**
- [ ] **Pipa busur** — PVC/paralon 1/2" atau pipa PP (fleksibel tapi kuat), ±4–6 batang buat ditekuk jadi lengkung (±100 cm tiap busur)
- [ ] **Plastik bening FLEKSIBEL** (terpal/PE bening) — selimut busur, ±1.5× luas selimut (busur + sisi). **BUKAN mika rigid** (gak bisa ditekuk)

**Kayu (dasar & dinding ujung):**
- [ ] Kayu rangka dasar (kayu bekas/sisa dulu) — penyangga ujung pipa busur + dinding depan/belakang

**Elektronik & fluida:**
- [ ] Kipas exhaust 80mm (12V; alternatif 60mm 5V)
- [ ] **Modul relay 2-ch unit ke-2** (2 relay 4-kanal total; unit ke-1 udah ada)
- [ ] Selang silicone ID 6mm (2m + 1m cadangan)
- [ ] Klem/klip penyangga busur + bracket rel kecil (kayu/3D print/bekas klip) buat TCS + DHT
- [ ] **Adaptor 5V** catu beban (kalau belum ada) — pompa/kipas dari adaptor, BUKAN pin ESP32

## Keputusan yang Sudah Final

Semua item di bawah **udah diputusin** — tinggal eksekusi rakit. (Bagian ini tadinya "Belum Diputusin", sekarang gak ada yang pending lagi.)

### Bentuk & dimensi greenhouse (26 Okt)

| Aspek | Nilai | Dasar |
|---|---|---|
| Bentuk | **Tunnel busur (setengah lingkaran segmental)** | Aliran air embun paling optimal (turun lengkung, gak netes ke daun). Rangka ditekuk dari pipa **fleksibel tapi kuat** |
| Tinggi maks | **60 cm** | Cukup ruang tumbuh buat 1 pot + bracket TCS di puncak busur; gak kebesaran |
| Footprint | **±100 × 80 cm** | Segmental (bukan setengah lingkaran penuh) biar tetap mini, sisi miring ke bawah |
| Rangka | **Hibrida** — kayu (dasar + dinding ujung) + pipa busur PVC/PP fleksibel | Dasar kayu gampang disekrup; busur ditekuk dari pipa |

### Parameter fertigasi (22 Okt, ngikut Sari 2024 / JITET — sumber yang udah dicite di Bab 5, bukan asal)

| Parameter | Nilai | Dasar |
|---|---|---|
| Jadwal | **1×/minggu, jam 07.00** | Sari 2024: jadwal RTC mingguan; jam pagi biar gak penguapan siang. Waktu kita NTP (bukan RTC hardware — WiFi selalu ada) |
| Durasi ON | **7 detik per dosis** | Sari 2024 persis (durasi 7 detik/minggu) |
| Interlock | **soil ≥ 40%** baru pupuk boleh ON | Bab 5 syarat pengaman; 40% = ambang ON pompa air, tanah gak lagi "kering" |
| Pupuk | larutan cair (AB mix) di reservoir terpisah | pola 2-pompa terpisah |
