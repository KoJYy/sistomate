// SISTOMATE — purwarupa smart greenhouse
// ESP32 + soil moisture + DHT11 + TCS34725 + relay 2×2-ch (pompa air, pompa pupuk, kipas)
// Data sensor & aktuator dikirim via HTTP POST ke ingest.php -> MySQL sistomate
//
// Pinout (hindari strapping pin 0/2/12/15):
//   Soil moisture (analog) : GPIO 34
//   DHT11 (digital)        : GPIO 13
//   TCS34725 I2C SDA/SCL   : GPIO 21 / GPIO 22
//   Relay A CH1 Pompa Air  : GPIO 26  (active-LOW)
//   Relay A CH2 Pompa Pupuk: GPIO 14  (active-LOW)
//   Relay B CH1 Kipas      : GPIO 27  (active-LOW)
//   Relay B CH2 cadangan   : (belum dipakai)
//
// Logika:
//   soil < 40%      -> pompa air ON ; soil >= 55% -> OFF  (histeresis)
//   suhu > 32 C     -> kipas ON ; suhu <= 31 C -> kipas OFF (histeresis)
//   jadwal 1x/minggu jam 07:00 (NTP) + soil >= 40% -> pompa pupuk ON 7 detik
//     (Sari 2024/JITET: jadwal RTC mingguan, durasi 7 detik; interlock tanah gak kering)
//   Tiap transisi relay -> POST actuator_logs
//   Tiap 2 detik        -> POST sensor_logs

#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <DHT.h>
#include <Adafruit_TCS34725.h>
#include <Preferences.h> // persist counter dosis pupuk anti dobel pas reboot

// ====== ISI SEBELUM DEMO ======
// THE PRIK (kost, 2.4GHz WPA2). IP laptop static DHCP: 192.168.1.101
// Alternatif hotspot HP "Ayi dodol" (open): SSID "Ayi dodol", pass "", URL 10.244.177.37
const char* WIFI_SSID   = "THE PRIK";
const char* WIFI_PASS   = "theprik123";
const char* SERVER_URL  = "http://192.168.1.101/ingest.php"; // IP laptop di THE PRIK
const char* API_KEY     = "sm_YOUR_SECRET_API_KEY";
const int   DEVICE_ID   = 1; // ESP32-Greenhouse-1
// ==============================

#define PIN_SOIL        34
#define PIN_DHT         13
#define PIN_RELAY_POMPA 26
#define RELAY_PUPUK     14
#define PIN_RELAY_KIPAS 27
#define RELAY_ON        LOW   // modul relay 2-ch umumnya aktif-LOW
#define RELAY_OFF       HIGH

#define SOIL_ON_PCT     40.0  // pompa nyala di bawah ini
#define SOIL_OFF_PCT    55.0  // pompa mati di atas ini
#define TEMP_ON_C       32.0  // kipas nyala di atas ini
#define TEMP_OFF_C      31.0  // kipas mati di bawah ini

// fertigasi (Sari 2024: 1x/minggu, durasi 7 detik; interlock Bab 5)
#define FERT_HOUR        7     // jam lokal WIB (NTP GMT+7)
#define FERT_MIN         0
#define FERT_DURATION_S  7     // detik dosis per semprot
#define FERT_MIN_SOIL    40.0  // syarat aman: tanah gak boleh kering
#define FERT_WDAY        1     // hari jadwal: 1 = Senin (0=Minggu .. 6=Sabtu) — 1x/minggu

#define SENSOR_INTERVAL_MS 2000UL  // POST sensor_logs tiap 2 detik
#define READ_INTERVAL_MS    2000UL  // baca sensor tiap 2 detik (DHT11 minimal 1 dtk)

DHT dht(PIN_DHT, DHT11);
Adafruit_TCS34725 tcs = Adafruit_TCS34725(TCS34725_INTEGRATIONTIME_101MS, TCS34725_GAIN_16X); // naik dari 50ms/4X (sinyal terlalu kecil 21 Okt)

bool pumpOn = false;
bool fanOn  = false;
bool tcsOk  = false;
bool fertOn = false;
unsigned long fertStartMs = 0;
Preferences prefs;
int lastFertKey = 0; // (tahun*1000 + yday) dosis terakhir — cegah dobel dalam sehari

unsigned long lastRead  = 0;
unsigned long lastPost  = 0;

// pembacaan terakhir
float soilPct = 0, airTemp = 0, airHumi = 0;
long soilRaw = 0; // nilai mentah ADC buat kalibrasi
uint16_t r = 0, g = 0, b = 0;

void setup() {
  Serial.begin(115200);

  // amankan relay SEBELUM pinMode (ceklek saat boot dihindari)
  digitalWrite(PIN_RELAY_POMPA, RELAY_OFF);
  digitalWrite(RELAY_PUPUK, RELAY_OFF);
  digitalWrite(PIN_RELAY_KIPAS, RELAY_OFF);
  pinMode(PIN_RELAY_POMPA, OUTPUT);
  pinMode(RELAY_PUPUK, OUTPUT);
  pinMode(PIN_RELAY_KIPAS, OUTPUT);

  prefs.begin("sistomate", false);
  lastFertKey = prefs.getInt("fertkey", 0);

  pinMode(PIN_SOIL, INPUT);
  dht.begin();
  Wire.begin(21, 22);
  tcsOk = tcs.begin();
  if (!tcsOk) Serial.println("[!] TCS34725 tidak terdeteksi — RGB pakai 0");

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);    // WAJIB: modem sleep bikin auth race gagal terus (root cause 21 Okt)
  WiFi.persistent(false);  // jangan pakai config NVS lama
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("WiFi connect");
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("OK IP=");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("GAGAL — retry di loop");
  }

  configTime(7 * 3600, 0, "pool.ntp.org", "time.nist.gov"); // NTP WIB — SNTP nyala sendiri begitu WiFi connected
}

bool postJson(const String& body) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);
  http.setTimeout(4000);
  int code = http.POST(body);
  http.end();
  Serial.printf("POST %d\n", code);
  return code >= 200 && code < 300;
}

// event aktuator ditunda (per-slot) sampai POST sukses — retry di loop(),
// biar row actuator_logs gak hilang kalau WiFi/network lagi mati.
// 3 slot terpisah: satu event gak nimpem event lain kalo POST lagi pending
#define ACT_POMPA 0
#define ACT_KIPAS 1
#define ACT_PUPUK 2
#define ACT_COUNT 3
bool actDirty[ACT_COUNT] = {false, false, false};
bool actOn[ACT_COUNT] = {false, false, false};
const char* actName[ACT_COUNT] = {"", "", ""};

void markActuator(int slot, const char* name, bool on) {
  actName[slot] = name;
  actOn[slot] = on;
  actDirty[slot] = true;
}

void flushActuator() {
  for (int i = 0; i < ACT_COUNT; i++) {
    if (!actDirty[i]) continue;
    String body = String("{\"type\":\"actuator\",\"device_id\":") + DEVICE_ID +
                  ",\"actuator_name\":\"" + actName[i] +
                  "\",\"status\":\"" + (actOn[i] ? "ON" : "OFF") + "\"}";
    if (postJson(body)) actDirty[i] = false; // sukses saja di-clear, gagal retry nanti
  }
}

void setPump(bool on) {
  if (on == pumpOn) return;
  pumpOn = on;
  digitalWrite(PIN_RELAY_POMPA, on ? RELAY_ON : RELAY_OFF);
  Serial.printf("POMPA %s\n", on ? "ON" : "OFF");
  markActuator(ACT_POMPA, "Pompa Air", on);
}

void setFert(bool on) {
  if (on == fertOn) return;
  fertOn = on;
  digitalWrite(RELAY_PUPUK, on ? RELAY_ON : RELAY_OFF);
  if (on) fertStartMs = millis();
  Serial.printf("PUPUK %s\n", on ? "ON" : "OFF");
  markActuator(ACT_PUPUK, "Pompa Pupuk", on);
}

void setFan(bool on) {
  if (on == fanOn) return;
  fanOn = on;
  digitalWrite(PIN_RELAY_KIPAS, on ? RELAY_ON : RELAY_OFF);
  Serial.printf("KIPAS %s\n", on ? "ON" : "OFF");
  markActuator(ACT_KIPAS, "Kipas Exhaust", on);
}

// fertigasi: dosis 7 detik tiap minggu jam 07:00 (Sari 2024), batal kalo tanah kering
void fertigasi() {
  if (fertOn) {
    if (millis() - fertStartMs >= (unsigned long)FERT_DURATION_S * 1000UL) setFert(false);
    else if (soilPct < FERT_MIN_SOIL) setFert(false); // interlock juga di tengah dosis
    return;
  }
  struct tm t;
  if (!getLocalTime(&t, 0)) return; // NTP belum sinkron (tahun < 2016 = false)
  if (t.tm_hour != FERT_HOUR || t.tm_min != FERT_MIN) return;
  if (t.tm_wday != FERT_WDAY) return;           // hanya hari Senin -> jadwal 1x/minggu (bukan tiap hari)
  int key = (t.tm_year + 1900) * 1000 + t.tm_yday;
  if (key == lastFertKey) return;           // hari ini udah dapat dosis
  if (soilPct < FERT_MIN_SOIL) return;      // tanah kering -> lewati minggu ini (retry selama menit jadwal)
  lastFertKey = key;
  prefs.putInt("fertkey", key);
  setFert(true);
}

// heuristik status tanaman dari kondisi sensor
// kering (soil) > layu (panas) > sakit (daun tidak hijau) > sehat
const char* plantStatus() {
  if (soilPct < SOIL_ON_PCT) return "kering";
  if (fanOn) return "layu"; // pake state fan (histeresis 32/31) — ambang mentah 32.0 bikin status kedip di sekitar ambang
  if (tcsOk) {
    // kalibrasi 21 Okt malam (data 4468 baris, bimodal jelas):
    //   hijau asli (ambient): G/R = 0.94–1.10  (R kadang >G, syarat g>r bikin salah sakit)
    //   kuning (senter putih): G/R ≈ 0.52
    //   lembah datanya 0.60–0.90 -> ambang G/R 0.75 = titik tengah, margin lebar dua sisi.
    // lowBlue dibuang: hijau & kuning sama-sama lolos (b/max ≈ 0.48 vs 0.22) — gak diskriminatif.
    bool redDom = (unsigned long)g * 4 < (unsigned long)r * 3; // G/R < 0.75
    if (redDom && r > 100) return "sakit"; // r>100 = sinyal nyata, bukan noise
  }
  return "sehat";
}

void readSensors() {
  long raw = analogRead(PIN_SOIL);            // 0..4095, kering = tinggi
  soilRaw = raw;
  soilPct = constrain(map(raw, 4095, 1500, 0, 100), 0, 100); // kalibrasi 21 Okt: udara=4095, air penuh=1500

  float t = dht.readTemperature();
  float h = dht.readHumidity();
  if (!isnan(t)) airTemp = t;
  if (!isnan(h)) airHumi = h;

  if (tcsOk) {
    uint16_t clear = 0;
    tcs.getRawData(&r, &g, &b, &clear);
  }
}

void loop() {
  unsigned long now = millis();

  // reconnect WiFi kalau putus
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastRetry = 0;
    if (now - lastRetry > 5000) {
      lastRetry = now;
      WiFi.disconnect();
      WiFi.begin(WIFI_SSID, WIFI_PASS);
      Serial.println("WiFi retry");
    }
  }

  if (now - lastRead >= READ_INTERVAL_MS) {
    lastRead = now;
    readSensors();

    // logika aktuator + histeresis
    setPump(soilPct < SOIL_ON_PCT ? true : (soilPct >= SOIL_OFF_PCT ? false : pumpOn));
    setFan(airTemp > TEMP_ON_C ? true : (airTemp <= TEMP_OFF_C ? false : fanOn));
  }

  if (now - lastPost >= SENSOR_INTERVAL_MS) {
    lastPost = now;
    String body = String("{\"type\":\"sensor\",\"device_id\":") + DEVICE_ID +
                  ",\"air_temperature\":" + String(airTemp, 1) +
                  ",\"air_humidity\":" + String(airHumi, 1) +
                  ",\"soil_moisture\":" + String(soilPct, 1) +
                  ",\"rgb_red\":" + r +
                  ",\"rgb_green\":" + g +
                  ",\"rgb_blue\":" + b +
                  ",\"plant_status\":\"" + plantStatus() + "\"}";
    postJson(body);
    Serial.printf("SENSOR suhu=%.1fC rh=%.0f%% soil=%.0f%% raw=%ld rgb=%u,%u,%u %s\n",
                  airTemp, airHumi, soilPct, soilRaw, r, g, b, plantStatus());
  }

  flushActuator(); // kirim/retry event aktuator yang belum sukses ter-post
  fertigasi();     // cek jadwal pupuk tiap loop (presisi durasi 50ms, bukan 2 dtk)

  delay(50);
}
