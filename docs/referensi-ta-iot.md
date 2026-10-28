# Referensi TA — IoT Monitoring (Sensor + Dashboard)

Sumber: Tavily via 9Router (tunnel `127.0.0.1:20128`). Diambil 2026-08-28.

## Repositori Digital (.ac.id — PDF full text)
1. **Smart Power Outlet + Dashboard Monitoring Listrik (IoT)** — Politeknik Negeri Bali, 2025
   `https://repository.pnb.ac.id/id/eprint/21682`
2. **Sistem Kontrol Penstabil Suhu Berbasis IoT** — UNP Kediri
   `https://repository.unpkediri.ac.id/20550/3/RAMA_20401_2223050019_0730128701_0702108901_01_Front_Ref.pdf`
3. **Sistem Pemantauan Cerdas Berbasis IoT (tekanan & flow real-time)** — Prosiding PNJ, 2026
   `https://prosiding.pnj.ac.id/index.php/sntm/article/view/5102`
4. **Sistem Pemantauan Listrik Berbasis IoT + proteksi otomatis** — IPB, 2026
   `https://repository.ipb.ac.id/handle/123456789/176971?show=full`
5. **Monitoring & Kontrol Beban PLTS Berbasis IoT** — Telkom University
   `https://repository.telkomuniversity.ac.id/pustaka/242595/`
6. **Monitoring & Kontrol Listrik + Prediksi Tagihan (LSTM)** — ResearchGate
   `https://www.researchgate.net/publication/400410164_`
7. **Monitoring Ketinggian Air Bendungan Berbasis IoT** — Politeknik Negeri Ujung Pandang
   `https://repository.poliupg.ac.id/id/eprint/12685/2/`
8. **Monitoring Lingkungan (CO, NO2, PM2.5, Kelembapan) real-time** — Undip, 2025
   `https://eprints2.undip.ac.id/id/eprint/39263`
9. **Monitoring Arus Listrik Rumah (R&D)** — Unhas, 2024
   `https://repository.unhas.ac.id/42712/2/`

## Arsitektur Umum (pola berulang di TA di atas)
MCU (ESP32 / NodeMCU ESP8266) → WiFi → MQTT/broker → database → dashboard web.
Contoh: UNRAM = NodeMCU ESP8266 + MQTT + MongoDB + Node-RED dashboard.

## Catatan Infra
- VPS `20.205.12.176` hidup; kampus blokir port :20128 publik → lewat tunnel SSH `9router-tunnel` (`127.0.0.1:20128`).
- Tavily MCP: URL di `~/mcp-tavily.py` di-point ke `127.0.0.1:20128`; framing stdio MCP (Content-Length) sudah benar.
- Provider aktif di 9Router: `tavily` saja (google-pse/brave gak ada kredensial).

## Greenhouse / Penyiraman + Deteksi Tanaman (diambil 2026-10-21, Tavily)
> Disaring buat SISTOMATE (ESP32 + soil/DHT/warna daun + relay pompa/kipas + dashboard web + AI).

### Tier 1 — pola sama persis (penyiraman otomatis + monitoring web + ESP32)
1. **Perancangan Sistem Greenhouse Cerdas IoT dengan Penyiraman Otomatis dan Monitoring Berbasis Web menggunakan ESP32** — Jurnal Kendali Teknik dan Sains Vol.4 No.3, 2026
   `https://ifrelresearch.org/index.php/jkts-widyakarya/article/view/6371`
2. **Smart Greenhouse Otomatis Berbasis IoT untuk Tanaman Selada** — Prosiding SENATIB, Univ. Duta Bangsa, 2025 (ESP32 + YL-69 + DHT22 + LDR + Blynk)
   `https://ojs.udb.ac.id/Senatib/article/view/5428`
3. **Rancang Bangun Smart Greenhouse Berbasis IoT** — Jurnal Expert, UBL Lampung
   `https://jurnal.ubl.ac.id/index.php/expert/article/download/4940/3269`

### Tier 2 — + deteksi kesehatan/penyakit tanaman (beda utama SISTOMATE)
4. **Perancangan Sistem Monitoring Parameter Pertumbuhan Tanaman Sawi Berbasis IoT Menggunakan ESP32 dan Kamera RGB pada Greenhouse Skala Kecil** — Politeknik Negeri Jakarta (repository)
   `https://repository.pnj.ac.id/id/eprint/39055`
5. **Akuisisi Citra Penyakit Padi Menggunakan Node Sensor IoT Berbasis ESP32-CAM** — RIGGS Journal
   `https://journal.ilmudata.co.id/index.php/RIGGS/article/view/8998`
6. **IoT based Automatic Drip Irrigation System with Plant Disease Detection** — IERT
   `https://www.ijert.org/iot-based-automatic-drip-irrigation-system-with-plant-disease-detection`
7. **Monitoring Plant Health and Detection of Plant Disease (IoT + color sensor)** — EasyChair — *paling mirip pendekatan TCS34725*
   `https://easychair.org/publications/preprint/Rrxk/open`
8. **IoT Smart Plant Monitoring, Watering and Security System (deteksi dari kesehatan daun)** — SLIIT, cited 9
   `https://rda.sliit.lk/server/api/core/bitstreams/d0d2dd5c-2193-4f78-91cc-3bbc89987cf2/content`
9. **Deep learning-driven IoT solution for smart tomato farming** — PMC, 2025, cited 21 (ESP32 + tomat!)
   `https://pmc.ncbi.nlm.nih.gov/articles/PMC12375779`
10. **Agrivision-IoT: Smart Greenhouse Automation with Disease Detection** — IJARSCT
    `https://www.ijarsct.co.in/Paper35567.pdf`

### Tier 3 — angle AI/LLM (setara Gemini API di spec)
11. **Smart AIoT Mobile App: Plant Disease Detection (MobileViT) + Environmental Monitoring + LLM chatbot** — MDPI Agriengineering 2026
    `https://www.mdpi.com/2624-7402/8/1/11`
12. **AI-IoT based smart agriculture pivot for plant diseases detection** — PMC, cited 95
    `https://pmc.ncbi.nlm.nih.gov/articles/PMC12075758`
13. **Revolutionizing agriculture with AI: plant disease detection methods and their limitations (review; tomato/chilli)** — Frontiers in Plant Science, 2024
    `https://www.frontiersin.org/journals/plant-science/articles/10.3389/fpls.2024.1356260/full`

### Skripsi / TA Indonesia
14. **Rancang Bangun Prototipe Greenhouse Cerdas Berbasis ESP32 untuk Monitoring Lingkungan dan Penyiraman Otomatis** — Rifanie, Reza Fachmi; Univ. Mercu Buana, 2026 (repository)
    `https://repository.mercubuana.ac.id/103651`
15. **Sistem Kendali dan Monitoring Penyiraman Taman Otomatis Berbasis IoT** — Diky Dwi Setiaji; Univ. Dinamika, 2024 (PDF full text)
    `https://repository.dinamika.ac.id/id/eprint/7849/5/20410200004-2024-UNIVERSITASDINAMIKA.pdf`
16. **Rancang Bangun Sistem Smart Garden Berbasis ESP32 (monitoring + penyiraman)** — JME UNM, 2026
    `https://journal.unm.ac.id/index.php/JME/article/view/12525`

## Ringkasan 3 paper paling mirip (fetch 2026-10-21)

### [1] Greenhouse Cerdas IoT — Penyiraman Otomatis + Monitoring Web (JKTS Vol.4 No.3, 2026)
- **Stack**: ESP32 + sensor kelembapan tanah kapasitif + DHT11 + relay 1-ch + pompa DC 12V · backend **Node.js + Express + SQLite** · dashboard **React + Chart.js**.
- **Logika**: histeresis soil **35–45%** (kita 40–55%), kontrol pompa manual dari web + auto mode · buffer 120 bacaan buat offline · arsip DB tiap jam.
- **Kelebihan**: VPD (Vapor Pressure Deficit) + trend analysis buat rekomendasi irigasi · metode R&D prototyping.
- **Kekurangan vs SISTOMATE**: **tidak ada deteksi kesehatan tanaman sama sekali** (tanpa sensor warna/AI) — status tanaman cuma angka parameter.

### [7] Monitoring Plant Health + Disease Detection via IoT & ML (EasyChair Preprint №7358, Chandan dkk., Jan 2022)
- **Stack**: NodeMCU (ESP8266) + DHT11 + soil moisture + rain sensor + **color sensor TCS3200** → ThingSpeak · notifikasi SMS/app.
- **Deteksi**: 2 jalur — (a) parameter sensor (suhu/lembap/**warna**) dibanding range normal, (b) citra daun tomat via **CNN VGG16** (10.895 gambar: bacterial spot / late blight / healthy), server-side HTTP base64.
- **Tanaman**: **tomat** — sama dengan demo kita.
- **Kekurangan vs SISTOMATE**: **tanpa aktuator** (gak ada pompa/kipas — monitoring + deteksi doang) · butuh server ML + dataset ribuan gambar · cloud ThingSpeak tergantung internet luar.
- **Kekurangan kita vs mereka**: mereka punya klasifikasi jenis penyakit spesifik (bacterial spot/late blight); kita cuma status sehat/kuning/stres.

### [9] Deep learning-driven IoT for smart tomato farming (Scientific Reports 15:31092, 2025, DOI 10.1038/s41598-025-15615-3, cited 21)
- **Stack**: ESP32 sensor suhu/kelembapan tanah+udara → dashboard **ThingsBoard** · Raspberry Pi + Pi Camera + **YOLOv8** klasifikasi **kematangan tomat** (green/half/full) — bukan penyakit.
- **Angka**: akurasi cuma **52.8%** (tahap awal), optimasi quantization/pruning/TensorRT +35% speed · energi 8.91 Wh/hari (ESP32) vs 78 Wh (RPi).
- **Kutipan krusial buat novelty**: *"Future work will explore … automated control of irrigation and ventilation systems to realize a fully autonomous smart greenhouse."* — **penyiraman+ventilasi otomatis = "future work" mereka, tapi udah jalan di SISTOMATE.**

### Pemetaan gap (buat bab 1 & bab 2)
| Aspek | JKTS'26 [1] | EasyChair'22 [7] | SciRep'25 [9] | **SISTOMATE** |
|---|---|---|---|---|
| Monitoring suhu/lembap/soil | ✅ | ✅ | ✅ | ✅ |
| Penyiraman otomatis (histeresis) | ✅ 35–45% | ❌ | ❌ (future work) | ✅ 40–55% |
| Kipas/ventilasi otomatis | ❌ | ❌ | ❌ (future work) | ✅ |
| Deteksi kesehatan daun (sensor warna) | ❌ | ✅ TCS3200 | ❌ | ✅ TCS34725 |
| Klasifikasi penyakit (CNN) | ❌ | ✅ VGG16 server | ⚠ kematangan, 52.8% | ⚠ heuristik + Gemini API |
| Dashboard realtime self-hosted | ✅ React | ⚠ ThingSpeak | ⚠ ThingsBoard | ✅ PHP+MySQL sendiri |
| Offline buffer | ✅ 120 item | ❌ | ❌ | ❌ (boleh ditiru) |

## Pompa air + pupuk cair (fertigasi) — fetch 2026-10-21
17. **Sistem Otomatis Penyiraman dan Pemupukan Tanaman Tin Menggunakan ESP32** — Sari dkk., JITET Vol.12 No.3, 2024, DOI 10.23960/jitet.v12i3.4564 (cited 20) — **udah dibangun & diuji**: relay1+pompa air (histeresis soil <50%) · relay2+pompa pupuk cair (jadwal RTC, durasi 7 detik/minggu) · sensor pH tanah + LCD · 2 unit ESP32.
   `https://journal.eng.unila.ac.id/index.php/jitet/article/viewFile/4564/1909`
18. **Perancangan Alat Penyiraman Otomatis Tanaman Teras Kantor ESP32 dengan Integrasi Pupuk Cair** — Hendri Wahyudi, Prosiding SENATIB UDB 2026 — **relay 2-ch + 2 pompa DC** (pompa air auto by soil + manual Blynk; pompa pupuk kendali terpisah) · design-only (belum dirakit).
   `https://ojs.udb.ac.id/Senatib/article/view/6754`
19. **Rancang Bangun Sistem Penyiraman Otomatis (ESP32 + soil + pH + RTC + pompa air + pompa pupuk)** — JRSIT
   `https://journal.ppmi.web.id/index.php/jrsit/article/download/3181/2417/25889`
20. **Inovasi Sistem Kendali Penyiraman dan Pemberian Pupuk (ESP32 + soil + DHT11 + relay + LCD + Blynk)** — Prosiding JUSIIK
   `https://ifrelresearch.org/index.php/jusiik-widyakarya/article/download/6163/5987/30755`
